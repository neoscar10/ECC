<?php

namespace App\Livewire\Admin\Archive\Enquiries;

use App\Models\Archive\ArchiveProductEnquiry; // Ensure you use the correct namespace
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['order-created' => '$refresh'];

    // Filter properties
    #[Url]
    public $search = '';
    #[Url]
    public $status = '';
    
    // Action properties
    public $selectedEnquiry = null;
    public $selectedEnquiries = [];
    
    // Payment Link Properties
    public $paymentEnquiryId = null;
    public $paymentAmount = '';
    public $paymentGateway = '';
    
    #[Url]
    public $viewId = null;

    public function mount()
    {
        if ($this->viewId) {
            $this->viewEnquiry($this->viewId);
            $this->viewId = null;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingViewId()
    {
        $this->resetPage();
    }

    public function viewEnquiry($id)
    {
        $primary = ArchiveProductEnquiry::with(['product.images', 'user'])->find($id);
        
        if ($primary->status === 'new') {
            // Find all 'new' enquiries from the same person
            $query = ArchiveProductEnquiry::with(['product.images', 'user'])
                ->where('status', 'new')
                ->where('id', '!=', $id); // exclude the primary so we can place it first or just fetch all
                
            if ($primary->user_id) {
                $query->where('user_id', $primary->user_id);
            } else {
                $query->where('contact_email', $primary->contact_email);
            }
            
            $related = $query->get();
            $this->selectedEnquiries = collect([$primary])->merge($related);
        } else {
            // If not 'new', just load the one they clicked
            $this->selectedEnquiries = collect([$primary]);
        }
        
        $this->selectedEnquiry = $primary;
        $this->dispatch('show-view-modal');
    }

    public function attemptLogSale($enquiryId)
    {
        $enquiry = ArchiveProductEnquiry::with('product')->find($enquiryId);
        
        if (!$enquiry || !$enquiry->product || (method_exists($enquiry->product, 'trashed') && $enquiry->product->trashed())) {
            session()->flash('error', 'You cannot log a sale for this enquiry because the associated product has been deleted or is no longer available in the system.');
            return;
        }
        
        $this->dispatch('log-sale-from-enquiry', enquiryId: $enquiry->id);
    }

    public function updateStatus($id, $newStatus)
    {
        $enquiry = ArchiveProductEnquiry::find($id);
        if ($enquiry) {
            $enquiry->update(['status' => $newStatus]);
            session()->flash('success', 'Enquiry status updated successfully.');
            
            // Refresh selected enquiry if open
            if ($this->selectedEnquiry && $this->selectedEnquiry->id == $id) {
                $this->selectedEnquiry = $enquiry->fresh(['product.images', 'user']);
            }
            
            // Refresh in the collection
            if ($this->selectedEnquiries) {
                $this->selectedEnquiries = $this->selectedEnquiries->map(function($e) use ($id, $enquiry) {
                    if ($e->id == $id) {
                        return $enquiry->fresh(['product.images', 'user']);
                    }
                    return $e;
                });
            }
        }
    }
    
    public function openPaymentModal($enquiryId)
    {
        $enquiry = ArchiveProductEnquiry::find($enquiryId);
        if ($enquiry) {
            $this->paymentEnquiryId = $enquiry->id;
            $this->paymentAmount = $enquiry->payment_amount ?? '';
            $this->paymentGateway = $enquiry->payment_gateway ?? 'razorpay';
            $this->dispatch('show-payment-modal');
        }
    }

    public function sendDeliveryDetailsRequest($enquiryId)
    {
        $enquiry = ArchiveProductEnquiry::find($enquiryId);
        if (!$enquiry) {
            session()->flash('error', 'Enquiry not found.');
            return;
        }

        if (!$enquiry->contact_email) {
            session()->flash('error', 'Cannot send request. No email address associated with this enquiry.');
            return;
        }

        $enquiry->update([
            'delivery_details_requested_at' => now(),
        ]);

        // 1. Dispatch WhatsApp delivery details request template job
        dispatch_sync(new \App\Jobs\Notifications\SendEnquiryDeliveryRequestWhatsAppJob($enquiry));

        // 2. Send the customer an email
        \Illuminate\Support\Facades\Mail::to($enquiry->contact_email)
            ->send(new \App\Mail\Archive\DeliveryDetailsRequestMail($enquiry));

        session()->flash('success', 'Delivery details form request sent successfully via Email and WhatsApp.');

        // Refresh selected enquiry if open
        if ($this->selectedEnquiry && $this->selectedEnquiry->id == $enquiryId) {
            $this->selectedEnquiry = $enquiry->fresh(['product.images', 'user']);
        }
        
        // Refresh in the collection
        if ($this->selectedEnquiries) {
            $this->selectedEnquiries = $this->selectedEnquiries->map(function($e) use ($enquiryId, $enquiry) {
                if ($e->id == $enquiryId) {
                    return $enquiry->fresh(['product.images', 'user']);
                }
                return $e;
            });
        }
    }

    public function sendPaymentLink()
    {
        $this->validate([
            'paymentEnquiryId' => 'required|exists:archive_product_enquiries,id',
            'paymentAmount' => 'required|numeric|min:1',
            'paymentGateway' => 'required|in:razorpay,cashfree',
        ]);

        $enquiry = ArchiveProductEnquiry::find($this->paymentEnquiryId);
        
        if (!$enquiry->user_id && !$enquiry->contact_email) {
            session()->flash('error', 'Cannot send payment link. No email address associated with this enquiry.');
            return;
        }

        $enquiry->update([
            'payment_amount' => $this->paymentAmount,
            'payment_gateway' => $this->paymentGateway,
            'payment_link_sent_at' => now(),
            'status' => 'awaiting payment',
        ]);

        // Generate the secure signed checkout URL
        $checkoutUrl = \Illuminate\Support\Facades\URL::signedRoute(
            'archive.enquiry.checkout', 
            ['enquiry' => $enquiry->id]
        );

        // Send Email
        \Illuminate\Support\Facades\Mail::to($enquiry->contact_email)
            ->send(new \App\Mail\Archive\EnquiryPaymentLinkMail($enquiry, $checkoutUrl));

        // Send WhatsApp
        dispatch_sync(new \App\Jobs\Notifications\SendEnquiryPaymentLinkWhatsAppJob($enquiry, $checkoutUrl));

        session()->flash('success', 'Payment link generated and sent successfully via Email and WhatsApp.');
        
        $this->dispatch('hide-payment-modal');
        $this->paymentEnquiryId = null;
        
        // Refresh if viewing
        if ($this->selectedEnquiry && $this->selectedEnquiry->id == $enquiry->id) {
            $this->selectedEnquiry = $enquiry->fresh(['product.images', 'user']);
        }
    }
    
    public function render()
    {
        $query = ArchiveProductEnquiry::with(['product', 'user'])
            ->latest();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('contact_name', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_email', 'like', '%' . $this->search . '%')
                  ->orWhereHas('product', function($subQ) {
                      $subQ->where('title', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->viewId) {
            $query->where('id', $this->viewId);
        }

        $enquiries = $query->paginate(10);

        return view('livewire.admin.archive.enquiries.index', [
            'enquiries' => $enquiries
        ])->layout('layouts.admin');
    }
}

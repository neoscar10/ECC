<?php

namespace App\Livewire\Archive;

use App\Models\Archive\ArchiveProductEnquiry;
use App\Services\Payments\PaymentManager;
use Livewire\Component;

class EnquiryCheckout extends Component
{
    public ArchiveProductEnquiry $enquiry;

    public function mount(ArchiveProductEnquiry $enquiry)
    {
        $this->enquiry = $enquiry->load(['product.images']);
        
        // Ensure the enquiry is valid for payment
        if (!$this->enquiry->payment_amount || $this->enquiry->status === 'paid') {
            abort(404, 'Payment link invalid or already paid.');
        }
    }

    public function processPayment(PaymentManager $paymentManager)
    {
        // 1. Double check the status
        if ($this->enquiry->status === 'paid') {
            session()->flash('error', 'This enquiry has already been paid.');
            return;
        }

        // 2. Identify the user (either logged in user, enquiry user, or matching account)
        // If none exists, we allow guest checkout (null).
        $user = auth()->user() ?: $this->enquiry->user;
        
        if (!$user) {
            $user = \App\Models\User::where('email', $this->enquiry->contact_email)->first();
        }

        try {
            $paymentInitiation = $paymentManager->initiatePayment(
                payable: $this->enquiry,
                amount: $this->enquiry->payment_amount,
                purpose: 'archive_enquiry_payment',
                user: $user,
                gateway: $this->enquiry->payment_gateway ?? 'razorpay'
            );

            // Redirect to the generic payment routing controller
            return redirect()->route('payments.pay', $paymentInitiation['payment']->id);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Enquiry Payment Error: ' . $e->getMessage());
            session()->flash('error', 'Failed to initiate payment. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.archive.enquiry-checkout')->layout('layouts.guest');
    }
}

<div class="min-h-screen bg-[#141008] text-[#f8fafc] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <!-- Logo / Header -->
        <div class="text-center mb-8">
            <a href="{{ url('/') }}" class="inline-block">
                <img src="{{ asset('ecc_logo_dark.png') }}" alt="ECC Logo" class="h-16 mx-auto object-contain mb-2">
            </a>
            <h1 class="text-2xl font-black text-[#c5a365] tracking-wider uppercase">Delivery Address Details</h1>
            <p class="text-sm text-slate-400 mt-1">Please provide your shipping destination for this archive item.</p>
        </div>

        <!-- Product Summary Card -->
        <div class="bg-[#19140b] border border-[#c5a365]/20 rounded-2xl p-4 sm:p-6 mb-6 shadow-xl">
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-xl overflow-hidden bg-slate-800 shrink-0 border border-[#c5a365]/30">
                    @if($enquiry->product && $enquiry->product->images->first())
                        <img src="{{ Storage::url($enquiry->product->images->first()->image_path) }}" alt="{{ $enquiry->product->title }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-slate-500">
                            <span class="material-symbols-outlined text-2xl">inventory_2</span>
                        </div>
                    @endif
                </div>

                <div class="flex-grow min-w-0">
                    <div class="text-xs uppercase tracking-widest text-[#c5a365] font-bold">Archive Enquiry #{{ $enquiry->id }}</div>
                    <h2 class="text-lg font-bold text-slate-100 truncate">{{ $enquiry->product->title ?? 'Archive Item' }}</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Contact: <span class="text-slate-200 font-semibold">{{ $enquiry->contact_name }}</span> ({{ $enquiry->contact_email }})</p>
                </div>
            </div>
        </div>

        @if(session()->has('success'))
            <div class="bg-emerald-900/40 border border-emerald-500/40 text-emerald-300 p-4 rounded-xl mb-6 text-sm flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($submittedSuccessfully)
            <!-- Confirmation Screen -->
            <div class="bg-[#19140b] border border-[#c5a365]/30 rounded-2xl p-6 sm:p-8 shadow-2xl text-center">
                <div class="w-16 h-16 rounded-full bg-[#c5a365]/10 border border-[#c5a365] flex items-center justify-center text-[#c5a365] mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl">local_shipping</span>
                </div>

                <h3 class="text-xl font-bold text-slate-100 mb-2">Address Submitted Successfully</h3>
                <p class="text-sm text-slate-400 mb-6">Your delivery address details have been securely recorded for this item.</p>

                <div class="bg-[#141008] border border-slate-800 rounded-xl p-4 text-left text-sm mb-6 flex flex-col gap-2">
                    <div class="flex justify-between border-b border-slate-800/80 pb-2">
                        <span class="text-slate-400 font-medium">Recipient Name:</span>
                        <span class="text-slate-200 font-bold">{{ $enquiry->delivery_name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-800/80 pb-2">
                        <span class="text-slate-400 font-medium">Phone Number:</span>
                        <span class="text-slate-200 font-bold">{{ $enquiry->delivery_phone }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-800/80 pb-2">
                        <span class="text-slate-400 font-medium">Address:</span>
                        <span class="text-slate-200 font-bold text-right">{{ $enquiry->delivery_line1 }} {{ $enquiry->delivery_line2 }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-800/80 pb-2">
                        <span class="text-slate-400 font-medium">City / State:</span>
                        <span class="text-slate-200 font-bold">{{ $enquiry->delivery_city }}, {{ $enquiry->delivery_state }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-800/80 pb-2">
                        <span class="text-slate-400 font-medium">Postal / PIN Code:</span>
                        <span class="text-slate-200 font-bold">{{ $enquiry->delivery_postal_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400 font-medium">Country:</span>
                        <span class="text-slate-200 font-bold">{{ $enquiry->delivery_country }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-4">
                    <button type="button" wire:click="editAddress" class="py-2.5 px-6 rounded-xl border border-[#c5a365] text-[#c5a365] hover:bg-[#c5a365] hover:text-[#111] font-bold text-xs uppercase tracking-wider transition-all">
                        Update Details
                    </button>
                </div>
            </div>
        @else
            <!-- Address Form -->
            <form wire:submit.prevent="saveAddress" class="bg-[#19140b] border border-[#c5a365]/20 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-5">
                <h3 class="text-lg font-bold text-slate-100 border-b border-slate-800 pb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#c5a365]">pin_drop</span>
                    <span>Shipping Destination Form</span>
                </h3>

                <!-- Name & Phone -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Recipient Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="delivery_name" class="w-full bg-[#141008] border border-slate-700 rounded-xl px-4 py-2.5 text-slate-100 text-sm focus:border-[#c5a365] focus:outline-none transition-colors" placeholder="e.g. John Doe">
                        @error('delivery_name') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Phone Number <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="delivery_phone" class="w-full bg-[#141008] border border-slate-700 rounded-xl px-4 py-2.5 text-slate-100 text-sm focus:border-[#c5a365] focus:outline-none transition-colors" placeholder="e.g. +91 9876543210">
                        @error('delivery_phone') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Address Line 1 -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Address Line 1 <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="delivery_line1" class="w-full bg-[#141008] border border-slate-700 rounded-xl px-4 py-2.5 text-slate-100 text-sm focus:border-[#c5a365] focus:outline-none transition-colors" placeholder="Street address, building, house no.">
                    @error('delivery_line1') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Address Line 2 -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Address Line 2 (Optional)</label>
                    <input type="text" wire:model="delivery_line2" class="w-full bg-[#141008] border border-slate-700 rounded-xl px-4 py-2.5 text-slate-100 text-sm focus:border-[#c5a365] focus:outline-none transition-colors" placeholder="Apartment, suite, landmark">
                    @error('delivery_line2') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- City & State -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">City <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="delivery_city" class="w-full bg-[#141008] border border-slate-700 rounded-xl px-4 py-2.5 text-slate-100 text-sm focus:border-[#c5a365] focus:outline-none transition-colors" placeholder="City">
                        @error('delivery_city') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">State / Province <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="delivery_state" class="w-full bg-[#141008] border border-slate-700 rounded-xl px-4 py-2.5 text-slate-100 text-sm focus:border-[#c5a365] focus:outline-none transition-colors" placeholder="State">
                        @error('delivery_state') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Postal Code & Country -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Postal Code / PIN Code <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="delivery_postal_code" class="w-full bg-[#141008] border border-slate-700 rounded-xl px-4 py-2.5 text-slate-100 text-sm focus:border-[#c5a365] focus:outline-none transition-colors" placeholder="Postal Code">
                        @error('delivery_postal_code') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Country <span class="text-rose-500">*</span></label>
                        <select wire:model="delivery_country" class="w-full bg-[#141008] border border-slate-700 rounded-xl px-4 py-2.5 text-slate-100 text-sm focus:border-[#c5a365] focus:outline-none transition-colors cursor-pointer">
                            @foreach($countries as $c)
                                <option value="{{ $c->name }}">{{ $c->name }}</option>
                            @endforeach
                            @if(!$countries->pluck('name')->contains('India'))
                                <option value="India">India</option>
                            @endif
                        </select>
                        @error('delivery_country') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" wire:loading.attr="disabled" class="w-full py-3.5 bg-[#c5a365] text-[#111] font-black tracking-wider uppercase rounded-xl text-sm shadow-lg hover:brightness-110 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                        <span wire:loading.remove wire:target="saveAddress">Submit Delivery Details</span>
                        <span wire:loading wire:target="saveAddress">Saving Address...</span>
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

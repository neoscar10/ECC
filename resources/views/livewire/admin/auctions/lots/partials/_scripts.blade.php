@once
<script>
(function(){
    // Countdown Timer logic with proper formatting and no flicker
    const timerEl = document.getElementById('auctionCountdown');
    
    function updateCountdowns() {
        if (!timerEl) return; 

        const startsAtStr = timerEl.getAttribute('data-starts-at');
        const endsAtStr = timerEl.getAttribute('data-end-at');
        
        // If neither exists, we can't count
        if (!endsAtStr && !startsAtStr) return;

        const now = new Date().getTime();
        
        let target = null;
        let mode = 'end'; // default

        // 1. Check if we are before start
        if (startsAtStr) {
            const start = new Date(startsAtStr).getTime();
            if (now < start) {
                target = start;
                mode = 'start';
            }
        }

        // 2. If not starting, checking if we are before end
        if (!target && endsAtStr) {
            const end = new Date(endsAtStr).getTime();
            target = end;
            mode = 'end';
        }

        // 3. Update Label if it exists
        const labelEl = document.getElementById('countdownLabel');
        if (labelEl) {
            if (mode === 'start') labelEl.innerText = 'Starts In';
            else if (mode === 'end') labelEl.innerText = 'Time Remaining';
        }

        // 4. Calculate Diff
        // If we have a target, diff is target - now.
        // If no target (implying invalid data?), or if we are in 'end' mode and passed it, we handle finish.
        
        if (!target) return; // Should not happen if data is valid

        const diff = target - now;

        // 5. Check lifecycle ends
        if (diff <= 0) {
            // A) If we were counting to START, we just let the next tick pick up the END target.
            //    But to avoid 1-second flicker of "00s", we can return immediately to let next tick run.
            if (mode === 'start') {
                return; 
            }

            // B) If we were counting to END, then it is truly ended.
            if (mode === 'end') {
                if (timerEl.innerHTML.indexOf('Ended') === -1) {
                    timerEl.innerHTML = '<span class="text-muted text-uppercase fw-bold">Ended</span>';
                    
                    if (labelEl) labelEl.innerText = 'Status';

                    // Dispatch event to Livewire only ONCE when it hits zero
                    if (!window.__eccAuctionEndedDispatched) {
                        window.__eccAuctionEndedDispatched = true;
                        console.log('Dispatching auction-countdown-ended');
                        Livewire.dispatch('auction-countdown-ended');
                        // Also generic refresh to ensure status badges update
                        setTimeout(() => Livewire.dispatch('refresh-panels'), 1000); 
                    }
                }
                return;
            }
        }
        
        // Reset flag if time is extended or we are just running normal
        window.__eccAuctionEndedDispatched = false;

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        let display = '';
        if (days > 0) {
            display = `<span class="fw-bold">${days}d</span> <span class="fw-medium">${hours}h ${minutes}m ${seconds}s</span>`;
        } else {
            // Highlight hours if less than 24h
            // If mode is start, maybe nice validation color? Keep standard for now.
            const colorClass = hours < 1 ? 'text-danger' : 'text-dark';
            display = `<span class="fw-bold ${colorClass}">${hours}h ${minutes}m ${seconds}s</span>`;
        }
        
        timerEl.innerHTML = `<i class="ri-time-line align-middle me-1 text-muted"></i> ` + display;
    }

    // Start interval
    if (!window.__eccAuctionTimerInterval) {
        window.__eccAuctionTimerInterval = setInterval(updateCountdowns, 1000);
    }
    updateCountdowns(); // Initial run
    
    // --- Livewire Event Listeners ---
    
    document.addEventListener('livewire:initialized', () => {
        // 1. Listen for new end time from socket events
        Livewire.on('auction-ends-updated', (data) => {
            // data might be array or object depending on dispatch format
            const eventData = Array.isArray(data) ? data[0] : data;
            if (timerEl && eventData.ends_at) {
                // Flash effect
                timerEl.parentElement.classList.add('bg-success-subtle');
                setTimeout(() => timerEl.parentElement.classList.remove('bg-success-subtle'), 500);
                
                // Update Attribute
                timerEl.setAttribute('data-end-at', eventData.ends_at);
                updateCountdowns(); // Forced immediate update
            }
        });

        // 2. Generic Modal Close
        Livewire.on('close-modal', (data) => {
             const eventData = Array.isArray(data) ? data[0] : data;
             const modalId = eventData.modalId;
             const modalEl = document.getElementById(modalId);
             if (modalEl) {
                 const modal = bootstrap.Modal.getInstance(modalEl);
                 if (modal) modal.hide();
                 else {
                     // Fallback if instance not found but backdrop present
                     const backdrop = document.querySelector('.modal-backdrop');
                     if(backdrop) backdrop.remove();
                     modalEl.classList.remove('show');
                     modalEl.style.display = 'none';
                     document.body.classList.remove('modal-open');
                 }
             }
        });
        
        Livewire.on('open-modal', (data) => {
             const eventData = Array.isArray(data) ? data[0] : data;
             const modalId = eventData.modalId;
             const modalEl = document.getElementById(modalId);
             if (modalEl) {
                 const modal = new bootstrap.Modal(modalEl);
                 modal.show();
             }
        });
        
        // 4. Persistent Page Alerts (replaces session flash which gets wiped by realtime updates)
        Livewire.on('show-alert', (data) => {
            const eventData = Array.isArray(data) ? data[0] : data;
            const container = document.getElementById('page-alerts');
            if (!container) return;

            const type = eventData.type || 'success';
            const msg = eventData.message;
            const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
            const title = type === 'error' ? 'Error!' : 'Success!';

            const html = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <strong>${title}</strong> ${msg}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            container.innerHTML = html; // Replaces previous alert (cleanup) or can use += to stack
        });
    });

})();
</script>
@endonce

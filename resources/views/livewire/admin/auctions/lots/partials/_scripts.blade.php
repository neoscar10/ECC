@once
<script>
(function(){
    // Countdown (only when LIVE)
    function tickCountdown(){
        const el = document.getElementById('countdown_timer');
        if(!el) return;

        const endsAt = el.getAttribute('data-ends-at');
        if(!endsAt) return;

        const end = new Date(endsAt).getTime();
        const now = new Date().getTime();
        const d = end - now;

        if(d <= 0){
            el.innerHTML = "ENDED";
            el.classList.remove('text-danger');
            el.classList.add('text-muted');
            return;
        }

        const h = Math.floor((d % (1000*60*60*24)) / (1000*60*60));
        const m = Math.floor((d % (1000*60*60)) / (1000*60));
        const s = Math.floor((d % (1000*60)) / 1000);

        el.innerHTML = (h<10?"0"+h:h)+":"+(m<10?"0"+m:m)+":"+(s<10?"0"+s:s);
    }

    // Prevent multiple intervals
    if(!window.__eccAuctionCountdown){
        window.__eccAuctionCountdown = setInterval(tickCountdown, 1000);
    }
    tickCountdown();
})();
</script>
@endonce

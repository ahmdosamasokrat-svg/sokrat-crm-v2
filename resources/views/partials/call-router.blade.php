@auth
<script>
(function() {
    'use strict';
    // Universal call router: intercepts tel: links and routes to WebRTC when available
    document.addEventListener('click', function(e) {
        var link = e.target.closest('a[href^="tel:"], a[href^="callto:"], a[data-call-href^="tel:"], a[data-call-href^="callto:"]');
        if (!link) return;

        // Extract phone number
        var href = link.getAttribute('data-call-href') || link.getAttribute('href') || '';
        var phone = href.replace(/^(tel:|callto:)/, '').replace(/[^0-9+]/g, '');
        if (!phone) return;

        // Check if desktop WebRTC is registered
        if (window.sokratDesktop && typeof window.sokratDesktop.isRegistered === 'function' && window.sokratDesktop.isRegistered()) {
            e.preventDefault();
            e.stopPropagation();
            var leadName = link.getAttribute('data-lead-name') || link.getAttribute('title') || '';
            window.sokratDesktop.dial(phone, leadName);
            // Also trigger voice dock expansion if available
            if (typeof window.sokratVoiceDial === 'function') {
                window.sokratVoiceDial(phone, leadName);
            }
            return;
        }

        // If sokratVoiceDial exists (voice-dock loaded, browser mode with WebRTC registered)
        // Let the existing voice-dock handler take over via data-voice-dial
        // Otherwise, fall through to native tel: URI (MicroSIP)
    });
})();
</script>
@endauth

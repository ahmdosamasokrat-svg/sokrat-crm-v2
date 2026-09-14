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

        // Check if desktop WebRTC bridge is present
        if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
            e.preventDefault();
            e.stopPropagation();
            var leadName = link.getAttribute('data-lead-name') || link.getAttribute('title') || '';

            var executeDial = function() {
                window.sokratDesktop.dial(phone, leadName);
                if (typeof window.sokratDesktop.showSoftphone === 'function') {
                    window.sokratDesktop.showSoftphone();
                }
            };

            if (typeof window.sokratDesktop.getSoftphoneSnapshot === 'function') {
                window.sokratDesktop.getSoftphoneSnapshot().then(function(snapshot) {
                    if (snapshot && snapshot.isRegistered) {
                        executeDial();
                    } else {
                        // Queue dialing until registered
                        var unsub = null;
                        if (typeof window.sokratDesktop.onSoftphoneSnapshot === 'function') {
                            unsub = window.sokratDesktop.onSoftphoneSnapshot(function(nextSnapshot) {
                                if (nextSnapshot && nextSnapshot.isRegistered) {
                                    if (typeof unsub === 'function') {
                                        unsub();
                                        unsub = null;
                                    }
                                    executeDial();
                                }
                            });
                        }
                        if (typeof window.sokratDesktop.showSoftphone === 'function') {
                            window.sokratDesktop.showSoftphone();
                        }
                    }
                }).catch(function(err) {
                    console.error('[CallRouter] Failed to query snapshot, dialing directly:', err);
                    executeDial();
                });
            } else {
                executeDial();
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

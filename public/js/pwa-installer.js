
// PWA Installation Handler
let deferredPrompt;

// Register service worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/tech-check-in-form/wp-content/plugins/tech-checkin-maps/public/sw.js')
            .then(registration => {
                console.log('ServiceWorker registration successful');
            })
            .catch(err => {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    showInstallBanner();
});

function showInstallBanner() {
    if (!deferredPrompt) return;

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches;

    if (isStandalone) return;

    const banner = document.createElement('div');
    banner.className = 'pwa-install-banner';

    if (isIOS) {
        banner.innerHTML = `
            <div class="banner-content">
                <h3>📱 Install Tech Check-in App</h3>
                <div class="ios-steps">
                    <div class="step">1. Tap the share button <img src="/tech-check-in-form/wp-content/plugins/tech-checkin-maps/public/images/share-icon.png" alt="Share"></div>
                    <div class="step">2. Scroll and tap "Add to Home Screen" <img src="/tech-check-in-form/wp-content/plugins/tech-checkin-maps/public/images/add-home-icon.png" alt="Add"></div>
                    <div class="step">3. Tap "Add" at the top right</div>
                </div>
                <button class="banner-close">Got it</button>
            </div>
        `;
    } else if (deferredPrompt) {
        banner.innerHTML = `
            <div class="banner-content">
                <h3>📱 Install Tech Check-in App</h3>
                <button class="install-btn">Install App</button>
                <button class="banner-close">Close</button>
            </div>
        `;

        const installBtn = banner.querySelector('.install-btn');
        installBtn.addEventListener('click', async () => {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                console.log('PWA installed');
                banner.remove();
            }
            deferredPrompt = null;
        });
    }

    const closeBtn = banner.querySelector('.banner-close');
    closeBtn.addEventListener('click', () => {
        banner.remove();
        localStorage.setItem('installBannerDismissed', Date.now());
    });

    // Only show banner if not dismissed in last 24 hours
    const lastDismissed = localStorage.getItem('installBannerDismissed');
    if (!lastDismissed || Date.now() - parseInt(lastDismissed) > 24 * 60 * 60 * 1000) {
        document.body.appendChild(banner);
    }
}

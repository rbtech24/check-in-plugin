
<?php
/*
Template Name: PWA Install Page
*/

get_header();
?>

<div class="pwa-install-page">
    <div class="pwa-content">
        <h1>Install Tech Check-in App</h1>
        <p>Get quick access to the Tech Check-in system on your mobile device</p>
        
        <div class="pwa-install-buttons">
            <div id="ios-install-button" class="pwa-install-button" style="display: none;">
                <button class="install-pwa-btn ios">
                    <span class="icon">📱</span>
                    Install for iOS
                </button>
                <p class="install-instructions">Tap Share then 'Add to Home Screen'</p>
            </div>
            
            <div id="android-install-button" class="pwa-install-button" style="display: none;">
                <button class="install-pwa-btn android">
                    <span class="icon">📱</span>
                    Install for Android
                </button>
            </div>
        </div>
        
        <div class="pwa-features">
            <h2>Features</h2>
            <ul>
                <li>✓ Quick access from your home screen</li>
                <li>✓ Works offline</li>
                <li>✓ Native app-like experience</li>
                <li>✓ Faster loading times</li>
            </ul>
        </div>
    </div>
</div>

<?php get_footer(); ?>

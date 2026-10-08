<?php
// includes/footer.php
?>
    </main>
</div>

<!-- Top Progress Bar -->
<div id="top-progress-bar"></div>

<!-- Global Loading Overlay -->
<div id="global-loading-overlay">
    <div class="loading-spinner-box">
        <div class="loading-spinner"></div>
        <div class="loading-text" id="loading-text-msg">Memuat data Supabase...</div>
    </div>
</div>

<script>
(function() {
    const progressBar = document.getElementById('top-progress-bar');
    const loadingOverlay = document.getElementById('global-loading-overlay');
    const loadingText = document.getElementById('loading-text-msg');

    window.showAppLoading = function(msg) {
        if (loadingText) loadingText.textContent = msg || 'Memproses...';
        if (loadingOverlay) loadingOverlay.classList.add('active');
        if (progressBar) {
            progressBar.style.width = '80%';
            progressBar.style.opacity = '1';
        }
    };

    window.hideAppLoading = function() {
        if (progressBar) {
            progressBar.style.width = '100%';
            setTimeout(function() {
                progressBar.style.opacity = '0';
                progressBar.style.width = '0%';
            }, 80);
        }
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }
    };

    // Hide immediately on DOM ready, load, and pageshow (back/forward navigation)
    hideAppLoading();
    window.addEventListener('pageshow', hideAppLoading);
    window.addEventListener('load', hideAppLoading);

    // Show loading on link navigation (only if not cancelled)
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]:not([href^="#"]):not([href^="javascript:"]):not([target="_blank"])');
        if (!link) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        setTimeout(function() {
            if (e.defaultPrevented) {
                window.hideAppLoading();
            } else {
                window.showAppLoading('Memuat...');
            }
        }, 10);
    });

    // Handle Form Submissions (only if not cancelled by confirm dialog or validation)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        setTimeout(function() {
            if (e.defaultPrevented) {
                window.hideAppLoading();
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.remove('btn-loading');
                }
            } else {
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('btn-loading');
                }
                window.showAppLoading('Memproses...');
            }
        }, 10);
    });
})();
</script>
</body>
</html>


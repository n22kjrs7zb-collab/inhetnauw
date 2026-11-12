(function () {

    // ============================================
    // THEMAWISSELAAR
    // ============================================

    var STORAGE_KEY = 'ihn-theme';

    function getEffectiveTheme(scheme) {
        if (scheme === 'dark')  return 'dark';
        if (scheme === 'light') return 'light';
        return window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark' : 'light';
    }

    function applyScheme(scheme) {
        var theme = getEffectiveTheme(scheme);
        document.documentElement.setAttribute('data-theme', theme);

        if (scheme === 'system') {
            localStorage.removeItem(STORAGE_KEY);
        } else {
            localStorage.setItem(STORAGE_KEY, scheme);
        }

        document.querySelectorAll('.ihn-scheme-btn').forEach(function (btn) {
            var active = btn.getAttribute('data-scheme') === scheme;
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            btn.classList.toggle('is-active', active);
        });
    }

    // Direct toepassen — voorkomt flits bij laden
    var saved  = localStorage.getItem(STORAGE_KEY);
    var scheme = saved || 'system';
    applyScheme(scheme);

    document.addEventListener('DOMContentLoaded', function () {

        // Thema knoppen
        document.querySelectorAll('.ihn-scheme-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                applyScheme(btn.getAttribute('data-scheme'));
            });
        });

        // Systeemvoorkeur live volgen
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
            if (!localStorage.getItem(STORAGE_KEY)) {
                document.documentElement.setAttribute('data-theme',
                    e.matches ? 'dark' : 'light');
            }
        });

        // ============================================
        // HAMBURGER MENU
        // ============================================

        var hamburger = document.getElementById('ihn-hamburger');
        var menu      = document.getElementById('ihn-nav-menu');

        if (hamburger && menu) {

            hamburger.addEventListener('click', function () {
                var open = hamburger.getAttribute('aria-expanded') === 'true';
                hamburger.setAttribute('aria-expanded', open ? 'false' : 'true');
                menu.setAttribute('aria-hidden', open ? 'true' : 'false');
                menu.classList.toggle('is-open', !open);
            });

            // Menu sluiten bij klik buiten het menu
            document.addEventListener('click', function (e) {
                if (!hamburger.contains(e.target) && !menu.contains(e.target)) {
                    hamburger.setAttribute('aria-expanded', 'false');
                    menu.setAttribute('aria-hidden', 'true');
                    menu.classList.remove('is-open');
                }
            });

            // Menu sluiten bij Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    hamburger.setAttribute('aria-expanded', 'false');
                    menu.setAttribute('aria-hidden', 'true');
                    menu.classList.remove('is-open');
                    hamburger.focus();
                }
            });

            document.querySelectorAll('.ihn-sub-toggle').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var open = btn.getAttribute('aria-expanded') === 'true';
                    btn.setAttribute('aria-expanded', open ? 'false' : 'true');
                    btn.parentElement.classList.toggle('is-sub-open', !open);
                });
            });
        }

        // ============================================
        // AUTOMATISCH JAAR IN FOOTER
        // ============================================

        var el = document.getElementById('ihn-year');
        if (el) {
            el.textContent = new Date().getFullYear();
        }

    });

})();

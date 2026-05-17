(function () {
    var STORAGE_KEY = 'bloodline-theme';

    function getPreferredTheme() {
        var stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'light' || stored === 'dark') {
            return stored;
        }
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
        document.querySelectorAll('.theme-btn').forEach(function (btn) {
            var isActive = btn.getAttribute('data-theme-set') === theme;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    window.BloodlineTheme = {
        get: getPreferredTheme,
        set: applyTheme
    };

    applyTheme(getPreferredTheme());

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.theme-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var theme = btn.getAttribute('data-theme-set');
                if (theme === 'light' || theme === 'dark') {
                    applyTheme(theme);
                }
            });
        });
    });
})();

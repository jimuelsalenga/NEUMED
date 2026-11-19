/* Light / dark / auto theme. Load this in <head> (before the page paints) so there is no flash.
   Modes:  'light', 'dark', or 'auto' (follow the phone's setting).
   NeuTheme.setMode('dark')   NeuTheme.mode()   NeuTheme.toggle() */
(function () {
    var KEY = 'neumed-theme', root = document.documentElement;
    var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function saved() {
        try {
            var v = localStorage.getItem(KEY);
            return (v === 'light' || v === 'dark') ? v : null;
        } catch (e) { return null; }
    }
    function system() { return (mq && mq.matches) ? 'dark' : 'light'; }

    function apply(t) {
        root.setAttribute('data-theme', t);
        root.setAttribute('data-bs-theme', t); // makes Bootstrap's own components match
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute('content', t === 'dark' ? '#0b1410' : '#137a3a');
    }

    apply(saved() || system());

    // In Auto mode, follow the phone if the user switches their phone's theme
    if (mq && mq.addEventListener) {
        mq.addEventListener('change', function () { if (!saved()) apply(system()); });
    }

    window.NeuTheme = {
        get: function () { return root.getAttribute('data-theme'); },
        mode: function () { return saved() || 'auto'; },
        setMode: function (m) {
            try {
                if (m === 'auto') localStorage.removeItem(KEY); else localStorage.setItem(KEY, m);
            } catch (e) {}
            apply(m === 'auto' ? system() : m);
        },
        set: function (t) { this.setMode(t); },
        toggle: function () { this.setMode(this.get() === 'dark' ? 'light' : 'dark'); }
    };
})();
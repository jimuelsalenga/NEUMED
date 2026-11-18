/* Light / dark theme. Load this in <head> (before the page paints) so there is no white flash.
   Follows the phone's setting until the user picks a theme. Later you can add a toggle button that calls NeuTheme.toggle(). */
(function () {
    var KEY = 'neumed-theme', root = document.documentElement;

    function apply(t) {
        root.setAttribute('data-theme', t);
        root.setAttribute('data-bs-theme', t); // makes Bootstrap's own components match
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute('content', t === 'dark' ? '#0b1410' : '#137a3a');
    }

    var saved = null;
    try { saved = localStorage.getItem(KEY); } catch (e) {}
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    apply(saved || (prefersDark ? 'dark' : 'light'));

    window.NeuTheme = {
        get: function () { return root.getAttribute('data-theme'); },
        set: function (t) { try { localStorage.setItem(KEY, t); } catch (e) {} apply(t); },
        toggle: function () { this.set(this.get() === 'dark' ? 'light' : 'dark'); }
    };
})();

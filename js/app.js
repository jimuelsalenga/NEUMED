/* NEUMed UI helpers.
   Show a toast from JavaScript:   NeuUI.toast('Saved!', 'fa-circle-check')
   Or from PHP, drop this anywhere in the page:   <span data-toast="Saved!" data-toast-icon="fa-circle-check" hidden></span> */
(function () {
    function wrap() {
        var w = document.querySelector('.n-toast-wrap');
        if (!w) {
            w = document.createElement('div');
            w.className = 'n-toast-wrap';
            w.setAttribute('role', 'status');
            document.body.appendChild(w);
        }
        return w;
    }

    window.NeuUI = {
        toast: function (message, icon) {
            var t = document.createElement('div');
            t.className = 'n-toast';
            var i = document.createElement('i');
            i.className = 'fas ' + (icon || 'fa-circle-check');
            var s = document.createElement('span');
            s.textContent = message; // textContent keeps it safe
            t.appendChild(i);
            t.appendChild(s);
            wrap().appendChild(t);
            setTimeout(function () {
                t.classList.add('out');
                setTimeout(function () { t.remove(); }, 320);
            }, 3200);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-toast]').forEach(function (el) {
            NeuUI.toast(el.getAttribute('data-toast'), el.getAttribute('data-toast-icon'));
            el.remove();
        });
        // Remove ?success=1 from the address so a refresh doesn't repeat the toast
        if (window.history.replaceState && /[?&]success=/.test(location.search)) {
            var u = new URL(location.href);
            u.searchParams.delete('success');
            window.history.replaceState(null, '', u.pathname + u.search + u.hash);
        }
    });
})();

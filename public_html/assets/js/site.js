document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        if (!confirm(form.dataset.confirm)) {
            e.preventDefault();
        }
    });
});

document.querySelectorAll('.js-print').forEach(function (btn) {
    btn.addEventListener('click', function () {
        window.print();
    });
});

(function () {
    var nav = document.querySelector('.nav');
    if (!nav) return;
    var onScroll = function () {
        nav.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();

(function () {
    var sidebar = document.getElementById('sidebar');
    var toggle = document.getElementById('sidebarToggle');
    var close = document.getElementById('sidebarClose');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar || !toggle) return;

    var setOpen = function (open) {
        sidebar.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
    };

    toggle.addEventListener('click', function () { setOpen(true); });
    if (close) close.addEventListener('click', function () { setOpen(false); });
    if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
})();

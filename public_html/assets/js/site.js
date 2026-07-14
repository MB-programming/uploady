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

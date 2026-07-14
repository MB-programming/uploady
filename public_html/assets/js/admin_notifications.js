(function () {
    var single = document.getElementById('notifTargetSingle');
    var all = document.getElementById('notifTargetAll');
    var targetUser = document.getElementById('notifTargetUser');
    if (!single || !all || !targetUser) return;

    var sync = function () {
        targetUser.style.display = single.checked ? 'block' : 'none';
    };
    single.addEventListener('change', sync);
    all.addEventListener('change', sync);
    sync();
})();

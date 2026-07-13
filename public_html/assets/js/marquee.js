(function () {
    var items = ['يوتيوب', 'يوتيوب Shorts', 'تيك توك', 'انستجرام Reels', 'جدولة تلقائية', 'نشر فوري', 'حذف تلقائي لتوفير المساحة'];
    var track = document.getElementById('marqueeTrack');
    if (!track) return;

    var html = '';
    for (var r = 0; r < 2; r++) {
        items.forEach(function (label) {
            html += '<span class="marquee-item"><span class="dot"></span>' + label + '</span>';
        });
    }
    track.innerHTML = html;
})();

(function () {
    var track = document.getElementById('marqueeTrack');
    if (!track) return;
    var items = JSON.parse(track.dataset.items || '[]');

    var html = '';
    for (var r = 0; r < 2; r++) {
        items.forEach(function (label) {
            html += '<span class="marquee-item"><span class="dot"></span>' + label + '</span>';
        });
    }
    track.innerHTML = html;
})();

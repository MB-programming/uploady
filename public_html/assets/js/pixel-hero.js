/**
 * Vanilla-JS port of a canvas pixel-reveal effect (no React/Framer Motion — this project
 * is plain PHP + vanilla JS, so the animation logic below is adapted directly to the DOM).
 */
(function () {
    function rand(min, max) {
        return Math.random() * (max - min) + min;
    }

    function createPixel(ctx, canvas, x, y, color, baseSpeed, delay) {
        const p = {
            x, y, color, ctx,
            speed: rand(0.08, 0.4) * baseSpeed,
            size: 0,
            sizeStep: rand(0.12, 0.28),
            minSize: 0.5,
            maxSizeInt: 2,
            maxSize: rand(0.5, 2),
            delay,
            counter: 0,
            counterStep: rand(1.8, 3.2) + (canvas.width + canvas.height) * 0.008,
            isIdle: false,
            isReverse: false,
            isShimmer: false,
        };

        p.draw = function () {
            const offset = p.maxSizeInt * 0.5 - p.size * 0.5;
            ctx.fillStyle = p.color;
            ctx.fillRect(p.x + offset, p.y + offset, p.size, p.size);
        };

        p.appear = function () {
            p.isIdle = false;
            if (p.counter <= p.delay) {
                p.counter += p.counterStep;
                return;
            }
            if (p.size >= p.maxSize) p.isShimmer = true;
            if (p.isShimmer) p.shimmer();
            else p.size += p.sizeStep;
            p.draw();
        };

        p.shimmer = function () {
            if (p.size >= p.maxSize) p.isReverse = true;
            else if (p.size <= p.minSize) p.isReverse = false;
            if (p.isReverse) p.size -= p.speed;
            else p.size += p.speed;
        };

        return p;
    }

    function PixelCanvas(wrap, colors, gap, speed) {
        gap = gap || 6;
        speed = speed || 30;

        const canvas = document.createElement('canvas');
        canvas.style.display = 'block';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        wrap.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        let pixels = [];
        let animationId = null;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function init() {
            const rect = wrap.getBoundingClientRect();
            const w = Math.floor(rect.width);
            const h = Math.floor(rect.height);
            canvas.width = w;
            canvas.height = h;

            const effectiveSpeed = reducedMotion ? 0 : Math.min(speed, 100) * 0.001;
            pixels = [];

            for (let x = 0; x < w; x += gap) {
                for (let y = 0; y < h; y += gap) {
                    const color = colors[Math.floor(Math.random() * colors.length)];
                    const dx = x - w / 2;
                    const dy = y - h / 2;
                    const delay = reducedMotion ? 0 : Math.sqrt(dx * dx + dy * dy) * 0.65;
                    pixels.push(createPixel(ctx, canvas, x, y, color, effectiveSpeed, delay));
                }
            }
        }

        function loop() {
            animationId = requestAnimationFrame(loop);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < pixels.length; i++) pixels[i].appear();
        }

        init();
        loop();

        const resizeObserver = new ResizeObserver(function () {
            init();
        });
        resizeObserver.observe(wrap);

        window.addEventListener('beforeunload', function () {
            cancelAnimationFrame(animationId);
            resizeObserver.disconnect();
        });
    }

    document.querySelectorAll('[data-pixel-canvas]').forEach(function (wrap) {
        const style = getComputedStyle(document.documentElement);
        const colors = [
            style.getPropertyValue('--muted').trim() || '#9aa1ad',
            style.getPropertyValue('--muted').trim() || '#9aa1ad',
            style.getPropertyValue('--accent').trim() || '#4f8cff',
        ];
        PixelCanvas(wrap, colors, 6, 30);
    });
})();

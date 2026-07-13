if (window.gsap && window.ScrollTrigger) {
    gsap.registerPlugin(ScrollTrigger);

    gsap.utils.toArray('.feature-card, .step').forEach(function (el, i) {
        gsap.to(el, {
            opacity: 1,
            y: 0,
            duration: 0.7,
            delay: (i % 4) * 0.08,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: el,
                start: 'top 88%',
                toggleActions: 'play none none reverse',
            },
        });
    });

    gsap.from('.hero-content', {
        opacity: 0,
        y: 20,
        duration: 0.9,
        ease: 'power2.out',
        delay: 0.1,
    });
}

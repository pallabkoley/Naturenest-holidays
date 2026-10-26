document.addEventListener('DOMContentLoaded', function () {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            mirror: false
        });
    }

    const heroCarousel = document.getElementById('packageHeroCarousel');
    if (heroCarousel && typeof bootstrap !== 'undefined') {
        new bootstrap.Carousel(heroCarousel, {
            interval: 5000,
            pause: 'hover',
            wrap: true,
            touch: true
        });
    }
});

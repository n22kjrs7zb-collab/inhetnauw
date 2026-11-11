(function () {
    document.querySelectorAll('.ihn-carousel-wrap').forEach(function (wrap) {
        var carousel = wrap.querySelector('.ihn-carousel');
        var prevBtn  = wrap.querySelector('.carousel-prev');
        var nextBtn  = wrap.querySelector('.carousel-next');
        if (!carousel || !prevBtn || !nextBtn) return;

        function scrollAmount() {
            var item = carousel.querySelector('.carousel-item');
            if (!item) return 320;
            var gap = parseInt(getComputedStyle(carousel).gap) || 0;
            return item.offsetWidth + gap;
        }

        prevBtn.addEventListener('click', function () {
            carousel.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
        });

        nextBtn.addEventListener('click', function () {
            carousel.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
        });

        function updateButtons() {
            prevBtn.disabled = carousel.scrollLeft <= 0;
            nextBtn.disabled =
                carousel.scrollLeft >= carousel.scrollWidth - carousel.clientWidth - 1;
        }

        carousel.addEventListener('scroll', updateButtons, { passive: true });
        updateButtons();
    });
})();

(function () {
    function matchUitspraakHoogte() {
        var left     = document.querySelector('.ihn-home-left');
        var uitspraak = document.querySelector('.ihn-uitspraak');
        if (left && uitspraak) {
            uitspraak.style.maxHeight = left.offsetHeight + 'px';
        }
    }

    document.addEventListener('DOMContentLoaded', matchUitspraakHoogte);
    window.addEventListener('resize', matchUitspraakHoogte);
})();

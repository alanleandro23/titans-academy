(() => {
    const menuButton = document.querySelector('[data-menu-button]');
    const menu = document.querySelector('[data-menu]');

    if (menuButton && menu) {
        const closeMenu = () => {
            menu.classList.remove('open');
            menuButton.classList.remove('is-open');
            menuButton.setAttribute('aria-expanded', 'false');
        };

        menuButton.addEventListener('click', () => {
            const isOpen = menu.classList.toggle('open');
            menuButton.classList.toggle('is-open', isOpen);
            menuButton.setAttribute('aria-expanded', String(isOpen));
        });

        menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) closeMenu();
        });
    }

    const carousel = document.querySelector('[data-news-carousel]');
    if (!carousel) return;

    const slides = Array.from(carousel.querySelectorAll('[data-news-slide]'));
    const dots = Array.from(carousel.querySelectorAll('[data-news-dot]'));
    const previousButton = carousel.querySelector('[data-news-prev]');
    const nextButton = carousel.querySelector('[data-news-next]');
    const currentCounter = document.querySelector('[data-news-current]');

    if (slides.length < 2) return;

    let current = 0;
    let timer = null;
    let touchStartX = 0;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const show = (index) => {
        current = (index + slides.length) % slides.length;

        slides.forEach((slide, slideIndex) => {
            const active = slideIndex === current;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
        });

        dots.forEach((dot, dotIndex) => {
            const active = dotIndex === current;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-selected', String(active));
        });

        if (currentCounter) {
            currentCounter.textContent = String(current + 1).padStart(2, '0');
        }
    };

    const stop = () => {
        if (timer) window.clearInterval(timer);
        timer = null;
    };

    const start = () => {
        stop();
        if (!reduceMotion) {
            timer = window.setInterval(() => show(current + 1), 6000);
        }
    };

    previousButton?.addEventListener('click', () => {
        show(current - 1);
        start();
    });

    nextButton?.addEventListener('click', () => {
        show(current + 1);
        start();
    });

    dots.forEach((dot, index) => dot.addEventListener('click', () => {
        show(index);
        start();
    }));

    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', start);

    carousel.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') show(current - 1);
        if (event.key === 'ArrowRight') show(current + 1);
    });

    carousel.addEventListener('touchstart', (event) => {
        touchStartX = event.changedTouches[0]?.clientX ?? 0;
        stop();
    }, { passive: true });

    carousel.addEventListener('touchend', (event) => {
        const touchEndX = event.changedTouches[0]?.clientX ?? 0;
        const distance = touchEndX - touchStartX;
        if (Math.abs(distance) > 50) show(current + (distance < 0 ? 1 : -1));
        start();
    }, { passive: true });

    document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
    show(0);
    start();
})();

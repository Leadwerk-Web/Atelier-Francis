/* ============================================
   ATELIER FRANCIS — Interactions & Animations
   ============================================ */

(function () {
    'use strict';

    /* --- Navigation Scroll Effect --- */
    const nav = document.getElementById('nav');
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');

    let lastScroll = 0;

    function handleNavScroll() {
        const scrollY = window.scrollY;
        if (scrollY > 60) {
            nav.classList.add('nav--scrolled');
        } else {
            nav.classList.remove('nav--scrolled');
        }
        lastScroll = scrollY;
    }

    window.addEventListener('scroll', handleNavScroll, { passive: true });

    /* --- Mobile Menu Toggle --- */
    navToggle.addEventListener('click', function () {
        navToggle.classList.toggle('active');
        navLinks.classList.toggle('open');
        document.body.style.overflow = navLinks.classList.contains('open') ? 'hidden' : '';
    });

    navLinks.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            navToggle.classList.remove('active');
            navLinks.classList.remove('open');
            document.body.style.overflow = '';
        });
    });

    /* --- Scroll Reveal (Intersection Observer) --- */
    const revealElements = document.querySelectorAll(
        '.reveal, .reveal-up, .reveal-slide-left, .reveal-slide-right'
    );

    const revealObserver = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.15,
            rootMargin: '0px 0px -40px 0px',
        }
    );

    revealElements.forEach(function (el) {
        revealObserver.observe(el);
    });

    /* --- Smooth Scroll for anchor links --- */
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            const target = document.querySelector(targetId);
            if (!target) return;

            e.preventDefault();
            const navHeight = nav.offsetHeight;
            const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight;

            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth',
            });
        });
    });

    /* --- Hero entrance animation --- */
    window.addEventListener('load', function () {
        const heroReveals = document.querySelectorAll('.hero .reveal');
        heroReveals.forEach(function (el) {
            el.classList.add('visible');
        });
    });

    /* --- Active nav link highlighting --- */
    const sections = document.querySelectorAll('section[id]');
    const navAnchors = document.querySelectorAll('.nav__links a[href^="#"]');

    function highlightNav() {
        var scrollPosition = window.scrollY + 120;
        sections.forEach(function (section) {
            var sectionTop = section.offsetTop;
            var sectionHeight = section.offsetHeight;
            var sectionId = section.getAttribute('id');

            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                navAnchors.forEach(function (a) {
                    a.style.color = '';
                    if (a.getAttribute('href') === '#' + sectionId) {
                        a.style.color = 'var(--gold)';
                    }
                });
            }
        });
    }

    window.addEventListener('scroll', highlightNav, { passive: true });

    /* --- Team Slider: Drag to scroll --- */
    var slider = document.getElementById('teamSlider');
    if (slider) {
        var isDown = false;
        var startX = 0;
        var scrollLeftStart = 0;

        function startDrag(e) {
            isDown = true;
            slider.classList.add('dragging');
            startX = e.pageX - slider.offsetLeft;
            scrollLeftStart = slider.scrollLeft;
        }

        function endDrag() {
            isDown = false;
            slider.classList.remove('dragging');
        }

        function moveDrag(e) {
            if (!isDown) return;
            e.preventDefault();
            var x = e.pageX - slider.offsetLeft;
            var walk = x - startX;
            slider.scrollLeft = scrollLeftStart - walk;
        }

        slider.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            startDrag(e);
        });

        document.addEventListener('mouseup', function () {
            endDrag();
        });

        slider.addEventListener('mouseleave', function () {
            if (!isDown) return;
            endDrag();
        });

        slider.addEventListener('mousemove', function (e) {
            if (!isDown) return;
            e.preventDefault();
            var x = e.pageX - slider.offsetLeft;
            var walk = x - startX;
            slider.scrollLeft = scrollLeftStart - walk;
        });

        // Touch: preventDefault verhindert Browser-Momentum — Bewegung stoppt sofort beim Loslassen
        var touchLastX = 0;

        slider.addEventListener('touchstart', function (e) {
            touchLastX = e.touches[0].pageX;
        }, { passive: true });

        slider.addEventListener('touchmove', function (e) {
            var x = e.touches[0].pageX;
            var delta = touchLastX - x;
            touchLastX = x;
            slider.scrollLeft += delta;
            e.preventDefault();
        }, { passive: false });

        slider.style.overflowX = 'auto';
        slider.style.scrollbarWidth = 'none';
        slider.style.msOverflowStyle = 'none';
    }

    /* --- Team Cards: 3D Tilt on mousemove --- */
    var teamCards = document.querySelectorAll('.team-card');

    teamCards.forEach(function (card) {
        var portrait = card.querySelector('.team-card__portrait');
        var info = card.querySelector('.team-card__info');

        card.addEventListener('mousemove', function (e) {
            var rect = portrait.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var y = e.clientY - rect.top;
            var centerX = rect.width / 2;
            var centerY = rect.height / 2;

            var rotateY = ((x - centerX) / centerX) * 10;
            var rotateX = ((centerY - y) / centerY) * 10;

            portrait.style.transform = 'rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)';
            portrait.style.boxShadow = '0 12px 40px rgba(0,0,0,0.3)';

            var translateX = ((x - centerX) / centerX) * 6;
            var translateY = ((y - centerY) / centerY) * 4;
            info.style.transform = 'translate(' + translateX + 'px, ' + translateY + 'px)';
        });

        card.addEventListener('mouseleave', function () {
            portrait.style.transform = '';
            portrait.style.boxShadow = '';
            info.style.transform = '';
        });
    });

    /* --- Testimonials: Dot and arrow navigation --- */
    var testimonialsSection = document.querySelector('.testimonials');
    if (testimonialsSection) {
        var testimonialItems = testimonialsSection.querySelectorAll('.testimonial');
        var dots = testimonialsSection.querySelectorAll('.testimonials__dot');
        var arrowPrev = testimonialsSection.querySelector('.testimonials__arrow--prev');
        var arrowNext = testimonialsSection.querySelector('.testimonials__arrow--next');
        var currentIndex = 0;
        var total = testimonialItems.length;

        function showTestimonial(index) {
            if (index < 0) index = total - 1;
            if (index >= total) index = 0;
            currentIndex = index;
            testimonialItems.forEach(function (item) {
                item.classList.remove('testimonial--active');
                if (parseInt(item.getAttribute('data-testimonial'), 10) === index) {
                    item.classList.add('testimonial--active');
                }
            });
            dots.forEach(function (dot) {
                dot.classList.remove('testimonials__dot--active');
                dot.removeAttribute('aria-current');
                if (parseInt(dot.getAttribute('data-dot'), 10) === index) {
                    dot.classList.add('testimonials__dot--active');
                    dot.setAttribute('aria-current', 'true');
                }
            });
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var index = parseInt(this.getAttribute('data-dot'), 10);
                showTestimonial(index);
            });
        });

        if (arrowPrev) {
            arrowPrev.addEventListener('click', function () {
                showTestimonial(currentIndex - 1);
            });
        }
        if (arrowNext) {
            arrowNext.addEventListener('click', function () {
                showTestimonial(currentIndex + 1);
            });
        }
    }
})();

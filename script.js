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
        if (!nav) return;
        const scrollY = window.scrollY;
        if (scrollY > 60) {
            nav.classList.add('nav--scrolled');
        } else {
            nav.classList.remove('nav--scrolled');
        }
        lastScroll = scrollY;
    }

    if (nav) {
        window.addEventListener('scroll', handleNavScroll, { passive: true });
    }

    /* --- Mobile Menu Toggle --- */
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            navToggle.classList.toggle('active');
            navLinks.classList.toggle('open');
            document.body.style.overflow = navLinks.classList.contains('open') ? 'hidden' : '';
        });

        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (!navLinks.classList.contains('open')) return;
                navToggle.classList.remove('active');
                navLinks.classList.remove('open');
                document.body.style.overflow = '';
            });
        });
    }

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
            const navHeight = nav ? nav.offsetHeight : 0;
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

    if (sections.length && navAnchors.length) {
        window.addEventListener('scroll', highlightNav, { passive: true });
    }

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
        if (!portrait) return;

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
            if (info) info.style.transform = 'translate(' + translateX + 'px, ' + translateY + 'px)';
        });

        card.addEventListener('mouseleave', function () {
            portrait.style.transform = '';
            portrait.style.boxShadow = '';
            if (info) info.style.transform = '';
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

    /* --- Framed image: border moves opposite to mouse --- */
    var framedImages = document.querySelectorAll('.about__image--framed');
    framedImages.forEach(function (container) {
        container.addEventListener('mousemove', function (e) {
            var rect = container.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width - 0.5;
            var y = (e.clientY - rect.top) / rect.height - 0.5;
            container.style.setProperty('--frame-x', (-x * 20) + 'px');
            container.style.setProperty('--frame-y', (-y * 20) + 'px');
        });

        container.addEventListener('mouseleave', function () {
            container.style.setProperty('--frame-x', '0px');
            container.style.setProperty('--frame-y', '0px');
        });
    });

    /* --- Galerie Lightbox (Kursgalerien, Eindrücke, Impressionen) --- */
    (function initLightbox() {
        var hasGallery =
            document.querySelector('.page-gallery__trigger') ||
            document.querySelector('.impressions__item') ||
            document.querySelector('.course-page__mini-gallery figure');

        if (!hasGallery) return;

        var lb = document.createElement('div');
        lb.className = 'lightbox';
        lb.setAttribute('role', 'dialog');
        lb.setAttribute('aria-modal', 'true');
        lb.setAttribute('aria-label', 'Vergrößerte Ansicht');
        lb.innerHTML =
            '<button type="button" class="lightbox__close" aria-label="Schließen">&times;</button>' +
            '<img class="lightbox__img" alt="">';
        document.body.appendChild(lb);
        var lbImg = lb.querySelector('.lightbox__img');
        var lbClose = lb.querySelector('.lightbox__close');

        function openLightbox(src, alt) {
            lbImg.src = src;
            lbImg.alt = alt || '';
            lb.classList.add('lightbox--open');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lb.classList.remove('lightbox--open');
            document.body.style.overflow = '';
            lbImg.removeAttribute('src');
        }

        function bindImageTrigger(el, getImg) {
            el.addEventListener('click', function () {
                var img = getImg(el);
                if (img && img.src) {
                    openLightbox(img.currentSrc || img.src, img.alt);
                }
            });
            el.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                e.preventDefault();
                var img = getImg(el);
                if (img && img.src) {
                    openLightbox(img.currentSrc || img.src, img.alt);
                }
            });
        }

        document.querySelectorAll('.page-gallery__trigger').forEach(function (btn) {
            bindImageTrigger(btn, function (el) {
                return el.querySelector('img');
            });
        });

        document.querySelectorAll('.impressions__item').forEach(function (item) {
            bindImageTrigger(item, function (el) {
                return el.querySelector('img');
            });
        });

        document.querySelectorAll('.course-page__mini-gallery figure').forEach(function (figure) {
            figure.setAttribute('tabindex', '0');
            figure.setAttribute('role', 'button');
            var img = figure.querySelector('img');
            if (img && img.alt) {
                figure.setAttribute('aria-label', img.alt + ' vergrößern');
            } else {
                figure.setAttribute('aria-label', 'Bild vergrößern');
            }
            bindImageTrigger(figure, function (el) {
                return el.querySelector('img');
            });
        });

        lb.addEventListener('click', function (e) {
            if (e.target === lb || e.target === lbClose) closeLightbox();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && lb.classList.contains('lightbox--open')) closeLightbox();
        });
    })();

    /* --- Kontakt: Formular öffnet E-Mail-Programm (mailto) --- */
    /* --- Kursseiten: Kurszeiten an Galerie oder Fließtext, „Mehr anzeigen“ --- */
    function initCourseSchedulePanels() {
        var sheets = document.querySelectorAll('.course-page__sheet');
        if (!sheets.length) return;

        var mobileQuery = window.matchMedia('(max-width: 1024px)');

        sheets.forEach(function (sheet) {
            var gallery = sheet.querySelector('.course-page__mini-gallery');
            var aside = sheet.querySelector('.course-page__aside');
            var main = sheet.querySelector('.course-page__main');
            if (!aside || !main) return;

            sheet.classList.toggle('course-page__sheet--no-gallery', !gallery);

            var panels = Array.prototype.slice.call(
                aside.querySelectorAll('.course-page__panel')
            );
            var schedulePanels = panels.filter(function (panel) {
                var title = panel.querySelector('.course-page__panel-title');
                return title && title.textContent.trim() !== 'Kursleitung';
            });

            if (!schedulePanels.length) return;

            schedulePanels.forEach(function (panel, index) {
                var list = panel.querySelector(':scope > .course-page__panel-list');
                if (!list || list.closest('.course-page__schedule-wrap')) return;

                panel.classList.add('course-page__panel--schedule');
                if (index === 0 && gallery) {
                    panel.classList.add('is-schedule-anchor');
                }

                var wrap = document.createElement('div');
                wrap.className = 'course-page__schedule-wrap';
                list.parentNode.insertBefore(wrap, list);
                wrap.appendChild(list);
                list.classList.add('course-page__schedule-list');

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'course-page__schedule-more';
                btn.setAttribute('aria-expanded', 'false');
                btn.textContent = 'Mehr anzeigen';
                wrap.appendChild(btn);

                btn.addEventListener('click', function () {
                    var expanded = wrap.classList.toggle('is-expanded');
                    btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    btn.textContent = expanded ? 'Weniger anzeigen' : 'Mehr anzeigen';
                    if (expanded) {
                        list.style.maxHeight = list.scrollHeight + 'px';
                    } else {
                        list.style.maxHeight = wrap.dataset.collapsedHeight + 'px';
                    }
                });
            });

            function getCollapsedListHeight(list, maxHeight) {
                var items = list.querySelectorAll('li');
                if (!items.length) return maxHeight;

                list.style.maxHeight = 'none';
                list.style.overflow = 'visible';

                var listTop = list.getBoundingClientRect().top;
                var fitHeight = 0;
                var visibleCount = 0;

                for (var i = 0; i < items.length; i++) {
                    var itemBottom = items[i].getBoundingClientRect().bottom - listTop;
                    if (itemBottom <= maxHeight + 1) {
                        fitHeight = itemBottom;
                        visibleCount++;
                    } else {
                        break;
                    }
                }

                if (!visibleCount) {
                    fitHeight = items[0].getBoundingClientRect().bottom - listTop;
                }

                return Math.ceil(fitHeight);
            }

            function getScheduleBudget() {
                if (gallery) {
                    return gallery.getBoundingClientRect().height;
                }

                var prose = main.querySelector('.course-page__prose');
                if (!prose) {
                    return main.getBoundingClientRect().height;
                }

                var proseBottom = prose.getBoundingClientRect().bottom;
                var asideTop = aside.getBoundingClientRect().top;
                var offset = 0;
                var kursleitungPanel = aside.querySelector(
                    '.course-page__panel:not(.course-page__panel--schedule)'
                );

                if (kursleitungPanel) {
                    offset = kursleitungPanel.getBoundingClientRect().bottom - asideTop;
                }

                var actions = aside.querySelector('.course-page__aside-actions');
                if (actions) {
                    offset += actions.getBoundingClientRect().height + 16;
                }

                return Math.max(80, proseBottom - asideTop - offset);
            }

            function syncScheduleHeights() {
                var budget = getScheduleBudget();
                if (!budget) return;

                var panelCount = schedulePanels.length;
                var perPanel =
                    panelCount > 1
                        ? Math.max(80, Math.floor(budget / panelCount))
                        : budget;

                schedulePanels.forEach(function (panel) {
                    var wrap = panel.querySelector('.course-page__schedule-wrap');
                    var list = panel.querySelector('.course-page__schedule-list');
                    var btn = panel.querySelector('.course-page__schedule-more');
                    if (!wrap || !list || !btn) return;

                    if (mobileQuery.matches) {
                        wrap.classList.remove('is-collapsible', 'is-expanded');
                        list.style.maxHeight = '';
                        list.style.overflow = '';
                        btn.hidden = false;
                        btn.setAttribute('aria-expanded', 'false');
                        btn.textContent = 'Mehr anzeigen';
                        return;
                    }

                    var items = list.querySelectorAll('li');
                    var collapsedHeight = getCollapsedListHeight(list, perPanel);
                    var needsMore = items.length > 0 && collapsedHeight < list.scrollHeight - 1;

                    wrap.dataset.collapsedHeight = String(collapsedHeight);
                    var isExpanded = wrap.classList.contains('is-expanded');

                    wrap.classList.toggle('is-collapsible', needsMore);

                    if (!needsMore) {
                        wrap.classList.remove('is-expanded');
                        list.style.maxHeight = '';
                        list.style.overflow = '';
                        btn.hidden = true;
                        btn.setAttribute('aria-expanded', 'false');
                        btn.textContent = 'Mehr anzeigen';
                        return;
                    }

                    list.style.overflow = 'hidden';
                    btn.hidden = false;

                    if (isExpanded) {
                        list.style.maxHeight = list.scrollHeight + 'px';
                        btn.setAttribute('aria-expanded', 'true');
                        btn.textContent = 'Weniger anzeigen';
                    } else {
                        list.style.maxHeight = collapsedHeight + 'px';
                        btn.setAttribute('aria-expanded', 'false');
                        btn.textContent = 'Mehr anzeigen';
                    }
                });
            }

            syncScheduleHeights();

            if (gallery) {
                gallery.querySelectorAll('img').forEach(function (img) {
                    if (!img.complete) {
                        img.addEventListener('load', syncScheduleHeights);
                    }
                });
            }

            if (typeof ResizeObserver !== 'undefined') {
                var ro = new ResizeObserver(syncScheduleHeights);
                if (gallery) {
                    ro.observe(gallery);
                } else {
                    var prose = main.querySelector('.course-page__prose');
                    ro.observe(prose || main);
                }
                ro.observe(aside);
            }

            window.addEventListener('resize', syncScheduleHeights);
            if (mobileQuery.addEventListener) {
                mobileQuery.addEventListener('change', syncScheduleHeights);
            } else if (mobileQuery.addListener) {
                mobileQuery.addListener(syncScheduleHeights);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCourseSchedulePanels);
    } else {
        initCourseSchedulePanels();
    }

    var reachForm = document.getElementById('reachForm');
    if (reachForm) {
        reachForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!reachForm.checkValidity()) {
                reachForm.reportValidity();
                return;
            }
            var nameInput = document.getElementById('reach-name');
            var emailInput = document.getElementById('reach-email');
            var messageInput = document.getElementById('reach-message');
            var name = nameInput ? nameInput.value.trim() : '';
            var email = emailInput ? emailInput.value.trim() : '';
            var message = messageInput ? messageInput.value.trim() : '';
            var subject = 'Kontaktanfrage';
            if (name) subject += ' — ' + name;
            var body =
                message +
                '\n\n—\n' +
                name +
                '\n' +
                email;
            var href =
                'mailto:info@atelierfrancis.de?subject=' +
                encodeURIComponent(subject) +
                '&body=' +
                encodeURIComponent(body);
            window.location.href = href;
        });
    }
})();

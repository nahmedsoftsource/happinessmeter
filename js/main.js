/**
 * Josh Warner Portfolio - Main JavaScript
 * Handles animations, interactions, and custom cursor
 */

(function ($) {
    'use strict';

    // =============================================
    // Custom Cursor
    // =============================================
    const cursor = {
        follower: document.querySelector('.cursor-follower'),
        dot: document.querySelector('.cursor-dot'),

        init: function () {
            if (!this.follower || !this.dot) return;

            // Check for touch device
            if ('ontouchstart' in window) {
                this.follower.style.display = 'none';
                this.dot.style.display = 'none';
                return;
            }

            let mouseX = 0, mouseY = 0;
            let followerX = 0, followerY = 0;
            let dotX = 0, dotY = 0;

            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
            });

            // Smooth cursor animation
            const animateCursor = () => {
                // Dot follows mouse exactly
                dotX += (mouseX - dotX) * 0.5;
                dotY += (mouseY - dotY) * 0.5;
                this.dot.style.left = dotX + 'px';
                this.dot.style.top = dotY + 'px';

                // Follower has slight lag
                followerX += (mouseX - followerX) * 0.15;
                followerY += (mouseY - followerY) * 0.15;
                this.follower.style.left = followerX + 'px';
                this.follower.style.top = followerY + 'px';

                requestAnimationFrame(animateCursor);
            };

            animateCursor();

            // Hover effects
            const hoverElements = document.querySelectorAll('a, button, .project-card, .skill-card');
            hoverElements.forEach(el => {
                el.addEventListener('mouseenter', () => {
                    this.follower.classList.add('hover');
                });
                el.addEventListener('mouseleave', () => {
                    this.follower.classList.remove('hover');
                });
            });
        }
    };

    // =============================================
    // Navigation
    // =============================================
    const navigation = {
        navbar: document.querySelector('.navbar'),

        init: function () {
            if (!this.navbar) return;

            // Scroll behavior
            let lastScroll = 0;
            window.addEventListener('scroll', () => {
                const currentScroll = window.pageYOffset;

                if (currentScroll > 50) {
                    this.navbar.classList.add('scrolled');
                } else {
                    this.navbar.classList.remove('scrolled');
                }

                lastScroll = currentScroll;
            });

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }
    };

    // =============================================
    // Scroll Animations (GSAP)
    // =============================================
    const animations = {
        init: function () {
            // Check if GSAP is available
            if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
                this.fallbackAnimations();
                return;
            }

            gsap.registerPlugin(ScrollTrigger);

            // Hero animations
            gsap.from('.hero-title .line', {
                y: 100,
                opacity: 0,
                duration: 1,
                stagger: 0.2,
                ease: 'power4.out',
                delay: 0.3
            });

            gsap.from('.hero-subtitle', {
                y: 30,
                opacity: 0,
                duration: 0.8,
                ease: 'power3.out',
                delay: 0.8
            });

            // Skill cards
            gsap.utils.toArray('.skill-card').forEach((card, i) => {
                gsap.from(card, {
                    scrollTrigger: {
                        trigger: card,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    },
                    y: 60,
                    opacity: 0,
                    duration: 0.8,
                    delay: i * 0.1,
                    ease: 'power3.out'
                });
            });

            // Project cards
            gsap.utils.toArray('.project-card').forEach((card, i) => {
                gsap.from(card, {
                    scrollTrigger: {
                        trigger: card,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    },
                    y: 80,
                    opacity: 0,
                    duration: 1,
                    delay: i * 0.15,
                    ease: 'power3.out'
                });
            });

            // Section titles
            gsap.utils.toArray('.section-title').forEach(title => {
                gsap.from(title, {
                    scrollTrigger: {
                        trigger: title,
                        start: 'top 90%',
                        toggleActions: 'play none none reverse'
                    },
                    y: 30,
                    opacity: 0,
                    duration: 0.6,
                    ease: 'power3.out'
                });
            });

            // Contact section
            gsap.from('.contact-title', {
                scrollTrigger: {
                    trigger: '.contact-section',
                    start: 'top 70%',
                    toggleActions: 'play none none reverse'
                },
                y: 50,
                opacity: 0,
                duration: 0.8,
                ease: 'power3.out'
            });

            gsap.from('.contact-subtitle, .contact-email, .social-links', {
                scrollTrigger: {
                    trigger: '.contact-section',
                    start: 'top 70%',
                    toggleActions: 'play none none reverse'
                },
                y: 30,
                opacity: 0,
                duration: 0.8,
                stagger: 0.15,
                delay: 0.2,
                ease: 'power3.out'
            });

            // About page animations
            if (document.querySelector('.about-section')) {
                gsap.from('.about-title', {
                    y: 50,
                    opacity: 0,
                    duration: 1,
                    ease: 'power4.out',
                    delay: 0.3
                });

                gsap.from('.about-intro', {
                    y: 30,
                    opacity: 0,
                    duration: 0.8,
                    ease: 'power3.out',
                    delay: 0.5
                });

                gsap.from('.about-image', {
                    scrollTrigger: {
                        trigger: '.about-content',
                        start: 'top 70%'
                    },
                    x: -50,
                    opacity: 0,
                    duration: 1,
                    ease: 'power3.out'
                });

                gsap.from('.about-text', {
                    scrollTrigger: {
                        trigger: '.about-content',
                        start: 'top 70%'
                    },
                    x: 50,
                    opacity: 0,
                    duration: 1,
                    delay: 0.2,
                    ease: 'power3.out'
                });
            }
        },

        fallbackAnimations: function () {
            // Simple CSS-based fallback animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            document.querySelectorAll('.skill-card, .project-card, .section-title').forEach(el => {
                el.classList.add('fade-in');
                observer.observe(el);
            });

            // Immediate animations for hero
            setTimeout(() => {
                document.querySelectorAll('.hero-title .line').forEach((line, i) => {
                    setTimeout(() => {
                        line.style.opacity = '1';
                        line.style.transform = 'translateY(0)';
                    }, i * 200);
                });

                setTimeout(() => {
                    const subtitle = document.querySelector('.hero-subtitle');
                    if (subtitle) {
                        subtitle.style.opacity = '1';
                        subtitle.style.transform = 'translateY(0)';
                    }
                }, 600);
            }, 100);
        }
    };

    // =============================================
    // Magnetic Buttons (Optional Enhancement)
    // =============================================
    const magneticElements = {
        init: function () {
            const elements = document.querySelectorAll('.contact-email, .social-link');

            elements.forEach(el => {
                el.addEventListener('mousemove', (e) => {
                    const rect = el.getBoundingClientRect();
                    const x = e.clientX - rect.left - rect.width / 2;
                    const y = e.clientY - rect.top - rect.height / 2;

                    el.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px)`;
                });

                el.addEventListener('mouseleave', () => {
                    el.style.transform = 'translate(0, 0)';
                });
            });
        }
    };

    // =============================================
    // Page Loader
    // =============================================
    const pageLoader = {
        init: function () {
            const loader = document.querySelector('.page-loader');
            if (loader) {
                window.addEventListener('load', () => {
                    setTimeout(() => {
                        loader.classList.add('hidden');
                    }, 500);
                });
            }
        }
    };

    // =============================================
    // Parallax Effects
    // =============================================
    const parallax = {
        init: function () {
            const projectImages = document.querySelectorAll('.project-image');

            window.addEventListener('scroll', () => {
                projectImages.forEach(img => {
                    const rect = img.getBoundingClientRect();
                    const inView = rect.top < window.innerHeight && rect.bottom > 0;

                    if (inView) {
                        const scrollPercent = (window.innerHeight - rect.top) / (window.innerHeight + rect.height);
                        const translateY = (scrollPercent - 0.5) * 20;
                        const placeholder = img.querySelector('.project-placeholder');
                        if (placeholder) {
                            placeholder.style.transform = `scale(1) translateY(${translateY}px)`;
                        }
                    }
                });
            });
        }
    };

    // =============================================
    // Mobile Menu
    // =============================================
    const mobileMenu = {
        init: function () {
            const toggler = document.querySelector('.navbar-toggler');
            const navCollapse = document.querySelector('.navbar-collapse');

            if (toggler && navCollapse) {
                // Close menu on link click
                navCollapse.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', () => {
                        const bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
                        if (bsCollapse) {
                            bsCollapse.hide();
                        }
                    });
                });
            }
        }
    };

    // =============================================
    // Text Reveal Animation
    // =============================================
    const textReveal = {
        init: function () {
            // Wrap hero title lines for reveal animation
            document.querySelectorAll('.hero-title .line').forEach(line => {
                const wrapper = document.createElement('span');
                wrapper.className = 'line-wrapper';
                wrapper.style.overflow = 'hidden';
                wrapper.style.display = 'block';

                const inner = document.createElement('span');
                inner.className = 'line-inner';
                inner.style.display = 'block';
                inner.style.transform = 'translateY(100%)';
                inner.style.opacity = '0';
                inner.style.transition = 'transform 0.8s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.8s ease';
                inner.textContent = line.textContent;

                line.textContent = '';
                wrapper.appendChild(inner);
                line.appendChild(wrapper);

                setTimeout(() => {
                    inner.style.transform = 'translateY(0)';
                    inner.style.opacity = '1';
                }, 100 + Array.from(line.parentNode.children).indexOf(line) * 150);
            });
        }
    };

    // =============================================
    // Tilt Effect for Project Cards
    // =============================================
    const tiltEffect = {
        init: function () {
            const cards = document.querySelectorAll('.project-card');

            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;

                    const rotateX = (y - centerY) / 20;
                    const rotateY = (centerX - x) / 20;

                    const image = card.querySelector('.project-image');
                    if (image) {
                        image.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
                    }
                });

                card.addEventListener('mouseleave', () => {
                    const image = card.querySelector('.project-image');
                    if (image) {
                        image.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
                    }
                });
            });
        }
    };

    // =============================================
    // Initialize Everything
    // =============================================
    document.addEventListener('DOMContentLoaded', function () {
        cursor.init();
        navigation.init();
        animations.init();
        magneticElements.init();
        mobileMenu.init();
        tiltEffect.init();

        // Add loaded class to body for CSS transitions
        document.body.classList.add('loaded');
    });

    // jQuery ready
    $(function () {
        // Smooth reveal on scroll for elements with data-aos attribute
        $('[data-aos]').each(function () {
            const $this = $(this);
            const delay = $this.data('aos-delay') || 0;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            $this.addClass('aos-animate');
                        }, delay);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            observer.observe(this);
        });

        // Active nav link based on scroll position
        $(window).on('scroll', function () {
            const scrollPos = $(window).scrollTop();

            $('section[id]').each(function () {
                const sectionTop = $(this).offset().top - 100;
                const sectionBottom = sectionTop + $(this).outerHeight();
                const sectionId = $(this).attr('id');

                if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                    $('.nav-link').removeClass('active');
                    $(`.nav-link[href="#${sectionId}"]`).addClass('active');
                }
            });
        });
    });

})(jQuery);

/**
 * Josh Warner Portfolio - Main JavaScript
 * Modal Carousel, Animations, and Interactions
 */

(function () {
    'use strict';

    // =============================================
    // Project Data for Modal
    // =============================================
    const projectsData = {
        1: {
            title: 'Libra',
            description: 'A modern public library catalog app integrating physical book checkout, e-readers, audiobooks, and more. Designed to make discovering and borrowing books seamless across all formats.',
            tags: ['Product Design', 'Mobile App', 'UX/UI'],
            images: [
                { src: '1.jpg', alt: 'Libra App - Main Interface' },
                { src: '1-2.jpg', alt: 'Libra App - Book Details' },
                { src: '1-3.jpg', alt: 'Libra App - Search Feature' },
                { src: '1-4.jpg', alt: 'Libra App - User Profile' }
            ]
        },
        2: {
            title: 'Energy Dashboard',
            description: 'A comprehensive dashboard for tracking energy from orbital solar satellites with real-time monitoring, analytics, and predictive insights for sustainable energy management.',
            tags: ['Product Design', 'Dashboard', 'Data Visualization'],
            images: [
                { src: '2.jpg', alt: 'Energy Dashboard - Overview' },
                { src: '2-2.jpg', alt: 'Energy Dashboard - Analytics' },
                { src: '2-3.jpg', alt: 'Energy Dashboard - Reports' },
                { src: '2-4.jpg', alt: 'Energy Dashboard - Settings' }
            ]
        },
        3: {
            title: 'Solar Company Branding',
            description: 'Complete branding and product design for a home solar energy company. Including logo design, brand guidelines, marketing materials, and digital presence.',
            tags: ['Branding', 'Graphic Design', 'Identity'],
            images: [
                { src: '3.jpg', alt: 'Solar Branding - Logo' },
                { src: '3-2.jpg', alt: 'Solar Branding - Stationery' },
                { src: '3-3.jpg', alt: 'Solar Branding - Marketing' },
                { src: '3-4.jpg', alt: 'Solar Branding - Guidelines' }
            ]
        },
        4: {
            title: 'Protocol',
            description: 'A platform that makes it easy for traditional art dealers and galleries to buy and sell art with crypto. Bridging the gap between traditional art market and Web3.',
            tags: ['Product Design', 'Web3', 'Marketplace'],
            images: [
                { src: '4.jpg', alt: 'Protocol - Homepage' },
                { src: '4-2.jpg', alt: 'Protocol - Gallery View' },
                { src: '4-3.jpg', alt: 'Protocol - Art Details' },
                { src: '4-4.jpg', alt: 'Protocol - Transaction' }
            ]
        }
    };

    // =============================================
    // Modal & Carousel Controller
    // =============================================
    const modalController = {
        modal: null,
        track: null,
        indicators: null,
        currentSlide: 0,
        totalSlides: 0,
        currentProject: null,

        init: function () {
            this.modal = document.getElementById('projectModal');
            if (!this.modal) return;

            this.track = this.modal.querySelector('.carousel-track');
            this.indicators = this.modal.querySelector('.carousel-indicators');

            this.bindEvents();
        },

        bindEvents: function () {
            // Portfolio item clicks
            document.querySelectorAll('.portfolio-item').forEach(item => {
                item.addEventListener('click', () => {
                    const projectId = item.dataset.project;
                    this.openModal(projectId);
                });
            });

            // Close button
            this.modal.querySelector('.modal-close').addEventListener('click', () => {
                this.closeModal();
            });

            // Overlay click to close
            this.modal.querySelector('.modal-overlay').addEventListener('click', () => {
                this.closeModal();
            });

            // Navigation buttons
            this.modal.querySelector('.carousel-prev').addEventListener('click', () => {
                this.prevSlide();
            });

            this.modal.querySelector('.carousel-next').addEventListener('click', () => {
                this.nextSlide();
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (!this.modal.classList.contains('active')) return;

                if (e.key === 'Escape') this.closeModal();
                if (e.key === 'ArrowLeft') this.prevSlide();
                if (e.key === 'ArrowRight') this.nextSlide();
            });

            // Touch/swipe support
            let touchStartX = 0;
            let touchEndX = 0;

            this.track.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            this.track.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                this.handleSwipe(touchStartX, touchEndX);
            }, { passive: true });
        },

        handleSwipe: function (startX, endX) {
            const threshold = 50;
            const diff = startX - endX;

            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    this.nextSlide();
                } else {
                    this.prevSlide();
                }
            }
        },

        openModal: function (projectId) {
            const project = projectsData[projectId];
            if (!project) return;

            this.currentProject = project;
            this.currentSlide = 0;

            // Build carousel slides
            this.buildCarousel(project.images);

            // Build indicators
            this.buildIndicators(project.images.length);

            // Update content
            this.modal.querySelector('.modal-title').textContent = project.title;
            this.modal.querySelector('.modal-description').textContent = project.description;

            // Build tags
            const tagsContainer = this.modal.querySelector('.modal-tags');
            tagsContainer.innerHTML = project.tags.map(tag =>
                `<span class="tag">${tag}</span>`
            ).join('');

            // Show modal
            this.modal.classList.add('active');
            document.body.classList.add('modal-open');

            // Update navigation buttons
            this.updateNavigation();
        },

        closeModal: function () {
            this.modal.classList.remove('active');
            document.body.classList.remove('modal-open');

            // Reset after animation
            setTimeout(() => {
                this.track.innerHTML = '';
                this.indicators.innerHTML = '';
                this.currentSlide = 0;
            }, 400);
        },

        buildCarousel: function (images) {
            this.totalSlides = images.length;

            this.track.innerHTML = images.map((img, index) => `
                <div class="carousel-slide" data-index="${index}">
                    <img src="${img.src}" alt="${img.alt}" loading="lazy" onerror="this.src='https://via.placeholder.com/1200x750/1a1a1a/666666?text=Image+${index + 1}'">
                </div>
            `).join('');

            this.goToSlide(0);
        },

        buildIndicators: function (count) {
            this.indicators.innerHTML = Array.from({ length: count }, (_, i) => `
                <button class="carousel-indicator ${i === 0 ? 'active' : ''}" data-index="${i}" aria-label="Go to slide ${i + 1}"></button>
            `).join('');

            // Bind indicator clicks
            this.indicators.querySelectorAll('.carousel-indicator').forEach(indicator => {
                indicator.addEventListener('click', () => {
                    const index = parseInt(indicator.dataset.index);
                    this.goToSlide(index);
                });
            });
        },

        goToSlide: function (index) {
            if (index < 0 || index >= this.totalSlides) return;

            this.currentSlide = index;
            this.track.style.transform = `translateX(-${index * 100}%)`;

            // Update indicators
            this.indicators.querySelectorAll('.carousel-indicator').forEach((ind, i) => {
                ind.classList.toggle('active', i === index);
            });

            this.updateNavigation();
        },

        prevSlide: function () {
            if (this.currentSlide > 0) {
                this.goToSlide(this.currentSlide - 1);
            }
        },

        nextSlide: function () {
            if (this.currentSlide < this.totalSlides - 1) {
                this.goToSlide(this.currentSlide + 1);
            }
        },

        updateNavigation: function () {
            const prevBtn = this.modal.querySelector('.carousel-prev');
            const nextBtn = this.modal.querySelector('.carousel-next');

            prevBtn.disabled = this.currentSlide === 0;
            nextBtn.disabled = this.currentSlide === this.totalSlides - 1;
        }
    };

    // =============================================
    // Smooth Scroll
    // =============================================
    const smoothScroll = {
        init: function () {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        const headerOffset = 80;
                        const elementPosition = target.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        }
    };

    // =============================================
    // Scroll Animations
    // =============================================
    const scrollAnimations = {
        init: function () {
            // Skill cards animation
            const skillObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('visible');
                        }, index * 100);
                        skillObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.15,
                rootMargin: '0px 0px -80px 0px'
            });

            document.querySelectorAll('.skill-card').forEach(card => {
                skillObserver.observe(card);
            });

            // Portfolio items animation
            const portfolioObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            document.querySelectorAll('.portfolio-item').forEach(item => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                portfolioObserver.observe(item);
            });
        }
    };

    // =============================================
    // Parallax Effects
    // =============================================
    const parallax = {
        init: function () {
            const heroVideo = document.querySelector('.hero-video');

            if (heroVideo) {
                window.addEventListener('scroll', () => {
                    const scrolled = window.pageYOffset;
                    if (scrolled < window.innerHeight) {
                        heroVideo.style.transform = `translateY(${scrolled * 0.3}px)`;
                    }
                }, { passive: true });
            }
        }
    };

    // =============================================
    // Video Controls
    // =============================================
    const videoController = {
        init: function () {
            const video = document.getElementById('heroVideo');
            if (!video) return;

            // Ensure video plays on mobile
            video.play().catch(() => {
                // Autoplay blocked, add click to play
                video.muted = true;
                video.play();
            });

            // Pause when not visible (performance)
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        video.play();
                    } else {
                        video.pause();
                    }
                });
            }, { threshold: 0.1 });

            observer.observe(video);
        }
    };

    // =============================================
    // Header Scroll Effect
    // =============================================
    const headerScroll = {
        init: function () {
            const header = document.querySelector('header');
            if (!header) return;

            let lastScroll = 0;

            window.addEventListener('scroll', () => {
                const currentScroll = window.pageYOffset;

                if (currentScroll > 100) {
                    header.style.background = 'rgba(0, 0, 0, 0.98)';
                } else {
                    header.style.background = 'rgba(0, 0, 0, 0.95)';
                }

                lastScroll = currentScroll;
            }, { passive: true });
        }
    };

    // =============================================
    // Initialize Everything
    // =============================================
    document.addEventListener('DOMContentLoaded', function () {
        modalController.init();
        smoothScroll.init();
        scrollAnimations.init();
        parallax.init();
        videoController.init();
        headerScroll.init();

        // Add loaded class
        document.body.classList.add('loaded');
    });

})();

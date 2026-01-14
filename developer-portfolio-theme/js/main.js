/**
 * Developer Portfolio Theme - Main JavaScript
 */

(function () {
    'use strict';

    // =============================================
    // Behance-style Project Modal Controller
    // =============================================
    const projectModalController = {
        modal: null,
        imagesContainer: null,
        scrollContainer: null,
        currentProject: null,

        init: function () {
            this.modal = document.getElementById('projectModal');
            if (!this.modal) return;

            this.imagesContainer = document.getElementById('projectImages');
            this.scrollContainer = this.modal.querySelector('.project-scroll-container');
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
            const closeBtn = this.modal.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.closeModal();
                });
            }

            // Overlay click to close
            const overlay = this.modal.querySelector('.modal-overlay');
            if (overlay) {
                overlay.addEventListener('click', () => {
                    this.closeModal();
                });
            }

            // Keyboard navigation (ESC to close)
            document.addEventListener('keydown', (e) => {
                if (!this.modal.classList.contains('active')) return;
                if (e.key === 'Escape') this.closeModal();
            });
        },

        openModal: function (projectId) {
            if (typeof projectsData === 'undefined') return;

            const project = projectsData[projectId];
            if (!project) return;

            this.currentProject = project;

            // Update title
            const titleEl = this.modal.querySelector('.modal-title');
            if (titleEl) titleEl.textContent = project.title;

            // Update description
            const descEl = this.modal.querySelector('.modal-description');
            if (descEl) descEl.textContent = project.description;

            // Build tags
            const tagsContainer = this.modal.querySelector('.modal-tags');
            if (tagsContainer && project.tags) {
                tagsContainer.innerHTML = project.tags.map(tag =>
                    `<span class="tag">${tag}</span>`
                ).join('');
            }

            // Build images
            this.buildImages(project.images || []);

            // Show modal
            this.modal.classList.add('active');
            document.body.classList.add('modal-open');

            // Scroll to top
            if (this.scrollContainer) {
                this.scrollContainer.scrollTop = 0;
            }
        },

        buildImages: function (images) {
            if (!this.imagesContainer) return;

            if (images.length === 0) {
                this.imagesContainer.innerHTML = `
                    <div class="project-image-item">
                        <div style="padding: 4rem; text-align: center; color: var(--text-muted);">
                            <p>No images available for this project yet.</p>
                        </div>
                    </div>
                `;
                return;
            }

            this.imagesContainer.innerHTML = images.map((src, index) => `
                <div class="project-image-item">
                    <img src="${src}"
                         alt="${this.currentProject.title} - Image ${index + 1}"
                         loading="${index < 2 ? 'eager' : 'lazy'}"
                         onerror="this.parentElement.style.display='none'">
                </div>
            `).join('');
        },

        closeModal: function () {
            this.modal.classList.remove('active');
            document.body.classList.remove('modal-open');

            setTimeout(() => {
                if (this.imagesContainer) {
                    this.imagesContainer.innerHTML = '';
                }
            }, 400);
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

            video.play().catch(() => {
                video.muted = true;
                video.play();
            });

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
    // Initialize Everything
    // =============================================
    document.addEventListener('DOMContentLoaded', function () {
        projectModalController.init();
        smoothScroll.init();
        scrollAnimations.init();
        parallax.init();
        videoController.init();

        document.body.classList.add('loaded');
    });

})();

/**
 * Art Gallery JavaScript
 * Filtering and Lightbox functionality
 */

(function() {
    'use strict';

    // =============================================
    // Art Data
    // =============================================
    const artData = [
        { src: 'art/art-1.jpg', title: 'Luminescence', category: '3D Art' },
        { src: 'art/art-2.jpg', title: 'Light Study #03', category: 'Abstract' },
        { src: 'art/art-3.jpg', title: 'Type Experiment', category: 'Typography' },
        { src: 'art/art-4.jpg', title: 'Geometric Dreams', category: '3D Art' },
        { src: 'art/art-5.jpg', title: 'Digital Erosion', category: 'Experimental' },
        { src: 'art/art-6.jpg', title: 'Ethereal Space', category: '3D Art' },
        { src: 'art/art-7.jpg', title: 'Motion Type', category: 'Typography' },
        { src: 'art/art-8.jpg', title: 'Fragments', category: 'Abstract' },
        { src: 'art/art-9.jpg', title: 'Signal Decay', category: 'Experimental' },
        { src: 'art/art-10.jpg', title: 'Digital Being', category: '3D Art' },
        { src: 'art/art-11.jpg', title: 'Chromatic Shift', category: 'Abstract' },
        { src: 'art/art-12.jpg', title: 'Dimensional Type', category: 'Typography' }
    ];

    // =============================================
    // Gallery Filter
    // =============================================
    const galleryFilter = {
        filterBtns: null,
        artItems: null,
        currentFilter: 'all',

        init: function() {
            this.filterBtns = document.querySelectorAll('.filter-btn');
            this.artItems = document.querySelectorAll('.art-item');

            if (!this.filterBtns.length) return;

            this.bindEvents();
            this.animateItems();
        },

        bindEvents: function() {
            this.filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const filter = btn.dataset.filter;
                    this.applyFilter(filter);

                    // Update active state
                    this.filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                });
            });
        },

        applyFilter: function(filter) {
            this.currentFilter = filter;

            this.artItems.forEach(item => {
                const category = item.dataset.category;

                if (filter === 'all' || category === filter) {
                    item.classList.remove('hidden');
                    setTimeout(() => {
                        item.classList.add('visible');
                    }, 50);
                } else {
                    item.classList.remove('visible');
                    setTimeout(() => {
                        item.classList.add('hidden');
                    }, 300);
                }
            });
        },

        animateItems: function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('visible');
                        }, index * 100);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            this.artItems.forEach(item => {
                observer.observe(item);
            });
        }
    };

    // =============================================
    // Lightbox (Single Image Only)
    // =============================================
    const lightbox = {
        element: null,
        image: null,

        init: function() {
            this.element = document.getElementById('artLightbox');
            if (!this.element) return;

            this.image = this.element.querySelector('.lightbox-image');
            this.bindEvents();
        },

        bindEvents: function() {
            // Open lightbox on art item click
            document.querySelectorAll('.art-item').forEach(item => {
                item.addEventListener('click', () => {
                    const index = parseInt(item.dataset.index);
                    this.open(index);
                });
            });

            // Close button
            this.element.querySelector('.lightbox-close').addEventListener('click', () => {
                this.close();
            });

            // Overlay click
            this.element.querySelector('.lightbox-overlay').addEventListener('click', () => {
                this.close();
            });

            // Keyboard - Escape to close
            document.addEventListener('keydown', (e) => {
                if (!this.element.classList.contains('active')) return;
                if (e.key === 'Escape') this.close();
            });
        },

        open: function(index) {
            const data = artData[index];
            if (!data) return;

            // Set image
            this.image.style.opacity = '0';
            this.image.src = data.src;
            this.image.alt = data.title;
            this.image.onload = () => {
                this.image.style.opacity = '1';
            };
            // Fallback for cached images
            if (this.image.complete) {
                this.image.style.opacity = '1';
            }

            this.element.classList.add('active');
            document.body.classList.add('modal-open');
        },

        close: function() {
            this.element.classList.remove('active');
            document.body.classList.remove('modal-open');
        }
    };

    // =============================================
    // Initialize
    // =============================================
    document.addEventListener('DOMContentLoaded', function() {
        galleryFilter.init();
        lightbox.init();
    });

})();

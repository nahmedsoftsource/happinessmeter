/**
 * Art Gallery JavaScript
 */

(function() {
    'use strict';

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
            document.querySelectorAll('.art-item').forEach(item => {
                item.addEventListener('click', () => {
                    const index = parseInt(item.dataset.index);
                    this.open(index);
                });
            });

            this.element.querySelector('.lightbox-close').addEventListener('click', () => {
                this.close();
            });

            this.element.querySelector('.lightbox-overlay').addEventListener('click', () => {
                this.close();
            });

            document.addEventListener('keydown', (e) => {
                if (!this.element.classList.contains('active')) return;
                if (e.key === 'Escape') this.close();
            });
        },

        open: function(index) {
            if (typeof artData === 'undefined') return;

            const data = artData[index];
            if (!data) return;

            this.image.style.opacity = '0';
            this.image.src = data.src;
            this.image.alt = data.title;
            this.image.onload = () => {
                this.image.style.opacity = '1';
            };
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

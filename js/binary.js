/**
 * Binary Background Animation
 * Creates falling binary code effect
 */

(function() {
    'use strict';

    const binaryBackground = {
        container: null,
        columns: [],
        columnCount: 0,

        init: function() {
            this.container = document.getElementById('binaryBg');
            if (!this.container) return;

            this.calculateColumns();
            this.createColumns();

            // Recalculate on resize
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    this.container.innerHTML = '';
                    this.columns = [];
                    this.calculateColumns();
                    this.createColumns();
                }, 250);
            });
        },

        calculateColumns: function() {
            const columnWidth = 80; // approximate width per column
            this.columnCount = Math.ceil(window.innerWidth / columnWidth);
        },

        createColumns: function() {
            for (let i = 0; i < this.columnCount; i++) {
                this.createColumn(i);
            }
        },

        createColumn: function(index) {
            const column = document.createElement('div');
            column.className = 'binary-column';

            // Random properties
            const left = (index / this.columnCount) * 100 + Math.random() * 5;
            const duration = 15 + Math.random() * 25; // 15-40 seconds
            const delay = Math.random() * -30; // Random start position
            const opacity = 0.3 + Math.random() * 0.5;

            column.style.left = `${left}%`;
            column.style.animationDuration = `${duration}s`;
            column.style.animationDelay = `${delay}s`;
            column.style.opacity = opacity;

            // Generate binary content
            column.textContent = this.generateBinaryString();

            this.container.appendChild(column);
            this.columns.push(column);

            // Regenerate content periodically
            setInterval(() => {
                column.textContent = this.generateBinaryString();
            }, duration * 1000);
        },

        generateBinaryString: function() {
            const lines = 50 + Math.floor(Math.random() * 30);
            let result = '';

            for (let i = 0; i < lines; i++) {
                // Create varied binary patterns
                const lineLength = 5 + Math.floor(Math.random() * 12);
                let line = '';

                for (let j = 0; j < lineLength; j++) {
                    // Mix of binary and spaces for visual interest
                    if (Math.random() > 0.15) {
                        line += Math.random() > 0.5 ? '1' : '0';
                    } else {
                        line += ' ';
                    }
                }

                // Add some special patterns occasionally
                if (Math.random() > 0.9) {
                    line = '11111110';
                } else if (Math.random() > 0.95) {
                    line = '00001111';
                }

                result += line + '\n';
            }

            return result;
        }
    };

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        binaryBackground.init();
    });

})();

/**
 * Dark Card Slider - Custom JavaScript
 * A responsive, auto-playing card carousel with fan spread layout
 */

class DarkCardSlider {
    constructor(deckElement, options = {}) {
        this.deck = deckElement;
        this.cards = Array.from(this.deck.querySelectorAll('.slider-product-card'));
        this.order = this.cards.map((_, i) => i);

        // Options
        this.options = {
            autoplayDelay: options.autoplayDelay || 3000,
            pauseOnHover: options.pauseOnHover !== false,
            enableKeyboard: options.enableKeyboard !== false,
            enableSwipe: options.enableSwipe !== false,
            ...options
        };

        this.autoplayInterval = null;
        this.isDragging = false;
        this.startX = 0;

        this.init();
    }

    init() {
        this.setDeckHeight();
        this.applyPositions();
        this.bindEvents();

        if (this.options.autoplayDelay > 0) {
            this.startAutoplay();
        }
    }

    setDeckHeight() {
        const maxHeight = Math.max(...this.cards.map(c => c.offsetHeight));
        this.deck.style.height = maxHeight + 'px';
    }

    applyPositions() {
        // Remove all position and animation classes
        this.cards.forEach(card => {
            card.classList.forEach(className => {
                if (/^card-pos-/.test(className) || className === 'card-deal-in') {
                    card.classList.remove(className);
                }
            });
        });

        // Apply new positions
        this.order.forEach((cardIndex, posIndex) => {
            const card = this.cards[cardIndex];
            const pos = Math.min(posIndex, 5); // 6 visible states
            card.classList.add(`card-pos-${pos}`);
            card.setAttribute('aria-label', `Product card ${cardIndex + 1}${pos === 0 ? ' (center)' : ''}`);
        });

        // Bind controls to front card
        this.bindControlsToFront();
    }

    next() {
        const first = this.order.shift();
        this.order.push(first);
        const front = this.cards[this.order[0]];
        front.classList.add('card-deal-in');
        this.applyPositions();
        setTimeout(() => front.classList.remove('card-deal-in'), 420);
    }

    prev() {
        const last = this.order.pop();
        this.order.unshift(last);
        const front = this.cards[this.order[0]];
        front.classList.add('card-deal-in');
        this.applyPositions();
        setTimeout(() => front.classList.remove('card-deal-in'), 420);
    }

    bindControlsToFront() {
        const front = this.cards[this.order[0]];
        if (!front) return;

        const minus = front.querySelector('.slider-qtty-btn.minus');
        const plus = front.querySelector('.slider-qtty-btn.plus');
        const qttyEl = front.querySelector('.slider-qtty-val');
        const priceEl = front.querySelector('.slider-price-val');

        if (!qttyEl || !priceEl) return;

        const unitPrice = Number(priceEl.textContent) || 24;

        const update = (delta) => {
            const current = Number(qttyEl.textContent) || 1;
            const next = Math.max(1, current + delta);
            qttyEl.textContent = String(next);
            priceEl.textContent = (unitPrice * next).toFixed(0);
        };

        // Clone buttons to remove old event listeners
        if (minus) {
            const minusClone = minus.cloneNode(true);
            minus.replaceWith(minusClone);
            minusClone.addEventListener('click', () => update(-1));
        }

        if (plus) {
            const plusClone = plus.cloneNode(true);
            plus.replaceWith(plusClone);
            plusClone.addEventListener('click', () => update(+1));
        }
    }

    startAutoplay() {
        if (this.options.autoplayDelay <= 0) return;

        this.stopAutoplay();
        this.autoplayInterval = setInterval(() => {
            this.next();
        }, this.options.autoplayDelay);
    }

    stopAutoplay() {
        if (this.autoplayInterval) {
            clearInterval(this.autoplayInterval);
            this.autoplayInterval = null;
        }
    }

    resetAutoplay() {
        if (this.options.autoplayDelay > 0) {
            this.stopAutoplay();
            this.startAutoplay();
        }
    }

    bindEvents() {
        // Find navigation buttons
        const carousel = this.deck.closest('.dark-card-carousel');
        if (carousel) {
            const nextBtn = carousel.querySelector('.carousel-nav.next');
            const prevBtn = carousel.querySelector('.carousel-nav.prev');

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    this.next();
                    this.resetAutoplay();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    this.prev();
                    this.resetAutoplay();
                });
            }
        }

        // Keyboard navigation
        if (this.options.enableKeyboard) {
            window.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowRight') {
                    this.next();
                    this.resetAutoplay();
                } else if (e.key === 'ArrowLeft') {
                    this.prev();
                    this.resetAutoplay();
                }
            });
        }

        // Pause on hover
        if (this.options.pauseOnHover) {
            this.deck.addEventListener('mouseenter', () => this.stopAutoplay());
            this.deck.addEventListener('mouseleave', () => this.startAutoplay());
        }

        // Swipe/Touch support
        if (this.options.enableSwipe) {
            this.deck.addEventListener('pointerdown', (e) => {
                this.isDragging = true;
                this.startX = e.clientX;
                this.deck.setPointerCapture(e.pointerId);
            });

            this.deck.addEventListener('pointerup', (e) => {
                if (!this.isDragging) return;
                this.isDragging = false;
                const dx = e.clientX - this.startX;
                const threshold = 40;

                if (dx > threshold) {
                    this.prev();
                    this.resetAutoplay();
                } else if (dx < -threshold) {
                    this.next();
                    this.resetAutoplay();
                }
            });

            this.deck.addEventListener('pointercancel', () => {
                this.isDragging = false;
            });
        }

        // Resize handler
        window.addEventListener('resize', () => this.setDeckHeight());
    }

    destroy() {
        this.stopAutoplay();
        // Additional cleanup can be added here
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const decks = document.querySelectorAll('.carousel-deck');
    decks.forEach(deck => {
        // Check if already initialized
        if (!deck.dataset.sliderInitialized) {
            new DarkCardSlider(deck, {
                autoplayDelay: 3000,
                pauseOnHover: true,
                enableKeyboard: true,
                enableSwipe: true
            });
            deck.dataset.sliderInitialized = 'true';
        }
    });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DarkCardSlider;
}

// Update clock in statusbar
function updateStatusbarClock() {
    const hourEl = document.getElementById('slider-statusbar-time');
    if (!hourEl) return;

    const format = () => {
        const d = new Date();
        const h = String(d.getHours()).padStart(2, '0');
        const m = String(d.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    };

    hourEl.textContent = format();
    setInterval(() => {
        hourEl.textContent = format();
    }, 30000); // Update every 30 seconds
}

// Initialize clock on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateStatusbarClock);
} else {
    updateStatusbarClock();
}

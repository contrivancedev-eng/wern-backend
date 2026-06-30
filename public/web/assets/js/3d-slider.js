/**
 * 3D Card Slider
 * Interactive card carousel with 3D transforms
 */

class CardSlider3D {
    constructor(containerSelector) {
        this.container = document.querySelector(containerSelector);
        if (!this.container) {
            console.error('Slider container not found');
            return;
        }

        this.cardsWrapper = this.container.querySelector('.cards-wrapper');
        this.cards = Array.from(this.container.querySelectorAll('.slider-card'));
        this.prevBtn = this.container.querySelector('.nav-btn.prev');
        this.nextBtn = this.container.querySelector('.nav-btn.next');

        this.currentIndex = 0;
        this.totalCards = this.cards.length;
        this.isAnimating = false;

        this.init();
    }

    init() {
        if (this.totalCards === 0) {
            console.error('No cards found in slider');
            return;
        }

        // Set initial positions
        this.updateCardPositions();

        // Add event listeners
        this.addEventListeners();

        // Auto-play (optional)
        // this.startAutoPlay();
    }

    addEventListeners() {
        // Navigation buttons
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.prev());
        }

        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.next());
        }

        // Card click navigation
        this.cards.forEach((card, index) => {
            card.addEventListener('click', () => {
                const currentPosition = this.getCardPosition(index);
                if (currentPosition === 1) {
                    this.next();
                } else if (currentPosition === -1) {
                    this.prev();
                }
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                this.prev();
            } else if (e.key === 'ArrowRight') {
                this.next();
            }
        });

        // Touch/swipe support
        this.addTouchSupport();
    }

    addTouchSupport() {
        let startX = 0;
        let currentX = 0;
        let isDragging = false;

        this.cardsWrapper.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isDragging = true;
        });

        this.cardsWrapper.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
        });

        this.cardsWrapper.addEventListener('touchend', () => {
            if (!isDragging) return;
            isDragging = false;

            const diff = startX - currentX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    this.next();
                } else {
                    this.prev();
                }
            }
        });

        // Mouse drag support for desktop
        let mouseDown = false;
        let startMouseX = 0;

        this.cardsWrapper.addEventListener('mousedown', (e) => {
            mouseDown = true;
            startMouseX = e.clientX;
            this.cardsWrapper.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', (e) => {
            if (!mouseDown) return;
            currentX = e.clientX;
        });

        document.addEventListener('mouseup', () => {
            if (!mouseDown) return;
            mouseDown = false;
            this.cardsWrapper.style.cursor = 'grab';

            const diff = startMouseX - currentX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    this.next();
                } else {
                    this.prev();
                }
            }
        });
    }

    next() {
        if (this.isAnimating) return;

        this.isAnimating = true;
        this.currentIndex = (this.currentIndex + 1) % this.totalCards;
        this.updateCardPositions();

        setTimeout(() => {
            this.isAnimating = false;
        }, 600);
    }

    prev() {
        if (this.isAnimating) return;

        this.isAnimating = true;
        this.currentIndex = (this.currentIndex - 1 + this.totalCards) % this.totalCards;
        this.updateCardPositions();

        setTimeout(() => {
            this.isAnimating = false;
        }, 600);
    }

    goToSlide(index) {
        if (this.isAnimating || index === this.currentIndex) return;

        this.isAnimating = true;
        this.currentIndex = index;
        this.updateCardPositions();

        setTimeout(() => {
            this.isAnimating = false;
        }, 600);
    }

    getCardPosition(cardIndex) {
        let position = cardIndex - this.currentIndex;

        // Wrap around logic
        if (position > this.totalCards / 2) {
            position -= this.totalCards;
        } else if (position < -this.totalCards / 2) {
            position += this.totalCards;
        }

        return position;
    }

    updateCardPositions() {
        this.cards.forEach((card, index) => {
            const position = this.getCardPosition(index);

            // Update data attribute for CSS styling
            card.setAttribute('data-position', position);

            // Add/remove active class
            if (position === 0) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
    }

    startAutoPlay(interval = 5000) {
        this.autoPlayInterval = setInterval(() => {
            this.next();
        }, interval);
    }

    stopAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
        }
    }

    destroy() {
        this.stopAutoPlay();
        // Remove event listeners if needed
    }
}

// Initialize slider when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const slider = new CardSlider3D('.card-slider-container');

    // Optional: Pause autoplay on hover
    const container = document.querySelector('.card-slider-container');
    if (container) {
        container.addEventListener('mouseenter', () => {
            if (slider.autoPlayInterval) {
                slider.stopAutoPlay();
            }
        });

        container.addEventListener('mouseleave', () => {
            // Restart autoplay if desired
            // slider.startAutoPlay();
        });
    }
});

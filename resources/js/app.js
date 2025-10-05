import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Custom Banner Carousel (works without Bootstrap)
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('homeHeroCarousel');
    if (!carousel) return;

    const items = carousel.querySelectorAll('.carousel-item');
    const indicators = carousel.querySelectorAll('.carousel-indicators button');
    const prevBtn = carousel.querySelector('.carousel-control-prev');
    const nextBtn = carousel.querySelector('.carousel-control-next');
    
    if (items.length === 0) return;

    let currentIndex = 0;
    let autoPlayInterval = null;
    let isHovering = false;

    // Show specific slide
    function showSlide(index) {
        items.forEach(item => item.classList.remove('active'));
        indicators.forEach(ind => ind.classList.remove('active'));
        
        currentIndex = (index + items.length) % items.length;
        items[currentIndex].classList.add('active');
        if (indicators[currentIndex]) {
            indicators[currentIndex].classList.add('active');
        }
    }

    // Next slide
    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    // Previous slide
    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    // Auto-play
    function startAutoPlay() {
        autoPlayInterval = setInterval(() => {
            if (!isHovering) nextSlide();
        }, 5000);
    }

    function stopAutoPlay() {
        if (autoPlayInterval) {
            clearInterval(autoPlayInterval);
            autoPlayInterval = null;
        }
    }

    // Event listeners for controls
    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            prevSlide();
            stopAutoPlay();
            startAutoPlay();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            nextSlide();
            stopAutoPlay();
            startAutoPlay();
        });
    }

    // Indicator clicks
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', (e) => {
            e.preventDefault();
            showSlide(index);
            stopAutoPlay();
            startAutoPlay();
        });
    });

    // Hover pause
    carousel.addEventListener('mouseenter', () => {
        isHovering = true;
    });

    carousel.addEventListener('mouseleave', () => {
        isHovering = false;
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') prevSlide();
        if (e.key === 'ArrowRight') nextSlide();
    });

    // Mouse drag support with better detection (supports dragging on links)
    let isDown = false;
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    let didDrag = false;

    carousel.addEventListener('mousedown', (e) => {
        // Allow drag on everything, including anchors; we'll suppress click later if it was a drag
        isDown = true;
        startX = e.pageX - carousel.offsetLeft;
        carousel.style.cursor = 'grabbing';
        isDragging = false;
        didDrag = false;
    });

    carousel.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        currentX = e.pageX - carousel.offsetLeft;
        const walk = currentX - startX;
        
        if (Math.abs(walk) > 5) {
            isDragging = true;
            didDrag = true;
        }
    });

    carousel.addEventListener('mouseup', (e) => {
        if (!isDown) return;
        
        const deltaX = currentX - startX;
        isDown = false;
        carousel.style.cursor = 'grab';
        
        // Only change slide if dragged enough distance
        if (isDragging && Math.abs(deltaX) > 50) {
            if (deltaX < 0) {
                nextSlide();
            } else {
                prevSlide();
            }
            stopAutoPlay();
            startAutoPlay();
        }
        
        isDragging = false;
    });

    carousel.addEventListener('mouseleave', () => {
        if (isDown) {
            isDown = false;
            carousel.style.cursor = 'grab';
        }
    });

    // Prevent text selection while dragging
    carousel.addEventListener('dragstart', (e) => {
        e.preventDefault();
    });

    // Suppress anchor navigation if a drag happened
    carousel.addEventListener('click', (e) => {
        if (!didDrag) return;
        const anchor = e.target.closest('a');
        if (anchor) {
            e.preventDefault();
            e.stopPropagation();
        }
        didDrag = false;
    }, true);

    // Touch support for mobile
    let touchStartX = 0;
    let touchEndX = 0;

    carousel.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });

    carousel.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });

    function handleSwipe() {
        if (touchEndX < touchStartX - 50) {
            nextSlide();
            stopAutoPlay();
            startAutoPlay();
        }
        if (touchEndX > touchStartX + 50) {
            prevSlide();
            stopAutoPlay();
            startAutoPlay();
        }
    }

    // Initialize
    showSlide(0);
    startAutoPlay();
    carousel.style.cursor = 'grab';
});

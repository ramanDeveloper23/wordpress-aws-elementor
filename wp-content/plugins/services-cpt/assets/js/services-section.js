/**
 * Services Section Carousel JavaScript
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		const $servicesSection = $('#services-section');
		
		if (!$servicesSection.length) {
			return;
		}

		const $carousel = $servicesSection.find('.services-carousel');
		const $slides = $carousel.find('.service-slide');
		const $prevButton = $servicesSection.find('.services-nav-prev');
		const $nextButton = $servicesSection.find('.services-nav-next');
		
		let currentIndex = 0;
		const totalSlides = $slides.length;

		// Initialize carousel
		function initCarousel() {
			if (totalSlides <= 1) {
				$prevButton.hide();
				$nextButton.hide();
				return;
			}

			// Show first slide
			showSlide(0);
			updateNavigationButtons();
		}

		// Show specific slide
		function showSlide(index) {
			// Ensure index is within bounds
			if (index < 0) {
				index = totalSlides - 1;
			} else if (index >= totalSlides) {
				index = 0;
			}

			currentIndex = index;

			// Remove active class from all slides
			$slides.removeClass('active');

			// Add active class to current slide
			$slides.eq(currentIndex).addClass('active');

			// Update carousel data attribute
			$carousel.attr('data-current', currentIndex);

			// Update navigation buttons
			updateNavigationButtons();
		}

		// Update navigation buttons state
		function updateNavigationButtons() {
			if (totalSlides <= 1) {
				$prevButton.prop('disabled', true);
				$nextButton.prop('disabled', true);
				return;
			}

			// Enable both buttons (circular navigation)
			$prevButton.prop('disabled', false);
			$nextButton.prop('disabled', false);
		}

		// Go to previous slide
		function prevSlide() {
			showSlide(currentIndex - 1);
		}

		// Go to next slide
		function nextSlide() {
			showSlide(currentIndex + 1);
		}

		// Event handlers
		$prevButton.on('click', function(e) {
			e.preventDefault();
			prevSlide();
		});

		$nextButton.on('click', function(e) {
			e.preventDefault();
			nextSlide();
		});

		// Keyboard navigation
		$(document).on('keydown', function(e) {
			// Only handle if services section is visible
			if (!$servicesSection.is(':visible')) {
				return;
			}

			// Check if user is interacting with an input/textarea
			if ($(e.target).is('input, textarea')) {
				return;
			}

			switch(e.key) {
				case 'ArrowLeft':
					e.preventDefault();
					prevSlide();
					break;
				case 'ArrowRight':
					e.preventDefault();
					nextSlide();
					break;
			}
		});

		// Touch/swipe support for mobile
		let touchStartX = 0;
		let touchEndX = 0;

		$carousel.on('touchstart', function(e) {
			touchStartX = e.originalEvent.touches[0].clientX;
		});

		$carousel.on('touchend', function(e) {
			touchEndX = e.originalEvent.changedTouches[0].clientX;
			handleSwipe();
		});

		function handleSwipe() {
			const swipeThreshold = 50;
			const diff = touchStartX - touchEndX;

			if (Math.abs(diff) > swipeThreshold) {
				if (diff > 0) {
					// Swipe left - next slide
					nextSlide();
				} else {
					// Swipe right - previous slide
					prevSlide();
				}
			}
		}

		// Auto-play option (optional, can be enabled via data attribute)
		const autoPlay = $carousel.data('autoplay');
		let autoPlayInterval = null;

		if (autoPlay && totalSlides > 1) {
			const interval = $carousel.data('interval') || 5000; // Default 5 seconds

			function startAutoPlay() {
				autoPlayInterval = setInterval(function() {
					nextSlide();
				}, interval);
			}

			function stopAutoPlay() {
				if (autoPlayInterval) {
					clearInterval(autoPlayInterval);
					autoPlayInterval = null;
				}
			}

			// Start auto-play
			startAutoPlay();

			// Pause on hover
			$carousel.on('mouseenter', stopAutoPlay);
			$carousel.on('mouseleave', startAutoPlay);

			// Pause on focus
			$carousel.on('focusin', stopAutoPlay);
			$carousel.on('focusout', startAutoPlay);
		}

		// Initialize
		initCarousel();
	});

})(jQuery);

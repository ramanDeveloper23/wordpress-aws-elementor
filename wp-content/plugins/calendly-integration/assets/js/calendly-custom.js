/**
 * Calendly Custom Calendar Component
 * Builds custom calendar grid matching the design
 */

(function($) {
	'use strict';
	
	// Check if jQuery is available
	if (typeof jQuery === 'undefined') {
		console.error('Calendly Integration: jQuery is required but not loaded.');
		return;
	}
	
	// Use jQuery directly (WordPress already loads it in noConflict mode)
	var $ = jQuery;
	
	const CalendlyCustomCalendar = {
		selectedDate: null,
		calendlyUrl: null,
		eventSlug: null,
		
		init: function() {
			// Check if calendlyAjax is defined
			if (typeof calendlyAjax === 'undefined') {
				console.error('Calendly Integration: calendlyAjax is not defined.');
				return;
			}
			
			this.bindEvents();
			this.loadCalendar();
		},
		
		bindEvents: function() {
			var self = this;
			
			// Use event delegation for dynamically created elements
			$(document).on('click', '.calendly-date-cell', function(e) {
				e.preventDefault();
				e.stopPropagation();
				self.handleDateClick.call(self, e);
			});
			
			$(document).on('click', '.calendly-time-slot', function(e) {
				e.preventDefault();
				e.stopPropagation();
				self.handleTimeSlotClick.call(self, e);
			});
			
			// Bind booking button click - use both delegation and direct binding
			$(document).on('click', '#calendly-open-booking', function(e) {
				e.preventDefault();
				e.stopPropagation();
				self.openBooking.call(self);
			});
			
			// Also bind directly if button exists
			setTimeout(function() {
				var $btn = $('#calendly-open-booking');
				if ($btn.length > 0) {
					$btn.off('click').on('click', function(e) {
						e.preventDefault();
						e.stopPropagation();
						self.openBooking.call(self);
					});
				}
			}, 500);
		},
		
		loadCalendar: function() {
			var $wrapper = $('.calendly-booking-wrapper');
			if ($wrapper.length === 0) return;
			
			this.calendlyUrl = $wrapper.data('calendly-url');
			this.eventSlug = $wrapper.data('event-slug');
			
			if (!this.calendlyUrl) {
				console.error('Calendly Integration: Calendly URL is not set.');
				return;
			}
			
			// If event slug is empty, try to extract it
			if (!this.eventSlug) {
				var urlParts = this.calendlyUrl.split('/');
				if (urlParts.length > 0) {
					this.eventSlug = urlParts[urlParts.length - 1] || '';
				}
			}
			
			this.fetchAvailability();
		},
		
		fetchAvailability: function() {
			var self = this;
			var $grid = $('#calendly-calendar-grid');
			
			// Check if calendlyAjax is available
			if (typeof calendlyAjax === 'undefined') {
				$grid.html('<div class="calendly-error">Configuration error. Please refresh the page.</div>');
				return;
			}
			
			$.ajax({
				url: calendlyAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'calendly_get_availability',
					nonce: calendlyAjax.nonce,
					calendly_url: this.calendlyUrl,
					event_slug: this.eventSlug || ''
				},
				success: function(response) {
					if (response && response.success) {
						self.renderCalendar(response.data.dates, response.data.available_dates);
					} else {
						var errorMsg = response && response.data && response.data.message 
							? response.data.message 
							: 'Error loading calendar. Please try again.';
						$grid.html('<div class="calendly-error">' + errorMsg + '</div>');
					}
				},
				error: function(xhr, status, error) {
					console.error('Calendly AJAX Error:', error);
					$grid.html('<div class="calendly-error">Error loading calendar. Please try again.</div>');
				}
			});
		},
		
		renderCalendar: function(dates, availableDates) {
			var $grid = $('#calendly-calendar-grid');
			$grid.empty();
			
			// console.log('Rendering calendar with dates:', dates.length);
			
			// Create header row with day names
			var html = '<div class="calendly-calendar-header">';
			var dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
			for (var i = 0; i < dayNames.length; i++) {
				html += '<div class="calendly-day-header">' + dayNames[i] + '</div>';
			}
			html += '</div>';
			
			// Create date cells
			html += '<div class="calendly-calendar-dates">';
			for (var j = 0; j < dates.length; j++) {
				var dateInfo = dates[j];
				var isAvailable = availableDates.indexOf(dateInfo.date) !== -1;
				var dateClass = isAvailable ? 'calendly-date-available' : 'calendly-date-unavailable';
				var today = new Date();
				today.setHours(0, 0, 0, 0);
				var cellDate = new Date(dateInfo.date + 'T00:00:00');
				cellDate.setHours(0, 0, 0, 0);
				var isToday = today.getTime() === cellDate.getTime();
				
				// Debug log for first few dates
				// if (j < 3) {
				// 	console.log('Date:', dateInfo.date, 'Available:', isAvailable, 'Class:', dateClass);
				// }
				
				html += '<div class="calendly-date-cell ' + dateClass + (isToday ? ' calendly-date-today' : '') + '" ' +
					'data-date="' + dateInfo.date + '" ' +
					'data-day-number="' + dateInfo.day_number + '">';
				html += '<span class="calendly-date-number">' + dateInfo.day_number + '</span>';
				if (isAvailable) {
					html += '<span class="calendly-available-indicator"></span>';
				}
				html += '</div>';
			}
			html += '</div>';
			
			$grid.html(html);
			
			// Log summary
			var availableCount = availableDates.length;
			console.log('Calendar rendered. Available dates: ' + availableCount + ' out of ' + dates.length);
		},
		
		handleDateClick: function(e) {
			var $cell = $(e.currentTarget);
			var date = $cell.data('date');
			var isAvailable = $cell.hasClass('calendly-date-available');
			
			if (!isAvailable) {
				// Show user-friendly message
				alert('This date is not available. Please select an available date (highlighted in brown).');
				return;
			}
			
			// Remove previous selection
			$('.calendly-date-cell').removeClass('calendly-date-selected');
			$cell.addClass('calendly-date-selected');
			
			this.selectedDate = date;

			// Show booking button immediately when date is selected
			var $bookingBtn = $('#calendly-open-booking');
			if ($bookingBtn.length > 0) {
				$bookingBtn.show();
			}
			
			this.fetchTimeSlots(date);
		},
		
		fetchTimeSlots: function(date) {
			var self = this;
			var $container = $('#calendly-time-slots');
			var $grid = $container.find('.calendly-time-slots-grid');
			var $title = $container.find('.calendly-selected-date-title');
			
			// Format date for display
			var dateObj = new Date(date + 'T00:00:00'); // Add time to avoid timezone issues
			var formattedDate = dateObj.toLocaleDateString('en-US', { 
				weekday: 'long', 
				year: 'numeric', 
				month: 'long', 
				day: 'numeric' 
			});
			$title.text('Available times for ' + formattedDate);
			
			$container.show();
			$grid.html('<div class="calendly-loading">Loading time slots...</div>');
			
			// Check if calendlyAjax is available
			if (typeof calendlyAjax === 'undefined') {
				$grid.html('<div class="calendly-error">Configuration error.</div>');
				return;
			}
			
			$.ajax({
				url: calendlyAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'calendly_get_time_slots',
					nonce: calendlyAjax.nonce,
					calendly_url: this.calendlyUrl,
					event_slug: this.eventSlug || '',
					date: date
				},
				success: function(response) {
					if (response && response.success && response.data && response.data.time_slots && response.data.time_slots.length > 0) {
						self.renderTimeSlots(response.data.time_slots);
						// Button already shown when date was selected, keep it visible
						$('#calendly-open-booking').show();
					} else {
						$grid.html('<div class="calendly-no-slots">No available time slots for this date. Click "Open Booking" to see all available times.</div>');
						// Still show booking button so user can check Calendly directly
						$('#calendly-open-booking').show();
					}
				},
				error: function(xhr, status, error) {
					console.error('Calendly Time Slots AJAX Error:', error);
					$grid.html('<div class="calendly-error">Error loading time slots.</div>');
					$('#calendly-open-booking').hide();
				}
			});
		},
		
		renderTimeSlots: function(timeSlots) {
			var $grid = $('#calendly-time-slots .calendly-time-slots-grid');
			var html = '';
			
			for (var i = 0; i < timeSlots.length; i++) {
				html += '<div class="calendly-time-slot" data-time="' + timeSlots[i] + '">' + timeSlots[i] + '</div>';
			}
			
			$grid.html(html);
		},
		
		handleTimeSlotClick: function(e) {
			var $slot = $(e.currentTarget);
			$('.calendly-time-slot').removeClass('calendly-time-selected');
			$slot.addClass('calendly-time-selected');
		},
		
		openBooking: function() {
			
			if (!this.calendlyUrl) {
				alert('Calendly URL is not configured. Please contact the administrator.');
				return;
			}
			
			var bookingUrl = this.calendlyUrl;
			
			// Add selected date as URL parameter if available (for user reference)
			// Note: Calendly doesn't directly support date pre-selection via URL,
			// but we can add it as a parameter for tracking
			if (this.selectedDate) {
				var separator = bookingUrl.indexOf('?') !== -1 ? '&' : '?';
				bookingUrl = bookingUrl + separator + 'date=' + encodeURIComponent(this.selectedDate);
			}
			
			// Open Calendly in a new tab/window to avoid SameSite cookie issues
			// This is more reliable than popup widget and avoids cookie warnings
			try {
				
				// Use window.open with _blank to open in new tab
				// This avoids SameSite cookie restrictions
				// var newWindow = window.open(bookingUrl, '_blank', 'noopener,noreferrer');
				
				if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
					// Popup blocked or failed, redirect current window
					window.location.href = bookingUrl;
				} else {
					// Focus the new window
					newWindow.focus();
				}
			} catch (e) {
				console.error('Error opening booking:', e);
				// Last resort: redirect current window
				window.location.href = bookingUrl;
			}
		}
	};
	
	// Initialize when DOM is ready
	jQuery(document).ready(function($) {
		// Wait a bit to ensure all scripts are loaded
		setTimeout(function() {
			try {
				CalendlyCustomCalendar.init();
			} catch (e) {
				console.error('Calendly Integration initialization error:', e);
			}
		}, 100);
	});
	
	// Also initialize on window load (for Elementor compatibility)
	jQuery(window).on('load', function() {
		setTimeout(function() {
			try {
				// Re-initialize if calendar wasn't loaded
				var $grid = jQuery('#calendly-calendar-grid');
				if ($grid.length > 0 && $grid.html().indexOf('Loading') !== -1) {
					CalendlyCustomCalendar.init();
				}
			} catch (e) {
				console.error('Calendly Integration window load error:', e);
			}
		}, 500);
	});
	
})(jQuery);

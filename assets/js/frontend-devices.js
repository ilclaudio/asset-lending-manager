/**
 * Frontend JavaScript for ALM devices
 *
 * Loaded only on device archive, single, and pages with device shortcodes.
 *
 * @package AssetLendingManager
 */

(function($) {
	'use strict';

	/**
	 * Initialize when DOM is ready.
	 */
	$(document).ready(function() {
		ALM_Frontend.init();
	});

	/**
	 * ALM Frontend object.
	 */
	var ALM_Frontend = {

		/**
		 * Initialize frontend functionality.
		 */
		init: function() {
			console.log('ALM Frontend initialized');
			
			// Access to data passed from PHP via wp_localize_script.
			if (typeof almFrontend !== 'undefined') {
				console.log('AJAX URL:', almFrontend.ajaxUrl);
				console.log('Nonce:', almFrontend.nonce);
			}

			// Initialize components.
			this.initImageLightbox();
			this.initDeviceFilters();
			this.initDeviceSearch();
		},

		/**
		 * Initialize image lightbox for device thumbnails.
		 * 
		 * Opens device images in a simple lightbox overlay.
		 */
		initImageLightbox: function() {
			$('.alm-device-thumbnail img').on('click', function(e) {
				// Only in single device view.
				if (!$('.alm-device-single').length) {
					return;
				}

				e.preventDefault();
				var imgSrc = $(this).attr('src');
				var imgAlt = $(this).attr('alt');

				// Create lightbox overlay.
				var lightbox = $('<div class="alm-lightbox"></div>');
				var img = $('<img>').attr({
					'src': imgSrc,
					'alt': imgAlt
				});

				lightbox.append(img);
				$('body').append(lightbox);

				// Show lightbox.
				setTimeout(function() {
					lightbox.addClass('active');
				}, 10);

				// Close on click.
				lightbox.on('click', function() {
					lightbox.removeClass('active');
					setTimeout(function() {
						lightbox.remove();
					}, 300);
				});
			});
		},

		/**
		 * Initialize device filters (if present).
		 * 
		 * Example: filter by type, state, etc.
		 */
		initDeviceFilters: function() {
			var $filters = $('.alm-device-filters');
			if (!$filters.length) {
				return;
			}

			$filters.find('select, input[type="checkbox"]').on('change', function() {
				console.log('Filter changed:', $(this).val());
				// Here you would implement AJAX filtering.
				// For now, just a placeholder.
			});
		},

		/**
		 * Initialize device search (if present).
		 * 
		 * Example: live search in device list.
		 */
		initDeviceSearch: function() {
			var $searchInput = $('.alm-device-search-input');
			if (!$searchInput.length) {
				return;
			}

			// Debounce search to avoid too many requests.
			var searchTimeout;
			$searchInput.on('input', function() {
				clearTimeout(searchTimeout);
				var query = $(this).val();
				
				searchTimeout = setTimeout(function() {
					if (query.length >= 3) {
						console.log('Searching for:', query);
						// Here you would implement AJAX search.
						// For now, just a placeholder.
					}
				}, 500);
			});
		}

	};

})(jQuery);

/**
 * Add basic lightbox styles dynamically.
 */
(function() {
	var style = document.createElement('style');
	style.textContent = `
		.alm-lightbox {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.9);
			display: flex;
			align-items: center;
			justify-content: center;
			z-index: 9999;
			opacity: 0;
			transition: opacity 0.3s ease;
			cursor: pointer;
		}
		.alm-lightbox.active {
			opacity: 1;
		}
		.alm-lightbox img {
			max-width: 90%;
			max-height: 90%;
			box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
		}
	`;
	document.head.appendChild(style);
})();
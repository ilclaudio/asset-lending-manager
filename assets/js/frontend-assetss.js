/**
 * Frontend JavaScript for ALM assets
 *
 * Loaded only on asset archive, single, and pages with asset shortcodes.
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
			console.log('*** ALM Frontend initialized');
			
			// Access to data passed from PHP via wp_localize_script.
			if (typeof almFrontend !== 'undefined') {
				console.log('*** AJAX URL:', almFrontend.ajaxUrl);
				console.log('*** Nonce:', almFrontend.nonce);
			}

			// Initialize components.
			this.initImageLightbox();
			this.initAssetFilters();
			this.initAssetSearch();
		},

		/**
		 * Initialize image lightbox for asset thumbnails.
		 * 
		 * Opens asset images in a simple lightbox overlay.
		 */
		initImageLightbox: function() {
			$('.alm-asset-thumbnail img').on('click', function(e) {
				// Only in single asset view.
				if (!$('.alm-asset-single').length) {
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
	 * Initialize asset filters (if present).
	 * 
	 * Handles visual feedback for active filters.
	 * Does NOT interfere with autocomplete functionality.
	 */
	initAssetFilters: function() {
		var $filters = $('.alm-filters-collapsible');
		if (!$filters.length) {
			return;
		}
		// Auto-open filters if any filter is active
		var hasActiveFilters = false;
		$filters.find('select').each(function() {
			if ($(this).val() !== '') {
				hasActiveFilters = true;
				return false; // break loop
			}
		});

		if (hasActiveFilters) {
			$filters.attr('open', 'open');
		}
		// Visual feedback on filter change
		$filters.find('select').on('change', function() {
			var $select = $(this);
			if ($select.val() !== '') {
				$select.addClass('alm-filter-active');
			} else {
				$select.removeClass('alm-filter-active');
			}
		});
		// Trigger change on page load to set initial state
		$filters.find('select').trigger('change');
		console.log('*** Asset filters initialized');
	},

		/**
		 * Initialize asset search (if present).
		 * 
		 * Example: live search in asset list.
		 */
		initAssetSearch: function() {
			var $searchInput = $('.alm-asset-search-input');
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
						console.log('*** Searching for:', query);
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
		.alm-filter-active {
			border-color: #0073aa !important;
			background-color: #f0f8ff !important;
		}
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
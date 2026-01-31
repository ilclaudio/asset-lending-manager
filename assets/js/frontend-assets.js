/**
 * Frontend JavaScript for ALM assets
 *
 * Loaded only on asset archive, single, and pages with asset shortcodes.
 *
 * @package AssetLendingManager
 */

(function() {
	'use strict';

	/**
	 * Initialize when DOM is ready.
	 */
	document.addEventListener('DOMContentLoaded', function() {
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
			if (typeof window.almFrontend !== 'undefined') {
				console.log('*** AJAX URL:', window.almFrontend.ajaxUrl);
				console.log('*** Nonce:', window.almFrontend.nonce);
			}

			// Initialize components.
			this.initImageLightbox();
			this.initAssetFilters();
			this.initAssetSearch();
			this.initLoanRequestForm(); // NUOVO: Aggiungiamo il form di richiesta prestito
		},

		/**
		 * Initialize image lightbox for asset thumbnails.
		 *
		 * Opens asset images in a simple lightbox overlay.
		 */
		initImageLightbox: function() {
			var images = document.querySelectorAll('.alm-asset-thumbnail img');
			if (!images.length) {
				return;
			}

			images.forEach(function(imgEl) {
				imgEl.addEventListener('click', function(e) {
					// Only in single asset view.
					if (!document.querySelector('.alm-asset-single')) {
						return;
					}

					e.preventDefault();

					var imgSrc = imgEl.getAttribute('src');
					var imgAlt = imgEl.getAttribute('alt');

					// Create lightbox overlay.
					var lightbox = document.createElement('div');
					lightbox.className = 'alm-lightbox';

					var img = document.createElement('img');
					img.setAttribute('src', imgSrc || '');
					img.setAttribute('alt', imgAlt || '');

					lightbox.appendChild(img);
					document.body.appendChild(lightbox);

					// Show lightbox.
					setTimeout(function() {
						lightbox.classList.add('active');
					}, 10);

					// Close on click.
					lightbox.addEventListener('click', function() {
						lightbox.classList.remove('active');
						setTimeout(function() {
							if (lightbox && lightbox.parentNode) {
								lightbox.parentNode.removeChild(lightbox);
							}
						}, 300);
					});
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
			var filters = document.querySelectorAll('.alm-filters-collapsible');
			if (!filters.length) {
				return;
			}

			// In jQuery code it targets all .alm-filters-collapsible at once.
			// We'll apply the same logic to each instance (covers single/multiple blocks).
			filters.forEach(function(filterEl) {
				var selects = filterEl.querySelectorAll('select');

				// Auto-open filters if any filter is active
				var hasActiveFilters = false;
				selects.forEach(function(selectEl) {
					if (selectEl.value !== '') {
						hasActiveFilters = true;
					}
				});

				if (hasActiveFilters) {
					filterEl.setAttribute('open', 'open');
				}

				// Visual feedback on filter change
				selects.forEach(function(selectEl) {
					selectEl.addEventListener('change', function() {
						if (selectEl.value !== '') {
							selectEl.classList.add('alm-filter-active');
						} else {
							selectEl.classList.remove('alm-filter-active');
						}
					});

					// Trigger change on page load to set initial state
					// Equivalent to jQuery's .trigger('change')
					selectEl.dispatchEvent(new Event('change', { bubbles: true }));
				});
			});

			console.log('*** Asset filters initialized');
		},

		/**
		 * Initialize asset search (if present).
		 *
		 * Example: live search in asset list.
		 */
		initAssetSearch: function() {
			var searchInput = document.querySelector('.alm-asset-search-input');
			if (!searchInput) {
				return;
			}

			// Debounce search to avoid too many requests.
			var searchTimeout;
			searchInput.addEventListener('input', function() {
				if (searchTimeout) {
					clearTimeout(searchTimeout);
				}

				var query = searchInput.value;

				searchTimeout = setTimeout(function() {
					if (query.length >= 3) {
						console.log('*** Searching for:', query);
						// Here you would implement AJAX search.
						// For now, just a placeholder.
					}
				}, 500);
			});
		},

		/**
		 * Initialize loan request form.
		 * 
		 * NUOVO: Gestisce l'invio della richiesta di prestito via AJAX.
		 */
		initLoanRequestForm: function() {
			var form = document.getElementById('alm-loan-request-form');
			
			if (!form) {
				return;
			}

			console.log('*** Loan request form found, initializing...');

			form.addEventListener('submit', function(e) {
				e.preventDefault();

				var submitBtn = form.querySelector('button[type="submit"]');
				var responseDiv = document.getElementById('alm-loan-request-response');
				var messageField = document.getElementById('alm-request-message');

				// Get asset ID from page context
				var assetId = ALM_Frontend.getAssetIdFromPage();
				if (!assetId) {
					ALM_Frontend.showResponse(responseDiv, 'error', 'Asset ID not found.');
					return;
				}

				// Validate message
				if (!messageField.value.trim()) {
					ALM_Frontend.showResponse(responseDiv, 'error', 'Please enter a message.');
					return;
				}

				// Check if almFrontend is available
				if (typeof window.almFrontend === 'undefined' || !window.almFrontend.loanRequestNonce) {
					ALM_Frontend.showResponse(responseDiv, 'error', 'Security token not found. Please reload the page.');
					console.error('almFrontend.loanRequestNonce is undefined');
					return;
				}

				// Disable submit button
				var originalBtnText = submitBtn.textContent;
				submitBtn.disabled = true;
				submitBtn.textContent = 'Sending...';
				responseDiv.style.display = 'none';

				// Prepare form data
				var formData = new FormData();
				formData.append('action', 'alm_submit_loan_request');
				formData.append('nonce', window.almFrontend.loanRequestNonce);
				formData.append('asset_id', assetId);
				formData.append('message', messageField.value.trim());

				console.log('*** Sending loan request for asset:', assetId);

				// Send AJAX request
				fetch(window.almFrontend.ajaxUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				})
				.then(function(response) {
					return response.json();
				})
				.then(function(data) {
					if (data.success) {
						ALM_Frontend.showResponse(responseDiv, 'success', data.data.message);
						messageField.value = ''; // Clear message
						
						console.log('*** Loan request sent successfully');
						
						// Close the form after 2 seconds
						setTimeout(function() {
							var section = document.getElementById('alm-loan-request-section');
							if (section) {
								section.removeAttribute('open');
							}
						}, 2000);
					} else {
						var errorMsg = data.data && data.data.message ? data.data.message : 'Request failed. Please try again.';
						ALM_Frontend.showResponse(responseDiv, 'error', errorMsg);
						console.error('*** Loan request failed:', errorMsg);
					}
				})
				.catch(function(error) {
					ALM_Frontend.showResponse(responseDiv, 'error', 'Request failed. Please try again.');
					console.error('*** AJAX error:', error);
				})
				.finally(function() {
					// Re-enable submit button
					submitBtn.disabled = false;
					submitBtn.textContent = originalBtnText;
				});
			});

			console.log('*** Loan request form initialized');
		},

		/**
		 * Get asset ID from the current page.
		 * 
		 * @return {number|null} Asset ID or null if not found
		 */
		getAssetIdFromPage: function() {
			// Try to get from article data attribute
			var article = document.querySelector('.alm-asset-detail');
			if (article && article.dataset.assetId) {
				return parseInt(article.dataset.assetId, 10);
			}

			// Fallback: try to get from body class (WordPress adds postid-XXX)
			var bodyClasses = document.body.className;
			var match = bodyClasses.match(/postid-(\d+)/);
			
			if (match && match[1]) {
				return parseInt(match[1], 10);
			}

			return null;
		},

		/**
		 * Show response message.
		 * 
		 * @param {HTMLElement} el DOM element
		 * @param {string} type 'success' or 'error'
		 * @param {string} message Message text
		 */
		showResponse: function(el, type, message) {
			if (!el) {
				return;
			}
			
			el.className = 'alm-response-message alm-response--' + type;
			el.innerHTML = '<p>' + this.escapeHtml(message) + '</p>';
			el.style.display = 'block';
		},

		/**
		 * Escape HTML to prevent XSS.
		 * 
		 * @param {string} text Text to escape
		 * @return {string} Escaped text
		 */
		escapeHtml: function(text) {
			var div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}

	};

})();

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
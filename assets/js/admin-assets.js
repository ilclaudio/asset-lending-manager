/**
 * Admin JavaScript for ALM assets
 *
 * Loaded only on ALM admin pages (asset edit, list, taxonomies, custom pages).
 *
 * @package AssetLendingManager
 */

(function($) {
	'use strict';

	/**
	 * Initialize when DOM is ready.
	 */
	$(document).ready(function() {
		ALM_Admin.init();
	});

	/**
	 * ALM Admin object.
	 */
	var ALM_Admin = {

		/**
		 * Initialize admin functionality.
		 */
		init: function() {
			console.log('ALM Admin initialized');
			
			// Access to data passed from PHP via wp_localize_script.
			if (typeof almAdmin !== 'undefined') {
				console.log('AJAX URL:', almAdmin.ajaxUrl);
				console.log('Nonce:', almAdmin.nonce);
			}

			// Initialize components.
			this.initAssetStatusBadges();
			this.initQuickActions();
			this.initFormValidation();
		},

		/**
		 * Add status badges to asset list table.
		 */
		initAssetStatusBadges: function() {
			// Add visual status indicators in the assets list.
			$('.post-type-alm_asset .wp-list-table tbody tr').each(function() {
				var $row = $(this);
				var $stateCell = $row.find('.column-taxonomy-alm_state');
				
				if ($stateCell.length) {
					var stateText = $stateCell.text().trim().toLowerCase();
					var badgeClass = '';
					
					if (stateText.includes('available') || stateText.includes('disponibile')) {
						badgeClass = 'available';
					} else if (stateText.includes('loaned') || stateText.includes('prestato')) {
						badgeClass = 'loaned';
					} else if (stateText.includes('maintenance') || stateText.includes('manutenzione')) {
						badgeClass = 'maintenance';
					}
					
					if (badgeClass) {
						$stateCell.wrapInner('<span class="alm-status-badge ' + badgeClass + '"></span>');
					}
				}
			});
		},

		/**
		 * Initialize quick actions for assets.
		 */
		initQuickActions: function() {
			// Add custom quick actions to asset rows.
			$('.post-type-alm_asset .row-actions').each(function() {
				var $actions = $(this);
				var postId = $actions.closest('tr').attr('id').replace('post-', '');
				
				// Example: Add a "View Frontend" link.
				var viewLink = '<span class="alm-view-frontend"> | ' +
					'<a href="' + ALM_Admin.getAssetPermalink(postId) + '" target="_blank">' +
					'View on Frontend</a></span>';
				
				$actions.append(viewLink);
			});
		},

		/**
		 * Get asset permalink (placeholder - would need actual data).
		 * 
		 * @param {number} postId Asset post ID.
		 * @return {string} Asset permalink.
		 */
		getAssetPermalink: function(postId) {
			// This is a placeholder. In a real implementation, you would:
			// 1. Store permalinks in data attributes
			// 2. Use AJAX to fetch the permalink
			// 3. Use REST API
			return '/?p=' + postId;
		},

		/**
		 * Initialize form validation for asset edit.
		 */
		initFormValidation: function() {
			// Only on asset edit page.
			if (!$('body').hasClass('post-type-alm_asset') || !$('body').hasClass('post-php')) {
				return;
			}

			// Example: Validate required fields before publish.
			$('#publish').on('click', function(e) {
				var isValid = true;
				var errors = [];

				// Check if title is filled.
				var title = $('#title').val().trim();
				if (!title) {
					errors.push('Asset name is required.');
					isValid = false;
				}

				// Check if asset type is selected.
				var assetType = $('input[name="tax_input[alm_type][]"]:checked').length;
				if (assetType === 0) {
					errors.push('Please select a asset type.');
					isValid = false;
				}

				// Show errors if any.
				if (!isValid) {
					e.preventDefault();
					alert('Please fix the following errors:\n\n' + errors.join('\n'));
					return false;
				}
			});
		},

		/**
		 * Show loading spinner.
		 * 
		 * @param {jQuery} $element Element to show spinner next to.
		 */
		showSpinner: function($element) {
			if ($element.find('.alm-spinner').length === 0) {
				$element.append('<span class="alm-spinner"></span>');
			}
		},

		/**
		 * Hide loading spinner.
		 * 
		 * @param {jQuery} $element Element to remove spinner from.
		 */
		hideSpinner: function($element) {
			$element.find('.alm-spinner').remove();
		},

		/**
		 * Show admin notice.
		 * 
		 * @param {string} message Notice message.
		 * @param {string} type Notice type: 'success', 'error', 'warning'.
		 */
		showNotice: function(message, type) {
			type = type || 'success';
			
			var $notice = $('<div class="alm-notice ' + type + '">' + message + '</div>');
			$('.wrap h1').after($notice);
			
			// Auto-hide after 5 seconds.
			setTimeout(function() {
				$notice.fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);
		}

	};

})(jQuery);
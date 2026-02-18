/**
 * Admin JavaScript for ALM assets.
 *
 * Loaded only on ALM admin pages (asset edit, list, taxonomies, custom pages).
 *
 * @package AssetLendingManager
 */

(function() {
	'use strict';

	var __ = (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__ : function(text) {
		return text;
	};

	var ALM_Admin = {
		/**
		 * Initialize admin functionality.
		 */
		init: function() {
			console.log('ALM Admin initialized');

			if (typeof window.almAdmin !== 'undefined') {
				console.log('AJAX URL:', window.almAdmin.ajaxUrl);
				console.log('Nonce:', window.almAdmin.nonce);
			}

			this.initAssetStatusBadges();
			this.initQuickActions();
			this.initFormValidation();
		},

		/**
		 * Add status badges to asset list table.
		 */
		initAssetStatusBadges: function() {
			var rows = document.querySelectorAll('.post-type-alm_asset .wp-list-table tbody tr');

			rows.forEach(function(row) {
				var stateCell = row.querySelector('.column-taxonomy-alm_state');
				if (!stateCell) {
					return;
				}

				var stateText = stateCell.textContent.trim().toLowerCase();
				var badgeClass = '';

				if (stateText.includes('available') || stateText.includes('disponibile')) {
					badgeClass = 'available';
				} else if (stateText.includes('loaned') || stateText.includes('prestato')) {
					badgeClass = 'loaned';
				} else if (stateText.includes('maintenance') || stateText.includes('manutenzione')) {
					badgeClass = 'maintenance';
				}

				if (!badgeClass || stateCell.querySelector('.alm-status-badge')) {
					return;
				}

				var wrapper = document.createElement('span');
				wrapper.className = 'alm-status-badge ' + badgeClass;
				while (stateCell.firstChild) {
					wrapper.appendChild(stateCell.firstChild);
				}
				stateCell.appendChild(wrapper);
			});
		},

		/**
		 * Initialize quick actions for assets.
		 */
		initQuickActions: function() {
			var actionsRows = document.querySelectorAll('.post-type-alm_asset .row-actions');

			actionsRows.forEach(function(actions) {
				var row = actions.closest('tr');
				if (!row || !row.id) {
					return;
				}

				var postId = row.id.replace('post-', '');
				var viewLabel = __( 'View on Frontend', 'asset-lending-manager' );
				var viewLink = '<span class="alm-view-frontend"> | ' +
					'<a href="' + ALM_Admin.getAssetPermalink(postId) + '" target="_blank">' +
					viewLabel + '</a></span>';

				actions.insertAdjacentHTML('beforeend', viewLink);
			});
		},

		/**
		 * Get asset permalink (placeholder - would need actual data).
		 *
		 * @param {number|string} postId Asset post ID.
		 * @return {string} Asset permalink.
		 */
		getAssetPermalink: function(postId) {
			return '/?p=' + postId;
		},

		/**
		 * Initialize form validation for asset edit.
		 */
		initFormValidation: function() {
			var body = document.body;
			if (!body.classList.contains('post-type-alm_asset') || !body.classList.contains('post-php')) {
				return;
			}

			var publishButton = document.getElementById('publish');
			if (!publishButton) {
				return;
			}

			publishButton.addEventListener('click', function(e) {
				var isValid = true;
				var errors = [];

				var titleField = document.getElementById('title');
				var title = titleField ? titleField.value.trim() : '';
				if (!title) {
					errors.push( __( 'Asset name is required.', 'asset-lending-manager' ) );
					isValid = false;
				}

				var assetType = document.querySelectorAll('input[name="tax_input[alm_type][]"]:checked').length;
				if (0 === assetType) {
					errors.push( __( 'Please select a asset type.', 'asset-lending-manager' ) );
					isValid = false;
				}

				if (!isValid) {
					e.preventDefault();
					alert( __( 'Please fix the following errors:\n\n', 'asset-lending-manager' ) + errors.join('\n') );
				}
			});
		},

		/**
		 * Show loading spinner.
		 *
		 * @param {HTMLElement} element Element to show spinner next to.
		 */
		showSpinner: function(element) {
			if (!element || element.querySelector('.alm-spinner')) {
				return;
			}
			var spinner = document.createElement('span');
			spinner.className = 'alm-spinner';
			element.appendChild(spinner);
		},

		/**
		 * Hide loading spinner.
		 *
		 * @param {HTMLElement} element Element to remove spinner from.
		 */
		hideSpinner: function(element) {
			if (!element) {
				return;
			}
			var spinner = element.querySelector('.alm-spinner');
			if (spinner) {
				spinner.remove();
			}
		},

		/**
		 * Show admin notice.
		 *
		 * @param {string} message Notice message.
		 * @param {string} type Notice type: success|error|warning.
		 */
		showNotice: function(message, type) {
			var noticeType = type || 'success';
			var notice = document.createElement('div');
			notice.className = 'alm-notice ' + noticeType;
			notice.textContent = message;

			var heading = document.querySelector('.wrap h1');
			if (heading && heading.parentNode) {
				heading.parentNode.insertBefore(notice, heading.nextSibling);
			}

			setTimeout(function() {
				notice.remove();
			}, 5000);
		},
	};

	document.addEventListener('DOMContentLoaded', function() {
		ALM_Admin.init();
	});
})();

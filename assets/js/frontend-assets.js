/**
 * Frontend JavaScript for ALM assets
 *
 * Loaded only on asset archive, single, and pages with asset shortcodes.
 *
 * @package AssetLendingManager
 */

(function() {
	'use strict';
	var __ = (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__ : function(text) {
		return text;
	};

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
			}

			// Initialize components.
			this.initImageLightbox();
			this.initAssetFilters();
			this.initAssetSearch();
			this.initLoanRequestForm();
			this.initRequestActions();
			this.initDirectAssignForm();
			this.initChangeStateForm();
			this.initRestoreStateForm();
			this.showActionResultMessage();
			this.initQrCode();
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
		 * Handles loan request submission via AJAX.
		 */
		initLoanRequestForm: function() {
			var form = document.getElementById('alm-loan-request-form');
			
			if (!form) {
				return;
			}

			console.log('*** Loan request form found, initializing...');

			// Character counter for request message
			var messageField = document.getElementById('alm-request-message');
			var charCount = document.getElementById('alm-request-char-count');

			if (messageField && charCount) {
				messageField.addEventListener('input', function() {
					var length = messageField.value.length;
					charCount.textContent = length + ' / 500';
					
					if (length >= 500) {
						charCount.style.color = '#dc3545';
					} else {
						charCount.style.color = '#6c757d';
					}
				});
			}

			form.addEventListener('submit', function(e) {
				e.preventDefault();

				var submitBtn = form.querySelector('button[type="submit"]');
				var responseDiv = document.getElementById('alm-loan-request-response');
				var messageField = document.getElementById('alm-request-message');

				// Get asset ID from page context
				var assetId = ALM_Frontend.getAssetIdFromPage();
				if (!assetId) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Asset ID not found.', 'asset-lending-manager' ));
					return;
				}

				// Validate message
				if (!messageField.value.trim()) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Please enter a message.', 'asset-lending-manager' ));
					return;
				}

				// Validate message length (max 500 characters)
				if (messageField.value.length > 500) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Request message must not exceed 500 characters.', 'asset-lending-manager' ));
					return;
				}

				// Disable submit button
				var originalBtnText = submitBtn.textContent;
				submitBtn.disabled = true;
				submitBtn.textContent = __( 'Sending...', 'asset-lending-manager' );
				responseDiv.style.display = 'none';

				// Prepare form data
				var formData = new FormData(form);
				formData.append('action', 'alm_submit_loan_request');
				formData.append('asset_id', assetId);
				formData.append('message', messageField.value.trim());

				// Fallback: keep compatibility when nonce field is not present in DOM.
				if (!formData.get('nonce')) {
					if (typeof window.almFrontend !== 'undefined' && window.almFrontend.loanRequestNonce) {
						formData.append('nonce', window.almFrontend.loanRequestNonce);
					} else {
						ALM_Frontend.showResponse(responseDiv, 'error', __( 'Security token not found. Please reload the page.', 'asset-lending-manager' ));
						submitBtn.disabled = false;
						submitBtn.textContent = originalBtnText;
						return;
					}
				}

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
						console.log('*** Loan request sent successfully, reloading page...');
						
						// Reload page with success message
						var currentUrl = window.location.href.split('?')[0];
						window.location.href = currentUrl + '?alm_action=send_request&alm_status=success';
					} else {
						var errorMsg = data.data && data.data.message ? data.data.message : __( 'Request failed. Please try again.', 'asset-lending-manager' );
						ALM_Frontend.showResponse(responseDiv, 'error', errorMsg);
						console.error('*** Loan request failed:', errorMsg);
						
						// Re-enable submit button only on error
						submitBtn.disabled = false;
						submitBtn.textContent = originalBtnText;
					}
				})
				.catch(function(error) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Request failed. Please try again.', 'asset-lending-manager' ));
					console.error('*** AJAX error:', error);
					
					// Re-enable submit button only on error
					submitBtn.disabled = false;
					submitBtn.textContent = originalBtnText;
				});
			});

			console.log('*** Loan request form initialized');
		},

		/**
		 * Initialize direct assignment form (operator only).
		 *
		 * Handles form submission, input validation, and AJAX call for the
		 * direct asset assignment feature.
		 */
		initDirectAssignForm: function() {
			var form = document.getElementById('alm-direct-assign-form');

			if (!form) {
				return;
			}

			// Character counter for reason field.
			var reasonField = document.getElementById('alm-direct-assign-reason');
			var charCount   = document.getElementById('alm-direct-assign-char-count');

			if (reasonField && charCount) {
				reasonField.addEventListener('input', function() {
					var length = reasonField.value.length;
					charCount.textContent = length + ' / 500';
					charCount.style.color = length >= 500 ? '#dc3545' : '#6c757d';
				});
			}

			form.addEventListener('submit', function(e) {
				e.preventDefault();

				var submitBtn    = form.querySelector('button[type="submit"]');
				var responseDiv  = document.getElementById('alm-direct-assign-response');
				var assigneeId   = document.getElementById('alm-direct-assign-user-id');
				var reasonField  = document.getElementById('alm-direct-assign-reason');

				var assetId = ALM_Frontend.getAssetIdFromPage();
				if (!assetId) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Asset ID not found.', 'asset-lending-manager' ));
					return;
				}

				// Validate assignee selection.
				if (!assigneeId || !assigneeId.value || parseInt(assigneeId.value, 10) <= 0) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Please select a user from the list.', 'asset-lending-manager' ));
					return;
				}

				// Validate reason.
				if (!reasonField || !reasonField.value.trim()) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Please enter the assignment reason.', 'asset-lending-manager' ));
					return;
				}

				if (reasonField.value.length > 500) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Reason must not exceed 500 characters.', 'asset-lending-manager' ));
					return;
				}

				// Verify nonce is available.
				if (typeof window.almFrontend === 'undefined' || !window.almFrontend.directAssignNonce) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Security token not found. Please reload the page.', 'asset-lending-manager' ));
					return;
				}

				// Disable submit button.
				var originalBtnText = submitBtn.textContent;
				submitBtn.disabled  = true;
				submitBtn.textContent = __( 'Assigning...', 'asset-lending-manager' );
				responseDiv.style.display = 'none';

				// Prepare form data.
				var formData = new FormData();
				formData.append('action',      'alm_direct_assign_asset');
				formData.append('nonce',       window.almFrontend.directAssignNonce);
				formData.append('asset_id',    assetId);
				formData.append('assignee_id', assigneeId.value);
				formData.append('reason',      reasonField.value.trim());

				fetch(window.almFrontend.ajaxUrl, {
					method:      'POST',
					body:        formData,
					credentials: 'same-origin'
				})
				.then(function(response) {
					return response.json();
				})
				.then(function(data) {
					if (data.success) {
						var currentUrl = window.location.href.split('?')[0];
						window.location.href = currentUrl + '?alm_action=direct_assign&alm_status=success';
					} else {
						var errorMsg = data.data && data.data.message ? data.data.message : __( 'Assignment failed. Please try again.', 'asset-lending-manager' );
						ALM_Frontend.showResponse(responseDiv, 'error', errorMsg);
						submitBtn.disabled    = false;
						submitBtn.textContent = originalBtnText;
					}
				})
				.catch(function(error) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Request failed. Please try again.', 'asset-lending-manager' ));
					console.error('*** Direct assign AJAX error:', error);
					submitBtn.disabled    = false;
					submitBtn.textContent = originalBtnText;
				});
			});
		},

		/**
		 * Initialize change asset state form (operator only).
		 *
		 * Handles form submission for setting an asset to maintenance or retired.
		 * Two submit buttons carry a data-target-state attribute to distinguish the action.
		 */
		initChangeStateForm: function() {
			var form = document.getElementById('alm-change-state-form');

			if (!form) {
				return;
			}

			// Character counter for notes field.
			var notesField = document.getElementById('alm-change-state-notes');
			var charCount  = document.getElementById('alm-change-state-char-count');

			if (notesField && charCount) {
				notesField.addEventListener('input', function() {
					var length = notesField.value.length;
					charCount.textContent = length + ' / ' + notesField.getAttribute('maxlength');
				});
			}

			form.addEventListener('submit', function(e) {
				e.preventDefault();

				var clickedBtn  = e.submitter || form.querySelector('button[type="submit"]');
				var targetState = clickedBtn ? clickedBtn.getAttribute('data-target-state') : '';
				var responseDiv = document.getElementById('alm-change-state-response');
				var submitBtns  = form.querySelectorAll('button[type="submit"]');

				if (!targetState) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Could not determine target state.', 'asset-lending-manager' ));
					return;
				}

				if (typeof window.almFrontend === 'undefined' || !window.almFrontend.changeStateNonce) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Security token not found. Please reload the page.', 'asset-lending-manager' ));
					return;
				}

				var assetId = ALM_Frontend.getAssetIdFromPage();
				if (!assetId) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Asset ID not found.', 'asset-lending-manager' ));
					return;
				}

				var originalTexts = [];
				submitBtns.forEach(function(btn) {
					originalTexts.push(btn.textContent);
					btn.disabled = true;
				});
				responseDiv.style.display = 'none';

				var formData = new FormData();
				formData.append('action',       'alm_change_asset_state');
				formData.append('nonce',        window.almFrontend.changeStateNonce);
				formData.append('asset_id',     assetId);
				formData.append('target_state', targetState);
				formData.append('notes',        notesField ? notesField.value.trim() : '');

				fetch(window.almFrontend.ajaxUrl, { method: 'POST', body: formData })
					.then(function(response) { return response.json(); })
					.then(function(data) {
						if (data.success) {
							var currentUrl = window.location.href.split('?')[0];
							window.location.href = currentUrl + '?alm_action=change_state&alm_status=success&alm_state=' + encodeURIComponent(targetState);
						} else {
							var errorMsg = data.data && data.data.message ? data.data.message : __( 'State change failed. Please try again.', 'asset-lending-manager' );
							ALM_Frontend.showResponse(responseDiv, 'error', errorMsg);
							submitBtns.forEach(function(btn, i) {
								btn.disabled    = false;
								btn.textContent = originalTexts[i];
							});
						}
					})
					.catch(function(error) {
						ALM_Frontend.showResponse(responseDiv, 'error', __( 'Request failed. Please try again.', 'asset-lending-manager' ));
						console.error('*** Change state AJAX error:', error);
						submitBtns.forEach(function(btn, i) {
							btn.disabled    = false;
							btn.textContent = originalTexts[i];
						});
					});
			});
		},

		/**
		 * Initialize restore asset state form (operator only).
		 *
		 * Handles form submission for restoring an asset from maintenance or retired to available.
		 */
		initRestoreStateForm: function() {
			var form = document.getElementById('alm-restore-state-form');

			if (!form) {
				return;
			}

			// Character counter for notes field.
			var notesField = document.getElementById('alm-restore-state-notes');
			var charCount  = document.getElementById('alm-restore-state-char-count');

			if (notesField && charCount) {
				notesField.addEventListener('input', function() {
					var length = notesField.value.length;
					charCount.textContent = length + ' / ' + notesField.getAttribute('maxlength');
				});
			}

			form.addEventListener('submit', function(e) {
				e.preventDefault();

				var responseDiv = document.getElementById('alm-restore-state-response');
				var submitBtn   = form.querySelector('button[type="submit"]');

				if (typeof window.almFrontend === 'undefined' || !window.almFrontend.restoreStateNonce) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Security token not found. Please reload the page.', 'asset-lending-manager' ));
					return;
				}

				var assetId = ALM_Frontend.getAssetIdFromPage();
				if (!assetId) {
					ALM_Frontend.showResponse(responseDiv, 'error', __( 'Asset ID not found.', 'asset-lending-manager' ));
					return;
				}

				var originalBtnText  = submitBtn ? submitBtn.textContent : '';
				if (submitBtn) {
					submitBtn.disabled    = true;
					submitBtn.textContent = __( 'Restoring...', 'asset-lending-manager' );
				}
				responseDiv.style.display = 'none';

				var formData = new FormData();
				formData.append('action',   'alm_restore_asset_state');
				formData.append('nonce',    window.almFrontend.restoreStateNonce);
				formData.append('asset_id', assetId);
				formData.append('notes',    notesField ? notesField.value.trim() : '');

				fetch(window.almFrontend.ajaxUrl, { method: 'POST', body: formData })
					.then(function(response) { return response.json(); })
					.then(function(data) {
						if (data.success) {
							var currentUrl = window.location.href.split('?')[0];
							window.location.href = currentUrl + '?alm_action=restore_state&alm_status=success';
						} else {
							var errorMsg = data.data && data.data.message ? data.data.message : __( 'Restore failed. Please try again.', 'asset-lending-manager' );
							ALM_Frontend.showResponse(responseDiv, 'error', errorMsg);
							if (submitBtn) {
								submitBtn.disabled    = false;
								submitBtn.textContent = originalBtnText;
							}
						}
					})
					.catch(function(error) {
						ALM_Frontend.showResponse(responseDiv, 'error', __( 'Request failed. Please try again.', 'asset-lending-manager' ));
						console.error('*** Restore state AJAX error:', error);
						if (submitBtn) {
							submitBtn.disabled    = false;
							submitBtn.textContent = originalBtnText;
						}
					});
			});
		},

		/**
		 * Initialize approve/reject request actions.
		 *
		 * Handle approve and reject buttons in the requests table.
		 */
		initRequestActions: function() {
			var approveButtons = document.querySelectorAll('[data-action="approve"]');
			var rejectButtons  = document.querySelectorAll('[data-action="reject"]');

			if (!approveButtons.length && !rejectButtons.length) {
				return;
			}

			console.log('*** Request action buttons found, initializing...');

			// Handle approve buttons
			approveButtons.forEach(function(btn) {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					ALM_Frontend.handleApproveRequest(btn);
				});
			});

			// Handle reject buttons
			rejectButtons.forEach(function(btn) {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					ALM_Frontend.handleRejectRequest(btn);
				});
			});

			console.log('*** Request action buttons initialized');
		},

		/**
		 * Handle approve request action.
		 * 
		 * @param {HTMLElement} btn Button element
		 */
		handleApproveRequest: function(btn) {
			var requestId = btn.getAttribute('data-request-id');
			var assetId = btn.getAttribute('data-asset-id');

			if (!requestId || !assetId) {
				console.error('Missing request ID or asset ID');
				return;
			}

			console.log('*** Approve button clicked for request:', requestId);

			// Show confirmation modal
			ALM_Frontend.showConfirmModal(
				__( 'Confirm Approval', 'asset-lending-manager' ),
				__( 'Are you sure you want to approve this loan request?', 'asset-lending-manager' ),
				function() {
					// On confirm
					ALM_Frontend.submitApprovalRequest(btn, requestId, assetId);
				}
			);
		},

		/**
		 * Show confirmation modal.
		 * 
		 * @param {string} title Modal title
		 * @param {string} message Modal message
		 * @param {Function} onConfirm Callback on confirm
		 */
		showConfirmModal: function(title, message, onConfirm) {
			// Create modal overlay
			var overlay = document.createElement('div');
			overlay.className = 'alm-modal-overlay alm-confirm-modal';

			// Create modal content
			var content = document.createElement('div');
			content.className = 'alm-modal-content';

			// Modal header
			var header = document.createElement('div');
			header.className = 'alm-modal-header';
			header.innerHTML = '<h2>' + this.escapeHtml(title) + '</h2>';

			// Modal body
			var body = document.createElement('div');
			body.className = 'alm-modal-body';
			body.innerHTML = '<p>' + this.escapeHtml(message) + '</p>';

			// Modal footer
			var footer = document.createElement('div');
			footer.className = 'alm-modal-footer';

			var cancelBtn = document.createElement('button');
			cancelBtn.type = 'button';
			cancelBtn.className = 'alm-button alm-button--secondary';
			cancelBtn.textContent = __( 'Cancel', 'asset-lending-manager' );

			var confirmBtn = document.createElement('button');
			confirmBtn.type = 'button';
			confirmBtn.className = 'alm-button alm-button--primary';
			confirmBtn.textContent = __( 'Confirm', 'asset-lending-manager' );

			footer.appendChild(cancelBtn);
			footer.appendChild(confirmBtn);

			// Assemble modal
			content.appendChild(header);
			content.appendChild(body);
			content.appendChild(footer);
			overlay.appendChild(content);
			document.body.appendChild(overlay);

			// Show modal with animation
			setTimeout(function() {
				overlay.classList.add('active');
			}, 10);

			// Handle cancel
			var closeModal = function() {
				overlay.classList.remove('active');
				setTimeout(function() {
					if (overlay && overlay.parentNode) {
						overlay.parentNode.removeChild(overlay);
					}
				}, 300);
			};

			cancelBtn.addEventListener('click', closeModal);

			// Close on overlay click (outside modal)
			overlay.addEventListener('click', function(e) {
				if (e.target === overlay) {
					closeModal();
				}
			});

			// Handle confirm
			confirmBtn.addEventListener('click', function() {
				// Disable button to prevent double-click
				confirmBtn.disabled = true;
				confirmBtn.textContent = __( 'Processing...', 'asset-lending-manager' );
				cancelBtn.disabled = true;

				closeModal();
				
				if (typeof onConfirm === 'function') {
					onConfirm();
				}
			});
		},

		/**
		 * Submit approval request via AJAX.
		 * 
		 * @param {HTMLElement} btn Button element
		 * @param {string} requestId Request ID
		 * @param {string} assetId Asset ID
		 */
		submitApprovalRequest: function(btn, requestId, assetId) {
			// Check if almFrontend is available
			if (typeof window.almFrontend === 'undefined' || !window.almFrontend.loanRequestNonce) {
				alert( __( 'Security token not found. Please reload the page.', 'asset-lending-manager' ) );
				return;
			}

			// Disable button to prevent double submission
			var originalBtnText = btn.textContent;
			btn.disabled = true;
			btn.textContent = __( 'Approving...', 'asset-lending-manager' );

			// Disable all other action buttons in the same row
			var row = btn.closest('tr');
			if (row) {
				var actionBtns = row.querySelectorAll('.alm-button--approve, .alm-button--reject');
				actionBtns.forEach(function(actionBtn) {
					actionBtn.disabled = true;
				});
			}

			// Prepare form data
			var formData = new FormData();
			formData.append('action', 'alm_approve_loan_request');
			formData.append('nonce', window.almFrontend.loanRequestNonce);
			formData.append('request_id', requestId);
			formData.append('asset_id', assetId);

			console.log('*** Sending approval request:', requestId);

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
					console.log('*** Approval successful, reloading page...');
					
					// Reload page with success message
					var currentUrl = window.location.href.split('?')[0];
					window.location.href = currentUrl + '?alm_action=approve&alm_status=success';
				} else {
					var errorMsg = data.data && data.data.message ? data.data.message : __( 'Approval failed. Please try again.', 'asset-lending-manager' );
					alert(errorMsg);
					console.error('*** Approval failed:', errorMsg);
					
					// Re-enable buttons on error
					btn.disabled = false;
					btn.textContent = originalBtnText;
					
					if (row) {
						var actionBtns = row.querySelectorAll('.alm-button--approve, .alm-button--reject');
						actionBtns.forEach(function(actionBtn) {
							actionBtn.disabled = false;
						});
					}
				}
			})
			.catch(function(error) {
				alert( __( 'Approval request failed. Please try again.', 'asset-lending-manager' ) );
				console.error('*** AJAX error:', error);
				
				// Re-enable buttons on error
				btn.disabled = false;
				btn.textContent = originalBtnText;
				
				if (row) {
					var actionBtns = row.querySelectorAll('.alm-button--approve, .alm-button--reject');
					actionBtns.forEach(function(actionBtn) {
						actionBtn.disabled = false;
					});
				}
			});
		},

		/**
		 * Handle reject request action.
		 * 
		 * @param {HTMLElement} btn Button element
		 */
		handleRejectRequest: function(btn) {
			var requestId = btn.getAttribute('data-request-id');
			var assetId = btn.getAttribute('data-asset-id');
			
			console.log('*** Reject request with id:', requestId);
			console.log('*** Asset ID:', assetId);
			
			// Show rejection modal
			this.showRejectionModal(requestId, assetId);
		},

		/**
		 * Show rejection modal.
		 * 
		 * @param {string} requestId Request ID
		 * @param {string} assetId Asset ID
		 */
		showRejectionModal: function(requestId, assetId) {
			// Create modal overlay
			var modal = document.createElement('div');
			modal.className = 'alm-modal-overlay';
			modal.setAttribute('role', 'dialog');
			modal.setAttribute('aria-labelledby', 'alm-reject-modal-title');
			modal.setAttribute('aria-modal', 'true');

			// Modal content
			var modalContent = document.createElement('div');
			modalContent.className = 'alm-modal-content';

			// Modal header
			var modalHeader = document.createElement('div');
			modalHeader.className = 'alm-modal-header';
			
			var modalTitle = document.createElement('h2');
			modalTitle.id = 'alm-reject-modal-title';
			modalTitle.textContent = __( 'Reject Loan Request', 'asset-lending-manager' );
			
			var closeBtn = document.createElement('button');
			closeBtn.className = 'alm-modal-close';
			closeBtn.setAttribute('aria-label', __( 'Close dialog', 'asset-lending-manager' ));
			closeBtn.innerHTML = '&times;';
			closeBtn.addEventListener('click', function() {
				ALM_Frontend.closeModal(modal);
			});

			modalHeader.appendChild(modalTitle);
			modalHeader.appendChild(closeBtn);

			// Modal body
			var modalBody = document.createElement('div');
			modalBody.className = 'alm-modal-body';

			var form = document.createElement('form');
			form.id = 'alm-reject-request-form';

			var label = document.createElement('label');
			label.setAttribute('for', 'alm-rejection-message');
			label.textContent = __( 'Rejection reason (required, max 255 characters):', 'asset-lending-manager' );

			var textarea = document.createElement('textarea');
			textarea.id = 'alm-rejection-message';
			textarea.name = 'rejection_message';
			textarea.rows = 4;
			textarea.maxLength = 255;
			textarea.required = true;
			textarea.placeholder = __( 'Please provide a reason for rejecting this loan request...', 'asset-lending-manager' );

			var charCount = document.createElement('div');
			charCount.className = 'alm-char-count';
			charCount.textContent = '0 / 255';

			textarea.addEventListener('input', function() {
				var length = textarea.value.length;
				charCount.textContent = length + ' / 255';
				
				if (length >= 255) {
					charCount.style.color = '#dc3545';
				} else {
					charCount.style.color = '#6c757d';
				}
			});

			var responseDiv = document.createElement('div');
			responseDiv.id = 'alm-reject-response';
			responseDiv.className = 'alm-response-message';
			responseDiv.style.display = 'none';

			form.appendChild(label);
			form.appendChild(textarea);
			form.appendChild(charCount);
			form.appendChild(responseDiv);

			// Modal footer (inside form so submit button works)
			var modalFooter = document.createElement('div');
			modalFooter.className = 'alm-modal-footer';

			var cancelBtn = document.createElement('button');
			cancelBtn.type = 'button';
			cancelBtn.className = 'alm-button alm-button--secondary';
			cancelBtn.textContent = __( 'Cancel', 'asset-lending-manager' );
			cancelBtn.addEventListener('click', function() {
				ALM_Frontend.closeModal(modal);
			});

			var submitBtn = document.createElement('button');
			submitBtn.type = 'submit';
			submitBtn.className = 'alm-button alm-button--reject';
			submitBtn.textContent = __( 'Reject Request', 'asset-lending-manager' );

			modalFooter.appendChild(cancelBtn);
			modalFooter.appendChild(submitBtn);
			
			// Add footer to form
			form.appendChild(modalFooter);

			modalBody.appendChild(form);

			// Assemble modal
			modalContent.appendChild(modalHeader);
			modalContent.appendChild(modalBody);
			modal.appendChild(modalContent);

			// Add to page
			document.body.appendChild(modal);

			// Show modal with animation
			setTimeout(function() {
				modal.classList.add('active');
				textarea.focus();
			}, 10);

			// Handle form submission
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				ALM_Frontend.submitRejectRequest(requestId, assetId, textarea.value, submitBtn, responseDiv, modal);
			});

			// Close on overlay click
			modal.addEventListener('click', function(e) {
				if (e.target === modal) {
					ALM_Frontend.closeModal(modal);
				}
			});

			// Close on ESC key
			var escHandler = function(e) {
				if (e.key === 'Escape') {
					ALM_Frontend.closeModal(modal);
					document.removeEventListener('keydown', escHandler);
				}
			};
			document.addEventListener('keydown', escHandler);

			// Trap focus in modal
			this.trapFocus(modal);
		},

		/**
		 * Submit reject request via AJAX.
		 * 
		 * @param {string} requestId Request ID
		 * @param {string} assetId Asset ID
		 * @param {string} message Rejection message
		 * @param {HTMLElement} submitBtn Submit button
		 * @param {HTMLElement} responseDiv Response message div
		 * @param {HTMLElement} modal Modal element
		 */
		submitRejectRequest: function(requestId, assetId, message, submitBtn, responseDiv, modal) {
			// Validate message
			if (!message.trim()) {
				this.showResponse(responseDiv, 'error', __( 'Please enter a rejection reason.', 'asset-lending-manager' ));
				return;
			}

			if (message.length > 255) {
				this.showResponse(responseDiv, 'error', __( 'Rejection reason must not exceed 255 characters.', 'asset-lending-manager' ));
				return;
			}

			// Check if almFrontend is available
			if (typeof window.almFrontend === 'undefined' || !window.almFrontend.loanRequestNonce) {
				this.showResponse(responseDiv, 'error', __( 'Security token not found. Please reload the page.', 'asset-lending-manager' ));
				return;
			}

			// Disable submit button
			var originalBtnText = submitBtn.textContent;
			submitBtn.disabled = true;
			submitBtn.textContent = __( 'Processing...', 'asset-lending-manager' );
			responseDiv.style.display = 'none';

			// Prepare form data
			var formData = new FormData();
			formData.append('action', 'alm_reject_loan_request');
			formData.append('nonce', window.almFrontend.loanRequestNonce);
			formData.append('request_id', requestId);
			formData.append('asset_id', assetId);
			formData.append('rejection_message', message.trim());

			console.log('*** Submitting rejection for request:', requestId);

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
					console.log('*** Rejection successful, reloading page...');
					
					// Close modal
					ALM_Frontend.closeModal(modal);
					
					// Reload page with success message
					var currentUrl = window.location.href.split('?')[0];
					window.location.href = currentUrl + '?alm_action=reject&alm_status=success';
				} else {
					var errorMsg = data.data && data.data.message ? data.data.message : __( 'Failed to reject request. Please try again.', 'asset-lending-manager' );
					ALM_Frontend.showResponse(responseDiv, 'error', errorMsg);
					console.error('*** Rejection failed:', errorMsg);
					
					// Re-enable submit button
					submitBtn.disabled = false;
					submitBtn.textContent = originalBtnText;
				}
			})
			.catch(function(error) {
				ALM_Frontend.showResponse(responseDiv, 'error', __( 'Request failed. Please try again.', 'asset-lending-manager' ));
				console.error('*** AJAX error:', error);
				
				// Re-enable submit button
				submitBtn.disabled = false;
				submitBtn.textContent = originalBtnText;
			});
		},

		/**
		 * Close modal.
		 * 
		 * @param {HTMLElement} modal Modal element
		 */
		closeModal: function(modal) {
			if (!modal) {
				return;
			}

			modal.classList.remove('active');
			
			setTimeout(function() {
				if (modal && modal.parentNode) {
					modal.parentNode.removeChild(modal);
				}
			}, 300);
		},

		/**
		 * Trap focus inside modal for accessibility.
		 * 
		 * @param {HTMLElement} modal Modal element
		 */
		trapFocus: function(modal) {
			var focusableElements = modal.querySelectorAll(
				'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
			);
			
			if (!focusableElements.length) {
				return;
			}

			var firstElement = focusableElements[0];
			var lastElement = focusableElements[focusableElements.length - 1];

			modal.addEventListener('keydown', function(e) {
				if (e.key !== 'Tab') {
					return;
				}

				if (e.shiftKey) {
					// Shift + Tab
					if (document.activeElement === firstElement) {
						e.preventDefault();
						lastElement.focus();
					}
				} else {
					// Tab
					if (document.activeElement === lastElement) {
						e.preventDefault();
						firstElement.focus();
					}
				}
			});
		},

		/**
		 * Show action result message after page reload.
		 * 
		 * Display global message based on URL parameters.
		 */
		showActionResultMessage: function() {
			var urlParams = new URLSearchParams(window.location.search);
			var action = urlParams.get('alm_action');
			var status = urlParams.get('alm_status');

			if (!action || !status) {
				return;
			}

			var message = '';
			var messageType = status;

			if (action === 'reject' && status === 'success') {
				message = __( 'Loan request rejected successfully.', 'asset-lending-manager' );
			} else if (action === 'reject' && status === 'error') {
				message = __( 'Failed to reject loan request. Please try again.', 'asset-lending-manager' );
			} else if (action === 'approve' && status === 'success') {
				message = __( 'Loan request approved successfully.', 'asset-lending-manager' );
			} else if (action === 'approve' && status === 'error') {
				message = __( 'Failed to approve loan request. Please try again.', 'asset-lending-manager' );
			} else if (action === 'send_request' && status === 'success') {
				message = __( 'Loan request sent successfully.', 'asset-lending-manager' );
			} else if (action === 'send_request' && status === 'error') {
				message = __( 'Failed to send loan request. Please try again.', 'asset-lending-manager' );
			} else if (action === 'direct_assign' && status === 'success') {
				message = __( 'Asset assigned successfully.', 'asset-lending-manager' );
			} else if (action === 'direct_assign' && status === 'error') {
				message = __( 'Failed to assign asset. Please try again.', 'asset-lending-manager' );
			} else if (action === 'change_state' && status === 'success') {
				message = __( 'Asset state updated successfully.', 'asset-lending-manager' );
			} else if (action === 'change_state' && status === 'error') {
				message = __( 'Failed to update asset state. Please try again.', 'asset-lending-manager' );
			} else if (action === 'restore_state' && status === 'success') {
				message = __( 'Asset restored to available successfully.', 'asset-lending-manager' );
			} else if (action === 'restore_state' && status === 'error') {
				message = __( 'Failed to restore asset. Please try again.', 'asset-lending-manager' );
			}

			if (message) {
				this.showGlobalMessage(message, messageType);
				
				// Clean URL (remove query parameters)
				var cleanUrl = window.location.href.split('?')[0];
				window.history.replaceState({}, document.title, cleanUrl);
			}
		},

		/**
		 * Initialize QR code generation for the asset detail page.
		 *
		 * Reads data-scan-url and data-asset-code from the QR container element,
		 * generates the QR code using qrcode-generator, and handles the print button.
		 */
		initQrCode: function() {
			if (typeof window.qrcode === 'undefined') {
				return;
			}

			// Find all QR canvas elements on the page.
			const containers = document.querySelectorAll('.alm-qr-canvas');
			if (!containers.length) {
				return;
			}

			// Read scan data from the first container.
			const firstContainer = containers[0];
			const scanUrl        = firstContainer.dataset.scanUrl;
			const assetCode      = firstContainer.dataset.assetCode;

			if (!scanUrl) {
				return;
			}

			// Generate QR code once (type 0 = auto, error correction level M).
			const qr = window.qrcode(0, 'M');
			qr.addData(scanUrl);
			qr.make();
			const svgMarkup = qr.createSvgTag({ scalable: true });

			// Inject the same SVG into every canvas element on the page.
			containers.forEach(function(container) {
				container.innerHTML = svgMarkup;
			});

			// Print button (first one found).
			const printBtn = document.querySelector('.alm-qr-print');
			if (printBtn) {
				printBtn.addEventListener('click', function() {
					window.print();
				});
			}

			// Build the print label card (hidden in normal view, shown only when printing).
			const printCard = document.createElement('div');
			printCard.className = 'alm-qr-print-card';
			printCard.setAttribute('aria-hidden', 'true');

			const printQr = document.createElement('div');
			printQr.className = 'alm-qr-print-card__qr';
			printQr.innerHTML = svgMarkup;

			const printTitle = document.createElement('p');
			printTitle.className = 'alm-qr-print-card__title';
			const titleEl = document.querySelector('.alm-asset-title');
			printTitle.textContent = titleEl ? titleEl.textContent.trim() : '';

			const printCode = document.createElement('p');
			printCode.className = 'alm-qr-print-card__code';
			printCode.textContent = assetCode || '';

			printCard.appendChild(printQr);
			printCard.appendChild(printTitle);
			printCard.appendChild(printCode);
			document.body.appendChild(printCard);
		},

		/**
		 * Show global message at the top of the page.
		 *
		 * @param {string} message Message text
		 * @param {string} type 'success' or 'error'
		 */
		showGlobalMessage: function(message, type) {
			// Check if message already exists
			var existingMessage = document.querySelector('.alm-global-message');
			if (existingMessage) {
				existingMessage.remove();
			}

			// Create message element
			var messageEl = document.createElement('div');
			messageEl.className = 'alm-global-message alm-global-message--' + type;
			messageEl.setAttribute('role', 'alert');
			messageEl.setAttribute('aria-live', 'polite');
			
			var messageText = document.createElement('p');
			messageText.textContent = message;
			
			var closeBtn = document.createElement('button');
			closeBtn.className = 'alm-global-message-close';
			closeBtn.setAttribute('aria-label', __( 'Close message', 'asset-lending-manager' ));
			closeBtn.innerHTML = '&times;';
			closeBtn.addEventListener('click', function() {
				messageEl.classList.remove('active');
				setTimeout(function() {
					if (messageEl && messageEl.parentNode) {
						messageEl.parentNode.removeChild(messageEl);
					}
				}, 300);
			});

			messageEl.appendChild(messageText);
			messageEl.appendChild(closeBtn);

			// Insert at the beginning of the main content
			var content = document.querySelector('.alm-asset-detail') || document.querySelector('.entry-content') || document.body;
			content.insertBefore(messageEl, content.firstChild);

			// Show with animation
			setTimeout(function() {
				messageEl.classList.add('active');
			}, 10);

			// Auto-hide after 5 seconds
			setTimeout(function() {
				if (messageEl && messageEl.parentNode) {
					messageEl.classList.remove('active');
					setTimeout(function() {
						if (messageEl && messageEl.parentNode) {
							messageEl.parentNode.removeChild(messageEl);
						}
					}, 300);
				}
			}, 5000);
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
 * Add styles dynamically for lightbox, modal, and global messages.
 */
(function() {
	var style = document.createElement('style');
	style.textContent = `
		/* Filter active state */
		.alm-filter-active {
			border-color: #0073aa !important;
			background-color: #f0f8ff !important;
		}

		/* Lightbox */
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

		/* Modal overlay */
		.alm-modal-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.6);
			display: flex;
			align-items: center;
			justify-content: center;
			z-index: 10000;
			opacity: 0;
			transition: opacity 0.3s ease;
		}
		.alm-modal-overlay.active {
			opacity: 1;
		}

		/* Modal content */
		.alm-modal-content {
			background: white;
			border-radius: 8px;
			max-width: 600px;
			width: 90%;
			max-height: 90vh;
			overflow-y: auto;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
			transform: translateY(-20px);
			transition: transform 0.3s ease;
		}
		.alm-modal-overlay.active .alm-modal-content {
			transform: translateY(0);
		}

		/* Modal header */
		.alm-modal-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 20px 24px;
			border-bottom: 1px solid #dee2e6;
		}
		.alm-modal-header h2 {
			margin: 0;
			font-size: 20px;
			color: #333;
		}
		.alm-modal-close {
			background: none;
			border: none;
			font-size: 28px;
			line-height: 1;
			color: #6c757d;
			cursor: pointer;
			padding: 0;
			width: 32px;
			height: 32px;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 4px;
			transition: background-color 0.2s ease;
		}
		.alm-modal-close:hover {
			background-color: #f8f9fa;
			color: #333;
		}
		.alm-modal-close:focus {
			outline: 2px solid #0073aa;
			outline-offset: 2px;
		}

		/* Modal body */
		.alm-modal-body {
			padding: 24px;
		}
		.alm-modal-body label {
			display: block;
			margin-bottom: 8px;
			font-weight: 600;
			color: #333;
		}
		.alm-modal-body textarea {
			width: 100%;
			padding: 10px 12px;
			border: 1px solid #dee2e6;
			border-radius: 4px;
			font-family: inherit;
			font-size: 14px;
			line-height: 1.5;
			resize: vertical;
			min-height: 100px;
		}
		.alm-modal-body textarea:focus {
			outline: none;
			border-color: #0073aa;
			box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
		}
		.alm-char-count {
			margin-top: 6px;
			font-size: 13px;
			color: #6c757d;
			text-align: right;
		}

		/* Modal footer */
		.alm-modal-footer {
			display: flex;
			justify-content: flex-end;
			gap: 12px;
			padding: 20px 24px;
			border-top: 1px solid #dee2e6;
		}
		.alm-button--secondary {
			background-color: #6c757d;
			color: white;
		}
		.alm-button--secondary:hover {
			background-color: #5a6268;
		}

		/* Global message */
		.alm-global-message {
			position: relative;
			padding: 16px 48px 16px 20px;
			margin-bottom: 24px;
			border-radius: 4px;
			border-left: 4px solid;
			opacity: 0;
			transform: translateY(-10px);
			transition: all 0.3s ease;
		}
		.alm-global-message.active {
			opacity: 1;
			transform: translateY(0);
		}
		.alm-global-message--success {
			background-color: #d4edda;
			border-color: #28a745;
			color: #155724;
		}
		.alm-global-message--error {
			background-color: #f8d7da;
			border-color: #dc3545;
			color: #721c24;
		}
		.alm-global-message p {
			margin: 0;
			font-weight: 500;
		}
		.alm-global-message-close {
			position: absolute;
			top: 12px;
			right: 12px;
			background: none;
			border: none;
			font-size: 24px;
			line-height: 1;
			color: inherit;
			cursor: pointer;
			padding: 0;
			width: 28px;
			height: 28px;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 4px;
			opacity: 0.7;
			transition: opacity 0.2s ease;
		}
		.alm-global-message-close:hover {
			opacity: 1;
		}
		.alm-global-message-close:focus {
			outline: 2px solid currentColor;
			outline-offset: 2px;
		}

		/* Response messages in modal */
		.alm-response-message {
			margin-top: 16px;
			padding: 12px;
			border-radius: 4px;
		}
		.alm-response--success {
			background-color: #d4edda;
			border: 1px solid #c3e6cb;
			color: #155724;
		}
		.alm-response--error {
			background-color: #f8d7da;
			border: 1px solid #f5c6cb;
			color: #721c24;
		}
		.alm-response-message p {
			margin: 0;
		}

		/* Responsive */
		@media (max-width: 768px) {
			.alm-modal-content {
				width: 95%;
				max-height: 95vh;
			}
			.alm-modal-header,
			.alm-modal-body,
			.alm-modal-footer {
				padding: 16px;
			}
		}
	`;
	document.head.appendChild(style);
})();

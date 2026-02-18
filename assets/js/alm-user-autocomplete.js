/**
 * User autocomplete widget for ALM.
 *
 * Provides a reusable, accessible autocomplete widget for user search inputs.
 * Used by the direct assignment form (operator-only) and the owner filter in the asset list.
 *
 * @package AssetLendingManager
 */

(function() {
	'use strict';

	/**
	 * Initialize a user autocomplete widget on a given set of DOM elements.
	 *
	 * @param {Object} config
	 * @param {string} config.inputId    ID of the visible text input.
	 * @param {string} config.hiddenId   ID of the hidden input that stores the selected user ID.
	 * @param {string} config.dropdownId ID of the dropdown container element.
	 */
	window.almInitUserAutocomplete = function(config) {
		var input    = document.getElementById(config.inputId);
		var hiddenId = document.getElementById(config.hiddenId);

		if (!input || !hiddenId) {
			return;
		}

		var wrapEl        = input.parentNode;
		var minChars      = window.almUserAutocomplete.minChars || 3;
		var debounceTimer = null;
		var activeIndex   = -1;

		// Create or find the dropdown container.
		var dropdown = document.getElementById(config.dropdownId);
		if (!dropdown) {
			dropdown = document.createElement('div');
			dropdown.id        = config.dropdownId;
			dropdown.className = 'alm-autocomplete-dropdown';
			dropdown.setAttribute('role', 'listbox');
			dropdown.setAttribute('aria-label', 'User suggestions');
			input.parentNode.appendChild(dropdown);
		}

		// Accessibility: connect input to dropdown.
		input.setAttribute('role', 'combobox');
		input.setAttribute('aria-autocomplete', 'list');
		input.setAttribute('aria-expanded', 'false');
		input.setAttribute('aria-controls', config.dropdownId);
		input.setAttribute('aria-haspopup', 'listbox');

		/**
		 * Render items in the dropdown.
		 *
		 * @param {Array} users Array of user result objects.
		 */
		function renderDropdown(users) {
			dropdown.innerHTML = '';
			activeIndex = -1;

			if (!users || !users.length) {
				hideDropdown();
				return;
			}

			users.forEach(function(user) {
				var item = document.createElement('div');
				item.className = 'alm-autocomplete-item';
				item.setAttribute('role', 'option');
				item.setAttribute('aria-selected', 'false');
				item.setAttribute('data-id', user.id);
				item.setAttribute('data-name', user.display_name);
				item.tabIndex = -1;

				var nameEl = document.createElement('div');
				nameEl.className = 'alm-autocomplete-title';

				var nameStrong = document.createElement('strong');
				nameStrong.textContent = user.display_name;
				nameEl.appendChild(nameStrong);

				var roleEl = document.createElement('div');
				roleEl.className   = 'alm-autocomplete-meta';
				roleEl.textContent = user.role;

				item.appendChild(nameEl);
				item.appendChild(roleEl);

				// Select user on click.
				item.addEventListener('mousedown', function(e) {
					e.preventDefault(); // Prevent input blur before click fires.
					selectUser(user.id, user.display_name);
				});

				dropdown.appendChild(item);
			});

			showDropdown();
		}

		/**
		 * Select a user and populate the form fields.
		 *
		 * @param {number} userId      User ID.
		 * @param {string} displayName User display name.
		 */
		function selectUser(userId, displayName) {
			input.value    = displayName;
			hiddenId.value = userId;
			hideDropdown();
		}

		/**
		 * Show the dropdown.
		 */
		function showDropdown() {
			dropdown.style.display = 'block';
			input.setAttribute('aria-expanded', 'true');
		}

		/**
		 * Hide the dropdown and reset keyboard state.
		 */
		function hideDropdown() {
			dropdown.style.display = 'none';
			input.setAttribute('aria-expanded', 'false');
			activeIndex = -1;
			clearActiveItem();
		}

		/**
		 * Remove active highlight from all items.
		 */
		function clearActiveItem() {
			var items = dropdown.querySelectorAll('.alm-autocomplete-item');
			items.forEach(function(item) {
				item.classList.remove('alm-autocomplete-item--active');
				item.setAttribute('aria-selected', 'false');
			});
		}

		/**
		 * Set keyboard focus highlight on a specific item index.
		 *
		 * @param {number} index Item index.
		 */
		function setActiveItem(index) {
			var items = dropdown.querySelectorAll('.alm-autocomplete-item');
			clearActiveItem();
			if (index < 0 || index >= items.length) {
				activeIndex = -1;
				return;
			}
			activeIndex = index;
			items[index].classList.add('alm-autocomplete-item--active');
			items[index].setAttribute('aria-selected', 'true');
			items[index].scrollIntoView({ block: 'nearest' });
			input.setAttribute('aria-activedescendant', items[index].id || '');
		}

		/**
		 * Fetch users from the REST endpoint.
		 *
		 * @param {string} term Search term.
		 */
		function fetchUsers(term) {
			wrapEl.classList.add('alm-autocomplete-loading');

			var params = new URLSearchParams();
			params.append('term', term);
			params.append('nonce', window.almUserAutocomplete.restNonce);

			fetch(window.almUserAutocomplete.restUrl, {
				method:  'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
					'X-WP-Nonce':   window.almUserAutocomplete.restNonce,
				},
				body:        params.toString(),
				credentials: 'same-origin',
			})
			.then(function(response) {
				return response.json();
			})
			.then(function(data) {
				renderDropdown(Array.isArray(data) ? data : []);
			})
			.catch(function() {
				hideDropdown();
			})
			.finally(function() {
				wrapEl.classList.remove('alm-autocomplete-loading');
			});
		}

		// Input event with debounce.
		input.addEventListener('input', function() {
			var term = input.value.trim();

			// Reset hidden ID when user edits the search field.
			hiddenId.value = '';

			if (term.length < minChars) {
				hideDropdown();
				return;
			}

			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(function() {
				fetchUsers(term);
			}, 300);
		});

		// Keyboard navigation.
		input.addEventListener('keydown', function(e) {
			var items  = dropdown.querySelectorAll('.alm-autocomplete-item');
			var isOpen = dropdown.style.display === 'block';

			if (e.key === 'ArrowDown') {
				e.preventDefault();
				if (!isOpen) {
					return;
				}
				setActiveItem(activeIndex + 1 < items.length ? activeIndex + 1 : 0);
			} else if (e.key === 'ArrowUp') {
				e.preventDefault();
				if (!isOpen) {
					return;
				}
				setActiveItem(activeIndex - 1 >= 0 ? activeIndex - 1 : items.length - 1);
			} else if (e.key === 'Enter') {
				if (isOpen && activeIndex >= 0 && items[activeIndex]) {
					e.preventDefault();
					var id   = items[activeIndex].getAttribute('data-id');
					var name = items[activeIndex].getAttribute('data-name');
					selectUser(id, name);
				}
			} else if (e.key === 'Escape') {
				hideDropdown();
			}
		});

		// Close dropdown on click outside.
		document.addEventListener('click', function(e) {
			if (!input.contains(e.target) && !dropdown.contains(e.target)) {
				hideDropdown();
			}
		});

		// Close dropdown on input blur (allow mousedown on item to fire first).
		input.addEventListener('blur', function() {
			setTimeout(hideDropdown, 150);
		});
	};

	document.addEventListener('DOMContentLoaded', function() {
		// Bail early if localized data is missing (non-operator pages).
		if (typeof window.almUserAutocomplete === 'undefined') {
			return;
		}

		// Initialize the direct assignment form widget (asset detail page).
		window.almInitUserAutocomplete({
			inputId:    'alm-direct-assign-user-input',
			hiddenId:   'alm-direct-assign-user-id',
			dropdownId: 'alm-user-autocomplete-dropdown',
		});

		// Initialize the owner filter widget (asset list page).
		window.almInitUserAutocomplete({
			inputId:    'alm-owner-filter-input',
			hiddenId:   'alm-owner-filter-id',
			dropdownId: 'alm-owner-filter-dropdown',
		});
	});
}());

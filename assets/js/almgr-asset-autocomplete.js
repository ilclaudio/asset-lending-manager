/**
 * Reusable asset autocomplete widget for ALMGR frontend pages.
 *
 * Supports:
 * - navigation mode for the asset list search bar;
 * - selection mode for forms that store a selected asset ID in a hidden field.
 *
 * @package AssetLendingManager
 */

(function() {
	'use strict';

	/**
	 * Initialize a single asset autocomplete widget.
	 *
	 * @param {Object} widget Widget configuration localized from PHP.
	 * @return {void}
	 */
	function initWidget(widget) {
		var input = document.getElementById(widget.inputId);
		if (!input) {
			return;
		}

		var hiddenInput = widget.hiddenId ? document.getElementById(widget.hiddenId) : null;
		var wrapEl = input.parentNode;
		var dropdown = document.getElementById(widget.dropdownId);
		var debounceTimer = null;

		if (!dropdown) {
			dropdown = document.createElement('div');
			dropdown.id = widget.dropdownId;
			dropdown.className = 'almgr-autocomplete-dropdown';
			wrapEl.appendChild(dropdown);
		}

		input.setAttribute('role', 'combobox');
		input.setAttribute('aria-autocomplete', 'list');
		input.setAttribute('aria-expanded', 'false');
		input.setAttribute('aria-controls', widget.dropdownId);

		if (widget.mode === 'select' && hiddenInput && widget.selectedId && widget.selectedTitle) {
			hiddenInput.value = widget.selectedId;
			input.value = widget.selectedTitle;
		}

		/**
		 * Escape text for safe HTML output.
		 *
		 * @param {string} text Text to escape.
		 * @return {string}
		 */
		function escapeHtml(text) {
			var div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}

		/**
		 * Show or hide the loading state.
		 *
		 * @param {boolean} isLoading Whether the widget is loading.
		 * @return {void}
		 */
		function setLoading(isLoading) {
			if (wrapEl) {
				wrapEl.classList.toggle('almgr-autocomplete-loading', !!isLoading);
			}
		}

		/**
		 * Hide the dropdown.
		 *
		 * @return {void}
		 */
		function hideDropdown() {
			dropdown.innerHTML = '';
			dropdown.style.display = 'none';
			input.setAttribute('aria-expanded', 'false');
		}

		/**
		 * Select an asset in selection mode.
		 *
		 * @param {Object} item Asset item returned by the REST endpoint.
		 * @return {void}
		 */
		function selectAsset(item) {
			if (!hiddenInput) {
				return;
			}

			hiddenInput.value = item.id;
			input.value = item.title;
			hideDropdown();
		}

		/**
		 * Render dropdown items.
		 *
		 * @param {Array} items Asset items.
		 * @return {void}
		 */
		function renderDropdown(items) {
			setLoading(false);

			if (!Array.isArray(items) || items.length === 0) {
				hideDropdown();
				return;
			}

			var html = '';

			items.forEach(function(item) {
				html += '<div class="almgr-autocomplete-item" data-id="' + escapeHtml(String(item.id)) + '" data-title="' + escapeHtml(item.title) + '">';
				if (widget.mode === 'navigate' && item.permalink) {
					html += '<div class="almgr-autocomplete-title"><a href="' + escapeHtml(item.permalink) + '"><strong>' + escapeHtml(item.title) + '</strong></a></div>';
				} else {
					html += '<div class="almgr-autocomplete-title"><strong>' + escapeHtml(item.title) + '</strong></div>';
				}
				html += '<div class="almgr-autocomplete-description">' + escapeHtml(item.description || '') + '</div>';
				html += '<div class="almgr-autocomplete-meta"><strong>' + escapeHtml(item.structure || '') + '</strong> - <em>' + escapeHtml(item.type || '') + '</em></div>';
				html += '</div>';
			});

			dropdown.innerHTML = html;
			dropdown.style.display = 'block';
			input.setAttribute('aria-expanded', 'true');

			if (widget.mode === 'select') {
				Array.prototype.forEach.call(
					dropdown.querySelectorAll('.almgr-autocomplete-item'),
					function(itemEl, index) {
						itemEl.addEventListener('mousedown', function(event) {
							event.preventDefault();
							selectAsset(items[index]);
						});
					}
				);
			}
		}

		input.addEventListener('input', function() {
			var term = input.value.trim();

			if (hiddenInput) {
				hiddenInput.value = '';
			}

			if (term.length < almgrAutocomplete.minChars) {
				setLoading(false);
				hideDropdown();
				return;
			}

			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(function() {
				setLoading(true);

				fetch(almgrAutocomplete.restUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
						'X-WP-Nonce': almgrAutocomplete.restNonce,
					},
					body: new URLSearchParams({
						term: term,
						nonce: almgrAutocomplete.restNonce
					}).toString(),
					credentials: 'same-origin',
				})
				.then(function(response) {
					return response.json();
				})
				.then(function(data) {
					renderDropdown(data);
				})
				.catch(function() {
					setLoading(false);
					hideDropdown();
				});
			}, 300);
		});

		document.addEventListener('click', function(event) {
			if (!dropdown.contains(event.target) && event.target !== input) {
				hideDropdown();
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function() {
		if (!window.almgrAutocomplete || !Array.isArray(window.almgrAutocomplete.widgets)) {
			return;
		}

		window.almgrAutocomplete.widgets.forEach(function(widget) {
			initWidget(widget);
		});
	});
}());

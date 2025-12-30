document.addEventListener('DOMContentLoaded', function() {

	const input = document.querySelector('#alm_device_search_form input[name="s"]');
	if (!input) return;

	// Create dropdown container
	const dropdown = document.createElement('div');
	dropdown.id = 'alm_device_autocomplete_dropdown';
	dropdown.className = 'alm-autocomplete-dropdown';
	input.parentNode.appendChild(dropdown);

	let debounceTimer;

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	function renderDropdown(items) {
		if (items.length === 0) {
			dropdown.innerHTML = '';
			dropdown.style.display = 'none';
			return;
		}

		let html = '';
		items.forEach(item => {
			html += `<div class="alm-autocomplete-item">
				<div class="alm-autocomplete-title">
					<a href="${escapeHtml(item.permalink)}">
						<strong>${escapeHtml(item.title)}</strong>
					</a>
				</div>
				<div class="alm-autocomplete-description">${escapeHtml(item.description)}</div>
				<div class="alm-autocomplete-meta">
					<strong>${escapeHtml(item.structure)}</strong> - 
					<em>${escapeHtml(item.type)}</em>
				</div>
			</div>`;
		});
		dropdown.innerHTML = html;
		dropdown.style.display = 'block';
	}

	input.addEventListener('input', function() {
		const term = input.value.trim();
		if (term.length < almAutocomplete.minChars) {
			dropdown.style.display = 'none';
			return;
		}

		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(function() {

			console.log("*** nonce:", almAutocomplete.nonce);
			fetch(almAutocomplete.restUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
					'X-WP-Nonce': almAutocomplete.restNonce,
				},
				body: new URLSearchParams({
					term: term,
					nonce: almAutocomplete.restNonce
				})
			})
			.then(res => res.json())
			.then(data => renderDropdown(data))
			.catch(() => dropdown.style.display = 'none');

		}, 300);
	});

	// Hide dropdown when clicking outside
	document.addEventListener('click', function(e) {
		if (!dropdown.contains(e.target) && e.target !== input) {
			dropdown.style.display = 'none';
		}
	});

});

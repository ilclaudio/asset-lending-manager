document.addEventListener('DOMContentLoaded', function () {
	const searchForm = document.getElementById('almgr_asset_search_form');
	const input = document.querySelector('#almgr_asset_search_form input[name="s"]');
	if (!searchForm || !input) return;

	// Usa il dropdown già presente; se non esiste lo crea.
	let dropdown = document.getElementById('almgr_asset_autocomplete_dropdown');
	if (!dropdown) {
		dropdown = document.createElement('div');
		dropdown.id = 'almgr_asset_autocomplete_dropdown';
		dropdown.className = 'almgr-autocomplete-dropdown';
		input.parentNode.appendChild(dropdown);
	}

	let debounceTimer;

	function setLoading(isLoading) {
		searchForm.classList.toggle('almgr-autocomplete-loading', !!isLoading);
	}

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	function renderDropdown(items) {
		setLoading(false);

		if (!items || items.length === 0) {
			dropdown.innerHTML = '';
			dropdown.style.display = 'none';
			input.setAttribute('aria-expanded', 'false');
			return;
		}

		let html = '';
		items.forEach(item => {
			html += `<div class="almgr-autocomplete-item">
				<div class="almgr-autocomplete-title">
					<a href="${escapeHtml(item.permalink)}"><strong>${escapeHtml(item.title)}</strong></a>
				</div>
				<div class="almgr-autocomplete-description">${escapeHtml(item.description)}</div>
				<div class="almgr-autocomplete-meta">
					<strong>${escapeHtml(item.structure)}</strong> - <em>${escapeHtml(item.type)}</em>
				</div>
			</div>`;
		});

		dropdown.innerHTML = html;
		dropdown.style.display = 'block';
		input.setAttribute('aria-expanded', 'true');
	}

	input.addEventListener('input', function () {
		const term = input.value.trim();

		if (term.length < almgrAutocomplete.minChars) {
			setLoading(false);
			dropdown.style.display = 'none';
			input.setAttribute('aria-expanded', 'false');
			return;
		}

		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(function () {
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
				})
			})
			.then(res => res.json())
			.then(data => renderDropdown(data))
			.catch(() => {
				setLoading(false);
				dropdown.style.display = 'none';
			});
		}, 300);
	});

	document.addEventListener('click', function (e) {
		if (!dropdown.contains(e.target) && e.target !== input) {
			dropdown.style.display = 'none';
			input.setAttribute('aria-expanded', 'false');
		}
	});
});

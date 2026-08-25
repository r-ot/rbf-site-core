document.addEventListener('DOMContentLoaded', function() {

	const containers = document.querySelectorAll('[data-rbf-family-dimensions]');

	if (!containers.length) {
		return;
	}

	containers.forEach(function(container) {
		loadFamilyDimensions(container);
	});


	async function loadFamilyDimensions(container) {

		const endpoint = container.dataset.endpoint;
		const list = container.querySelector('[data-rbf-family-dimensions-list]');
		const template = container.querySelector('[data-rbf-family-dimension-template]');
		const toggle = container.querySelector('[data-rbf-family-dimensions-toggle]');

		if (!endpoint || !list || !template) {
			return;
		}

		const visibleCount = parseInt(container.dataset.visibleCount, 10) || 3;

		try {

			const response = await fetch(endpoint, {
				method: 'POST',
			});

			if (!response.ok) {
				throw new Error(`HTTP ${response.status}`);
			}

			const data = await response.json();

			const dimensions = Array.isArray(data.dimensions)
				? data.dimensions
				: [];

			if (!dimensions.length) {
				return;
			}

			dimensions.forEach(function(dimension, index) {

				const fragment = template.content.cloneNode(true);
				const item = fragment.querySelector('[data-rbf-family-dimension-item]');

				if (!item) {
					return;
				}

				item.textContent = dimension;

				if (index >= visibleCount) {
					item.hidden = true;
				}

				if (toggle) {
					list.insertBefore(fragment, toggle);
				} else {
					list.appendChild(fragment);
				}
			});

			if (!toggle || dimensions.length <= visibleCount) {
				return;
			}

			toggle.hidden = false;

			toggle.addEventListener('click', function() {

				const expanded = toggle.getAttribute('aria-expanded') === 'true';

				const items = list.querySelectorAll(
					'[data-rbf-family-dimension-item]'
				);

				items.forEach(function(item, index) {

					if (index >= visibleCount) {
						item.hidden = expanded;
					}
				});

				toggle.setAttribute(
					'aria-expanded',
					expanded ? 'false' : 'true'
				);

				toggle.textContent = expanded
					? toggle.dataset.collapsedText
					: toggle.dataset.expandedText;

				toggle.setAttribute(
					'aria-label',
					expanded
						? toggle.dataset.labelExpand
						: toggle.dataset.labelCollapse
				);
			});

		} catch (error) {
			console.error('RBF Product Family Dimensions:', error);
		}
	}

});
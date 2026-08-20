document.addEventListener('DOMContentLoaded', async () => {

	const containers = document.querySelectorAll('[data-rbf-product-families]');

	if (!containers.length) {
		return;
	}

	const template = document.querySelector('[data-rbf-product-family-template]');

	if (!template) {
		return;
	}

	for (const container of containers) {

		const endpoint = container.dataset.endpoint;

		if (!endpoint) {
			continue;
		}

		try {

			const response = await fetch(endpoint);

			if (!response.ok) {
				throw new Error(`HTTP ${response.status}`);
			}

			const data = await response.json();

			if (!data.items || !Array.isArray(data.items)) {
				continue;
			}

			container.innerHTML = '';

			data.items.forEach((item) => {

				const fragment = template.content.cloneNode(true);

				const link = fragment.querySelector('[data-rbf-family-link]');
				const title = fragment.querySelector('[data-rbf-family-title]');
				const strengths = fragment.querySelector('[data-rbf-family-strengths]');
				const strengthsText = fragment.querySelector('[data-rbf-family-strengths-text]');

				if (link) {
					link.href = item.url;
				}

				if (title) {
					title.textContent = item.name;
				}

				if (strengths && Array.isArray(item.strengths)) {

					item.strengths.forEach((strength) => {

						const badge = document.createElement('span');

						badge.classList.add('rbf-product-family-item__strength');
						badge.textContent = strength;

						strengths.appendChild(badge);
					});
				}

				if (strengthsText && Array.isArray(item.strengths)) {
					strengthsText.textContent = item.strengths.join(', ');
				}

				container.appendChild(fragment);
			});

		} catch (error) {
			console.error('RBF Product Families:', error);
		}
	}
});
document.addEventListener('DOMContentLoaded', async () => {

	const containers = document.querySelectorAll('[data-rbf-product-families]');

	if (!containers.length) {
		return;
	}

	const template = document.querySelector('[data-rbf-product-family-template]');

	if (!template) {
		return;
	}


	const renderStrengths = (strengthsElement, strengthsTextElement, values) => {

		const strengths = Array.isArray(values) ? values : [];

		if (strengthsElement) {
			strengthsElement.innerHTML = '';

			strengths.forEach((strength) => {
				const badge = document.createElement('span');

				badge.classList.add('rbf-product-family-item__strength');
				badge.textContent = strength;

				strengthsElement.appendChild(badge);
			});
		}

		if (strengthsTextElement) {
			strengthsTextElement.textContent = strengths.join(', ');
		}
	};


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

			const familyElements = new Map();


			/*
			 * Erst alle vorhandenen Daten sofort rendern.
			 */
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

				renderStrengths(
					strengths,
					strengthsText,
					item.strengths
				);

				familyElements.set(String(item.id), {
					strengths,
					strengthsText,
				});

				container.appendChild(fragment);
			});


			/*
			 * Ungelernte Families danach sequenziell lernen.
			 */
			const unlearnedItems = data.items.filter((item) => {
				return (
					item.index
					&& item.index.learned === false
					&& item.learn_url
				);
			});


			for (const item of unlearnedItems) {

				try {

					const learnResponse = await fetch(item.learn_url, {
						method: 'POST',
					});

					if (!learnResponse.ok) {
						console.warn(
							'RBF Product Family learn:',
							item.name,
							learnResponse.status
						);

						continue;
					}

					const learnedData = await learnResponse.json();

					const elements = familyElements.get(String(item.id));

					if (!elements) {
						continue;
					}

					renderStrengths(
						elements.strengths,
						elements.strengthsText,
						learnedData.strengths
					);

				} catch (error) {
					console.error(
						'RBF Product Family learn:',
						item.name,
						error
					);
				}
			}

		} catch (error) {
			console.error('RBF Product Families:', error);
		}
	}

});
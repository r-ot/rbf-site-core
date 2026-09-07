(function() {

	'use strict';

	var cartStore = 'wc/store/cart';
	var namespace = 'rbf-site-core';
	var maxLength = 400;
	var syncTimeout = null;

	function getCartData() {

		if (
			!window.wp
			|| !window.wp.data
		) {
			return null;
		}

		return window.wp.data.select(cartStore).getCartData();
	}

	function getCartItem(cartData, cartItemKey) {

		if (
			!cartData
			|| !Array.isArray(cartData.items)
		) {
			return null;
		}

		return cartData.items.find(function(item) {
			return item.key === cartItemKey;
		}) || null;
	}

	function getCartItemNote(cartItem) {

		if (
			!cartItem
			|| !cartItem.extensions
			|| !cartItem.extensions[namespace]
		) {
			return '';
		}

		return cartItem.extensions[namespace].cart_item_note || '';
	}

	function saveCartItemNote(textarea) {

		var cartItemKey = textarea.dataset.cartItemKey;
		var cartItemNote = textarea.value.substring(0, maxLength);

		if (
			!cartItemKey
			|| !window.wc
			|| !window.wc.blocksCheckout
			|| !window.wc.blocksCheckout.extensionCartUpdate
		) {
			return;
		}

		if (textarea.dataset.savedValue === cartItemNote) {
			return;
		}

		window.wc.blocksCheckout.extensionCartUpdate({
			namespace: namespace,
			data: {
				cart_item_key: cartItemKey,
				cart_item_note: cartItemNote
			}
		}).then(function() {

			textarea.dataset.savedValue = cartItemNote;

		}).catch(function(error) {

			console.error(
				'RBF cart item note update failed.',
				error
			);

		});
	}

	function createCartItemNote(row, cartItem) {

		var productWrap = row.querySelector('.wc-block-cart-item__wrap');
		var quantity = row.querySelector('.wc-block-cart-item__quantity');

		if (!productWrap) {
			return;
		}

		var existing = row.querySelector('[data-rbf-cart-item-note]');

		if (existing) {
			return;
		}

		var cartItemNote = getCartItemNote(cartItem);

		var noteWrap = document.createElement('div');
		noteWrap.className = 'rbf-cart-item-note';
		noteWrap.dataset.rbfCartItemNote = '';

		var textarea = document.createElement('textarea');
		textarea.className = 'rbf-cart-item-note__input';
		textarea.dataset.cartItemKey = cartItem.key;
		textarea.dataset.savedValue = cartItemNote;
		textarea.maxLength = maxLength;
		textarea.rows = 2;
		textarea.placeholder = 'Notiz zur Position';
		textarea.value = cartItemNote;

		textarea.addEventListener('change', function() {
			saveCartItemNote(textarea);
		});

		noteWrap.appendChild(textarea);

		if (quantity) {
			quantity.insertAdjacentElement('afterend', noteWrap);
		} else {
			productWrap.appendChild(noteWrap);
		}
	}

	function syncCartItemNotes() {

		var cartData = getCartData();

		// console.log(
		// 	'RBF cart item notes sync',
		// 	cartData,
		// 	document.querySelectorAll('.wc-block-cart-items__row[data-cart-item-key]').length
		// );

		if (!cartData) {
			return;
		}

		document.querySelectorAll(
			'.wc-block-cart-items__row[data-cart-item-key]'
		).forEach(function(row) {

			var cartItemKey = row.dataset.cartItemKey;
			var cartItem = getCartItem(cartData, cartItemKey);

			if (!cartItem) {
				return;
			}

			createCartItemNote(row, cartItem);
		});
	}

	function scheduleSync() {

		window.clearTimeout(syncTimeout);

		syncTimeout = window.setTimeout(function() {
			syncCartItemNotes();
		}, 50);
	}



	function initCartItemNotes() {

		var cartData = getCartData();

		syncCartItemNotes();

		window.wp.data.subscribe(
			function() {
				scheduleSync();
			},
			cartStore
		);

		/*
		* WooCommerce renders the Cart Block asynchronously via React.
		* If cart rows already exist, no DOM observer is needed.
		*/
		if (
			document.querySelector(
				'.wc-block-cart-items__row[data-cart-item-key]'
			)
		) {
			return;
		}

		/*
		* Empty cart: there will be no cart rows to wait for.
		*/
		if (
			cartData
			&& Array.isArray(cartData.items)
			&& cartData.items.length === 0
		) {
			return;
		}


		console.log('[rbf][cart-note] init');
		/*
		* Initial Woo Cart mount only.
		* Stop observing the document as soon as the first cart row exists.
		*/
		var observer = new MutationObserver(function() {

			if (
				!document.querySelector(
					'.wc-block-cart-items__row[data-cart-item-key]'
				)
			) {
				return;
			}


			console.log('[rbf][cart-note] observer disconnect');

			observer.disconnect();

			scheduleSync();
		});

		observer.observe(
			document.body,
			{
				childList: true,
				subtree: true
			}
		);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initCartItemNotes);
	} else {
		initCartItemNotes();
	}

})();
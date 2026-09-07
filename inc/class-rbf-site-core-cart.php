<?php

defined('ABSPATH') || exit;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

class RbfSiteCoreCart {

	private const STORE_API_NAMESPACE = 'rbf-site-core';
	private const CART_ITEM_NOTE_KEY = 'cart_item_note';

	public function __construct() {

		$this->init_hooks();

	}

	private function init_hooks() {

		add_action(
			'woocommerce_blocks_loaded',
			[$this, 'register_store_api_extensions']
		);

		add_action(
			'wp_enqueue_scripts',
			[$this, 'enqueue_assets']
		);

	}


	public function enqueue_assets() {

		if (
			! function_exists('is_cart')
			|| ! is_cart()
		) {
			return;
		}

		wp_enqueue_script(
			'rbf-site-core-cart-item-note',
			RBF_SITE_CORE_URL . 'assets/js/cart-item-note.js',
			[
				'wp-data',
				'wc-blocks-data-store',
				'wc-blocks-checkout',
			],
			RBF_SITE_CORE_VERSION,
			true
		);

	}



	public function register_store_api_extensions() {

		if (
			! function_exists('woocommerce_store_api_register_endpoint_data')
			|| ! function_exists('woocommerce_store_api_register_update_callback')
		) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			[
				'endpoint'        => CartItemSchema::IDENTIFIER,
				'namespace'       => self::STORE_API_NAMESPACE,
				'data_callback'   => [$this, 'get_cart_item_extension_data'],
				'schema_callback' => [$this, 'get_cart_item_extension_schema'],
				'schema_type'     => ARRAY_A,
			]
		);

		woocommerce_store_api_register_update_callback(
			[
				'namespace' => self::STORE_API_NAMESPACE,
				'callback'  => [$this, 'update_cart_item_extension_data'],
			]
		);

	}

	public function get_cart_item_extension_data($cart_item) {

		return [
			self::CART_ITEM_NOTE_KEY => isset($cart_item[self::CART_ITEM_NOTE_KEY])
				? (string) $cart_item[self::CART_ITEM_NOTE_KEY]
				: '',
		];

	}

	public function get_cart_item_extension_schema() {

		return [
			'properties' => [
				self::CART_ITEM_NOTE_KEY => [
					'description' => 'Customer note attached to this cart item.',
					'type'        => 'string',
					'readonly'    => true,
				],
			],
		];

	}

	public function update_cart_item_extension_data($data) {

		if (
			! is_array($data)
			|| empty($data['cart_item_key'])
			|| ! array_key_exists(self::CART_ITEM_NOTE_KEY, $data)
			|| ! function_exists('WC')
			|| ! WC()->cart
		) {
			return;
		}

		$cart_item_key = sanitize_text_field($data['cart_item_key']);
		$cart_item_note = sanitize_textarea_field(
			(string) $data[self::CART_ITEM_NOTE_KEY]
		);
		$cart_item_note = mb_substr($cart_item_note, 0, 400);

		$cart_contents = WC()->cart->get_cart_contents();

		if (! isset($cart_contents[$cart_item_key])) {
			return;
		}

		$cart_contents[$cart_item_key][self::CART_ITEM_NOTE_KEY] = $cart_item_note;

		WC()->cart->set_cart_contents($cart_contents);
		WC()->cart->set_session();

	}

}

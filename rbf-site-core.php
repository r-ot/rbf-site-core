<?php
/**
 * Plugin Name: RBF Site Core
 * Description: Site-spezifische Datenstrukturen und Fallback-Registrierungen.
 * Version: 0.2.7
 * Author: RBF
 */

defined('ABSPATH') || exit;

define('RBF_SITE_CORE_VERSION', '0.2.7');
define('RBF_SITE_CORE_FILE', __FILE__);
define('RBF_SITE_CORE_DIR', plugin_dir_path(__FILE__));
define('RBF_SITE_CORE_URL', plugin_dir_url(__FILE__));

require_once RBF_SITE_CORE_DIR . 'inc/class-rbf-site-core.php';

add_action('plugins_loaded', function() {
	RbfSiteCore::get_instance();
});

//HOTFIX 404 problem in woo 1.11.0
/*
 * WooCommerce 11.1.0 compatibility.
 * Full cart pages may initialize the cart refresh store without restUrl,
 * causing requests to /undefinedwc/store/v1/cart.
 *
 * Remove after upgrading beyond WooCommerce 11.1.0.
 */
add_action('wp_enqueue_scripts', function() {

	if (
		! function_exists('is_cart') ||
		! is_cart() ||
		! function_exists('wp_interactivity_state') ||
		! defined('WC_VERSION') ||
		'11.1.0' !== WC_VERSION
	) {
		return;
	}

	wp_interactivity_state(
		'woocommerce',
		[
			'restUrl' => get_rest_url(),
		]
	);

}, 20);


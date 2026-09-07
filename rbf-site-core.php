<?php
/**
 * Plugin Name: RBF Site Core
 * Description: Site-spezifische Datenstrukturen und Fallback-Registrierungen.
 * Version: 0.2.6
 * Author: RBF
 */

defined('ABSPATH') || exit;

define('RBF_SITE_CORE_VERSION', '0.2.6');
define('RBF_SITE_CORE_FILE', __FILE__);
define('RBF_SITE_CORE_DIR', plugin_dir_path(__FILE__));
define('RBF_SITE_CORE_URL', plugin_dir_url(__FILE__));

require_once RBF_SITE_CORE_DIR . 'inc/class-rbf-site-core.php';

add_action('plugins_loaded', function() {
	RbfSiteCore::get_instance();
});
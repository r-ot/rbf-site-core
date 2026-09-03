<?php

defined('ABSPATH') || exit;

class RbfSiteCore {

	private static $instance = null;

	public static function get_instance() {

		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {

		require_once RBF_SITE_CORE_DIR . 'inc/data/class-rbf-site-core-data-keys.php';
		require_once RBF_SITE_CORE_DIR . 'inc/data/class-rbf-site-core-products.php';
		require_once RBF_SITE_CORE_DIR . 'inc/data/class-rbf-site-core-family-index.php';

		require_once RBF_SITE_CORE_DIR . 'inc/admin/class-rbf-site-core-family-admin.php';

		require_once RBF_SITE_CORE_DIR . 'inc/class-rbf-site-core-rest.php';
		require_once RBF_SITE_CORE_DIR . 'inc/class-rbf-site-core-shortcodes.php';

		require_once RBF_SITE_CORE_DIR . 'inc/class-rbf-site-core-b2b-branding.php';

		$this->init_hooks();

		new RbfSiteCoreFamilyAdmin();
		new RbfSiteCoreRest();
		new RbfSiteCoreShortcodes();

		new RbfSiteCoreB2BBranding();
	}

	private function init_hooks() {

		/*
		 * Late priority:
		 * Andere Plugins bekommen zuerst die Möglichkeit,
		 * product_family selbst zu registrieren.
		 */
		add_action('init', [$this, 'register_product_family_taxonomy'], 99);

		add_action('wp_enqueue_scripts', [$this, 'register_assets']);
	}

	public function register_assets() {
		wp_register_script(
			'rbf-site-core-product-family-data',
			RBF_SITE_CORE_URL . 'assets/js/product-family-data.js',
			[],
			RBF_SITE_CORE_VERSION,
			true
		);
	}

	public function register_product_family_taxonomy() {
		$taxonomy = RbfSiteCoreDataKeys::get_taxonomy_key(
			RbfSiteCoreDataKeys::TAXONOMY_PRODUCT_FAMILY
		);

		if ($taxonomy === '') {
			return;
		}

		if (taxonomy_exists($taxonomy)) {
			return;
		}

		$labels = [
			'name'              => 'Product Families',
			'singular_name'     => 'Product Family',
			'search_items'      => 'Search Product Families',
			'all_items'         => 'All Product Families',
			'parent_item'       => 'Parent Product Family',
			'parent_item_colon' => 'Parent Product Family:',
			'edit_item'         => 'Edit Product Family',
			'update_item'       => 'Update Product Family',
			'add_new_item'      => 'Add New Product Family',
			'new_item_name'     => 'New Product Family Name',
			'menu_name'         => 'Product Families',
		];

		register_taxonomy(
			$taxonomy,
			['product'],
			[
				'labels'            => $labels,
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => [
					'slug' => 'product-family',
				],
			]
		);
	}
}
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
		$this->init_hooks();

		//rest class
		require_once RBF_SITE_CORE_DIR . 'inc/class-rbf-site-core-rest.php';
		require_once RBF_SITE_CORE_DIR . 'inc/class-rbf-site-core-shortcodes.php';

		new RbfSiteCoreRest();
		new RbfSiteCoreShortcodes();
	}

	private function init_hooks() {

		/*
		 * Late priority:
		 * Andere Plugins bekommen zuerst die Möglichkeit,
		 * product_family selbst zu registrieren.
		 */
		add_action('init', [$this, 'register_product_family_taxonomy'], 99);
	}

	public function register_product_family_taxonomy() {

		if (taxonomy_exists('product_family')) {
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
			'product_family',
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
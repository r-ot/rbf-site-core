<?php

defined('ABSPATH') || exit;

class RbfSiteCoreShortcodes {

	private $product_family_template_required = false;

	public function __construct() {
		//shortcode registrieren
		add_shortcode('rbf_product_families', [$this, 'render_product_families']);
		//template in den wp footer rendern
		add_action('wp_footer', [$this, 'render_product_family_template']);
	}

	public function render_product_families($atts = []) {

		$atts = shortcode_atts(
			[],
			$atts,
			'rbf_product_families'
		);

		wp_enqueue_script(
			'rbf-site-core-product-families',
			RBF_SITE_CORE_URL . 'assets/js/product-families.js',
			[],
			RBF_SITE_CORE_VERSION,
			true
		);

		$endpoint = rest_url('rbf-site-core/v1/product-families');

		//set template required true - guard for using template
		$this->product_family_template_required = true;

		$output  = '';
		$output .= '<div class="rbf-product-families" data-rbf-product-families data-endpoint="' . esc_url($endpoint) . '">';
		$output .= '</div>';

		return $output;
	}

	//remder family item using template from /templates/product-family-item.php
	public function render_product_family_template() {
		if (!$this->product_family_template_required) {
			return;
		}

		$template = RBF_SITE_CORE_DIR . 'templates/product-family-item.php';

		//apply filters for theme
		$template = apply_filters(
			'rbf_product_family_template',
			$template
		);

		if (!$template || !is_readable($template)) {
			return;
		}

		include $template;
	}
}
<?php

defined('ABSPATH') || exit;

class RbfSiteCoreRest {

	public function __construct() {
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function register_routes() {

		register_rest_route(
			'rbf-site-core/v1',
			'/product-families',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_product_families'],
				'permission_callback' => '__return_true',
			]
		);
	}

	public function get_product_families() {

		$terms = get_terms([
			'taxonomy'   => 'product_family',
			'hide_empty' => false,
		]);

		if (is_wp_error($terms)) {
			return new WP_Error(
				'rbf_product_families_error',
				$terms->get_error_message(),
				[
					'status' => 500,
				]
			);
		}

		$items = [];

		foreach ($terms as $term) {

			$term_link = get_term_link($term);

			if (is_wp_error($term_link)) {
				continue;
			}

			$items[] = [
				'id'    => (int) $term->term_id,
				'name'  => $term->name,
				'slug'  => $term->slug,
				'url'   => $term_link,
				'count' => (int) $term->count,
				'strengths' => $this->get_product_family_strengths((int) $term->term_id),
			];
		}

		return rest_ensure_response([
			'items' => $items,
		]);
	}

	private function get_product_family_strengths($term_id) {
		if (!function_exists('wc_get_product')) {
			return [];
		}
		$product_ids = get_posts([
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => [
				[
					'taxonomy' => 'product_family',
					'field'    => 'term_id',
					'terms'    => $term_id,
				],
			],
		]);

		$strengths = [];

		foreach ($product_ids as $product_id) {
			$product = wc_get_product($product_id);
			if (!$product) {
				continue;
			}

			$attributes = $product->get_attributes();
			foreach ($attributes as $attribute) {
				$attribute_name = sanitize_title($attribute->get_name());
				if ($attribute_name !== 'gliederstaerke') {
					continue;
				}
				foreach ($attribute->get_options() as $option) {
					$option = trim((string) $option);
					if ($option !== '') {
						$strengths[] = $option;
					}
				}
			}
		}

		$strengths = array_values(array_unique($strengths));
		natsort($strengths);

		return array_values($strengths);
	}
}
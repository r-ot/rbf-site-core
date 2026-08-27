<?php

defined('ABSPATH') || exit;

class RbfSiteCoreRest {

	public function __construct() {
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function register_routes() {

		//gET families
		register_rest_route(
			'rbf-site-core/v1',
			'/product-families',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_product_families'],
				'permission_callback' => '__return_true',
			]
		);

		//post learn
		register_rest_route(
			'rbf-site-core/v1',
			'/product-families/(?P<term_id>\d+)/learn',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'learn_product_family'],
				'permission_callback' => '__return_true',
			]
		);
	}


	public function get_product_families() {

		$taxonomy = RbfSiteCoreDataKeys::get_taxonomy_key(
			RbfSiteCoreDataKeys::TAXONOMY_PRODUCT_FAMILY
		);

		if ($taxonomy === '') {
			return new WP_Error(
				'rbf_product_family_taxonomy_missing',
				'Product Family taxonomy mapping is missing.',
				[
					'status' => 500,
				]
			);
		}

		$terms = get_terms([
			'taxonomy'   => $taxonomy,
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

			$index = RbfSiteCoreFamilyIndex::get((int) $term->term_id);

			$is_learned = isset(
				$index['version'],
				$index['learned_at'],
				$index['values']
			);

			$values = $is_learned && is_array($index['values'])
				? $index['values']
				: [];

			$item = [
				'id'    => (int) $term->term_id,
				'name'  => $term->name,
				'slug'  => $term->slug,
				'url'   => $term_link,
				'count' => (int) $term->count,

				'learn_url' => rest_url(
					'rbf-site-core/v1/product-families/' . (int) $term->term_id . '/learn'
				),

				'strengths' => $values[
					RbfSiteCoreDataKeys::ATTRIBUTE_CHAIN_STRENGTH
				] ?? [],

				'dimensions' => $values[
					RbfSiteCoreDataKeys::ATTRIBUTE_TIRE_DIMENSION
				] ?? [],

				'index' => [
					'learned'       => $is_learned,
					'learned_at'    => $is_learned
						? (int) $index['learned_at']
						: null,
					'product_count' => $is_learned
						? (int) ($index['product_count'] ?? 0)
						: 0,
				],
			];

			$item = apply_filters(
				'rbf_site_core_product_family_rest_item',
				$item,
				$term
			);

			$items[]=$item;
		}

		return rest_ensure_response([
			'items' => $items,
		]);
	}



	//LEARN
	//LEARN
	//LEARN
	//LEARN
	public function learn_product_family(WP_REST_Request $request) {

		$term_id = absint($request->get_param('term_id'));

		if (!$term_id) {
			return new WP_Error(
				'rbf_product_family_invalid_term',
				'Invalid Product Family term ID.',
				[
					'status' => 400,
				]
			);
		}

		$taxonomy = RbfSiteCoreDataKeys::get_taxonomy_key(
			RbfSiteCoreDataKeys::TAXONOMY_PRODUCT_FAMILY
		);

		$term = get_term($term_id, $taxonomy);

		if (is_wp_error($term) || !$term instanceof WP_Term) {
			return new WP_Error(
				'rbf_product_family_not_found',
				'Product Family not found.',
				[
					'status' => 404,
				]
			);
		}

		$index = RbfSiteCoreFamilyIndex::learn($term_id);

		if (is_wp_error($index)) {
			return $index;
		}

		$values = isset($index['values']) && is_array($index['values'])
			? $index['values']
			: [];

		return rest_ensure_response([
			'id' => $term_id,

			'strengths' => $values[
				RbfSiteCoreDataKeys::ATTRIBUTE_CHAIN_STRENGTH
			] ?? [],

			'dimensions' => $values[
				RbfSiteCoreDataKeys::ATTRIBUTE_TIRE_DIMENSION
			] ?? [],

			'index' => [
				'learned'       => true,
				'learned_at'    => (int) ($index['learned_at'] ?? 0),
				'product_count' => (int) ($index['product_count'] ?? 0),
			],
		]);
	}

}
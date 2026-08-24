<?php

defined('ABSPATH') || exit;

class RbfSiteCoreProducts {

	public static function get_ids_by_term($taxonomy, $term_id) {

		$taxonomy = RbfSiteCoreDataKeys::get_taxonomy_key($taxonomy);
		$term_id = absint($term_id);

		if ($taxonomy === '' || !$term_id) {
			return [];
		}

		return get_posts([
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => [
				[
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_id,
				],
			],
		]);
	}


	public static function get_attribute_options($product, $attribute) {

		if (!function_exists('wc_get_product')) {
			return [];
		}

		if (is_numeric($product)) {
			$product = wc_get_product((int) $product);
		}

		if (!$product || !is_a($product, 'WC_Product')) {
			return [];
		}

		$options = [];

		foreach ($product->get_attributes() as $product_attribute) {

			if (!RbfSiteCoreDataKeys::matches_attribute(
				$product_attribute->get_name(),
				$attribute
			)) {
				continue;
			}

			foreach ($product_attribute->get_options() as $option) {

				$option = trim((string) $option);

				if ($option !== '') {
					$options[] = $option;
				}
			}
		}

		return $options;
	}


	//unser eigener kleiner "cache" für die reifendimensionen
	public static function get_attribute_options_by_products($product_ids, $attributes) {

		if (
			!function_exists('wc_get_product')
			|| !is_array($product_ids)
			|| !is_array($attributes)
		) {
			return [];
		}

		$values = [];

		foreach ($attributes as $attribute) {

			if (RbfSiteCoreDataKeys::get_attribute_key($attribute) === '') {
				continue;
			}

			$values[$attribute] = [];
		}

		if (empty($values)) {
			return [];
		}

		foreach ($product_ids as $product_id) {

			$product = wc_get_product($product_id);

			if (!$product) {
				continue;
			}

			foreach ($product->get_attributes() as $product_attribute) {

				foreach (array_keys($values) as $attribute) {

					if (!RbfSiteCoreDataKeys::matches_attribute(
						$product_attribute->get_name(),
						$attribute
					)) {
						continue;
					}

					foreach ($product_attribute->get_options() as $option) {

						$option = trim((string) $option);

						if ($option !== '') {
							$values[$attribute][] = $option;
						}
					}
				}
			}
		}

		foreach ($values as $attribute => $options) {

			$options = array_values(array_unique($options));

			natsort($options);

			$values[$attribute] = array_values($options);
		}

		return $values;
	}
	//methode zum cachen






	public static function get_attribute_options_by_term($taxonomy, $term_id, $attribute) {

		$product_ids = self::get_ids_by_term($taxonomy, $term_id);

		if (empty($product_ids)) {
			return [];
		}

		$options = [];

		foreach ($product_ids as $product_id) {
			$options = array_merge(
				$options,
				self::get_attribute_options($product_id, $attribute)
			);
		}

		$options = array_values(array_unique($options));

		natsort($options);

		return array_values($options);
	}
}
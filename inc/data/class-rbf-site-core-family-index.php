<?php

defined('ABSPATH') || exit;

class RbfSiteCoreFamilyIndex {

	private const META_KEY = '_rbf_family_index';
	private const VERSION = 1;


	private const LOCK_PREFIX = '_rbf_family_index_lock_';
	private const LOCK_TTL = 300;



	public static function get($term_id) {

		$term_id = absint($term_id);

		if (!$term_id) {
			return [];
		}

		$index = get_term_meta(
			$term_id,
			self::META_KEY,
			true
		);

		return is_array($index) ? $index : [];
	}



	public static function delete($term_id) {

		$term_id = absint($term_id);

		if (!$term_id) {
			return new WP_Error(
				'rbf_family_index_invalid_term',
				'Invalid Product Family term ID.'
			);
		}

		delete_term_meta(
			$term_id,
			self::META_KEY
		);

		return true;
	}



	public static function rebuild($term_id) {

		$term_id = absint($term_id);

		if (!$term_id) {
			return new WP_Error(
				'rbf_family_index_invalid_term',
				'Invalid Product Family term ID.'
			);
		}

		$taxonomy = RbfSiteCoreDataKeys::get_taxonomy_key(
			RbfSiteCoreDataKeys::TAXONOMY_PRODUCT_FAMILY
		);

		if ($taxonomy === '') {
			return new WP_Error(
				'rbf_family_index_missing_taxonomy',
				'Product Family taxonomy mapping is missing.'
			);
		}

		$term = get_term($term_id, $taxonomy);

		if (is_wp_error($term) || !$term instanceof WP_Term) {
			return new WP_Error(
				'rbf_family_index_term_not_found',
				'Product Family term not found.'
			);
		}

		$product_ids = RbfSiteCoreProducts::get_ids_by_term(
			RbfSiteCoreDataKeys::TAXONOMY_PRODUCT_FAMILY,
			$term_id
		);

		$values = RbfSiteCoreProducts::get_attribute_options_by_products(
			$product_ids,
			[
				RbfSiteCoreDataKeys::ATTRIBUTE_TIRE_DIMENSION,
				RbfSiteCoreDataKeys::ATTRIBUTE_CHAIN_STRENGTH,
			]
		);

		$index = [
			'version'       => self::VERSION,
			'learned_at'    => time(),
			'product_count' => count($product_ids),
			'values'        => [
				RbfSiteCoreDataKeys::ATTRIBUTE_TIRE_DIMENSION =>
					$values[RbfSiteCoreDataKeys::ATTRIBUTE_TIRE_DIMENSION] ?? [],

				RbfSiteCoreDataKeys::ATTRIBUTE_CHAIN_STRENGTH =>
					$values[RbfSiteCoreDataKeys::ATTRIBUTE_CHAIN_STRENGTH] ?? [],
			],
		];

		$result = update_term_meta(
			$term_id,
			self::META_KEY,
			$index
		);

		if (is_wp_error($result)) {
			return $result;
		}

		return $index;
	}





	//auto learn und learn lock
	public static function learn($term_id) {
		$term_id = absint($term_id);
		if (!$term_id) {
			return new WP_Error(
				'rbf_family_index_invalid_term',
				'Invalid Product Family term ID.'
			);
		}
		$index = self::get($term_id);
		if (!empty($index)) {
			return $index;
		}
		if (!self::acquire_lock($term_id)) {
			return new WP_Error(
				'rbf_family_index_locked',
				'Product Family data is currently being learned.',
				[
					'status' => 409,
				]
			);
		}
		try {
			/*
			* Nach dem Lock nochmals prüfen.
			* Ein paralleler Request könnte den Index inzwischen erzeugt haben.
			*/
			$index = self::get($term_id);

			if (!empty($index)) {
				return $index;
			}

			return self::rebuild($term_id);

		} finally {
			self::release_lock($term_id);
		}
	}


	private static function acquire_lock($term_id) {
		$lock_key = self::LOCK_PREFIX . absint($term_id);
		$now = time();

		if (add_option($lock_key, $now, '', false)) {
			return true;
		}

		$locked_at = (int) get_option($lock_key, 0);
		if (
			$locked_at > 0
			&& ($now - $locked_at) > self::LOCK_TTL
		) {
			delete_option($lock_key);
			return add_option($lock_key, $now, '', false);
		}
		return false;
	}


	private static function release_lock($term_id) {
		delete_option(
			self::LOCK_PREFIX . absint($term_id)
		);
	}
}

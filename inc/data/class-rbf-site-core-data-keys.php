<?php

defined('ABSPATH') || exit;

class RbfSiteCoreDataKeys {

	public const ATTRIBUTE_TIRE_DIMENSION = 'tire_dimension';
	public const ATTRIBUTE_CHAIN_STRENGTH = 'chain_strength';

	public const TAXONOMY_PRODUCT_FAMILY = 'product_family';


	private const ATTRIBUTE_KEYS = [
		self::ATTRIBUTE_TIRE_DIMENSION => [
			'tire_dimension_fit',
			'dimension',
		],
		self::ATTRIBUTE_CHAIN_STRENGTH => [
			'gliederstaerke',
		],
];


	private const TAXONOMY_KEYS = [
		self::TAXONOMY_PRODUCT_FAMILY => 'product_family',
	];


	public static function get_attribute_key($key) {

		$keys = self::get_attribute_keys($key);

		return $keys[0] ?? '';
	}


	public static function get_attribute_keys($key) {

		$keys = self::ATTRIBUTE_KEYS[$key] ?? [];

		return is_array($keys) ? $keys : [];
	}


	public static function get_taxonomy_key($key) {

		return self::TAXONOMY_KEYS[$key] ?? '';
	}


	public static function get_matching_attribute_key($attribute_name, $key) {

		$attribute_name = sanitize_title($attribute_name);

		if (str_starts_with($attribute_name, 'pa_')) {
			$attribute_name = substr($attribute_name, 3);
		}

		foreach (self::get_attribute_keys($key) as $mapped_key) {

			$mapped_key = sanitize_title($mapped_key);

			if ($attribute_name === $mapped_key) {
				return $mapped_key;
			}
		}

		return '';
	}


	public static function matches_attribute($attribute_name, $key) {

		return self::get_matching_attribute_key($attribute_name, $key) !== '';
	}
}

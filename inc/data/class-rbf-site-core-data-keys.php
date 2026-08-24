<?php

defined('ABSPATH') || exit;

class RbfSiteCoreDataKeys {

	public const ATTRIBUTE_TIRE_DIMENSION = 'tire_dimension';
	public const ATTRIBUTE_CHAIN_STRENGTH = 'chain_strength';

	public const TAXONOMY_PRODUCT_FAMILY = 'product_family';


	private const ATTRIBUTE_KEYS = [
		self::ATTRIBUTE_TIRE_DIMENSION => 'tire_dimension_fit',
		self::ATTRIBUTE_CHAIN_STRENGTH => 'gliederstaerke',
	];


	private const TAXONOMY_KEYS = [
		self::TAXONOMY_PRODUCT_FAMILY => 'product_family',
	];


	public static function get_attribute_key($key) {

		return self::ATTRIBUTE_KEYS[$key] ?? '';
	}


	public static function get_taxonomy_key($key) {

		return self::TAXONOMY_KEYS[$key] ?? '';
	}


	public static function matches_attribute($attribute_name, $key) {

		$mapped_key = self::get_attribute_key($key);

		if ($mapped_key === '') {
			return false;
		}

		return sanitize_title($attribute_name) === sanitize_title($mapped_key);
	}
}

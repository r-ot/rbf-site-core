<?php

defined('ABSPATH') || exit;

class RbfSiteCoreFamilyAdmin {

	private $taxonomy = '';


	public function __construct() {

		$this->taxonomy = RbfSiteCoreDataKeys::get_taxonomy_key(
			RbfSiteCoreDataKeys::TAXONOMY_PRODUCT_FAMILY
		);

		if ($this->taxonomy === '') {
			return;
		}

		add_action(
			$this->taxonomy . '_edit_form_fields',
			[$this, 'render_family_index'],
			20,
			2
		);

		add_action(
			'admin_post_rbf_rebuild_family_index',
			[$this, 'handle_rebuild']
		);

		add_action(
			'admin_post_rbf_delete_family_index',
			[$this, 'handle_delete']
		);
	}



	public function render_family_index($term, $taxonomy) {

		if (!$term instanceof WP_Term) {
			return;
		}

		$index = RbfSiteCoreFamilyIndex::get($term->term_id);

		$learned_at = !empty($index['learned_at'])
			? wp_date('d.m.Y H:i:s', (int) $index['learned_at'])
			: 'Noch nicht gelernt';

		$learned_product_count = isset($index['product_count'])
			? (int) $index['product_count']
			: 0;

		$current_product_count = (int) $term->count;

		$dimensions = $index['values'][RbfSiteCoreDataKeys::ATTRIBUTE_TIRE_DIMENSION] ?? [];
		$strengths = $index['values'][RbfSiteCoreDataKeys::ATTRIBUTE_CHAIN_STRENGTH] ?? [];

		$is_stale = !empty($index)
			&& $learned_product_count !== $current_product_count;


		//absichtlich admin-post.php lin kstatt form submit... weil gesamter bereich schon in einer form mit submit- html
		$rebuild_url = wp_nonce_url(
			add_query_arg(
				[
					'action'  => 'rbf_rebuild_family_index',
					'term_id' => $term->term_id,
				],
				admin_url('admin-post.php')
			),
			'rbf_rebuild_family_index_' . $term->term_id
		);

		$delete_url = wp_nonce_url(
			add_query_arg(
				[
					'action'  => 'rbf_delete_family_index',
					'term_id' => $term->term_id,
				],
				admin_url('admin-post.php')
			),
			'rbf_delete_family_index_' . $term->term_id
		);


		?>
		<tr class="form-field">
			<th scope="row">
				<label>SOMA Family-Daten</label>
			</th>

			<td>
				<?php if (isset($_GET['rbf_family_index']) && $_GET['rbf_family_index'] === 'updated') : ?>
					<div class="notice notice-success inline">
						<p>Family-Daten wurden neu gelernt.</p>
					</div>
				<?php endif; ?>

				<p>
					<strong>Letztes Lernen:</strong>
					<?php echo esc_html($learned_at); ?>
				</p>

				<p>
					<strong>Produkte beim Lernen:</strong>
					<?php echo esc_html($learned_product_count); ?><br>

					<strong>Produkte aktuell:</strong>
					<?php echo esc_html($current_product_count); ?>
				</p>

				<p>
					<strong>Reifendimensionen:</strong>
					<?php echo esc_html(count($dimensions)); ?><br>

					<strong>Gliederstärken:</strong>
					<?php echo esc_html(implode(', ', $strengths)); ?>
				</p>

				<?php if ($is_stale) : ?>
					<p>
						<strong>⚠ Die gespeicherten Family-Daten könnten veraltet sein.</strong>
					</p>
				<?php endif; ?>

				<p>
					<a
						class="button button-secondary"
						href="<?php echo esc_url($rebuild_url); ?>"
					>
						Family-Daten neu lernen
					</a>

					<?php if (!empty($index)) : ?>
						<a
							class="button"
							href="<?php echo esc_url($delete_url); ?>"
							onclick="return confirm('Gelernte Family-Daten wirklich löschen?');"
						>
							Gelernte Daten löschen
						</a>
					<?php endif; ?>
				</p>
				<?php
				if (defined('WP_DEBUG') && WP_DEBUG) : ?>
					<pre style="
						margin: 16px 0;
						padding: 16px;
						max-width: 100%;
						overflow: auto;
						background: #242424;
						color: #7CFC00;
						font-family: monospace;
						font-size: 12px;
						line-height: 1.5;
					"><?php echo esc_html(print_r($index, true)); ?></pre>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}


	public function handle_rebuild() {

		$term_id = isset($_GET['term_id'])
			? absint($_GET['term_id'])
			: 0;

		if (!$term_id) {
			wp_die('Invalid Product Family term.');
		}

		check_admin_referer(
			'rbf_rebuild_family_index_' . $term_id
		);

		$taxonomy = RbfSiteCoreDataKeys::get_taxonomy_key(
			RbfSiteCoreDataKeys::TAXONOMY_PRODUCT_FAMILY
		);

		$taxonomy_object = get_taxonomy($taxonomy);

		if (
			!$taxonomy_object
			|| !current_user_can($taxonomy_object->cap->manage_terms)
		) {
			wp_die('You are not allowed to manage Product Families.');
		}

		$term = get_term($term_id, $taxonomy);

		if (is_wp_error($term) || !$term instanceof WP_Term) {
			wp_die('Product Family term not found.');
		}

		$result = RbfSiteCoreFamilyIndex::rebuild($term_id);

		if (is_wp_error($result)) {
			wp_die(esc_html($result->get_error_message()));
		}

		$redirect_url = get_edit_term_link(
			$term_id,
			$taxonomy,
			'product'
		);

		$redirect_url = add_query_arg(
			'rbf_family_index',
			'updated',
			$redirect_url
		);

		wp_safe_redirect($redirect_url);
		exit;
	}




	public function handle_delete() {

		$term_id = isset($_GET['term_id'])
			? absint($_GET['term_id'])
			: 0;

		if (!$term_id) {
			wp_die('Invalid Product Family term.');
		}

		check_admin_referer(
			'rbf_delete_family_index_' . $term_id
		);

		$taxonomy = RbfSiteCoreDataKeys::get_taxonomy_key(
			RbfSiteCoreDataKeys::TAXONOMY_PRODUCT_FAMILY
		);

		$taxonomy_object = get_taxonomy($taxonomy);

		if (
			!$taxonomy_object
			|| !current_user_can($taxonomy_object->cap->manage_terms)
		) {
			wp_die('You are not allowed to manage Product Families.');
		}

		$term = get_term($term_id, $taxonomy);

		if (is_wp_error($term) || !$term instanceof WP_Term) {
			wp_die('Product Family term not found.');
		}

		$result = RbfSiteCoreFamilyIndex::delete($term_id);

		if (is_wp_error($result)) {
			wp_die(esc_html($result->get_error_message()));
		}

		$redirect_url = get_edit_term_link(
			$term_id,
			$taxonomy,
			'product'
		);

		$redirect_url = add_query_arg(
			'rbf_family_index',
			'deleted',
			$redirect_url
		);

		wp_safe_redirect($redirect_url);
		exit;
	}
}
<?php

defined('ABSPATH') || exit;

class RbfSiteCoreB2BBranding {

	const META_LOGO_ID = 'rbf_b2b_logo_id';
	const META_LOGO_INLINE = 'rbf_b2b_logo_inline';

	const MAX_FILE_SIZE = 204800;

	const OPTION_SITE_LOGO_ID = 'rbf_site_logo_id';
	const OPTION_SITE_LOGO_INLINE = 'rbf_site_logo_inline';

	private $allow_svg_upload = false;

	private $supported_roles = [
		'shop_manager',
		'b2b_haendler',
		'b2b_vertrieb',
	];

	public function __construct() {

		add_action('show_user_profile', [$this, 'render_profile_fields']);
		add_action('edit_user_profile', [$this, 'render_profile_fields']);

		add_action('admin_menu', [$this, 'register_branding_options_page']);
		add_action('admin_post_rbf_site_branding_save', [$this, 'save_site_branding']);

		add_action('personal_options_update', [$this, 'save_profile_fields']);
		add_action('edit_user_profile_update', [$this, 'save_profile_fields']);

		add_action('user_edit_form_tag', [$this, 'add_form_enctype']);
		add_action('woocommerce_edit_account_form_tag', [$this, 'add_my_account_form_enctype']);
		add_action('woocommerce_edit_account_form', [$this, 'render_my_account_fields']);
		add_action('woocommerce_save_account_details', [$this, 'save_my_account_fields']);


		add_filter('upload_mimes', [$this, 'filter_upload_mimes']);
	}

	public function add_form_enctype() {

		echo ' enctype="multipart/form-data"';
	}

	public function render_profile_fields($user) {

		if (! $user instanceof WP_User) {
			return;
		}

		if (! $this->is_supported_user($user)) {
			return;
		}

		if (! $this->can_manage_branding($user->ID)) {
			return;
		}

		$logo_id = absint(
			get_user_meta($user->ID, self::META_LOGO_ID, true)
		);

		$inline_svg = (bool) get_user_meta(
			$user->ID,
			self::META_LOGO_INLINE,
			true
		);

		$logo_url = $logo_id
			? wp_get_attachment_url($logo_id)
			: '';

		$logo_mime = $logo_id
			? get_post_mime_type($logo_id)
			: '';

		$nonce = wp_nonce_field(
			'rbf_b2b_branding_save_' . $user->ID,
			'rbf_b2b_branding_nonce',
			true,
			false
		);

		$output_logo = '';

		if ($logo_url) {
			$output_logo =
				'<div style="margin:0 0 12px;">'.
					'<img src="' . esc_url($logo_url) . '" alt="" style="display:block;max-width:240px;max-height:100px;width:auto;height:auto;">'.
				'</div>';
		}

		$output_inline = '';

		if (current_user_can('manage_options')) {
			$output_inline =
				'<tr>'.
					'<th scope="row">SVG inline rendern</th>'.
					'<td>'.
						'<label>'.
							'<input type="checkbox" name="' . esc_attr(self::META_LOGO_INLINE) . '" value="1" ' . checked($inline_svg, true, false) . '>'.
							' SVG später inline statt als &lt;img&gt; ausgeben'.
						'</label>'.
						'<p class="description">'.
							'Nur für Administratoren. Greift ausschließlich bei SVG-Logos.'.
						'</p>'.
					'</td>'.
				'</tr>';
		}

		$html =
			'<h2>B2B Branding</h2>'.
			$nonce.
			'<table class="form-table" role="presentation">'.

				'<tr>'.
					'<th scope="row">'.
						'<label for="rbf_b2b_logo">Firmenlogo</label>'.
					'</th>'.

					'<td>'.
						$output_logo.

						'<input'.
							' type="file"'.
							' id="rbf_b2b_logo"'.
							' name="rbf_b2b_logo"'.
							' accept=".png,.webp,.svg,image/png,image/webp,image/svg+xml"'.
						'>'.

						'<p class="description">'.
							'Erlaubt: PNG, WebP oder SVG. Maximale Dateigröße: 200 KB.'.
						'</p>'.

						(
							$logo_id
								? '<label style="display:block;margin-top:10px;">'.
									'<input type="checkbox" name="rbf_b2b_logo_remove" value="1">'.
									' Aktuelles Logo entfernen'.
								'</label>'
								: ''
						).

						(
							$logo_mime
								? '<p class="description">Aktueller Dateityp: ' . esc_html($logo_mime) . '</p>'
								: ''
						).
					'</td>'.
				'</tr>'.

				$output_inline.

			'</table>';

		echo $html;
	}

	public function save_profile_fields($user_id) {

		$user_id = absint($user_id);

		if (! $user_id) {
			return;
		}

		if (! $this->can_manage_branding($user_id)) {
			return;
		}

		$user = get_userdata($user_id);

		if (! $user || ! $this->is_supported_user($user)) {
			return;
		}

		if (
			empty($_POST['rbf_b2b_branding_nonce']) ||
			! wp_verify_nonce(
				sanitize_text_field(
					wp_unslash($_POST['rbf_b2b_branding_nonce'])
				),
				'rbf_b2b_branding_save_' . $user_id
			)
		) {
			return;
		}

		if (
			! empty($_POST['rbf_b2b_logo_remove']) &&
			'1' === sanitize_text_field(
				wp_unslash($_POST['rbf_b2b_logo_remove'])
			)
		) {
			delete_user_meta($user_id, self::META_LOGO_ID);
			delete_user_meta($user_id, self::META_LOGO_INLINE);
		}

		if (current_user_can('manage_options')) {
			$inline_svg = ! empty($_POST[self::META_LOGO_INLINE])
				? 1
				: 0;

			update_user_meta(
				$user_id,
				self::META_LOGO_INLINE,
				$inline_svg
			);
		}

		if (
			! empty($_FILES['rbf_b2b_logo']) &&
			! empty($_FILES['rbf_b2b_logo']['name'])
		) {
			$result = $this->handle_logo_upload(
				'rbf_b2b_logo'
			);

			if (is_wp_error($result)) {
				wp_die(
					esc_html($result->get_error_message())
				);
			}

			update_user_meta(
				$user_id,
				self::META_LOGO_ID,
				absint($result)
			);
		}

	}




	//render method
	public function add_my_account_form_enctype() {

		$user = wp_get_current_user();

		if (! $this->is_supported_user($user)) {
			return;
		}

		if (! current_user_can('rbf_can_access_b2b_admin')) {
			return;
		}

		echo ' enctype="multipart/form-data"';
	}

	public function render_my_account_fields() {

		$user = wp_get_current_user();

		if (! $this->is_supported_user($user)) {
			return;
		}

		$logo_id = absint(
			get_user_meta($user->ID, self::META_LOGO_ID, true)
		);

		$logo_url = $logo_id
			? wp_get_attachment_url($logo_id)
			: '';

		$can_edit = current_user_can('rbf_can_access_b2b_admin');

		$output_logo = '';

		if ($logo_url) {
			$output_logo =
				'<div class="rbf-b2b-branding__preview">'.
					'<img'.
						' src="' . esc_url($logo_url) . '"'.
						' alt="' . esc_attr__('Firmenlogo', 'rbf-site-core') . '"'.
						' style="display:block;max-width:240px;max-height:100px;width:auto;height:auto;"'.
					'>'.
				'</div>';
		} else {
			$output_logo =
				'<p class="rbf-b2b-branding__empty">'.
					esc_html__('Noch kein Firmenlogo hinterlegt.', 'rbf-site-core').
				'</p>';
		}

		$output_edit = '';

		if ($can_edit) {
			$output_remove = '';

			if ($logo_id) {
				$output_remove =
					'<label style="display:block;margin-top:10px;">'.
						'<input'.
							' type="checkbox"'.
							' name="rbf_b2b_logo_remove"'.
							' value="1"'.
						'>'.
						' ' . esc_html__('Aktuelles Logo entfernen', 'rbf-site-core').
					'</label>';
			}

			$output_edit =
				'<div class="rbf-b2b-branding__upload" style="margin-top:15px;">'.
					'<label for="rbf_b2b_logo">'.
						esc_html__('Firmenlogo ändern', 'rbf-site-core').
					'</label>'.

					'<input'.
						' type="file"'.
						' id="rbf_b2b_logo"'.
						' name="rbf_b2b_logo"'.
						' accept=".png,.webp,.svg,image/png,image/webp,image/svg+xml"'.
						' style="display:block;margin-top:6px;"'.
					'>'.

					'<small style="display:block;margin-top:5px;">'.
						esc_html__('PNG, WebP oder SVG, maximal 200 KB.', 'rbf-site-core').
					'</small>'.

					$output_remove.
				'</div>';
		}

		$html =
			'<fieldset class="rbf-b2b-branding" style="margin-top:30px;">'.
				'<legend>'.
					esc_html__('B2B Branding', 'rbf-site-core').
				'</legend>'.

				$output_logo.
				$output_edit.
			'</fieldset>';

		echo $html;
	}

	public function save_my_account_fields($user_id) {

		$user_id = absint($user_id);

		if (! $user_id) {
			return;
		}

		if (get_current_user_id() !== $user_id) {
			return;
		}

		$user = get_userdata($user_id);

		if (! $user || ! $this->is_supported_user($user)) {
			return;
		}

		if (! current_user_can('rbf_can_access_b2b_admin')) {
			return;
		}

		if (
			! empty($_POST['rbf_b2b_logo_remove']) &&
			'1' === sanitize_text_field(
				wp_unslash($_POST['rbf_b2b_logo_remove'])
			)
		) {
			delete_user_meta($user_id, self::META_LOGO_ID);
			delete_user_meta($user_id, self::META_LOGO_INLINE);
		}

		if (
			empty($_FILES['rbf_b2b_logo']) ||
			empty($_FILES['rbf_b2b_logo']['name'])
		) {
			return;
		}

		$result = $this->handle_logo_upload(
			'rbf_b2b_logo'
		);

		if (is_wp_error($result)) {
			wc_add_notice(
				$result->get_error_message(),
				'error'
			);

			return;
		}

		update_user_meta(
			$user_id,
			self::META_LOGO_ID,
			absint($result)
		);
	}

	private function handle_logo_upload($file_field) {

		if (
			empty($_FILES[$file_field]) ||
			empty($_FILES[$file_field]['name'])
		) {
			return new WP_Error(
				'rbf_b2b_logo_missing',
				'Es wurde keine Logo-Datei übermittelt.'
			);
		}

		$file = $_FILES[$file_field];

		if (
			! isset($file['error']) ||
			UPLOAD_ERR_OK !== (int) $file['error']
		) {
			return new WP_Error(
				'rbf_b2b_logo_upload_error',
				'Das Firmenlogo konnte nicht hochgeladen werden.'
			);
		}

		if (
			empty($file['size']) ||
			(int) $file['size'] > self::MAX_FILE_SIZE
		) {
			return new WP_Error(
				'rbf_b2b_logo_file_size',
				'Das Firmenlogo darf maximal 200 KB groß sein.'
			);
		}

		$filename = sanitize_file_name($file['name']);

		$extension = strtolower(
			pathinfo($filename, PATHINFO_EXTENSION)
		);

		$allowed_extensions = [
			'png',
			'webp',
			'svg',
		];

		if (! in_array($extension, $allowed_extensions, true)) {
			return new WP_Error(
				'rbf_b2b_logo_file_type',
				'Erlaubt sind ausschließlich PNG, WebP und SVG.'
			);
		}

		if ('svg' === $extension) {
			$sanitized = $this->sanitize_svg_file(
				$file['tmp_name']
			);

			if (is_wp_error($sanitized)) {
				return $sanitized;
			}
		}

		if (! function_exists('media_handle_upload')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$this->allow_svg_upload = true;

		$attachment_id = media_handle_upload(
			$file_field,
			0
		);

		$this->allow_svg_upload = false;

		if (is_wp_error($attachment_id)) {
			return $attachment_id;
		}

		$attachment_mime = get_post_mime_type($attachment_id);

		$allowed_mimes = [
			'image/png',
			'image/webp',
			'image/svg+xml',
		];

		if (! in_array($attachment_mime, $allowed_mimes, true)) {
			wp_delete_attachment($attachment_id, true);

			return new WP_Error(
				'rbf_b2b_logo_invalid_mime',
				'Der erkannte Dateityp ist für Firmenlogos nicht erlaubt.'
			);
		}

		return absint($attachment_id);
	}







	//branding page für admin
	public function register_branding_options_page() {

		add_options_page(
			'SOMA Branding',
			'SOMA Branding',
			'manage_options',
			'rbf-site-branding',
			[$this, 'render_site_branding_page']
		);
	}

	public function render_site_branding_page() {

		if (! current_user_can('manage_options')) {
			return;
		}

		$logo_id = self::get_site_logo_id();

		$logo_url = $logo_id
			? wp_get_attachment_url($logo_id)
			: '';

		$inline_svg = (bool) get_option(
			self::OPTION_SITE_LOGO_INLINE,
			false
		);

		$output_logo = '';

		if ($logo_url) {
			$output_logo =
				'<div style="margin-bottom:20px;">'.
					'<img'.
						' src="' . esc_url($logo_url) . '"'.
						' alt=""'.
						' style="display:block;max-width:300px;max-height:120px;width:auto;height:auto;"'.
					'>'.
				'</div>';
		}

		$nonce = wp_nonce_field(
			'rbf_site_branding_save',
			'rbf_site_branding_nonce',
			true,
			false
		);

		$html =
			'<div class="wrap">'.
				'<h1>SOMA Branding</h1>'.

				'<form'.
					' method="post"'.
					' action="' . esc_url(admin_url('admin-post.php')) . '"'.
					' enctype="multipart/form-data"'.
				'>'.
					'<input type="hidden" name="action" value="rbf_site_branding_save">'.
					$nonce.

					'<table class="form-table" role="presentation">'.
						'<tr>'.
							'<th scope="row">'.
								'<label for="rbf_site_logo">SOMA Standardlogo</label>'.
							'</th>'.

							'<td>'.
								$output_logo.

								'<input'.
									' type="file"'.
									' id="rbf_site_logo"'.
									' name="rbf_site_logo"'.
									' accept=".png,.webp,.svg,image/png,image/webp,image/svg+xml"'.
								'>'.

								'<p class="description">'.
									'PNG, WebP oder SVG. Maximal 200 KB.'.
								'</p>'.

								(
									$logo_id
										? '<label style="display:block;margin-top:10px;">'.
											'<input type="checkbox" name="rbf_site_logo_remove" value="1">'.
											' Aktuelles Logo entfernen'.
										'</label>'
										: ''
								).
							'</td>'.
						'</tr>'.

						'<tr>'.
							'<th scope="row">SVG inline rendern</th>'.
							'<td>'.
								'<label>'.
									'<input'.
										' type="checkbox"'.
										' name="' . esc_attr(self::OPTION_SITE_LOGO_INLINE) . '"'.
										' value="1"'.
										' ' . checked($inline_svg, true, false).
									'>'.
									' SVG inline ausgeben'.
								'</label>'.
							'</td>'.
						'</tr>'.
					'</table>'.

					'<p class="submit">'.
						'<button type="submit" class="button button-primary">'.
							'Branding speichern'.
						'</button>'.
					'</p>'.
				'</form>'.
			'</div>';

		echo $html;
	}
	//save funktion für admin logo
	public function save_site_branding() {

		if (! current_user_can('manage_options')) {
			wp_die(
				esc_html__('Keine Berechtigung.', 'rbf-site-core')
			);
		}

		if (
			empty($_POST['rbf_site_branding_nonce']) ||
			! wp_verify_nonce(
				sanitize_text_field(
					wp_unslash($_POST['rbf_site_branding_nonce'])
				),
				'rbf_site_branding_save'
			)
		) {
			wp_die(
				esc_html__('Ungültige Anfrage.', 'rbf-site-core')
			);
		}

		if (
			! empty($_POST['rbf_site_logo_remove']) &&
			'1' === sanitize_text_field(
				wp_unslash($_POST['rbf_site_logo_remove'])
			)
		) {
			delete_option(self::OPTION_SITE_LOGO_ID);
			delete_option(self::OPTION_SITE_LOGO_INLINE);
		}

		$inline_svg = ! empty($_POST[self::OPTION_SITE_LOGO_INLINE])
			? 1
			: 0;

		update_option(
			self::OPTION_SITE_LOGO_INLINE,
			$inline_svg,
			false
		);

		if (
			! empty($_FILES['rbf_site_logo']) &&
			! empty($_FILES['rbf_site_logo']['name'])
		) {
			$result = $this->handle_logo_upload(
				'rbf_site_logo'
			);

			if (is_wp_error($result)) {
				wp_die(
					esc_html($result->get_error_message())
				);
			}

			update_option(
				self::OPTION_SITE_LOGO_ID,
				absint($result),
				false
			);
		}

		$redirect_url = add_query_arg(
			[
				'page'    => 'rbf-site-branding',
				'updated' => '1',
			],
			admin_url('options-general.php')
		);

		wp_safe_redirect($redirect_url);
		exit;
	}



	public function filter_upload_mimes($mimes) {

		if (! $this->allow_svg_upload) {
			return $mimes;
		}

		$mimes['svg'] = 'image/svg+xml';

		return $mimes;
	}

	private function is_supported_user($user) {

		if (! $user instanceof WP_User) {
			return false;
		}

		return (bool) array_intersect(
			$this->supported_roles,
			(array) $user->roles
		);
	}

	private function can_manage_branding($user_id) {

		$user_id = absint($user_id);

		if (! $user_id) {
			return false;
		}

		if (current_user_can('manage_options')) {
			return current_user_can(
				'edit_user',
			$user_id
			);
		}

		if (get_current_user_id() !== $user_id) {
			return false;
		}

		$current_user = wp_get_current_user();

		return $this->is_supported_user(
			$current_user
		);
	}



	public static function get_logo_id($user_id = 0) {

		$user_id = absint($user_id);

		if (! $user_id) {
			$user_id = get_current_user_id();
		}

		if (! $user_id) {
			return 0;
		}

		return absint(
			get_user_meta($user_id, self::META_LOGO_ID, true)
		);
	}

	public static function get_logo_markup($user_id = 0, $args = []) {

		$user_id = absint($user_id);

		if (! $user_id) {
			$user_id = get_current_user_id();
		}

		if (! $user_id) {
			return '';
		}

		$logo_id = self::get_logo_id($user_id);

		if (! $logo_id) {
			return '';
		}

		$logo_url = wp_get_attachment_url($logo_id);

		if (! $logo_url) {
			return '';
		}

		$user = get_userdata($user_id);

		$company = get_user_meta(
			$user_id,
			'billing_company',
			true
		);

		$defaults = [
			'class' => 'rbf-b2b-logo',
			'alt'   => $company
				? $company
				: ($user ? $user->display_name : get_bloginfo('name')),
		];

		$args = wp_parse_args($args, $defaults);

		$logo_mime = get_post_mime_type($logo_id);

		$inline_svg = (bool) get_user_meta(
			$user_id,
			self::META_LOGO_INLINE,
			true
		);

		if (
			'image/svg+xml' === $logo_mime &&
			$inline_svg
		) {
			$file_path = get_attached_file($logo_id);

			if (
				$file_path &&
				is_file($file_path) &&
				is_readable($file_path)
			) {
				$svg = file_get_contents($file_path);

				if ($svg) {
					$inline_markup = self::sanitize_svg_inline(
						$svg,
						$args['class'],
						$args['alt']
					);

					if ($inline_markup) {
						return $inline_markup;
					}
				}
			}
		}

		return
			'<img'.
				' src="' . esc_url($logo_url) . '"'.
				' class="' . esc_attr($args['class']) . '"'.
				' alt="' . esc_attr($args['alt']) . '"'.
			'>';
	}




	public static function get_site_logo_id() {
		return absint(
			get_option(self::OPTION_SITE_LOGO_ID, 0)
		);
	}




	public static function get_site_logo_markup($args = []) {
		$logo_id = self::get_site_logo_id();

		if (! $logo_id) {
			return '';
		}

		$logo_url = wp_get_attachment_url($logo_id);

		if (! $logo_url) {
			return '';
		}

		$defaults = [
			'class' => 'rbf-site-logo',
			'alt'   => get_bloginfo('name'),
		];

		$args = wp_parse_args($args, $defaults);

		$logo_mime = get_post_mime_type($logo_id);

		$inline_svg = (bool) get_option(
			self::OPTION_SITE_LOGO_INLINE,
			false
		);

		if (
			'image/svg+xml' === $logo_mime &&
			$inline_svg
		) {
			$file_path = get_attached_file($logo_id);

			if (
				$file_path &&
				is_file($file_path) &&
				is_readable($file_path)
			) {
				$svg = file_get_contents($file_path);

				if ($svg) {
					$inline_markup = self::sanitize_svg_inline(
						$svg,
						$args['class'],
						$args['alt']
					);

					if ($inline_markup) {
						return $inline_markup;
					}
				}
			}
		}

		return
			'<img'.
				' src="' . esc_url($logo_url) . '"'.
				' class="' . esc_attr($args['class']) . '"'.
				' alt="' . esc_attr($args['alt']) . '"'.
			'>';
	}



	public static function get_effective_logo_id($user_id = 0) {

		$user_id = absint($user_id);

		if (! $user_id) {
			$user_id = get_current_user_id();
		}

		if ($user_id) {
			$user = get_userdata($user_id);

			if ($user) {
				$supported_roles = [
					'shop_manager',
					'b2b_haendler',
					'b2b_vertrieb',
				];

				$is_b2b_user = (bool) array_intersect(
					$supported_roles,
					(array) $user->roles
				);

				if ($is_b2b_user) {
					$user_logo_id = self::get_logo_id($user_id);

					if ($user_logo_id) {
						return $user_logo_id;
					}
				}
			}
		}

		return self::get_site_logo_id();
	}



	public static function get_effective_logo_markup($user_id = 0, $args = []) {

		$logo_id = self::get_effective_logo_id($user_id);

		if (! $logo_id) {
			return '';
		}

		if ($logo_id === self::get_site_logo_id()) {
			return self::get_site_logo_markup($args);
		}

		return self::get_logo_markup(
			$user_id,
			$args
		);
	}



	public static function render_logo($user_id = 0, $args = []) {

		echo self::get_logo_markup(
			$user_id,
			$args
		);
	}

	private static function sanitize_svg_inline($svg, $class = '', $alt = '') {

		if (
			! $svg ||
			! class_exists('DOMDocument')
		) {
			return '';
		}

		if (
			false !== stripos($svg, '<!DOCTYPE') ||
			false !== stripos($svg, '<!ENTITY')
		) {
			return '';
		}

		$previous_libxml_state = libxml_use_internal_errors(true);

		$dom = new DOMDocument();

		$loaded = $dom->loadXML(
			$svg,
			LIBXML_NONET
		);

		libxml_clear_errors();
		libxml_use_internal_errors($previous_libxml_state);

		if (! $loaded || ! $dom->documentElement) {
			return '';
		}

		$root = $dom->documentElement;

		if ('svg' !== strtolower($root->localName)) {
			return '';
		}

		$blocked_elements = [
			'script',
			'style',
			'foreignobject',
			'iframe',
			'object',
			'embed',
			'audio',
			'video',
		];

		$elements = [];

		foreach ($dom->getElementsByTagName('*') as $element) {
			$elements[] = $element;
		}

		foreach ($elements as $element) {
			$tag_name = strtolower($element->localName);

			if (in_array($tag_name, $blocked_elements, true)) {
				if ($element->parentNode) {
					$element->parentNode->removeChild($element);
				}

				continue;
			}

			if (! $element->hasAttributes()) {
				continue;
			}

			$attributes = [];

			foreach ($element->attributes as $attribute) {
				$attributes[] = $attribute;
			}

			foreach ($attributes as $attribute) {
				$attribute_name = strtolower($attribute->name);
				$attribute_value = trim($attribute->value);

				if (0 === strpos($attribute_name, 'on')) {
					$element->removeAttributeNode($attribute);
					continue;
				}

				if (
					in_array(
						$attribute_name,
						[
							'href',
							'xlink:href',
						],
						true
					) &&
					'' !== $attribute_value &&
					'#' !== substr($attribute_value, 0, 1)
				) {
					$element->removeAttributeNode($attribute);
					continue;
				}

				if (
					false !== stripos($attribute_value, 'javascript:') ||
					false !== stripos($attribute_value, 'vbscript:')
				) {
					$element->removeAttributeNode($attribute);
					continue;
				}

				if (
					'style' === $attribute_name &&
					preg_match(
						'/url\s*\(\s*[\'"]?(?!#)/i',
						$attribute_value
					)
				) {
					$element->removeAttributeNode($attribute);
				}
			}
		}

		if ($class) {
			$class_names = preg_split(
				'/\s+/',
				trim($class)
			);

			$class_names = array_filter(
				array_map(
					'sanitize_html_class',
					$class_names
				)
			);

			if ($class_names) {
				$existing_class = trim(
					$root->getAttribute('class')
				);

				$root->setAttribute(
					'class',
					trim(
						$existing_class . ' ' . implode(' ', $class_names)
					)
				);
			}
		}

		$root->setAttribute(
			'role',
			'img'
		);

		if ($alt) {
			$root->setAttribute(
				'aria-label',
				$alt
			);
		}

		$output = $dom->saveXML($root);

		return $output ?: '';
	}





	private function sanitize_svg_file($file_path) {

		if (
			! $file_path ||
			! is_file($file_path) ||
			! is_readable($file_path)
		) {
			return new WP_Error(
				'rbf_invalid_svg_file',
				'Die SVG-Datei konnte nicht gelesen werden.'
			);
		}

		if (! class_exists('DOMDocument')) {
			return new WP_Error(
				'rbf_svg_dom_missing',
				'SVG-Uploads sind nicht möglich, weil DOMDocument auf dem Server fehlt.'
			);
		}

		$svg = file_get_contents($file_path);

		if (false === $svg || '' === trim($svg)) {
			return new WP_Error(
				'rbf_empty_svg',
				'Die SVG-Datei ist leer oder ungültig.'
			);
		}

		$previous_libxml_state = libxml_use_internal_errors(true);

		$dom = new DOMDocument();

		$loaded = $dom->loadXML(
			$svg,
			LIBXML_NONET
		);

		libxml_clear_errors();
		libxml_use_internal_errors($previous_libxml_state);

		if (! $loaded || ! $dom->documentElement) {
			return new WP_Error(
				'rbf_invalid_svg',
				'Die SVG-Datei enthält kein gültiges XML.'
			);
		}

		if ('svg' !== strtolower($dom->documentElement->localName)) {
			return new WP_Error(
				'rbf_invalid_svg_root',
				'Die Datei enthält kein gültiges SVG-Dokument.'
			);
		}

		$blocked_elements = [
			'script',
			'foreignobject',
			'iframe',
			'object',
			'embed',
			'audio',
			'video',
		];

		$elements = [];

		foreach ($dom->getElementsByTagName('*') as $element) {
			$elements[] = $element;
		}

		foreach ($elements as $element) {
			$tag_name = strtolower($element->localName);

			if (in_array($tag_name, $blocked_elements, true)) {
				if ($element->parentNode) {
					$element->parentNode->removeChild($element);
				}

				continue;
			}

			if (! $element->hasAttributes()) {
				continue;
			}

			$attributes = [];

			foreach ($element->attributes as $attribute) {
				$attributes[] = $attribute;
			}

			foreach ($attributes as $attribute) {
				$attribute_name = strtolower($attribute->name);
				$attribute_value = trim($attribute->value);

				if (0 === strpos($attribute_name, 'on')) {
					$element->removeAttributeNode($attribute);
					continue;
				}

				if (
					in_array(
						$attribute_name,
						[
							'href',
							'xlink:href',
						],
						true
					) &&
					'' !== $attribute_value &&
					'#' !== substr($attribute_value, 0, 1)
				) {
					$element->removeAttributeNode($attribute);
					continue;
				}

				if (
					false !== stripos($attribute_value, 'javascript:') ||
					false !== stripos($attribute_value, 'vbscript:')
				) {
					$element->removeAttributeNode($attribute);
				}
			}
		}

		$style_elements = [];

		foreach ($dom->getElementsByTagName('style') as $style_element) {
			$style_elements[] = $style_element;
		}

		foreach ($style_elements as $style_element) {
			$css = $style_element->textContent;

			if (
				false !== stripos($css, '@import') ||
				false !== stripos($css, 'javascript:') ||
				false !== stripos($css, 'expression(') ||
				preg_match('/url\s*\(\s*[\'"]?(?!#)/i', $css)
			) {
				if ($style_element->parentNode) {
					$style_element->parentNode->removeChild(
						$style_element
					);
				}
			}
		}

		$sanitized_svg = $dom->saveXML(
			$dom->documentElement
		);

		if (! $sanitized_svg) {
			return new WP_Error(
				'rbf_svg_sanitize_failed',
				'Die SVG-Datei konnte nicht bereinigt werden.'
			);
		}

		$result = file_put_contents(
			$file_path,
			$sanitized_svg
		);

		if (false === $result) {
			return new WP_Error(
				'rbf_svg_write_failed',
				'Die bereinigte SVG-Datei konnte nicht gespeichert werden.'
			);
		}

		return true;
	}
}
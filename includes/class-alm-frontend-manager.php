<?php
/**
 * Asset Lending Manager - Frontend Manager
 *
 * Handles frontend rendering for ALM devices using
 * WordPress-native archive and single templates.
 *
 * Responsibilities:
 * - Provide fallback templates for alm_device CPT
 * - Keep rendering logic inside plugin templates
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Definition of the public layout of the plugin.
 */
class ALM_Frontend_Manager {

	/**
	 * Register frontend hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'template_include', array( $this, 'load_device_template' ) );
	}

	/**
	 * Load plugin templates for alm_device archive and single views
	 * if the active theme does not provide them.
	 *
	 * @param string $template The path to the template WordPress intends to use.
	 * @return string
	 */
	public function load_device_template( $template ) {
		if ( is_post_type_archive( ALM_DEVICE_CPT_SLUG ) ) {
			return $this->locate_template( 'archive-alm_device.php', $template );
		}
		if ( is_singular( ALM_DEVICE_CPT_SLUG ) ) {
			return $this->locate_template( 'single-alm_device.php', $template );
		}
		return $template;
	}

	/**
	 * Locate a template, allowing theme override with plugin fallback.
	 *
	 * @param string $template_name Template file name.
	 * @param string $default       Default template resolved by WordPress.
	 * @return string
	 */
	protected function locate_template( $template_name, $default ) {
		$theme_template = locate_template( $template_name );
		if ( $theme_template ) {
			return $theme_template;
		}
		$plugin_template = trailingslashit( ALM_PLUGIN_DIR ) . 'templates/' . $template_name;
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
		return $default;
	}
}

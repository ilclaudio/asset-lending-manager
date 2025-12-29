<?php
/**
 * Plugin Name: Asset Lending Manager
 * Plugin URI:  https://github.com/ilclaudio/asset-lending-manager
 * Description: Manage shared assets and internal lending workflows.
 * Version:     0.1.0
 * Author:      AAGG
 * Author URI:  https://www.astrofilipisani.it/
 * Text Domain: asset-lending-manager
 * Domain Path: /languages
 * License:     GPLv2 or later
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * The plugin configurations.
 */
require 'plugin-config.php';

// Load classes.
require_once ALM_PLUGIN_DIR . 'includes/class-alm-logger.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-plugin-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-settings-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-role-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-device-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-loan-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-notification-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-frontend-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-admin-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-alm-autocomplete-manager.php';

// Get the singleton Plugin Manager.
$alm_plugin_manager = ALM_Plugin_Manager::get_instance();

// Register activation/deactivation hooks.
register_activation_hook(
	__FILE__,
	array( $alm_plugin_manager, 'activate' )
);

register_deactivation_hook(
	__FILE__,
	array( $alm_plugin_manager, 'deactivate' )
);

/**
 * Initialize PluginManager.
 */
function alm_init_plugin() {
	$alm_plugin_manager = ALM_Plugin_Manager::get_instance();
	$alm_plugin_manager->init();
	// ALM_Logger::debug( '*** Init the Plugin' ).
}

add_action( 'plugins_loaded', 'alm_init_plugin' );

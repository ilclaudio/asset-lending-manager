<?php
/**
 * Plugin Name: Asset Lending Manager
 * Plugin URI:  https://github.com/ilclaudio/asset-lending-manager
 * Description: Open-source tool to manage shared physical assets and loan workflows for associations, schools, libraries, laboratories, and any organization.
 * Requires at least: 6.2
 * Requires Plugins: advanced-custom-fields
 * Version:     0.2.2
 * Author:      IoClaudio
 * Author URI:  https://www.claudiobattaglino.it
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
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-logger.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-plugin-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-settings-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-role-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-asset-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-loan-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-notification-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-frontend-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-admin-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-tools-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-autocomplete-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-almgr-rest-manager.php';

// Get the singleton Plugin Manager.
$almgr_plugin_manager = ALMGR_Plugin_Manager::get_instance();

// Register activation/deactivation hooks.
register_activation_hook(
	__FILE__,
	array( $almgr_plugin_manager, 'activate' )
);

register_deactivation_hook(
	__FILE__,
	array( $almgr_plugin_manager, 'deactivate' )
);

/**
 * Initialize PluginManager.
 */
function almgr_init_plugin() {
	$almgr_plugin_manager = ALMGR_Plugin_Manager::get_instance();
	$almgr_plugin_manager->init();
}

add_action( 'plugins_loaded', 'almgr_init_plugin' );

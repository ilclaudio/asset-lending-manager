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
 * @package Asset_Lending_Manager
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Define constants.
define( 'ALM_VERSION', '0.1.0' );
define( 'ALM_PLUGIN_FILE', __FILE__ );
define( 'ALM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALM_TEXT_DOMAIN', 'asset-lending-manager' );

// Load classes.
require_once ALM_PLUGIN_DIR . 'includes/class-plugin-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-settings-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-role-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-device-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-loan-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-notification-manager.php';
require_once ALM_PLUGIN_DIR . 'includes/class-frontend-manager.php';

/**
 * Initialize PluginManager.
 */
function alm_init_plugin() {
	$plugin_manager = Plugin_Manager::get_instance();
	$plugin_manager->init();
}
add_action( 'plugins_loaded', 'alm_init_plugin' );

<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.chargebee.com
 * @package           Chargebee_Membership
 *
 * @wordpress-plugin
 * Plugin Name:       Chargebee Membership
 * Plugin URI:        https://www.chargebee.com
 * Description:       Chargebee Membership Plugin
 * Version:           0.0.6
 * Requires at least: 4.5.0
 * Author:            rtCamp
 * Author URI:        https://rtcamp.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       chargebee-membership
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'CHARGEBEE_MEMBERSHIP_PLUGIN_FILE' ) ) {
	define( 'CHARGEBEE_MEMBERSHIP_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'CHARGEBEE_MEMBERSHIP_BASE_NAME' ) ) {
	define( 'CHARGEBEE_MEMBERSHIP_BASE_NAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'CHARGEBEE_MEMBERSHIP_PATH' ) ) {
	define( 'CHARGEBEE_MEMBERSHIP_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'CHARGEBEE_MEMBERSHIP_URL' ) ) {
	define( 'CHARGEBEE_MEMBERSHIP_URL', plugin_dir_url( __FILE__ ) );
}

global $wpdb;

if ( ! defined( 'CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT' ) ) {
	define( 'CHARGEBEE_MEMBERSHIP_TABLE_PRODUCT', $wpdb->prefix . 'cbm_products' );
}

if ( ! defined( 'CHARGEBEE_MEMBERSHIP_TABLE_LEVEL' ) ) {
	define( 'CHARGEBEE_MEMBERSHIP_TABLE_LEVEL', $wpdb->prefix . 'cbm_levels' );
}

if ( ! defined( 'CHARGEBEE_MEMBERSHIP_TABLE_LEVEL_PRODUCT_RELATION' ) ) {
	define( 'CHARGEBEE_MEMBERSHIP_TABLE_LEVEL_PRODUCT_RELATION', $wpdb->prefix . 'cbm_level_relationships' );
}
if ( ! defined( 'CHARGEBEE_MEMBERSHIP_TABLE_USER_NOTIFICATION' ) ) {
	define( 'CHARGEBEE_MEMBERSHIP_TABLE_USER_NOTIFICATION', $wpdb->prefix . 'cbm_user_notification' );
}

if ( ! function_exists( 'get_plugins' ) )
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
$plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
$plugin_file = basename( ( __FILE__ ) );
global $CB_PLUGIN_VERSION;
$CB_PLUGIN_VERSION = $plugin_folder[$plugin_file]['Version'];

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-chargebee-membership-activator.php
 */
function activate_chargebee_membership() {
        $CURRENT_VERSION=get_bloginfo('version');
        $MIN_VERSION=str_replace(".","",$CURRENT_VERSION);
	if ($MIN_VERSION < 100){
		$MIN_VERSION=$MIN_VERSION*10;
	}
        if ( $MIN_VERSION < 450){
                die("Need Wordpress Version > 4.5.0");
        }

        if (!defined('CB_PHP_VERSION_ID')) {
                $cbversion = explode('.', PHP_VERSION);
                define('CB_PHP_VERSION_ID', ($cbversion[0] * 10000 + $cbversion[1] * 100 + $cbversion[2]));
        }
        if ( CB_PHP_VERSION_ID < 50509 ){
                die("Need PHP Version > 5.5.9");
        }

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-chargebee-membership-activator.php';
	Chargebee_Membership_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-chargebee-membership-deactivator.php
 */
function deactivate_chargebee_membership() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-chargebee-membership-deactivator.php';
	Chargebee_Membership_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_chargebee_membership' );
register_deactivation_hook( __FILE__, 'deactivate_chargebee_membership' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-chargebee-membership.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_chargebee_membership() {
	$plugin = new Chargebee_Membership();
	$plugin->run();

}
run_chargebee_membership();

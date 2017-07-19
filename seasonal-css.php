<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://typewheel.xyz/wp/
 * @since             0.3
 * @package           Seasonal CSS
 *
 * @wordpress-plugin
 * Plugin Name:       Seasonal CSS
 * Plugin URI:        http://typewheel.xyz/wp/
 * Description:       The Seasonal CSS plugin was created to allow seasonal styling of a WordPress site.
 * Version:           0.3
 * Author:            Typewheel
 * Author URI:        https://typewheel.xyz/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       seasonal-css
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define plugins globals.
 */

define( 'SEASONAL_CSS_VERSION', '0.3' );
define( 'SEASONAL_CSS_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'SEASONAL_CSS_DIR_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require SEASONAL_CSS_DIR_PATH . 'class-seasonal-css.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.1
 */
function run_seasonal_css() {

	$plugin = new Seasonal_CSS();
	$plugin->run();

}
run_seasonal_css();

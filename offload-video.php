<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://webgarh.com/
 * @since             1.0.2
 * @package           Video_Uploader_For_Tutorlms
 *
 * @wordpress-plugin
 * Plugin Name:       Offload Videos-Bunny.net,AWS S3
 * Description:       Upload videos to Bunny.net and AWS S3 storage via using bunny streaming API's and AWS SDK services
 * Version:           1.0.2
 * Author:            Webgarh Solutions
 * Author URI:        https://webgarh.com/
 * License:           GPL-2.0+
 * License URI:       https://webgarh.com/
 * Text Domain:       offload-videos-bunny-netaws-s3
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( "WPINC" ) ) {
	die;
}

$PluginTextDomain="offload-videos-bunny-netaws-s3";
define( "OFFLOAD_VIDEO_VERSION", "1.0.2");
define("BUNNY_LIBRARY_URL","https://video.bunnycdn.com/library");
define("STREAMING_PLUGIN_PATH",plugin_dir_path( __FILE__ ));
define("STREAMING_PLUGIN_NAME","offload-videos");
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-video-uploader-for-tutorlms-activator.php
 */
function offload_video_activate() {
	require_once plugin_dir_path( __FILE__ ) . "includes/class-offload-video-activator.php";
	Offload_video_Activator::activate();
}


/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-video-uploader-for-tutorlms-deactivator.php
 */
function offload_video_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . "includes/class-offload-video-deactivator.php";
	Offload_video_Deactivator::deactivate();
}

register_activation_hook( __FILE__, "offload_video_activate");
register_deactivation_hook( __FILE__,"offload_video_deactivate");

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) ."includes/class-offload-video.php";


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.1
 */
function run_offload_video() {

	$plugin = new Offload_video();
	$plugin->run();

}
run_offload_video();
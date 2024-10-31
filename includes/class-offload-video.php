<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://webgarh.com/
 * @since      1.0.2
 *
 * @package    offload-videos
 * @subpackage offload-videos/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.2
 * @package    offload-videos
 * @subpackage offload-videos/includes
 * @author     Webgarh <info@cwebconsultants.com>
 */
class Offload_video {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.2
	 * @access   protected
	 * @var      Video_Uploader_For_Tutorlms_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.2
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.2
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.2
	 */
	public function __construct() {
		if ( defined( 'OFFLOAD_VIDEO_VERSION' ) ) {
			$this->version = OFFLOAD_VIDEO_VERSION;
		} else {
			$this->version = '1.0.2';
		}
		$this->plugin_name = 'offload-videos';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Video_Uploader_For_Tutorlms_Loader. Orchestrates the hooks of the plugin.
	 * - Video_Uploader_For_Tutorlms_i18n. Defines internationalization functionality.
	 * - Video_Uploader_For_Tutorlms_Admin. Defines all hooks for the admin area.
	 * - Video_Uploader_For_Tutorlms_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.2
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-video-uploader-for-tutorlms-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-video-uploader-for-tutorlms-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-offload-video-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-offload-video-public.php';

		$this->loader = new Video_Uploader_For_Tutorlms_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Video_Uploader_For_Tutorlms_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.2
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Video_Uploader_For_Tutorlms_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.2
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Offload_video_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'offload_video_enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'offload_video_enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.2
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Offload_video_Public( $this->get_plugin_name(), $this->get_version() );
		if(get_option('streaming_connect_service')=='bunny')
		{
	        if(get_option('BUNNY_ACCESS_KEY') && get_option('BUNNY_LIBRARY_ID'))
	        {
	        	$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'offload_video_enqueue_styles' );
				$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'offload_video_enqueue_scripts' );
	        }
		}
		elseif(get_option('streaming_connect_service')=='amazon')
		{
	        if(get_option('amazon_s3_bucket') && get_option('amazon_s3_key') && get_option('amazon_s3_secret') && get_option('amazon_s3_region'))
	        {
		        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'offload_video_enqueue_styles' );
				$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'offload_video_enqueue_scripts' );
	        }
		}

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.2
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.2
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.2
	 * @return    Video_Uploader_For_Tutorlms_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.2
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}

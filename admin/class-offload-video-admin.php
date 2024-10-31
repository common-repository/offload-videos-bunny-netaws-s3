<?php
use Aws\S3\S3Client;
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wegarh.com
 * @since      1.0.2
 *
 * @package    offload-videos
 * @subpackage offload-videos/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    offload-videos
 * @subpackage offload-videos/admin
 * @author     cwebco <info@cwebconsultants.com>
 */
class Offload_video_Admin{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.2
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.2
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.2
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
        add_action('admin_menu',array( $this, 'offload_video_admin_menu'));
		add_action('admin_notices',array( $this, 'offload_video_admin_notice__success' ));
		add_action('admin_head',array( $this,'offload_video_define_admin'));
	    add_action("wp_ajax_verify_and_save_api_settings", array( $this, "offload_video_verify_and_save_api_settings"));
        add_action("wp_ajax_nopriv_verify_and_save_api_settings", array( $this, "offload_video_verify_and_save_api_settings"));
        add_action("wp_ajax_bulk_delete_video", [$this, "offload_video_bulk_delete_video", ]);
        add_action("wp_ajax_nopriv_bulk_delete_video", [$this, "offload_video_bulk_delete_video", ]);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.2
	 */
	public function offload_video_enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Video_Uploader_For_Tutorlms_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Video_Uploader_For_Tutorlms_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/offload-video-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.2
	 */
	public function offload_video_enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Video_Uploader_For_Tutorlms_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Video_Uploader_For_Tutorlms_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/offload-video-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'offloadvideos',  array('ajax_url' => admin_url('admin-ajax.php'), 'nonce'  => wp_create_nonce('offload_video_nonce'))
    );
	}

	public function offload_video_admin_menu() 
	{
    	add_menu_page(__( 'Offload Videos', 'offload-video' ),__( 'Offload Videos', 'offload-video' ),'manage_options','offload-video',array( $this,'offload_video_settings_page_contents'),'dashicons-admin-generic',5);
        add_submenu_page( 'offload-video', 'Videos', 'Videos','manage_options', 'videos',array( $this,'offload_video_admin_video_listing'));
    }
    /****************Admin settings page content*********************/
    public function offload_video_settings_page_contents() 
    {
		global $PluginTextDomain;
		if (!current_user_can('read')) 
		{
		wp_die(__('You do not have sufficient permissions to access this page.',$PluginTextDomain));
		} 
		else 
		{
		include(plugin_dir_path( __FILE__ ) . 'partials/offload_video_admin_settings.php');
		}
    }
    /****************Update & save Api settings*********************/
    public function offload_video_verify_and_save_api_settings() 
    {	
    	if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'offload_video_nonce') ) {
        wp_send_json('failed');
        return;
    	}

    	$streaming_connect_service=sanitize_text_field($_POST['streaming_connect_service']);
		if(isset($streaming_connect_service))
		{
		    if($streaming_connect_service=='bunny')
		    {
            update_option('BUNNY_LIBRARY_ID',sanitize_text_field($_POST['BUNNY_LIBRARY_ID']));
            update_option('BUNNY_ACCESS_KEY',sanitize_text_field($_POST['BUNNY_ACCESS_KEY']));
            update_option('BUNNY_FILE_UPLOAD_LIMIT',sanitize_text_field($_POST['BUNNY_FILE_UPLOAD_LIMIT']));
		    }
		    elseif($streaming_connect_service=='amazon')
		    {
		    update_option('amazon_s3_bucket',sanitize_text_field($_POST['amazon_s3_bucket']));
            update_option('amazon_s3_key',sanitize_text_field($_POST['amazon_s3_key']));
            update_option('amazon_s3_secret',sanitize_text_field($_POST['amazon_s3_secret']));
            update_option('amazon_s3_region',sanitize_text_field($_POST['amazon_s3_region']));
            update_option('AMAZON_FILE_UPLOAD_LIMIT',sanitize_text_field($_POST['AMAZON_FILE_UPLOAD_LIMIT']));
		    }
            update_option('streaming_connect_service',sanitize_text_field($_POST['streaming_connect_service']));
			echo 'success';
		}
		die;
    }
    /****************Show success notification on api settings update*********************/
	public function offload_video_admin_notice__success() 
	{
		
		@$bsacft_success=sanitize_text_field($_GET['bsacft_success']);
		if(isset($bsacft_success) && $bsacft_success=='success') 
		{
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo __( 'Saved successfully!', 'offload_video' ); ?></p>
			</div>
			<?php
		}
		elseif(isset($bsacft_success) && $bsacft_success=='failed') 
		{
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo __( 'Something went wrong!', 'offload_video' ); ?></p>
			</div>
			<?php
		}
    }
    /****************Define admin URLS*********************/
    public function offload_video_define_admin() 
    { ?>
    	<input type="text" id="bsacft_admin_ajax_url" value="<?php echo esc_html(admin_url('admin-ajax.php'));?>" style="display:none;">
    	<?php
    }

    public function offload_video_admin_video_listing()
    {
	    	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	    	require_once( __DIR__.'/class-offload-video-admin-table.php' );
	    	$wp_list_table = new Offload_video_Admin_Table();
	        $wp_list_table->prepare_items();
	        $wp_list_table->display();
    }

    public function offload_video_bulk_delete_video()
    {	
    	if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'offload_video_nonce') ) {
        wp_send_json('failed');
        return;
    	}

    	$videos_path=sanitize_text_field($_POST['videos']);
	    if(isset($videos_path))
	    {
	    	if(get_option('streaming_connect_service') == 'bunny')
	    	{
	            require_once(dirname(__DIR__).'/public/guzzle/vendor/autoload.php');
			    $BUNNY_ACCESS_KEY = get_option('BUNNY_ACCESS_KEY');
	            $BUNNY_LIBRARY_ID = get_option('BUNNY_LIBRARY_ID');
			    $client = new \GuzzleHttp\Client();
			    $videos = explode(',',$videos_path);
			    foreach($videos as $video_id)
			    {
	                $response = $client->request('DELETE', BUNNY_LIBRARY_URL.'/'.$BUNNY_LIBRARY_ID.'/videos/'.$video_id, [
				    'headers' => [
				    'AccessKey' => $BUNNY_ACCESS_KEY,
				    'accept' => 'application/json',
				    ],
				    ]);
				    $result = json_decode($response->getBody());
				    if($result->success)
				    {
				    	$response_arr['message'] = $result->message;
				    }
				    else
			        {
	            		$response_arr['message'] = $result->message;
			        }
			    }
	    	}
	    	elseif(get_option('streaming_connect_service') == 'amazon')
	    	{
		    	$amazon_s3_key = get_option('amazon_s3_key',0);
		        $amazon_s3_secret = get_option('amazon_s3_secret',0);
		        $amazon_s3_region = get_option('amazon_s3_region',0);
		        $amazon_s3_bucket = get_option('amazon_s3_bucket',0);
		        $s3Client = new S3Client([
	            'version' => 'latest',
	            'region'  => $amazon_s3_region,
	            'credentials' => [
	            'key'    => $amazon_s3_key,
	            'secret' => $amazon_s3_secret
	            ]
	            ]);
                //bulk delete
                try {
                $objects = [];
                $videos = explode(',',sanitize_text_field($_POST['videos']));
                foreach ($videos as $content) 
                {
                	$objects[] = ['Key' => $content,];
                }
                $result = $s3Client->deleteObjects(array(
                'Bucket' => $amazon_s3_bucket,
                'Delete' => ['Objects' => $objects,],
                ));
                if($result)
                {
                	$response_arr['message'] = 'OK';
                }
                } catch (Exception $exception) {
                $response_arr['message'] = "Failed: " . $exception->getMessage();
                }
                //bulk delete
	    	}
		    echo $response_arr['message'];
	    }
	    die;
    }

}

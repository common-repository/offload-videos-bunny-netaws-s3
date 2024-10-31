<?php
include(STREAMING_PLUGIN_PATH.'includes/aws/aws-autoloader.php');
use Aws\S3\S3Client;
// use Aws\Exception\AwsException;
// use Aws\S3\ObjectUploader;
// use Aws\Common\Exception\MultipartUploadException;
// use Aws\S3\MultipartUploader;
// use Aws\S3\S3Client;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://webgarh.com
 * @since      1.0.2
 *
 * @package    offload-videos
 * @subpackage offload-videos/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    offload-videos
 * @subpackage offload-videos/public
 * @author     webgarh <info@cwebconsultants.com>
 */
class Offload_video_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_shortcode('show_users_video_listing',[$this,'offload_video_user_video_listing',]);
        add_action("wp_ajax_send_course_media_on_bunny", [$this, "offload_video_send_course_video_on_bunny", ]);
        add_action("wp_ajax_nopriv_send_course_media_on_bunny", [$this, "offload_video_send_course_video_on_bunny", ]);
        add_action("wp_ajax_delete_video_on_bunny", [$this, "offload_video_delete_video_on_bunny", ]);
        add_action("wp_ajax_nopriv_delete_video_on_bunny", [$this, "offload_video_delete_video_on_bunny", ]);
        add_action('wp_head',[$this,'offload_video_define_frontend']);
        add_action('wp_head',[$this,'offload_video_upload_popup']);
        add_action('wp_footer',[$this,'offload_video_check_foot_js']);
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.2
	 */
	public function offload_video_check_foot_js()
    {
    	wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/offload-video-public.js', array( 'jquery' ), $this->version, false );
    	// Localize script
		wp_localize_script( $this->plugin_name, 'offloadVideoPublic', array('ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce'    => wp_create_nonce( 'offload_video_nonce' ),
		));
    }
    
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/offload-video-public.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name.'-grid', plugin_dir_url( __FILE__ ) . 'css/offload-video-grid.css', array(), $this->version, 'all' );

        wp_enqueue_style( 'bootstrap.min',plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css', array(), '5.2.3', 'all' );
        
        wp_enqueue_style( 'basic_css', plugin_dir_url( __FILE__ ) . 'css/basic.css', array(), '3.8.4', 'all' );
        wp_enqueue_style( 'font-awesome.min', plugin_dir_url( __FILE__ ) . 'css/fontawesome.min.css', array(), '5.8.2', 'all');


        wp_enqueue_style( 'all.css', plugin_dir_url( __FILE__ ) . 'css/all.css', array(), '5.8.2', 'all');

      
          

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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
			
			wp_enqueue_script('bootstrap.min', plugin_dir_url( __FILE__ ) . 'js/bootstrap.min.js', array( 'jquery' ),'5.2.3', true );
			wp_enqueue_script('dropzone', plugin_dir_url( __FILE__ ) . 'js/dropzone.js', array( 'jquery' ),'3.8.4', true );
			wp_enqueue_script('crypto', plugin_dir_url( __FILE__ ) . 'js/crypto-js.min.js', array( 'jquery' ),'4.1.1', true );
			wp_enqueue_script('sha256', plugin_dir_url( __FILE__ ) . 'js/sha256.min.js', array( 'jquery' ),'4.1.1', true );
			wp_enqueue_script('tus', plugin_dir_url( __FILE__ ) . 'js/tus.js', array( 'jquery' ), true );

			
			
	}

	/****************Define admin URLS*********************/
    public function offload_video_define_frontend() 
    {
    	$BUNNY_FILE_UPLOAD_LIMIT=get_option('BUNNY_FILE_UPLOAD_LIMIT');
    	$AMAZON_FILE_UPLOAD_LIMIT=get_option('AMAZON_FILE_UPLOAD_LIMIT');
    	?>
		<input type="text" id="bsacft_ajax_url" value="<?php echo esc_html(admin_url('admin-ajax.php'));?>" style="display:none;">
		<input type="text" id="BUNNY_FILE_UPLOAD_LIMIT" value="<?php echo esc_html($BUNNY_FILE_UPLOAD_LIMIT);?>" style="display:none;">
		<input type="text" id="AMAZON_FILE_UPLOAD_LIMIT" value="<?php echo esc_html($AMAZON_FILE_UPLOAD_LIMIT);?>" style="display:none;">

		<?php
		
    }

	/***********************Upload video on bunny video library********************************/
    public function offload_video_send_course_video_on_bunny()
    {	
    	// Check the nonce
	    if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'offload_video_nonce') ) {
	        wp_send_json_error(array('message' => 'Nonce verification failed!'));
	        return;
	    }

    	if(isset($_POST['media_file']) && sanitize_text_field($_POST['streaming_connect_service']) == 'bunny')
		{
	    	require_once(dirname(__FILE__).'/guzzle/vendor/autoload.php');
	    	$current_user = wp_get_current_user();
	    	$BUNNY_ACCESS_KEY = get_option('BUNNY_ACCESS_KEY');
            $BUNNY_LIBRARY_ID = get_option('BUNNY_LIBRARY_ID');
	    	$client = new \GuzzleHttp\Client(['headers' => ['AccessKey' => $BUNNY_ACCESS_KEY]]);
	    	if(""==get_user_meta($current_user->ID,'collection_id',true))
	    	{
	    		//Creating collection on bunny video library with username and domain
	    		$site_domain = parse_url( get_site_url(), PHP_URL_HOST );
		        $dir = $current_user->user_login.'-'.$site_domain;
		        $collection_response = $client->request('POST', BUNNY_LIBRARY_URL.'/'.$BUNNY_LIBRARY_ID.'/collections', [
		        'body' => '{"name":"'.$dir.'"}',
		        'headers' => [
		        'accept' => 'application/json',
		        'content-type' => 'application/*+json',
		        ],
		        ]);
		        $collection_result = json_decode($collection_response->getBody());
		        if(isset($collection_result->guid))
		        {
		        	update_user_meta($current_user->ID,'collection_id',$collection_result->guid);
		        }
	    	}

	    	//Creating video in users collection on bunny video library
	    	$collection_id = get_user_meta($current_user->ID,'collection_id',true);
	    	//$collection_id = '20fd0feb-71ea-4b10-b5eb-aeb0f62198d1';
	    	$video_response = $client->request('POST', BUNNY_LIBRARY_URL.'/'.$BUNNY_LIBRARY_ID.'/videos', [
	        'body' => '{"title":"'.sanitize_text_field($_POST['media_file']).'","collectionId":"'.$collection_id.'"}',
	        'headers' => [
	        'accept' => 'application/json',
	        'content-type' => 'application/*+json',
	        ],
	        ]);
	        $video_result = json_decode($video_response->getBody());
		    if(isset($video_result->guid))
		    {
	            $response['video_id'] = $video_result->guid;
	            $response['library_id'] = $BUNNY_LIBRARY_ID;
	            $response['access_key'] = $BUNNY_ACCESS_KEY;
	            print_r(json_encode($response));
		    }
	    }
	    elseif(isset($_FILES['media_file']) && sanitize_text_field($_POST['streaming_connect_service']) == 'amazon')
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
            'secret' => $amazon_s3_secret,
            'httpOptions' => ['connect_timeout' => 0,'timeout' => 60]
            ]
            ]);
            $current_user = wp_get_current_user();
            $site_domain = parse_url( get_site_url(), PHP_URL_HOST );
		    $dir = $current_user->user_login.'-'.$site_domain.'/';
            $info = $s3Client->doesObjectExist($amazon_s3_bucket, $dir);
            if(!$info)
            {
	            $s3->putObject(array( 
	            'Bucket' => $amazon_s3_bucket,
	            'Key'    => $dir,
	            'Body'   => ""
	            ));
            }
	        $file_data = file_get_contents($_FILES['media_file']['tmp_name']);
            $file_name = explode(".",$_FILES['media_file']['name']);
            $n=32;
            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
            $randomString = ''; 
            for ($i = 0; $i < $n; $i++) 
            {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
            } 
            $key1 = $file_name[0].'_'.$randomString.'.'.$file_name[1];
            //$key1 = $_FILES['media_file']['name'];
         /***********old file uplaod******************/
            try {
            //https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-multipart-upload.html
            $result = $s3Client->putObject([
            'Bucket' => $amazon_s3_bucket,
            'Key'    => $dir.$key1,
            'ContentLength' => sanitize_text_field($_FILES['media_file']['size']),
            'Body'   => $file_data,
            'ContentType' => sanitize_text_field($_FILES['media_file']['type']),
            'ACL' => 'private'
            ]);
            $response['ObjectURL'] = $result->get('ObjectURL');
	        print_r(json_encode($response));
            } catch (Aws\S3\Exception\S3Exception $e) {
            echo "There was an error uploading the file.\n";
            echo $e->getMessage();
            }
         
	    }
	    die;
    }

    public function offload_video_delete_video_on_bunny()
    {	
    	// Check the nonce
	    if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'offload_video_nonce') ) {
	        wp_send_json_error(array('message' => 'Nonce verification failed!'));
	        return;
	    }

    	$video_id=sanitize_text_field($_POST['video_id']);
	    if(isset($video_id))
	    {
	    	if(get_option('streaming_connect_service') == 'bunny')
	    	{
	            require_once(dirname(__FILE__).'/guzzle/vendor/autoload.php');
			    $BUNNY_ACCESS_KEY = get_option('BUNNY_ACCESS_KEY');
	            $BUNNY_LIBRARY_ID = get_option('BUNNY_LIBRARY_ID');
			    $client = new \GuzzleHttp\Client();
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
	            $result = $s3Client->deleteObject([
	            'Bucket' => $amazon_s3_bucket,
	            'Key'    => sanitize_text_field($_POST['video_id'])
	            ]);
	            if($result)
	            {
                	$response_arr['message'] = 'OK';
	            }
	    	}
		    echo $response_arr['message'];
	    }
	    die;
    }

    public function offload_video_user_video_listing()
    {
    	ob_start();
		$html = '<div class="user_video_upload">';
		if(is_user_logged_in())
		{
			$user = wp_get_current_user();
			$streaming_connect_service = get_option('streaming_connect_service');
			if($streaming_connect_service=='bunny' && get_option('BUNNY_ACCESS_KEY') && get_option('BUNNY_LIBRARY_ID'))
			{
				$html.= '<input type="hidden" id="streaming_connect_service" value="'.$streaming_connect_service.'">';
			   
			    $html.= '<input type="text" id="endpoint" name="endpoint" value="https://video.bunnycdn.com/tusupload/"><input type="number" id="chunksize" name="chunksize"><input type="number" id="paralleluploads" name="paralleluploads" value="1">';
               

                $html.='<div class="upload_btn_div"><a href="javascript:void(0)" class="btn stop open_dropzone">Upload Video</a></div>';
				if(get_user_meta($user->ID,'collection_id',true)!="")
				{
		            require_once(dirname(__DIR__).'/public/guzzle/vendor/autoload.php');
					$client = new \GuzzleHttp\Client();
					$collection_id = get_user_meta($user->ID,'collection_id',true);
					//$collection_id = get_user_meta(2,'collection_id',true);
					$BUNNY_LIBRARY_ID = get_option('BUNNY_LIBRARY_ID');
					$BUNNY_ACCESS_KEY = get_option('BUNNY_ACCESS_KEY');
					  
					 if(isset($_GET['num'])){
                        $page_num = filter_var($_GET['num'], FILTER_SANITIZE_NUMBER_INT);
					 }else{
					 	$page_num = 1;
					 }
                      // $page_num = isset($_GET['num'])?$_GET['num']:1;

					$response = $client->request('GET', 'https://video.bunnycdn.com/library/'.$BUNNY_LIBRARY_ID.'/videos?page='.$page_num.'&itemsPerPage=21&collection='.$collection_id.'&orderBy=date', [
					  'headers' => [
					    'AccessKey' => $BUNNY_ACCESS_KEY,
					    'accept' => 'application/json',
					  ],
					]);
					$video_result_arr = json_decode($response->getBody());
					if($video_result_arr->totalItems>0)
					{
						
						
						$html.= '<div class="card-category-2"><ul>';
						$all_videos = $video_result_arr->items;
						foreach($all_videos as $key=>$all_video)
						{
							if($all_video->storageSize>0)
							{
	                        $video_size = round($all_video->storageSize/(1024*1024),2);
							}
							else
							{
							$video_size = 0;
							}
							$class = '';
							$key = $key+1;

			                $html.= '<li class="'.$class.'">';
			                $html.='<div class="img-card iCard-style1">
	                        <div class="card-content">
	                            <div class="card-image">';
	                        $html.= '<iframe src="https://iframe.mediadelivery.net/embed/'.$all_video->videoLibraryId.'/'.$all_video->guid.'?autoplay=false&preload=false" progress="'.$all_video->encodeProgress.'" allow="accelerometer; gyroscope; encrypted-media; picture-in-picture;" allowfullscreen="true"></iframe>';
	                        $html.='</div>';
	                        $html.='<div class="card-text">';
	                        if($all_video->encodeProgress==100)
			                {
	                        $video_title = '<p class="bunny_title">'.substr($all_video->title, 0, 25).'</p>';
			                }
			                else
			                {
	                        $video_title = '<p class="bunny_title in_progress">Processing...</p>';
			                }
			                $html.= '<div class="item_bottom"><div class="bottom_left">'.$video_title.'<span class="video_length"><i class="fa fa-clock-o"></i>'.gmdate("H:i:s", $all_video->length).'</span><span class="video_size"><i class="fa fa-file"></i>'.$video_size.' MB</span></div>';

			                $html.= '<div class="bottom_right"><p class="copy_embed" title="Copy to clipboard" guid="'.$all_video->guid.'"><i class="fa fa-copy"></i></p><p title="Delete" class="delete_video" guid="'.$all_video->guid.'"><i class="fa fa-trash"></i></p><textarea class="'.$all_video->guid.'" style="display:none;"><iframe src="https://iframe.mediadelivery.net/embed/'.$all_video->videoLibraryId.'/'.$all_video->guid.'?autoplay=false&preload=false" style="border: none; position: absolute; top: 0; height: 100%; width: 100%;" allow="accelerometer; gyroscope; encrypted-media; picture-in-picture;" allowfullscreen="true"></iframe></textarea></div></div>';
	                        $html.='</div></div></div> ';
			                $html.= '</li>';
						}
						$html.= '</ul></div>';
						if($video_result_arr->totalItems>21)
						{  
							$HTTP_HOST=sanitize_text_field($_SERVER['HTTP_HOST']);
							$REQUEST_URI=sanitize_text_field($_SERVER['REQUEST_URI']);
							if(isset($HTTP_HOST) && isset($REQUEST_URI)){
                                   $actual_link = "http://".$HTTP_HOST.$REQUEST_URI;
							}
							
							else{
                                $actual_link = "";
							}
							$actual_link_new = explode('?',$actual_link);
							$html.= '<div class="pagination"><ul>';
							$num_of_pages = $video_result_arr->totalItems/21;
							if($video_result_arr->totalItems%21)
							{
							$num_of_pages = $num_of_pages+1;	
							}
							//$num_of_pages = 20;
							for($i=1; $i<=$num_of_pages; $i++)
							{
								$html.= '<li><a href="'.$actual_link_new[0].'?num='.$i.'">'.$i.'</a></li>';
							}
							$html.= '</ul></div>';
						}
					}
					else
		            {
			        	$html.= "<p class='no_video'>You are either not authorized to use this page or you don't have any video uploaded to our streaming platform yet.</p>";
		            }
				}
				else
		        {
			    	$html.= "<p class='no_video'>You are either not authorized to use this page or you don't have any video uploaded to our streaming platform yet.</p>";
		        }
			}
			elseif($streaming_connect_service=='amazon' && get_option('amazon_s3_bucket') && get_option('amazon_s3_key') && get_option('amazon_s3_secret') && get_option('amazon_s3_region'))
			{
				$html.= '<input type="hidden" id="streaming_connect_service" value="'.$streaming_connect_service.'">';
			    $html.= '<input type="text" id="endpoint" name="endpoint" value="https://video.bunnycdn.com/tusupload/"><input type="number" id="chunksize" name="chunksize"><input type="number" id="paralleluploads" name="paralleluploads" value="1">';
               
                $html.='<div class="upload_btn_div"><a href="javascript:void(0)" class="btn stop open_dropzone">Upload Video</a></div>';
		        $amazon_s3_key = get_option('amazon_s3_key',0);
		        $amazon_s3_secret = get_option('amazon_s3_secret',0);
		        $amazon_s3_region = get_option('amazon_s3_region',0);
		        $amazon_s3_bucket = get_option('amazon_s3_bucket',0);
		        if($amazon_s3_key && $amazon_s3_secret && $amazon_s3_region && $amazon_s3_bucket)
		        {
		        	$s3 = new S3Client([
	                'version' => 'latest',
	                'region'  => $amazon_s3_region,
	                'endpoint'    => 'https://s3.'.$amazon_s3_region.'.amazonaws.com',
	                'credentials' => [
	                'key'      => $amazon_s3_key,
	                'secret'   => $amazon_s3_secret,
	                ]
	                ]);
	                $current_user = wp_get_current_user();
	                $site_domain = parse_url( get_site_url(), PHP_URL_HOST );
			        $dir = $current_user->user_login.'-'.$site_domain.'/';
	                $info = $s3->doesObjectExist($amazon_s3_bucket, $dir);
	                if(!$info)
	                {
		                $s3->putObject(array( 
		                   'Bucket' => $amazon_s3_bucket,
		                   'Key'    => $dir,
		                   'Body'   => ""
		                ));
	                }
	                // $results = $s3->getPaginator('ListObjects', [
	                // 'Bucket' => $amazon_s3_bucket
	                // ]);
	                $results = $s3->getPaginator('ListObjects', array(
	                "Bucket" => $amazon_s3_bucket,
	                "Prefix" => $dir
	                )); 
	                if($results)
	                {
		                foreach ($results as $result) 
		                {
		                	$result_count = count($result['Contents'])-1;
		                	if($result_count>0)
		                	{
		                		
						        $html.= '<div class="card-category-2"><ul>';
						        $content_array_sorted = $this->offload_video_sort_s3_objects_by_date($result['Contents']);
						        foreach ($content_array_sorted as $obj_key => $object) 
			                    {
					                if($object['Key']==$dir)
					                continue;
					                //echo "<pre>"; echo $obj_key; print_r($object); //die;
					                $video_title_arr = explode('/',$object['Key']);
					                $video_title = '<p class="bunny_title">'.substr($video_title_arr[1], 0, 25).'</p>';
					                $video_size = round($object['Size']/(1024*1024),2);
					                $cmd = $s3->getCommand('GetObject', [
					                'Bucket' => $amazon_s3_bucket,
					                'Key'    => $object['Key']
					                ]);
					                $signed_url = $s3->createPresignedRequest($cmd, '+1 hour');
					                $html.= '<li>';
				                    $html.='<div class="img-card iCard-style1"><div class="card-content"><div class="card-image">';
		                            $html.= '<iframe src="'.$signed_url->getUri().'" allow="accelerometer; gyroscope; encrypted-media; picture-in-picture;" allowfullscreen="true"></iframe>';
		                            $html.='</div>';
		                            $html.='<div class="card-text">';
				                    $html.= '<div class="item_bottom"><div class="bottom_left">'.$video_title.'<span class="video_size"><i class="fa fa-file"></i>'.$video_size.' MB</span></div>';

				                    $html.= '<div class="bottom_right"><p class="copy_embed" title="Copy to clipboard" guid="amazon_'.$obj_key.'"><i class="fa fa-copy"></i></p><p title="Delete" class="delete_video" guid="'.$object['Key'].'"><i class="fa fa-trash"></i></p><textarea class="amazon_'.$obj_key.'" style="display:none;"><iframe src="'.$signed_url->getUri().'" style="border: none; position: absolute; top: 0; height: 100%; width: 100%;" allow="accelerometer; gyroscope; encrypted-media; picture-in-picture;" allowfullscreen="true"></iframe></textarea></div></div>';
		                            $html.='</div></div></div> ';
				                    $html.= '</li>';
			                    }
			                    $html.= '</ul></div>';
		                	}
		                	else
	                        {
	                	    	$html.= "<p class='no_video'>You are either not authorized to use this page or you don't have any video uploaded to our streaming platform yet.</p>";	
	                        }
		                }
	                }
	                else
	                {
	                	$html.= "<p class='no_video'>You are either not authorized to use this page or you don't have any video uploaded to our streaming platform yet.</p>";	
	                }
		        	//$html.= '<video width="320" height="240" controls><source src="https://webgarhstreamingconnect.s3.ap-northeast-1.amazonaws.com/file_example_MP4_480_1_5MG.mp4" type="video/mp4"></video>';
		        }
		        else
		        {
		        	$html.= "<p class='no_video'>You are either not authorized to use this page or you don't have any video uploaded to our streaming platform yet.</p>";		
		        }
			}
			else
		    {
				$html.= "<p class='no_video'>You are either not authorized to use this page or you don't have any video uploaded to our streaming platform yet.</p>";
		    }
		}
		else
		{
			$html.= "<p class='no_video'>You are either not authorized to use this page or you don't have any video uploaded to our streaming platform yet.</p>";
		}
		$html.= '</div>';
		ob_get_clean();
		return $html;
    }

    public function offload_video_sort_s3_objects_by_date($content_array)
    {
    	$content_array_new = array();
	    foreach($content_array as $obj_key => $object)
		{
        $LastModified = (array) $object['LastModified'];
		$object['LastModified_date'] = strtotime($LastModified['date']);
		array_push($content_array_new,$object);
		}
        usort($content_array_new, function ($item1, $item2) { return $item2['LastModified_date'] <=> $item1['LastModified_date']; });
        $content_array = $content_array_new;
        return $content_array;
    }

    public function offload_video_upload_popup()
{
	?>
	
  <!-- Modal -->
  <div class="modal fade video_stream" id="streaming_connect_myModal" role="dialog">
    <div class="modal-dialog">
    
      <!-- Modal content-->
      <div class="modal-content">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      <!-- <form action="" id="video_uploader_form" method="post" enctype="multipart/form-data">
      	<div id="dropzone-previews">
      </div>
      </form> -->
      <button class="btn stop toggle-btn" style="display:none!important;">start upload</button>
      <div id="myId" class="dropzone_" style="width:100%;height:200px;border: 1px dotted #cccccc;">
      	<div class="dz-message drop_text" data-dz-message><h3>Upload Videos </h3><p>Drop your video files here<br />or<br /><span class="browse_text">Browse</span></p></div>
      <div class="fallback">
      <!-- <input name="file" type="file" multiple  style="display:none;"/> -->
      </div>
      </div>
      <form action="" class="dropzone" id="my-awesome-dropzone" enctype="multipart/form-data">
      <div id="dropzone-previews">
      </div>
      </form>
     
      <ul class="progress progress-striped progress-success"></ul><div class="video_progress"></div>
      </div>
      
    </div>
  </div>

	<?php
}
}

<?php
use Aws\S3\S3Client;
class Offload_video_Admin_Table extends WP_List_Table {

   /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    */
    function __construct() 
    {
       parent::__construct( array(
      'singular'=> 'wp_list_text_link', //Singular label
      'plural' => 'wp_list_test_links', //plural label, also this well be one of the table css class
      'ajax'   => false //We won't support Ajax for this table
      ) );
    }

    // function extra_tablenav( $which ) 
    // {
    //   if ( $which == "top" ){
    //     //The code that goes before the table is here
    //     echo"Hello, I'm before the table";
    //   }
    //   if ( $which == "bottom" ){
    //     //The code that goes after the table is there
    //     echo"Hi, I'm after the table";
    //   }
    // }

    function get_columns() 
    {
      return $columns= array(
      'video_select'=>__(''),
      'video_title'=>__('Title'),
      'video_size'=>__('Size'),
      'video_folder'=>__('Folder'),
      'video_date'=>__('Date')
      );
    }

    public function get_sortable_columns() 
    {
      return $sortable = array(
        'video_title'=>'title',
        'video_size'=>'size',
        'video_folder'=>'folder'
      );
    }


    function prepare_items() 
    {
      global $_wp_column_headers;
      $screen = get_current_screen();
      $streaming_connect_service = get_option('streaming_connect_service');
      if($streaming_connect_service=='bunny')
      {
          require_once(dirname(__DIR__).'/public/guzzle/vendor/autoload.php');
          $client = new \GuzzleHttp\Client();
          $BUNNY_LIBRARY_ID = get_option('BUNNY_LIBRARY_ID');
          $BUNNY_ACCESS_KEY = get_option('BUNNY_ACCESS_KEY');
          //$page_num = isset($_GET['paged'])?$_GET['paged']:1;
          $response = $client->request('GET', 'https://video.bunnycdn.com/library/'.$BUNNY_LIBRARY_ID.'/videos?page=1&itemsPerPage=1000&orderBy=date', [
            'headers' => [
              'AccessKey' => $BUNNY_ACCESS_KEY,
              'accept' => 'application/json',
            ],
          ]);
          $video_result_arr = json_decode($response->getBody());


          //pagination
          @$orderby = !empty(sanitize_text_field($_GET["orderby"])) ? sanitize_text_field($_GET["orderby"]) : 'ASC';
          @$order = !empty(sanitize_text_field($_GET["order"])) ? sanitize_text_field($_GET["order"]) : '';
          if(!empty($orderby) & !empty($order))
          { 
            $query =' ORDER BY '.$orderby.' '.$order; 
          }


          $total_items = array();
          $totalitems=count($video_result_arr->items);                      
          $perpage = 10;
          @$paged = !empty(sanitize_text_field($_GET["paged"])) ? sanitize_text_field($_GET["paged"]) : '';
          if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
          $totalpages = ceil($totalitems/$perpage); 
          if(!empty($paged) && !empty($perpage))
          { 
            $offset=($paged-1)*$perpage;  
          }
          $this->set_pagination_args(array("total_items" => $totalitems,"total_pages" => $totalpages,"per_page" => $perpage,));
          //pagination

          $main_content = array();
          $i=0;
          foreach($video_result_arr->items as $key => $value)
          {
            if(!empty($value->guid))
            {
              if($i++ < $offset) continue;
              if($i > $offset + $perpage) break;
              array_push($main_content,$value);
            }
          }
          $result_count = count($main_content)-1;
          if($result_count>0)
          {
            $all_videos = $main_content;
            $columns = $this->get_columns();
            $this->_column_headers[0] = $columns;
            $this->items = $all_videos;
          }
      }
      elseif($streaming_connect_service=='amazon')
      {
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
                  $results = $s3->getPaginator('ListObjects', array(
                  "Bucket" => $amazon_s3_bucket
                  )); 
                  
                  if($results)
                  {
                    foreach ($results as $result) 
                    {
                      //pagination
                      @$orderby = !empty(sanitize_text_field($_GET["orderby"])) ? sanitize_text_field($_GET["orderby"]) : 'ASC';
                      @$order = !empty(sanitize_text_field($_GET["order"])) ? sanitize_text_field($_GET["order"]) : '';
                      if(!empty($orderby) & !empty($order))
                      { 
                        $query =' ORDER BY '.$orderby.' '.$order; 
                      }
                      $total_items = array();
                      if($result['Contents']) {
                        foreach($result['Contents'] as $key => $value)
                        {
                          $rec_key = explode('/',$value['Key']);
                          $title = $rec_key[1];
                          if(!empty($title))
                          {
                            array_push($total_items,$value);
                          }
                        }
                      }
                      $totalitems=count($total_items);                      
                      $perpage = 10;
                      @$paged = !empty(sanitize_text_field($_GET["paged"])) ? sanitize_text_field($_GET["paged"]) : '';
                      if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
                      $totalpages = ceil($totalitems/$perpage); 
                      if(!empty($paged) && !empty($perpage))
                      { 
                        $offset=($paged-1)*$perpage;  
                      }
                      $this->set_pagination_args(array("total_items" => $totalitems,"total_pages" => $totalpages,"per_page" => $perpage,));
                      //pagination
                      $main_content = array();
                      $i=0;
                      if($result['Contents']) {
                        foreach($result['Contents'] as $key => $value)
                        {
                          $rec_key = explode('/',$value['Key']);
                          $title = $rec_key[1];
                          if(!empty($title))
                          {
                            if($i++ < $offset) continue;
                            if($i > $offset + $perpage) break;
                            array_push($main_content,$value);
                          }
                        }
                      }
                      //$result_count = count($main_content)-1;
                      $result_count = count($main_content);
                      if($result_count>0)
                      {
                        $content_array_sorted = $this->sort_s3_objects_by_date_admin($main_content);
                        $columns = $this->get_columns();
                        $this->_column_headers[0] = $columns;
                        $this->items = $content_array_sorted;
                      }
                    }
                }
            }
        }
    }



function extra_tablenav( $which ) {
      if ( $which == "top" ){
        ?>
        <a href="#" class="delete_all_button"><?php esc_html_e( 'Delete', 'offload-videos-bunny-netaws-s3' );?></a>
        <?php
     }
   }

    function display_rows() 
    {
      global $_wp_column_headers;
      $screen = get_current_screen();
      $columns = $this->_column_headers[0];  
      $html='';  
      $records = $this->items;
      if(!empty($records))
      {
        $streaming_connect_service = get_option('streaming_connect_service');
        if($streaming_connect_service=='amazon')
        {
          foreach($records as $rec)
          {
            //print_r($rec); die;
            $rec_key = explode('/',$rec['Key']);
            $title = $rec_key[1];
            $folder = $rec_key[0];
            if(!empty($title))
            {
             ?>
             <tr id="record_<?php echo esc_html($rec['Key']);?>" >
              <?php
             foreach ( $columns as $column_name => $column_display_name ) {
                 $class = "class='$column_name column-$column_name'";
                 $style = "";
                 $attributes = $class . $style;
                 switch ( $column_name ) 
                 {
                    case "video_select":  
                    ?>
                    <td <?php echo esc_html($attributes);?>>
                      <input type="checkbox" name="deleteSelected" class="deleteSelected" value="<?php echo esc_html($rec['Key']);?>"></td>
                    <?php   break;
                    case "video_title":  ?>
                    <td <?php echo esc_html($attributes);?>><?php echo esc_html($title);?></td> 
                    <?php  break;
                    case "video_size": ?>
                    <td <?php echo esc_html($attributes);?>><?php echo esc_html(round($rec['Size']/(1024*1024),2))?> MB</td>
                    <?php break;
                    case "video_folder":?>
                    <td <?php echo esc_html($attributes);?>><?php echo esc_html($folder);?></td>
                     <?php break;
                    case "video_date": ?><td <?php echo esc_html($attributes);?>><?php echo esc_html(date('d-m-Y H:i:s',$rec['LastModified_date']))?></td>
                    <?php
                    break;
                 }
              }
            ?></tr>
            <?php
            }
          }
        }
        elseif($streaming_connect_service=='bunny')
        {
          foreach($records as $rec)
          {
            $collectionId = $rec->collectionId;
            require_once(dirname(__DIR__).'/public/guzzle/vendor/autoload.php');
            $BUNNY_LIBRARY_ID = get_option('BUNNY_LIBRARY_ID');
            $BUNNY_ACCESS_KEY = get_option('BUNNY_ACCESS_KEY');
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', 'https://video.bunnycdn.com/library/'.$BUNNY_LIBRARY_ID.'/collections/'.$collectionId, [
            'headers' => [
            'AccessKey' => $BUNNY_ACCESS_KEY,
            'accept' => 'application/json'
            ],
            ]);
            $collection_result_arr = json_decode($response->getBody());
            $folder= $collection_result_arr->name;
            if(!empty($rec->title))
            {
            ?><tr id="record_<?php echo esc_html($rec->guid);?>" ><?php
             foreach ( $columns as $column_name => $column_display_name ) {
                 $class = "class='$column_name column-$column_name'";
                 $style = "";
                 $attributes = $class . $style;
                 switch ( $column_name ) 
                 {
                    case "video_select":  ?><td <?php echo esc_html($attributes);?>><input type="checkbox" name="deleteSelected" class="deleteSelected" value="<?php echo esc_html($rec->guid);?>"></td>
                    <?php   break;
                    case "video_title":  ?><td <?php echo esc_html($attributes);?>><?php echo esc_html($rec->title);?></td>
                    <?php  break;
                    case "video_size": ?><td <?php echo esc_html($attributes);?>><?php echo esc_html(round($rec->storageSize/(1024*1024),2));?> MB</td>
                    <?php break;
                    case "video_folder": ?><td <?php echo esc_html($attributes);?>><?php echo esc_html($folder);?></td>
                    <?php break;
                    case "video_date": ?><td <?php echo esc_html($attributes);?>><?php echo esc_html($rec->dateUploaded);?></td>
                    <?php break;
                 }
              }
            ?>
          </tr>
            <?php
            }
          }
        }
      }
    }

    public function sort_s3_objects_by_date_admin($content_array)
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

}
?>
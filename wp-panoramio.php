<?php
/*
Plugin Name: wp-panoramio
Plugin URI: http://sabirul-mostofa.blogspot.com
This plugin is the ultimate experiment of what wordpress can do
Author: Sabirul Mostofa
Version: 1.0
Author URI: http://sabirul-mostofa.blogspot.com

Description: This Plugin adds a nice Image gallery after a post using panoramio API. you can call the plugin using a function call from the theme.
If Any image fails to load it also keeps the record in a Database Table
*/


require_once( 'wp-panoramio-init.php' );

if(!class_exists('WpPanoramioUnique')):

class WpPanoramioUnique{
	var $test='';
var $prefix='panoramio_';
var $meta_box;
var $panoramio_url=null;
var $post_id;
var $photo_url_prefix;


function __construct(){	
	$this->set_meta();
$this->panoramio_url = plugins_url('/' , __FILE__);
$this->set_all_actions();
}




//All hooks are defined here

function set_all_actions(){
$instance=WpPanoramioInit::get_instance();
register_activation_hook(__FILE__, array($instance,
'wp_panoramio_init'));
	
	// WP 3.0+
 add_action('add_meta_boxes', array($this,'add_custom_box'));

// backwards compatible
add_action('admin_init', array($this,'add_custom_box'));

add_action('save_post', array($this,'panoramio_save_postdata'));



add_action('admin_init', array($this,'options_init_fn' ));
add_action('admin_menu', array($this,'options_add_page_fn'));
add_action( 'wp_ajax_nopriv_myajax-submit', array($this, 'myajax_submit' ));
add_action( 'wp_ajax_myajax-submit', array($this,'myajax_submit' ));
add_action('wp_print_scripts' , array($this,'wp_panoramio_scripts'));
add_action('wp_print_styles',array($this,'add_css_script'));
add_action('template_redirect',array($this,'set_post_id'));
add_action('save_post',array($this,'check_and_delete'));

	}
	
	
	
	//checking if corrected
	function check_and_delete($id){
				
		global $wpdb;
		
		if($data = get_post_meta($id,'panoramio_text')):
		$data=$data[0];	
		$data=trim(trim($data),';');	
		$data = explode(';',$data);
		
			$AllPhotoIds=array();
		
		foreach($data as $value):		
		 preg_match_all('/\d+/',$value,$holder);
		 $photo_id=$holder[0][1];
		 $AllPhotoIds[]=$photo_id;
		 endforeach;
		 
		 $result = $wpdb->get_row("SELECT photo_id FROM wp_panoramio WHERE post_id = '$id'", ARRAY_N);
		 
		 foreach($result as $check){
			 if(!in_array($check,$AllPhotoIds)){
				 
				 $wpdb->query("DELETE FROM wp_panoramio WHERE photo_id = '$check' and post_id = '$id'");
				 				 
				 }
			 
			 
			 }		

	


		
		
		else:
	$wpdb->query("DELETE FROM wp_panoramio WHERE post_id = '$id'");
		
		endif;
		
		}
	
	

	
	//checking if scripts are needed to load
	function if_pan_scripts_needed(){
		if($ids=get_option('wp_panoramio_post_ids')){
		$ids=unserialize($ids);
		if(in_array($this->post_id,$ids))return true;
		else return;
	}
	return;		
		}
		
		

		
	//setting the option pan_current_post_id to use for ajax calls	
	
	function set_post_id(){
		global $post;
		$this->post_id=$post->ID;
		delete_option('pan_current_post_id');
		add_option('pan_current_post_id',$this->post_id);	
		
		}
	
	
	//The function to add widget from theme
function add_widget($post_id){
	$tmp=get_option('panoramio_plugin_options');
	$testValue=get_post_meta($post_id[0],'panoramio_text');
	if(empty($testValue[0]))return;
	
	$str=get_post_meta($post_id[0],'panoramio_text');
	$str=$this->process_meta($str[0]);	

?>
<div id="div_attr_ex">
 
  <div id="div_attr_ex_photo">
    <a href="http://www.panoramio.com">Panoramio - Photos of the World</a>
  </div>
   <div id="div_attr_ex_list">
    <a href="http://www.panoramio.com">Panoramio - Photos of the World</a>
  </div>
  <div id="div_attr_ex_attr"></div>
</div>

<script type="text/javascript">
  var sand = {
	  'ids' : <?php echo $str; ?>
	  	
	  	  };
	  	    //'ids' : [{'photoId': 48058635, 'userId': 1412763}, {'photoId': 48058624, 'userId': 1412763}]
  var sandRequest = new panoramio.PhotoRequest(sand);
  var attr_ex_photo_options = {
    'width': <?php echo $tmp['text_string_width']?>,
    'height': <?php echo $tmp['text_string_height']?>,
    'attributionStyle': panoramio.tos.Style.HIDDEN};
  var attr_ex_photo_widget = new panoramio.PhotoWidget(
      'div_attr_ex_photo', sandRequest, attr_ex_photo_options);

  var attr_ex_list_options = {
    'width': <?php echo $tmp['text_string']?>,
    'height': <?php echo $tmp['text_string1']?>,
    'columns': <?php echo $tmp['text_string_columns']?>,
    'rows': 1,
    'croppedPhotos': true,
    'disableDefaultEvents': [panoramio.events.EventType.PHOTO_CLICKED],
    'orientation': panoramio.PhotoListWidgetOptions.Orientation.HORIZONTAL,
    'attributionStyle': panoramio.tos.Style.HIDDEN
    };
    
   
  var attr_ex_list_widget = new panoramio.PhotoListWidget(
    'div_attr_ex_list', sandRequest, attr_ex_list_options);


  var attr_ex_attr_options = {'width': 419};
  var attr_ex_attr_widget = new panoramio.TermsOfServiceWidget(
    'div_attr_ex_attr', attr_ex_attr_options);

  function onListPhotoClicked(event) {
    var position = event.getPosition();
    if (position !== null) attr_ex_photo_widget.setPosition(position);
  }
  panoramio.events.listen(
    attr_ex_list_widget, panoramio.events.EventType.PHOTO_CLICKED,
    function(e) { onListPhotoClicked(e); });
  //attr_ex_photo_widget.enablePreviousArrow(false);
  //attr_ex_photo_widget.enableNextArrow(false);

  attr_ex_photo_widget.setPosition(0);
  attr_ex_list_widget.setPosition(0);
  
	

	 
</script>

<?php
}

//process the meta string
function process_meta($a){
$a=trim(trim($a),';');
$ar=explode(';',$a);


	$str='[';

	foreach ($ar as $key=>$value){
	preg_match_all('/\d+/',$value,$holder);
	
	if($str!='[')$str.=',';
	$str.='{\'photoId\':'.$holder[0][1].','.'\'userId\':'.$holder[0][0].'}';
}

   
	return $str.']';

}





//loading Scripts
function wp_panoramio_scripts(){

if (!is_admin()){
if(is_single() && $this->if_pan_scripts_needed())
{		
wp_enqueue_script('jquery');

wp_enqueue_script('panoramio_api_script','http://www.panoramio.com/wapi/wapi.js?v=1');


wp_enqueue_script('wp_panoramio_script',
$this->panoramio_url.'js/wp-panoramio.js',
array('jquery'));

$nonce=wp_create_nonce('wp-panoramio');
//localizing script for passing data to javascript
wp_localize_script('wp_panoramio_script', 'WpPanoramioSettings',
array(
'ajaxurl'=>admin_url('admin-ajax.php'),
'plugin_url' => $this->panoramio_url,
      'panoramio_nonce'=>$nonce,
      'post_id'=>$this->post_id
));

}
}else
wp_enqueue_script('jquery');
 

}

function add_css_script(){
	if(!is_admin())wp_enqueue_style('panoramio_css', $this->panoramio_url.'css/style.css');
	}

//handle ajax
public static function myajax_submit(){
	$nonce=$_REQUEST['nonce'];
	$prefix = $_REQUEST['image_prefix'];
	$prefix=preg_replace( '/medium+.+/', '' , $prefix);	
	
	
	if ( ! wp_verify_nonce( $nonce, 'wp-panoramio' ) )
	die ( 'Busted! Unverified ajax Request');
	
	$data=$_REQUEST['urls'];
	//var_dump($data);
	//exit;
	$data=explode(';',$data);
	array_shift($data);
	
	$listPrefix=$prefix.'square/';
	
	
	//making the success post ids
	foreach($data as $key=>$val){
		$data[$key]=str_replace($listPrefix,'',$val);
		$data[$key]=str_replace( '.jpg' , '', $data[$key]);	
		
		}	
	
	self::process_ajax_data($prefix,$data);
	}
	
	
	
	//Insert into database
	public static function process_ajax_data($prefix,$successIds){
		$post_id=get_option('pan_current_post_id');
		$data=get_post_meta($post_id,'panoramio_text');
		$data=$data[0];	
		$data=trim(trim($data),';');
		$prefix.='medium/';	
		$data=explode(';',$data);
		$options = get_option('panoramio_plugin_options');
		
		
		if(count($successIds) >= $options['text_string_columns'])
		exit;
		
		$AllPhotoIds=array();
		
		foreach($data as $value):		
		 preg_match_all('/\d+/',$value,$holder);
		 $photo_id=$holder[0][1];
		 $AllPhotoIds[]=$photo_id;
		 endforeach;
		
		 $diff=array_diff($AllPhotoIds,$successIds);
		 
		 //var_dump($diff);
		// var_dump($data);
		
		
		foreach($data as $value):
		
		
		  preg_match_all('/\d+/',$value,$holder);
		 $user_id=$holder[0][0];
		  $photo_id=$holder[0][1];		 
		  $user_photo=$user_id.','.$photo_id;
		 // var_dump($photo_id);
		  if(!preg_match('/[0-9]/',$photo_id))continue;
		  //var_dump($photo_id);
		  
		  $photo_url=$prefix.$photo_id.'.jpg';
		  	
				 $post_id=get_option('pan_current_post_id');
				 if($post_id==0)continue;
				 date_default_timezone_set('Europe/Lisbon');
				 $date=date('Y-m-d H:i:s');
				 global $wpdb;				 
				   
                     $failure_count=0;
                     /*
                     if(in_array($photo_id,$diff)){
						 echo 'in diff';
                     echo self::exists_in_table($photo_id,$post_id);
                     }
                     //if($handle=fopen($photo_url,'r'))fclose($handle)
                     * */
				    if(!self::exists_in_table($photo_id,$post_id) && $photo_id!='0' && in_array($photo_id,$diff))
					 {		
						 //echo "found one";									 
					 $wpdb->insert( 'wp_panoramio', 
					 array( 'post_id' => $post_id, 
							 'time' => $date ,
							 'photo_id'=> $photo_id,
							 'user_photo'=>$user_photo,
							 'failure_count'=>++$failure_count
							 ),
							 array( '%s', '%s','%s','%s','%d' ) );						 
						 
					 }elseif(self::exists_in_table($photo_id,$post_id) && $photo_id!='0' && in_array($photo_id,$diff)){
						 
						 
						 $failure_count=$result=$wpdb->get_results( "SELECT failure_count FROM wp_panoramio where photo_id='$photo_id' and post_id='$post_id'",ARRAY_N );
						 $failure_count = $failure_count[0][0];						 
						  $wpdb->update( 'wp_panoramio', 
					 array( 'post_id' => $post_id, 
							 'time' => $date ,
							 'photo_id'=> $photo_id,
							 'user_photo'=>$user_photo,
							 'failure_count'=>++$failure_count
							 ),
							 array(
							 'post_id' => $post_id, 
							 'photo_id'=> $photo_id							 
							 ),
							 array( '%s', '%s','%s','%s','%d' ),
							 array('%s','%s') 
							 
							 );
						 
						 
						 
						 }
							 
				 
				 
			
			
			endforeach;
			exit;
		
		}
		
		
		/* checking if value exists in database */
		
		public static function exists_in_table($value,$post_id){
			global $wpdb;
			//$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
			$result=$wpdb->get_results( "SELECT photo_id,post_id FROM wp_panoramio where photo_id='$value' and post_id='$post_id'" );
			if(empty($result))return false;
			else return true;			

			}
	
	/***********************/
	
    //custom-field post meta
    
    function set_meta(){
			$this->meta_box = array(
		'id' => 'panoramio-meta-box',
		'title' => 'Panoramio info',
		'page' => 'post',
		'context' => 'normal',
		'priority' => 'high',
		'fields' => array(
			array(
				'name' => 'Panoramio Box',
				'desc' => ' Example:  user/725330?with_photo_id=3618248;user/725330?with_photo_id=3618248;user/725330?with_photo_id=3618248',
				'id' => $this->prefix . 'text',
				'type' => 'text',
				'std' => ''
			  )
					   )
		   );
		
		}
		
		
    function add_custom_box(){
		$meta_box=$this->meta_box;
		
		add_meta_box($meta_box['id'], $meta_box['title'], array($this,'show_box'), $meta_box['page'], $meta_box['context'], $meta_box['priority']);
		
		}
		
		
		
		
	function show_box(){
		$meta_box=$this->meta_box;
		global $post;
			
	// Use nonce for verification
	echo '<input type="hidden" name="panoramio_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
	
	echo '<table class="form-table">';

	foreach ($meta_box['fields'] as $field) {
		// get current post meta data
		$meta = get_post_meta($post->ID, $field['id'], true);
		
		echo '<tr>',
				'<th style="width:20%"><label for="', $field['id'], '">', $field['name'], '</label></th>',
				'<td>';
		switch ($field['type']) {
			case 'text':
				echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" size="30" style="width:97%" />',
					'<br />', $field['desc'];
				break;
			
		}
		echo 	'<td>',
			'</tr>';
	}
	
	echo '</table>';
			
			
			}
			



function panoramio_save_postdata($post_id){
	$meta_box=$this->meta_box;
				
				// verify nonce
	if (!wp_verify_nonce($_POST['panoramio_meta_box_nonce'], basename(__FILE__))) {
		return $post_id;
	}

	// check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}

	// check permissions
	if ('page' == $_POST['post_type']) {
		if (!current_user_can('edit_page', $post_id)) {
			return $post_id;
		}
	} elseif (!current_user_can('edit_post', $post_id)) {
		return $post_id;
	}
	
	foreach ($meta_box['fields'] as $field) {
		$old = get_post_meta($post_id, $field['id'], true);
		$new = $_POST[$field['id']];
		
		if ($new && $new != $old) {
			update_post_meta($post_id, $field['id'], $new);
		} elseif ('' == $new && $old) {
			delete_post_meta($post_id, $field['id'], $old);
		}
	}
	
	//making an option for checking if scripts needed for the page

	$post_metavalue=get_post_meta($post_id,'panoramio_text');
	
	if(empty($post_metavalue[0])){
		if($a=get_option('wp_panoramio_post_ids')):
		       $a=unserialize($a);
		       foreach($a as $key=>$value){
				   if($value==$post_id)unset($a[$key]);
				   }
				$a=serialize($a);
				update_option('wp_panoramio_post_ids',$a);		
				   
		endif;
		
		}else
	if(preg_match('/\d+/',$post_metavalue[0])):
		if($a=get_option('wp_panoramio_post_ids')){
			$a=unserialize($a);
			if(!in_array($post_id,$a)){
			$a[]=$post_id;
			$a=serialize($a);
			update_option('wp_panoramio_post_ids',$a);
		   }
			
		}else{
		$a=array($post_id);
		$a=serialize($a);
	add_option('wp_panoramio_post_ids',$a);
}
	endif;			
				
				}
	
	
	
	
	/********************************************************************************/
	/*
	 * Options page
	 * 
	 * 
	 * */

	
	// Register our settings. Add the settings section, and settings fields
function options_init_fn(){
	register_setting('panoramio_plugin_options', 'panoramio_plugin_options', array($this,'plugin_options_validate' ));
	add_settings_section('main_section', 'Main Settings', array($this,'section_text_fn'), __FILE__);
	add_settings_field('plugin_text_string', 'Thumbnail list width(Default 419)', array($this,'setting_string_fn'), __FILE__, 'main_section');
	add_settings_field('plugin_text_string1', 'Thumbnail Height(Defaullt 70)', array($this,'setting_string_fn1'), __FILE__, 'main_section');
	add_settings_field('plugin_text_string_width', 'widget width(Default 419)', array($this,'setting_string_fn2'), __FILE__, 'main_section');
	add_settings_field('plugin_text_string_height', 'widget height(Default 294)', array($this,'setting_string_fn3'), __FILE__, 'main_section');	
	
	add_settings_field('plugin_text_string_columns', 'Column Number(Default 5)', array($this,'setting_string_fn4'), __FILE__, 'main_section');	
}

// Add sub page to the Settings Menu
function options_add_page_fn() {
	add_options_page('WP Panoraimo Settings page', 'WP-Panoramio settings', 'administrator', __FILE__, array($this, 'options_page_fn'));
}


// Display the admin options page
function options_page_fn() {
?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>WP-Panoramio Settings page</h2>
		
		<form action="options.php" method="post">
		<?php settings_fields('panoramio_plugin_options'); ?>
		<?php do_settings_sections(__FILE__); ?>
		<p class="submit">
			<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
		</p>
		</form>
	</div>
<?php
}

function  section_text_fn() {
	echo '<p>If you edit the configuration,You need to edit the css file located in the plugin directory/css/style.css to change the view</p>';
}

// TEXTBOX - Name: panoramio_plugin_options[text_string]
function setting_string_fn() {
	$options = get_option('panoramio_plugin_options');
	echo "<input id='plugin_text_string' name='panoramio_plugin_options[text_string]' size='40' type='text' value='{$options['text_string']}' />";
}

// TEXTBOX - Name: panoramio_plugin_options[text_string1]
function setting_string_fn1() {
	$options = get_option('panoramio_plugin_options');
	echo "<input id='plugin_text_string1' name='panoramio_plugin_options[text_string1]' size='40' type='text' value='{$options['text_string1']}' />";
}

function setting_string_fn2() {
	$options = get_option('panoramio_plugin_options');
	echo "<input id='plugin_text_string_width' name='panoramio_plugin_options[text_string_width]' size='40' type='text' value='{$options['text_string_width']}' />";
}

function setting_string_fn3() {
	$options = get_option('panoramio_plugin_options');
	echo "<input id='plugin_text_string_height' name='panoramio_plugin_options[text_string_height]' size='40' type='text' value='{$options['text_string_height']}' />";
}
function setting_string_fn4() {
	$options = get_option('panoramio_plugin_options');
	echo "<input id='plugin_text_string_columns' name='panoramio_plugin_options[text_string_columns]' size='40' type='text' value='{$options['text_string_columns']}' />";
}

//validates data
function plugin_options_validate($input) {
	// Check our textbox option field contains no HTML tags - if so strip them out
	$input['text_string'] =  wp_filter_nohtml_kses($input['text_string']);	
	return $input; // return validated input
}




}//class ends

endif;



//Instantiating the plugin

$panoramio_init=new WpPanoramioUnique();

//Call the function from inside a loop in the theme. Use $post-ID as parameter 

if(!function_exists('add_panoramio_at_theme')){
function add_panoramio_at_theme($post_id=null){
	if(is_single()):
	global $panoramio_init;
	call_user_func(array($panoramio_init,'add_widget'),array($post_id));
	endif;
	
	}



}



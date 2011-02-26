<?php
/*A singleton class that does some initial requirements
 *
 *
 * */

//singleton class to initialize the plugin

if(!class_exists('WpPanoramioInit')):

class WpPanoramioInit{

private static $instance = null;

	private function __construct(){

		}
	
static function get_instance(){
	if(self::$instance == null)
	return self::$instance = new self();
    else
    return;
}

//checking version
function wp_panoramio_init(){
global $wp_version;
$exit_msg = 'wp-panoramio requires WordPress 2.7 or newer.
<a href = "http://codex.wordpress.org/Upgrading_WordPress">Please update!</a>';

if ( version_compare ( $wp_version, "2.7","<")){
exit ( $exit_msg );
}

$this->create_table();
$this->add_defaults_fn();

}

//creating table
function create_table(){
	
   $sql = "CREATE TABLE IF NOT EXISTS `wp_panoramio` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` varchar(20) NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `photo_id` varchar(20) NOT NULL,
  `user_photo` text NOT NULL,
  `failure_count` int unsigned NOT NULL,  
   PRIMARY KEY (`id`),
   key `photo_id`(`photo_id`),
   key `post_id`(`post_id`)
)";


global $wpdb;
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

	}
	
	
// Define default option settings
function add_defaults_fn() {
	
	delete_option('panoramio_plugin_options');
	$tmp = get_option('panoramio_plugin_options');
	$arr = array("text_string" => 419, 
		             "text_string1"=>70,
		             "text_string_width"=>419,
		             "text_string_height"=>294,
		             "text_string_columns"=>5		             
		             );
    if(is_array($tmp)) {		
		update_option('panoramio_plugin_options', $arr);
	}else{
		add_option('panoramio_plugin_options', $arr);
		}
}
	
	



}

endif;

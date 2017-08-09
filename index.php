<?php
/*
Plugin Name: Push Notifications by Device Push
Description: Direct and effective communication in real time. A new way to communicate with your customers: Communicate in a personalized way and in real time with your customers. Increase the conversion rate of your campaigns. Increase your customers' commitment to your brand. Manage your campaigns from an intuitive and easy to use control panel: Plan, segment and analyze your campaigns, and make better decisions.
Author: Device Push
Author URI: www.devicepush.com
Version: 1.2
*/

// Add hook for front-end <head></head>
function create_devicepush_js(){
	//Get language user visit web
	$accept = strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
	$lang = explode( ",", $accept);
	$language_first = explode('-',$lang[0]);
	$language = $language_first[0];
	//Get data from wordpress site
	$site_name = get_bloginfo('name');
	$site_version = get_bloginfo('version');
	$site_wpurl = get_bloginfo('wpurl');
	$site_language = get_bloginfo('language');
	//Get data from user
	$user = wp_get_current_user();
	if (isset($user->ID)) {
		$site_userid = $user->ID;
	}else{
		$site_userid = '';
	}
    $mainfest_file = plugins_url( 'sdk/manifest.json.php', __FILE__ );
    echo '<link rel="manifest" href="'.$mainfest_file.'">';                            
    echo '
    	<script>
    		console.log("Hi from Device Push!");
    		function initDevicePush(){
    			devicePush.register({
			    	idUser: "'.get_option('dp_option_iduser').'",
			    	idApplication: "'.get_option('dp_option_idaplication').'",
			    	additionalData: {
			    		cms: "Wordpress",
			    		name: "'.$site_name.'",
			    		version: "'.$site_version.'",
			    		url: "'.$site_wpurl.'",
			    		language: "'.$site_language.'",
			    		userid: "'.$site_userid.'",
			    		userlanguage: "'.$language.'"
			    	}
			    });
    		}
    		document.addEventListener("DOMContentLoaded", function(event) {
    			initDevicePush();
			});
    	</script>
    ';
    $sw_data_array = array( 'file' => plugins_url( 'js/sw.js', __FILE__ ) );
    wp_enqueue_script(
        'devicepush',
        plugins_url('js/devicepush.js?v1'.time(), __FILE__)
    );
    wp_localize_script( 'devicepush', 'sw', $sw_data_array );
}

function init_devicepush_js() {
    if (get_option('devicepush_fcm') == FALSE || get_option('devicepush_app_name') == FALSE){
		$postData = array( 
		    'idApplication' => get_option('dp_option_idaplication')
		);
		$context = stream_context_create(array(
		    'http' => array(
		        'method' => 'POST',
		        'header' => 'token: '.get_option('dp_option_iduser'),
		        'content' => http_build_query($postData)
		    )
		));
		$url = 'http://api.devicepush.com/1.0/list/applications/';
		$result = file_get_contents($url, false, $context);
		if($result){
			$json = json_decode($result, true);
			foreach ($json as $key => $val) {
				if($key == 'name'){
					add_option( 'devicepush_app_name', $val['name']);
				}
				if($key == 'fcmsenderid'){
					add_option( 'devicepush_fcm', $val['fcmsenderid']);
				}
			}
			create_devicepush_js();
	    }
    }else{
	 	create_devicepush_js();   
    }
}
add_action('wp_enqueue_scripts', 'init_devicepush_js');

// Add hook for back-end <head></head>
function admin_devicepush_js() {
    wp_enqueue_style( 
    	'devicepush',
    	plugins_url('/css/devicepush.css?v5', __FILE__)
    );
}
add_action('admin_enqueue_scripts', 'admin_devicepush_js');

// Create custom plugin settings menu
add_action('admin_menu', 'dp_create_menu');

function dp_create_menu() {
	add_menu_page('Device Push Plugin Settings', 'Device Push', 'administrator', __FILE__, 'dp_settings_page',plugins_url('/images/icon-small.png', __FILE__));
	add_action( 'admin_init', 'register_mysettings' );
}

// Function send notification API
add_action( 'publish_post', 'SendNotificacion', 10, 2);
function SendNotificacion($ID, $post) {
	if(esc_attr( get_option('dp_option_iduser') ) && esc_attr( get_option('dp_option_idaplication') ) && esc_attr( get_option('dp_option_status') )){
		if(has_post_thumbnail()){
			$thumbnail = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );
		}else{
			$thumbnail = plugins_url('/images/logo-device.png', __FILE__);
		}
		$postData = array( 
			'idApplication' => esc_attr( get_option('dp_option_idaplication') ),			
		    'title' => $post->post_title,
		    'content' => $post->post_content,
		    'icon' => $thumbnail,
		    'data' => '[{"action": "open", "url": "'.get_permalink($post->ID).'"}]'
		);
		 
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => 'token: '.esc_attr( get_option('dp_option_iduser') ),
				'content' => http_build_query($postData)
			)
		));
		 
		$url = 'http://api.devicepush.com/send';
		$result = file_get_contents($url, false, $context);
	}
}
// Function send notification API
add_action( 'publish_page', 'SendNotificacionPage', 10, 2);
function SendNotificacionPage($ID, $post) {
	if(esc_attr( get_option('dp_option_iduser') ) && esc_attr( get_option('dp_option_idaplication') ) && esc_attr( get_option('dp_option_status_page') )){
		if(has_post_thumbnail()){
			$thumbnail = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );
		}else{
			$thumbnail = plugins_url('/images/logo-device.png', __FILE__);
		}
		$postData = array( 
			'idApplication' => esc_attr( get_option('dp_option_idaplication') ),			
		    'title' => $post->post_title,
		    'content' => $post->post_content,
		    'icon' => $thumbnail,
		    'data' => '[{"action": "open", "url": "'.get_permalink($post->ID).'"}]'
		);
		 
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => 'token: '.esc_attr( get_option('dp_option_iduser') ),
				'content' => http_build_query($postData)
			)
		));
		 
		$url = 'http://api.devicepush.com/send';
		$result = file_get_contents($url, false, $context);
	}
}

// Set value input form
function register_mysettings() {
	register_setting( 'dp-settings-group', 'dp_option_iduser' );
	register_setting( 'dp-settings-group', 'dp_option_idaplication' );
	register_setting( 'dp-settings-group', 'dp_option_status' );
	register_setting( 'dp-settings-group', 'dp_option_status_page' );
}

function dp_settings_page() {
?>
<div class="wrap">

	<div class="dp_contain">
	<h1><img src="<?php echo plugins_url('/images/logo-device.png', __FILE__); ?>" class="dp_iconlogo"><span class="dp_blue">Device</span> <span class="dp_grey">Push</span> for Wordpress</h1>
	<h4 class="dp_grey">Direct and effective communication in real time. Push Notifications for Apps and Webs</h4>
	<hr>
	
	<table class="form-table"><tr><td><h2>How can I start?</h2></td></tr></table>
	
	<div class="col">
	<form method="post" action="options.php" style="padding-right:40px">
	    <?php settings_fields( 'dp-settings-group' ); ?>
	    <?php do_settings_sections( 'dp-settings-group' ); ?>
		<table class="form-table">  

			<tr>
				<td>
					<h3>1. Active and configure your Device Push User Account</h3>
					<p>Go to <a href="https://www.devicepush.com/" target="_blank">www.devicepush.com</a> and request a user account.</p>
					<br/>
					<h3>2. Create into adminitration panel of Device Push your first application/web</h3>
					<p>Go to <a href="http://panel.devicepush.com/" target="_blank">panel.devicepush.com</a> and create your first app/web and copy your "User ID" and your "Application ID" and paste into the next form.</p>
				</td>
			</tr>
	        <tr class="dp_form" valign="top">
		        <td class="dp_titleinputform">
		        	<span class="dp_blue">User ID:</span>
		        	<input type="text" class="dp_input_text" name="dp_option_iduser" value="<?php echo esc_attr( get_option('dp_option_iduser') ); ?>" />
		        </td>
	        </tr>
	        <tr valign="top">
	        	<td class="dp_titleinputform">
	        		<span class="dp_blue">Application ID:</span>
	        		<input type="text" class="dp_input_text" name="dp_option_idaplication" value="<?php echo esc_attr( get_option('dp_option_idaplication') ); ?>" />
	        	</td>
	        </tr>
	        <tr valign="top">
	        	<td>
	        		<?php submit_button('Sincronice account') ?>
	        	</td>
	        </tr>
	        
	        <tr>
		        <td>
		        	<h3>3. Active when you want send your push notifications</h3>
		        </td>
	        </tr>
	        <tr valign="top">
		        <td>
		        <div class="check">
		        	<input type="checkbox" name="dp_option_status" <?php if (esc_attr( get_option('dp_option_status') )){echo 'checked'; } ?>> Each time I post an article on my blog.
		        </div>
		        <div class="check">
		        	<input type="checkbox" name="dp_option_status_page" <?php if (esc_attr( get_option('dp_option_status_page') )){echo 'checked'; } ?>> Each time I post a new page.
		        </div>
		        </td>
		    </tr>
		    <tr valign="top">
		        <td><?php submit_button('Active') ?></td>
	        </tr>
	    </table>
	    
	    <p>Find more information about Device Push in its website: <a href="https://www.devicepush.com/" target="_blank">www.devicepush.com</a></p>
	    <p>Follow us in:</p>
	    
	<div style="height:30px; line-height:30px"><table><tr><td><img src="<?php echo plugins_url('/images/twitter.png', __FILE__); ?>" style="width: 20px; height: auto; margin-right: 5px; margin-bottom: -5px;"></td><td>Twitter: <a href="https://twitter.com/devicepush" target="_blank">@devicepush</a></td></tr></table></div>
	 
	<div style="height:30px; line-height:30px"><table><tr><td><img src="<?php echo plugins_url('/images/facebook.png', __FILE__); ?>" style="width: 20px; height: auto; margin-right: 5px; margin-bottom: -5px;"></td><td>Facebook: <a href="https://fb.com/devicepush" target="_blank">fb.com/devicepush</a></td></tr></table></div>
	
	<div style="height:30px; line-height:30px"><table><tr><td><img src="<?php echo plugins_url('/images/linkedin.png', __FILE__); ?>" style="width: 20px; height: auto; margin-right: 5px; margin-bottom: -5px;"></td><td>Linkedin: <a href="https://www.linkedin.com/company/9418990" target="_blank">fb.com/devicepush</a></td></tr></table></div>
	
	</form>
	</div>
	<div class="col">
		<img class="dp_maciphone" src="<?php echo plugins_url('/images/bg-mac-iphone-en.jpg', __FILE__); ?>">
	</div>
	
	</div>
</div>
<?php } ?>
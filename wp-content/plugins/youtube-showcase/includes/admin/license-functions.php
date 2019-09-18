<?php
/**
 * License Functions
 *
 * @package     EMD
 * @copyright   Copyright (c) 2014,  Emarket Design
 * @since       WPAS 4.2
 */
if (!defined('ABSPATH')) exit;

add_action('emd_show_license_page','emd_show_license_page',1);
/**
 * Show settings page for licenses
 *
 * @param string $app
 * @since WPAS 4.2
 *
 * @return html page content
 */

function emd_show_license_page($app){
	//display app license and all extension licenses	
	global $title;
	$settings = get_option('emd_license_settings');
	?>
		<div class="wrap emd-licenses">
		<h2><?php echo $title; ?></h2>
		<p><?php _e('Keeping your software updated is the best and easiest thing you can do to make sure your site stays up and running. We regularly update our products to fix defects, add new features or ensure compatibility with the latest releases of WordPress.</p><p>Please enter and activate your <b>license keys</b>.', 'emd-plugins'); ?></p>
		<form method="post" action="options.php">
		<table class="form-table">
		<tbody>
		<?php 
		settings_fields('emd_licenses'); 
	if(!empty($settings[$app])){
		emd_show_license_row($app,$settings[$app]['name']);
	}
	foreach($settings as $key => $val){
		if($val['type'] == 'ext'){
			emd_show_license_row($key,$val['name']);
		}
	} 
	?>
		</tbody>
		</table>
		<?php submit_button(); ?>
		</form>
		</div>
		<?php
}

/**
 * Show input field and license activate/deactivate link for app and each extension
 *
 * @param string $app
 * @param string $name license name
 * @since WPAS 4.2
 *
 * @return html page content
 */
function emd_show_license_row($app,$name){
	$license = false;
	$licenses = get_option('emd_licenses',Array());
	$status = get_option($app . '_license_status','inactive');
	$error = get_option($app . '_license_error','');
	if(!empty($licenses) && isset($licenses[$app . '_license_key'])){
		$license = $licenses[$app . '_license_key'];
	}
	$lic_status_class = '';
	$expires = get_option($app . '_license_expires','');
	?>
		<tr>
		<th scope="row">
		<?php echo $name; ?>
		</th>
		<td>
		<input id="<?php esc_attr_e($app) ?>_license_key" name="emd_licenses[<?php esc_attr_e($app) ?>_license_key]" type="text" class="regular-text" value="<?php esc_attr_e($license); ?>" />
		<?php if (false !== $license) { ?>
		<input type="hidden" id="<?php esc_attr_e($app) ?>_license_status" name="<?php esc_attr_e($app) ?>_license_status" value="<?php echo $status;?>">
			<?php if ($status == 'valid') { 
				wp_nonce_field($app . '_license_nonce', $app . '_license_nonce'); ?>
					<input type="submit" class="button-secondary" name="<?php esc_attr_e($app) ?>_license_deactivate" value="<?php _e('Deactivate License', 'emd-plugins'); ?>"/>
					<?php
			} else {
				$lic_status_class = 'license-no-key';
				$messages[] = __('To receive updates, please activate your license key.', 'emd-plugins' );
				wp_nonce_field($app . '_license_nonce', $app . '_license_nonce'); ?>
					<input type="submit" class="button-secondary" name="<?php esc_attr_e($app) ?>_license_activate" value="<?php _e('Activate License', 'emd-plugins'); ?>"/>
					<?php
			}
		}
		else {
			$lic_status_class = 'license-no-key';
			$messages[] = __('To receive updates, please enter your valid license key.', 'emd-plugins' );
		}
		if(!empty($error)){
			$messages[] = $error;
			$lic_status_class = 'license-error-notice';
			$status = "error";
			if(preg_match('/key expired/',$error)){
				$status = "expired";
				$lic_status_class = 'license-expired-notice';
			}
		}
		elseif(!empty($expires) && ($status == 'valid' || $status == 'expired')){
			$now = current_time('timestamp');
			$expiration = strtotime($expires, current_time('timestamp'));
			if('lifetime' === $expires){
				$messages[] = __( 'Your license key is valid and never expires.', 'emd-plugins' );
				$lic_status_class = 'license-lifetime-notice';
			}
			elseif($expiration > $now && $expiration - $now < (DAY_IN_SECONDS * 30)){
				$messages[] = sprintf(
				__( 'Your license key expires soon! It expires on %s. <a href="%s" target="_blank">Renew your license key</a>.', 'emd-plugins' ),
				date_i18n(get_option('date_format'), strtotime($expires, current_time('timestamp'))),
				'https://emdplugins.com/checkout/?edd_license_key=' . $license . '&pk_campaign=admin&pk_source=licenses&pk_medium=renew'
				);
				$lic_status_class = 'license-expires-soon-notice';
			} elseif($expiration < $now){
				$messages[] = sprintf(
				__( 'Your license key expired on %s. <a href="%s" target="_blank">Renew your license key</a>.', 'emd-plugins' ),
				date_i18n(get_option('date_format'), strtotime($expires, current_time('timestamp'))),
				'https://emdplugins.com/checkout/?edd_license_key=' . $license . '&pk_campaign=admin&pk_source=licenses&pk_medium=renew'
				);
				$status = "expired";
				$lic_status_class = 'license-expired-notice';
				
			} else {
				$messages[] = sprintf(
					__( 'Your license key is valid and expires on %s.', 'emd-plugins' ),
					date_i18n(get_option('date_format'), strtotime($expires, current_time('timestamp')))
					);
				$lic_status_class = 'license-expiration-date-notice';
			}
		}
		if(!empty($messages)){
			foreach($messages as $message){
				echo "<div class='emd-license-data emd-license-" . $status . " " . $lic_status_class . "'><p>" . $message . "</p></div>";
			}
		}
		?>
	</td>
		</tr>
		<?php 
}

add_action( 'admin_init', 'emd_license_register');

/**
 * Register license settings option
 *
 * @since WPAS 4.2
 *
 */
function emd_license_register(){
	if ( false == get_option( 'emd_licenses' ) ) {
                add_option( 'emd_licenses', Array() );
        }
	register_setting('emd_licenses','emd_licenses','emd_sanitize_license');
}
/**
 * Sanitize license settings
 *
 * @since WPAS 4.2
 * @param array $new
 *
 * @return array $new
 */
function emd_sanitize_license($new){
	$old = get_option('emd_licenses',Array());
        if(!is_array($old)){
                $old = Array();
        }
	if(empty($_POST)){ return $new; }
	if(!isset($_POST['submit'])) { return $old; }
	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $old;
        }
	foreach($new as $nkey => $nval){
		if(preg_match('/_license_key$/',$nkey)){
			$match = str_replace("_license_key","",$nkey);
			if((!empty($old[$nkey]) && $new[$nkey] != $old[$nkey]) || empty($old[$nkey])){
				update_option($match.'_license_status','inactive');
				update_option($match . '_license_error', '');
			}
			if(empty($new[$nkey])){
				unset($old[$nkey]);
			}
			else {
				$old[$nkey] = $new[$nkey];
			}
		}
	}
	return $old;
}

add_action( 'admin_init', 'emd_activate_deactivate_license');
/**
 * Activate/Deactivate license by calling edd api on plugin author's site
 * @since WPAS 4.0
 *
 */
function emd_activate_deactivate_license() {
	if(!isset($_POST['emd_licenses'])) return;
	if(isset($_POST['submit'])) return;
	$license_action = "";
	$license_on = "";
	$license_settings = get_option('emd_license_settings');
	
	if(!empty($license_settings)){
		foreach($license_settings as $key => $val){
			$license_status = get_option($key . '_license_status','');
			if($license_status != 'valid' && isset($_POST[$key . '_license_activate'])){
				$license_action = "activate";
				$license_on = $key;
				break;
			} elseif ($license_status != 'deactivated' && isset($_POST[$key . '_license_deactivate'])) {
				$license_action = "deactivate";
				$license_on = $key;
				break;
			}
		}
	}
	if (!empty($license_action)) {
		$post_licenses = $_POST['emd_licenses'];
		// run a quick security check
		if (!check_admin_referer($license_on . '_license_nonce', $license_on . '_license_nonce')) return;
		// retrieve the license from the database
		$license = trim($post_licenses[$license_on . '_license_key']);
		// data to send in our API request
		$api_params = array(
				'edd_action' => $license_action . '_license',
				'license' => $license,
				'item_name' => urlencode(constant(strtoupper($license_on) . '_EDD_ITEM_NAME')) , // the name of product
				'url' => home_url()
				);

		// Call the custom API.
		$response = wp_remote_post(constant(strtoupper($license_on) . '_EDD_STORE_URL') , array(
					'timeout' => 15,
					'sslverify' => false,
					'body' => $api_params
					));
		$error = '';
		// make sure the response came back okay
		if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
			if(is_wp_error($response)){
                                $error = $response->get_error_message();
                        } else {
                                $error = __('An error occurred, please try again.','emd-plugins');
                        }
			update_option($license_on . '_license_error', $error);
			return false;
		}
		// decode the license data
		$license_data = json_decode(wp_remote_retrieve_body($response));

		if($license_action == 'activate' && false === $license_data->success){
			switch($license_data->error){
				case 'expired':
					$error = sprintf(
						__('Your license key expired on %s. Please <a href="%s" target="_blank">renew your license key</a>.','emd-plugins'),
						date_i18n(get_option('date_format'), strtotime($license_data->expires, current_time('timestamp'))), 'https://emdplugins.com/checkout/?edd_license_key=' . $license . '&pk_campaign=admin&pk_source=licenses&pk_medium=expired'
					);
					break;
				case 'revoked':
					$error = sprintf(
						__( 'Your license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', 'emd-plugins' ),
						'https://emdplugins.com/support?pk_campaign=admin&pk_source=licenses&pk_medium=revoked'
					);
					break;
				case 'missing':
					$error =  sprintf(
							__( 'Invalid license. Please <a href="%s" target="     _blank">visit your account page</a> and verify it.', 'emd-plugins' ),
							'https://emdplugins.com/myaccount?pk_campaign=admin&pk_source=licenses&pk_medium=missing'
					);
					break;
				case 'invalid':
				case 'site_inactive':
					$error = sprintf(
						__( 'Your license is not active for this URL. Please <a href="%s" target="_blank">visit your account page</a> to manage your license key URLs.', 'emd-plugins' ),
						'https://emdplugins.com/myaccount?pk_campaign=admin&pk_source=licenses&pk_medium=invalid'
                                                );
					break;
				case 'item_name_mismatch':
					$error = sprintf(__('This appears to be an invalid license key for %s.','emd-plugins'), constant(strtoupper($license_on) . '_EDD_ITEM_NAME'));
					break;

				case 'no_activations_left':
					$error = sprintf( __( 'Your license key has reached its activation limit. <a href="%s">View possible upgrades</a> now.', 'emd-plugins' ), 'https://emdplugins.com/myaccount/' );
					break;
				default :
					$error = sprintf( __( 'There was an error with this license key: %s. Please <a href="%s">contact our support team</a>.', 'emd-plugins' ), $error, 'https://emdplugins.com/support' );
					break;
			}
			if(!empty($error)){
				update_option($license_on . '_license_error', $error);
				return false;
			}
		}
		update_option($license_on . '_license_error', '');

		if ($license_action == 'activate') {
			update_option($license_on . '_license_status',$license_data->license);
			update_option($license_on . '_license_expires',$license_data->expires);
			if(!empty($license_data->purchase_date)){
				update_option($license_on . '_license_purchase_date',$license_data->purchase_date);
			}
			update_option($license_on . '_license_price_id',$license_data->price_id);
			do_action('emd_license_run_install');
		} elseif ($license_action == 'deactivate' && $license_data->license == 'deactivated') {
			update_option($license_on . '_license_status',$license_data->license);
		}
	}
}
function emd_check_license_weekly($license,$license_on){
	// data to send to API request
	$api_params = array(
		'edd_action'=> 'check_license',
		'license'       => $license,
		'item_name' => urlencode(constant(strtoupper($license_on) . '_EDD_ITEM_NAME')),
		'url'       => home_url()
	);

	// Call the API
	$response = wp_remote_post(
		constant(strtoupper($license_on) . '_EDD_STORE_URL'),
		array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		)
	);

	// make sure the response came back okay
	if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
		if(is_wp_error($response)){
			$error = $response->get_error_message();
		} else {
			$error = __('An error occurred, please try again.','emd-plugins');
		}
		update_option($license_on . '_license_error', $error);
		return false;
	}

	$license_data = json_decode(wp_remote_retrieve_body($response));
	update_option($license_on . '_license_status', $license_data->license);
	update_option($license_on . '_license_error', '');
	update_option($license_on . '_license_expires',$license_data->expires);
	return $license_data->license;
}
function emd_check_license_notices($license_on,$plugin_name,$plugin_roles){
	$check_lic = 0;
	if(current_user_can('manage_options')){
		$check_lic = 1;
	}
	elseif(!empty($plugin_roles)){
		$user = wp_get_current_user();
		foreach($plugin_roles as $prole){
			if(in_array($prole,(array) $user->roles)) {
				$check_lic = 1;
				break;
			}
		}
	}
	if($check_lic == 0) return;		
	$message = '';
	$licenses = get_option('emd_licenses',Array());
	if(!empty($licenses) && isset($licenses[$license_on . '_license_key'])){
		$license = $licenses[$license_on . '_license_key'];
		//check status 
		$status = get_option($license_on . '_license_status','inactive');
		$error = get_option($license_on . '_license_error','');
		if($status == 'expired'){
			$message = 'message_expired';
		}	
		elseif($status != 'valid' && empty($error)){
			$message = 'message_activate';
		}
		elseif($status != 'valid' && !empty($error)){
			$message = 'message_invalid';
		}
		else{
			//get next license check date and see now > 
			$next_check = get_option($license_on . '_next_license_check','');
			if(!empty($next_check)){
				$today = date('Y-m-d');
				if($today >= $next_check){
					if(!empty($error)){
						$message = 'message_invalid';
					}
					else {
						$lic_check = emd_check_license_weekly($license,$license_on);
						if($lic_check != 'valid'){
							$message = 'message_invalid';
						}
					}
				}	
			}
			$next_check = date('Y-m-d', strtotime('+3 days'));
			update_option($license_on . '_next_license_check',$next_check);
		}
	}
	else {
		$message = 'message_activate';
	}
	if($message == 'message_expired'){
		if(current_user_can('manage_options')){	
		echo '<div class="error"><p>' . sprintf(__('Your %s license has expired. <a href="%s" target="_blank">Renew your license</a> to protect your investment and keep your software functioning at all times.','emd-plugins'), $plugin_name,'https://emdplugins.com/checkout/?edd_license_key=' . $license . '&pk_campaign=admin&pk_source=licenses&pk_medium=renew') . '</p></div>';
		}
		else {
			echo '<div class="error"><p>' . sprintf(__('Please notify the site administrator to activate expired %s license.','emd-plugins'), $plugin_name) . '</p></div>';
		}
	}
	elseif($message == 'message_activate'){
		if(current_user_can('manage_options')){	
			echo '<div class="error"><p>' . sprintf(__('Thanks for installing %s. Please <a href="%s">activate your license key</a> before start using.','emd-plugins'), $plugin_name,admin_url("admin.php?page=" . $license_on . "_licenses")) . '</p></div>';
		}
		else {
			echo '<div class="error"><p>' . sprintf(__('Please notify the site administrator to activate %s license.','emd-plugins'), $plugin_name) . '</p></div>';
		}
	}
	elseif($message == 'message_invalid'){
		if(current_user_can('manage_options')){	
			echo '<div class="error"><p>' . sprintf(__('You have invalid or expired license for %s. Please <a href="%s">go to licenses page</a> to correct this issue.','emd-plugins'), $plugin_name,admin_url("admin.php?page=" . $license_on . "_licenses")) . '</p></div>';
		}
		else {
			echo '<div class="error"><p>' . sprintf(__('Please notify the site administrator to activate expired or invalid %s license.','emd-plugins'), $plugin_name) . '</p></div>';
		}
	}
	do_action('emd_license_price_id_check',$license_on,$plugin_name);
}
function emd_license_check_valid($license_on){
	$licenses = get_option('emd_licenses',Array());
	if(!empty($licenses) && isset($licenses[$license_on . '_license_key'])){
		$license = $licenses[$license_on . '_license_key'];
		//check status 
		$status = get_option($license_on . '_license_status','inactive');
		if(!empty($license) && ($status == 'valid' || $status == 'expired')){
			return $status;
		}
	}
	return 'inactive';
}
function emd_license_add_log($license_on,$action){
	$licenses = get_option('emd_licenses',Array());
	if(!empty($licenses) && isset($licenses[$license_on . '_license_key'])){
		$data['license_id'] = $licenses[$license_on . '_license_key'];
		$data['plugin_name'] = $license_on;
		$data['plugin_version'] = constant(strtoupper($license_on) . '_VERSION');
		$data['action'] = $action;
		$resp = wp_remote_post('https://api.emarketdesign.com/add_license_log.php', array(
			'sslverify' => false,
			'body' => $data,
			));
	}
}

<?php
/**
 * Login/Password Functions
 *
 * @package     EMD
 * @copyright   Copyright (c) 2014,  Emarket Design
 * @since       WPAS 4.0
 */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

require_once 'settings-functions-login.php';
add_action('init', 'emd_form_builder_login_actions');

function emd_form_builder_login_actions(){
	if(!empty($_GET['emd_action']) && $_GET['emd_action'] == 'logout'){
		//check_admin_referer('log-out');
		if(!wp_verify_nonce($_GET['_wpnonce'],'log-out')){
        		wp_logout();
			wp_safe_redirect(site_url());
			exit();
		}	
        	$user = wp_get_current_user();
		$redirect_to = '';
		if(!empty($_GET['emd_app'])){
			$login_settings = get_option($_GET['emd_app'] . '_login_settings');
			foreach($user->roles as $urole){
				if(!empty($login_settings['redirect_logout'][$urole])){
					$redirect_to = $login_settings['redirect_logout'][$urole];
					break;
				}
			}
		}
        	wp_logout();
		if(!empty($redirect_to)){
			wp_redirect($redirect_to);
			exit();
		}
		$url = remove_query_arg(array('emd_action','_wpnonce'));
		wp_safe_redirect(add_query_arg(array('loggedout'=>'true'),$url));
		exit();
	}
	if(!empty($_POST['emd_action']) && $_POST['emd_action'] == 'login'){
		//If the user wants ssl but the session is not ssl, force a secure cookie.
		$secure_cookie = '';
		if (!empty($_POST['emd_user_login']) && !force_ssl_admin() ) {
			$user_name = sanitize_user($_POST['emd_user_login']);
			$pass1 = $_POST['emd_user_pass'];
			$user = get_user_by('login', $user_name);

			if (!$user && strpos($user_name, '@')){
				$user = get_user_by('email', $user_name);
			}
			if ($user){
				if(get_user_option('use_ssl', $user->ID)){
					$secure_cookie = true;
					force_ssl_admin(true);
				}
			}
		}
		if (isset($_REQUEST['redirect_to'])) {
			$redirect_to = $_REQUEST['redirect_to'];
			// Redirect to HTTPS if user wants SSL.
			if ( $secure_cookie && false !== strpos( $redirect_to, 'wp-admin' ) ) {
				$redirect_to = preg_replace( '|^http://|', 'https://', $redirect_to );
			}
		}
		else {
			$redirect_to = '';
		}

		$user = wp_signon(array('user_login' => $_POST['emd_user_login'], 'user_password' => $_POST['emd_user_pass']), $secure_cookie);
		if ( empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			if ( headers_sent() ) {
				/* translators: 1: Browser cookie documentation URL, 2: Support forums URL */
				$user = new WP_Error( 'test_cookie', sprintf( __( '<strong>ERROR</strong>: Cookies are blocked due to unexpected output. For help, please see <a href="%1$s">this documentation</a> or try the <a href="%2$s">support forums</a>.' ),
					__( 'https://codex.wordpress.org/Cookies' ), __( 'https://wordpress.org/support/' ) ) );
			} elseif ( isset( $_POST['testcookie'] ) && empty( $_COOKIE[ TEST_COOKIE ] ) ) {
				// If cookies are disabled we can't log in even with a valid user+pass
				/* translators: 1: Browser cookie documentation URL */
				$user = new WP_Error( 'test_cookie', sprintf( __( '<strong>ERROR</strong>: Cookies are blocked or not supported by your browser. You must <a href="%s">enable cookies</a> to use WordPress.' ),
					__( 'https://codex.wordpress.org/Cookies' ) ) );
			}
		}
		if ( !is_wp_error($user) && !$reauth ) {
			//we are logged in lets redirect to users page
			if(empty($redirect_to) && !empty($_POST['emd_app'])){
				$login_settings = get_option($_POST['emd_app'] . '_login_settings');
				foreach($user->roles as $urole){
					if(!empty($login_settings['redirect_login'][$urole])){
						$redirect_to = $login_settings['redirect_login'][$urole];
						break;
					}
				}
			}
			if(!empty($redirect_to)){
				wp_redirect($redirect_to);
				exit;
			}
			$redirect_to = home_url();
			$ent_list = get_option($_POST['emd_app'] . '_ent_list');
			foreach($ent_list as $kent => $vent){
				if(!empty($vent['limit_user_roles'])){
					foreach($user->roles as $urole){
						if(in_array($urole,$vent['limit_user_roles']) && !empty($vent['user_key'])){
							//go to entity post
							$args = Array('posts_per_page' => 1, 'post_type' => $kent, 
								'meta_key' => $vent['user_key'], 'meta_value' => $user->ID,'fields'=>'ids');
							$posts = get_posts($args);
							if(!empty($posts[0])){
								$redirect_to = get_permalink($posts[0]);
								break;
							}
						}
					}
				}
			}	
			wp_safe_redirect($redirect_to);
			exit;
		}
		$errors = $user;
		$url = remove_query_arg('emd_action');
		$err_code = $errors->get_error_code();
		if(in_array($err_code, Array('incorrect_password','invalid_username','invalid_email','invalidcombo'))){
			$err_code = 'invalid_login';
		}
		$url = add_query_arg(array('emd_error'=>$err_code),$url);
		wp_safe_redirect($url);
		exit;
        }
	if(!empty($_GET['emd_action']) && $_GET['emd_action'] == 'rp' && !empty($_GET['emd_key']) && !empty($_GET['emd_login'])){
		list($rp_path) = explode( '?', wp_unslash($_SERVER['REQUEST_URI']));
		$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
		$emd_key = $_GET['emd_key'];
		$emd_login = $_GET['emd_login'];
		$value = sprintf('%s:%s', wp_unslash($emd_login), wp_unslash($emd_key));
		setcookie($rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
		wp_safe_redirect(remove_query_arg(array('emd_key', 'emd_login')));
		exit;
	}
	if(!empty($_POST['emd_action']) && $_POST['emd_action'] == 'getpass'){
		$url = remove_query_arg(array('emd_action','emd_error')); 
		$errors = emd_shc_login_get_password($url);
		if ( !is_wp_error($errors) ) {
			wp_safe_redirect(add_query_arg(array('checkemail'=>'confirm'),$url));
			exit();
		}
		wp_safe_redirect(add_query_arg(array('emd_action'=> 'lostpass', 'emd_error'=>$errors->get_error_code()),$url));
		exit();
	}
	if(!empty($_GET['emd_action']) && $_GET['emd_action'] == 'resetpass'){
		list($rp_path) = explode( '?', wp_unslash($_SERVER['REQUEST_URI']));
		$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
		if(isset($_COOKIE[$rp_cookie]) && 0 < strpos($_COOKIE[$rp_cookie], ':' )){
			list($rp_login, $rp_key) = explode(':', wp_unslash($_COOKIE[$rp_cookie]), 2);
			$user = check_password_reset_key($rp_key, $rp_login);
			if(isset($_POST['pass1']) && !hash_equals($rp_key, $_POST['emd_key'])){
				$user = false;
			}
		}
		else {
			$user = false;
		}
		if(!$user || is_wp_error($user)) {
			setcookie($rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true);
			if ($user && $user->get_error_code() === 'expired_key'){
				wp_safe_redirect(add_query_arg(array('emd_action'=> 'lostpass', 'emd_error' => 'expiredkey')));
			}
			else{
				wp_safe_redirect(add_query_arg(array('emd_action'=> 'lostpass', 'emd_error' => 'invalidkey')));
			}
			exit;
		}
		$errors = new WP_Error();
		/*if(isset($_POST['pass1']) && $_POST['pass1'] != $_POST['pass2']){
			$errors->add('password_reset_mismatch', __('The passwords do not match.'));
		}*/
		do_action('validate_password_reset', $errors, $user);
		if((!$errors->get_error_code()) && isset($_POST['pass1']) && !empty($_POST['pass1'])){
			reset_password($user, $_POST['pass1']);
			setcookie($rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true);
			wp_safe_redirect(add_query_arg(array('emd_action' => 'reset_success')));
			exit;
        	}
	}
}
add_shortcode('emd_login','emd_form_builder_login');

function emd_form_builder_login($atts){
	$errors = new WP_Error();
	if(isset($_GET['emd_error'])){
		if('invalidkey' == $_GET['emd_error']) {
			$errors->add('invalidkey', __( 'Your password reset link appears to be invalid. Please request a new link below.','emd-plugins'));
		}
		elseif('expiredkey' == $_GET['emd_error']){
			$errors->add('expiredkey', __( 'Your password reset link has expired. Please request a new link below.','emd-plugins'));
		}
		elseif('invalid_login' == $_GET['emd_error']){
			$errors->add('invalid_username', __( 'Invalid username, email or password.','emd-plugins'));
		}
		elseif('validate_email' == $_GET['emd_error']){
			$errors->add('validate_email', __( 'We have sent you a welcome email. Please click the verification link in the email to get started.','emd-plugins'));
		}
		/*elseif('incorrect_password' == $_GET['emd_error']){
			$errors->add('incorrect_password', __( 'Invalid username or password.','emd-plugins'));
		}
		elseif('invalid_username' == $_GET['emd_error']){
			$errors->add('invalid_username', __( 'Invalid username or password.','emd-plugins'));
		}
		elseif('invalid_email' == $_GET['emd_error']){
			$errors->add('invalid_email', __( 'Invalid email.','emd-plugins'));
		}
		elseif('invalidcombo' == $_GET['emd_error']){
			$errors->add('invalidcombo', __( 'Invalid username or email.','emd-plugins'));
		}*/
	}
	if(!empty($_GET['loggedout']) || $reauth){
		$errors = new WP_Error();
	}
	// Some parts of this script use the main login form to display a message
	if (isset($_GET['loggedout']) && true == $_GET['loggedout'] )
		$errors->add('loggedout', __('You are now logged out.'), 'message');
	elseif  ( isset($_GET['registration']) && 'disabled' == $_GET['registration'] )
		$errors->add('registerdisabled', __('User registration is currently not allowed.'));
	elseif  ( isset($_GET['checkemail']) && 'confirm' == $_GET['checkemail'] )
		$errors->add('confirm', __('Check your email for the confirmation link.'), 'message');
	elseif  ( isset($_GET['checkemail']) && 'newpass' == $_GET['checkemail'] )
		$errors->add('newpass', __('Check your email for your new password.'), 'message');
	
	if(!empty($atts['app']) && !empty($_GET['emd_action']) && $_GET['emd_action'] == 'rp'){
		list($rp_path) = explode( '?', wp_unslash($_SERVER['REQUEST_URI']));
		$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
		if(isset($_COOKIE[$rp_cookie]) && 0 < strpos($_COOKIE[$rp_cookie], ':' )){
			list($rp_login, $rp_key) = explode(':', wp_unslash($_COOKIE[$rp_cookie]), 2);
			$user = check_password_reset_key($rp_key, $rp_login);
			if(isset($_POST['pass1']) && !hash_equals($rp_key, $_POST['emd_key'])){
				$user = false;
			}
		}
		else {
			$user = false;
		}
		if(!$user || is_wp_error($user)) {
			setcookie($rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true);
			//lets show reset password page with username
			if($user && $user->get_error_code() === 'expired_key'){
				$errors->add('expiredkey', __( 'Your password reset link has expired. Please request a new link below.'));
				emd_shc_login_header($atts['app'],$errors);
				emd_shc_login_content('lostpass',$atts,$rp_key,'expiredkey');
			}
			else {
				$errors->add('invalidkey', __( 'Your password reset link appears to be invalid. Please request a new link below.'));
				emd_shc_login_header($atts['app'],$errors);
				emd_shc_login_content('lostpass',$atts,$rp_key,'invalidkey');
			}
			emd_shc_login_footer();
		}
		else {
			emd_shc_login_header($atts['app'],$errors);
			emd_shc_login_content('resetpass',$atts,$rp_key);
			emd_shc_login_footer();
		}
	}
	elseif(!empty($atts['app']) && !empty($_GET['emd_action']) && $_GET['emd_action'] == 'reset_success'){
			$errors->add('reset_success', __('Your password has been reset.', 'emd-plugins'),'message');
			emd_shc_login_header($atts['app'],$errors);
			emd_shc_login_content('reset_success',$atts);
			emd_shc_login_footer();
	}
	elseif(!empty($atts['app']) && !empty($_GET['emd_action']) && $_GET['emd_action'] == 'lostpass'){
			$errors->add('lostpass', __('Please enter your username or email address. You will receive a link to create a new password via email.', 'emd-plugins'),'message');
			emd_shc_login_header($atts['app'],$errors);
			emd_shc_login_content('lostpass',$atts);
			emd_shc_login_footer();
	}
	elseif(!empty($atts['app'])){
		ob_start();
		emd_shc_login_header($atts['app'],$errors);
		emd_shc_login_content('login',$atts);
		emd_shc_login_footer();
		$layout = ob_get_clean();
		echo $layout;
	}
}
function emd_shc_login_header($app,$errors){
	$dir_url = constant(strtoupper($app) . "_PLUGIN_URL");
	wp_enqueue_style('emd-shc-login-css', $dir_url . 'includes/emd-form-builder-lite/css/emd-shc-login.css');
	//wp_enqueue_style( 'login' );
	
	//user-profile => jquery, zxcvbn.min.js, zxcvbn-async, password-strength-meter, wp-util, 
	wp_enqueue_script('utils');
	wp_enqueue_script('user-profile');
	//wp_enqueue_script('emd-shake');
	if ($errors->get_error_code()) {
                $error_msgs = '';
                $messages = '';
		foreach($errors->get_error_codes() as $code){
			$severity = $errors->get_error_data($code);
			foreach($errors->get_error_messages( $code ) as $error_message){
				if ('message' == $severity){
					$messages .= '  ' . $error_message . "<br />\n";
				}
				else {
				      $error_msgs .= '    ' . $error_message . "<br />\n";
				}
			}
		}
        }
	?>
		<section class="emd-login-page">
		<div class="container">
		<div class="row justify-content-md-center">
		<div class="card-wrapper">
		<div class="brand">
		<?php
		if ( function_exists( 'the_custom_logo' ) ) {
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			$logo = wp_get_attachment_image_src( $custom_logo_id , 'full' );
			if ( has_custom_logo() ) {
				echo '<a style="display:block" class="emdt-bar-item emdt-hover-none" href="'.home_url('/').'"><img style="height:38px" class="emdlogo" src="'. esc_url( $logo[0] ) .'"></a>';
			} else {
				$app_logo_url = apply_filters('emd_login_logo','',$app);
				if(!empty($app_logo_url)){
					echo '<a style="display:block" class="emdt-bar-item emdt-hover-none" href="'.home_url('/').'"><img style="height:38px" class="emdlogo" src="' . esc_url($app_logo_url) . '"></a>';
				}
			}
		}
	?>

		</div>
		<div class="card fat">
		<div class="card-body">
		<?php
                if(!empty($error_msgs)){
                        echo '<div id="login_error" class="error">' . $error_msgs . "</div>";
                }
                if (!empty($messages)){
                        echo '<div id="login_message" class="message">' . $messages . "</div>";
                }
}
function emd_shc_login_footer(){
?>
		<div id="backtosite"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="backtosite-link">
	<?php
                printf(__('Back to %s','emd-plugins'), get_bloginfo('title', 'display'));
        ?>
		</a></div>
		</div>
		</div>
		</div>
		</div>
		</div>
		</section>
		<?php
}
function emd_shc_login_content($action,$atts,$emd_key='',$error=''){
	switch($action){
		case 'reset_success':
		case 'login':
			if (isset($_REQUEST['redirect_to'])) {
                        	$redirect_to = $_REQUEST['redirect_to'];
			}
?>	
		<div class="card-title">
		<?php _e( 'Log into Your Account', 'emd-plugins' ); ?>
		</div>
		<form action="<?php echo get_permalink($post->ID); ?>" class="emdloginreg-container emd_form" id="emd_login_form" method="post" name="emd_login_form">
		<fieldset>
			<div class="form-group emd-login-username">
				<label for="emd_user_login">
					<?php _e( 'Username or Email', 'emd-plugins' ); ?></label>
				<input class="required emd-input form-control" id="emd_user_login" name="emd_user_login" type="text" required autofocus>
			</div>
			<div class="form-group emd-login-password">
				<label for="emd_user_pass">
					<?php _e( 'Password', 'emd-plugins' ); ?></label>
				<input class="required emd-input form-control" id="emd_user_pass" name="emd_user_pass" type="password" required>
			</div>
			<div class="form-group emd-login-remember">
				<div class="custom-checkbox custom-control checkbox">
					<input id="rememberme" name="rememberme" type="checkbox" value="forever" class="custom-control-input">
					<label for="rememberme" class="custom-control-label">
						<?php _e( 'Remember Me', 'emd-plugins' ); ?></label>
				</div>
			</div>
			<div>
				<input name="emd_app" type="hidden" value="<?php echo $atts['app']; ?>">
				<?php 
				if(!empty($redirect_to)){ ?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />
				<?php } ?>
				<input name="emd_login_nonce" type="hidden" value="<?php echo wp_create_nonce( 'emd-login-nonce' ); ?>">
				<input name="emd_action" type="hidden" value="login">
				<input class="emd_submit btn btn-primary btn-block" id="emd-login-submit" type="submit" value="<?php _e( 'Log In', 'emd-plugins' ); ?>">
			</div>
			<div class="emd-button-wrap">
			<?php
			if(!empty($atts['show_lost'])){ 
				$url = remove_query_arg(array('emd_error','checkemail','loggedout'));?>
				<p class="emd-lost-password"><a href="<?php echo esc_url(add_query_arg(array('emd_action'=>'lostpass'),$url)); ?>">
				<?php _e( 'Lost Password?', 'emd-plugins' ); ?></a></p>
			<?php 
			}
			if(!empty($atts['show_reg']) && !empty($atts['reg_link'])){ 
				$reg_label = __('Register', 'emd-plugins');
				if(!empty($atts['reg_label'])){
					$reg_label = $atts['reg_label'];
				}
			?>
			<p class="emd-register-link"><a href="<?php echo $atts['reg_link']; ?>"><?php echo $reg_label; ?></a></p>
		<?php
			} ?>
			</div>
		</fieldset>
		</form>
<?php
		break;
		case 'lostpass':
?>
			<form name="emd-getpass" id="emd-getpass" class="emdloginreg-container emd_form" action="<?php echo esc_url(remove_query_arg('emd_action')); ?>" method="post" autocomplete="off">
			<fieldset>
			<div class="form-group emd-login-username">
				<label for="emd_user_login">
					<?php _e( 'Username or Email', 'emd-plugins' ); ?></label>
				<input class="required emd-input form-control" id="emd_user_login" name="emd_user_login" type="text" required autofocus>
			</div>
			<div>
				<input name="emd_app" type="hidden" value="<?php echo $atts['app']; ?>">
				<input name="emd_action" type="hidden" value="getpass">
				<input name="emd_login_nonce" type="hidden" value="<?php echo wp_create_nonce( 'emd-login-nonce' ); ?>">
				<input class="emd_submit btn btn-primary btn-block" id="wp-submit" name="wp-submit" type="submit" value="<?php _e( 'Get New Password', 'emd-plugins' ); ?>">
			</div>
			</fieldset>
			</form>
<?php
		break;			
		case 'resetpass':
?>
			<div class="card-title">
			<?php _e( 'Reset Password', 'emd-plugins' ); ?>
			</div>
			<form name="emd-resetpass" id="emd-resetpass" class="emdloginreg-container emd_form" action="<?php echo esc_url(add_query_arg('emd_action','resetpass')); ?>" method="post" autocomplete="off">
			<fieldset>
			<input type="hidden" id="user_login" value="<?php echo esc_attr($emd_login); ?>" autocomplete="off" />
			<div class="form-group emd-login-password user-pass1-wrap input-group">
			<label for="emd_user_pass1">
			<?php _e( 'Password', 'emd-plugins' ); ?></label>
			<input type="password" data-reveal="1" data-pw="<?php echo esc_attr(wp_generate_password(12)); ?>" name="pass1" id="pass1" class="emd-input form-control input password-input" size="24" value="" autocomplete="off" aria-describedby="pass-strength-result" />
			<div class="input-group-append">
			<div class="emdeye wp-hide-pw hide-if-no-js">
			</div>
 			</div>
			</div>
			<div id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php _e('Strength indicator'); ?></div>
			<div class="form-group pw-weak">
				<div class="custom-checkbox custom-control checkbox">
					<input id="rememberme" name="pw_weak" type="checkbox" value="forever" class="custom-control-input pw-checkbox">
					<label for="rememberme" class="custom-control-label">
						<?php _e( 'Confirm use of weak password' ); ?>
					</label>
				</div>
			</div>
			<div class="description indicator-hint"><?php echo wp_get_password_hint(); ?></div>
			<input name="emd_app" type="hidden" value="<?php echo $atts['app']; ?>">
			<input type="hidden" name="emd_key" value="<?php echo esc_attr($emd_key); ?>" />
			<div class="emd-button-wrap">
			<input type="submit" name="wp-submit" id="wp-submit" class="emd-submit btn btn-primary btn-block" value="<?php esc_attr_e('Reset Password'); ?>" />
			</div>
			</fieldset>
			</form>
<?php
			break;
	}
}
function emd_shc_login_get_password($url) {
	$errors = new WP_Error();
	if(empty($_POST['emd_user_login']) || !is_string($_POST['emd_user_login'])){
		$errors->add('empty_username', __('<strong>ERROR</strong>: Enter a username or email address.'));
	}
	elseif(strpos($_POST['emd_user_login'], '@')){
		$user_data = get_user_by('email', trim(wp_unslash($_POST['emd_user_login'])));
		if(empty($user_data)){
			$errors->add('invalid_email', __('<strong>ERROR</strong>: There is no user registered with that email address.'));
		}
	} else {
		$login = trim($_POST['emd_user_login']);
		$user_data = get_user_by('login', $login);
	}

	if ($errors->get_error_code()){
		return $errors;
	}
	if(!$user_data){
		$errors->add('invalidcombo', __('<strong>ERROR</strong>: Invalid username or email.'));
		return $errors;
	}
	// Redefining user_login ensures we return the right case in the email.
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;
	$key = get_password_reset_key($user_data);

	if (is_wp_error($key)){
		return $key;
	}

	if(is_multisite()){
		$site_name = get_network()->site_name;
	} else {
		$site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	}
	$url = site_url() . add_query_arg(array('emd_action' => 'rp', 'emd_key' => $key, 'emd_login' => rawurlencode($user_login)), $url);

	$message = '';
	$subject = '';
	if(!empty($_POST['emd_app'])){
		$app = $_POST['emd_app'];
		$login_settings = get_option($app . '_login_settings');
		if(!empty($login_settings['pass_reset_msg'])){
			$message = $login_settings['pass_reset_msg'];
			$message = preg_replace('/{sitename}/',$site_name,$message);
			$message = preg_replace('/{username}/',$user_login,$message);
			$message = preg_replace('/{password_reset_link}/',$url,$message);
		}		
		if(!empty($login_settings['pass_reset_subj'])){
			$subject = $login_settings['pass_reset_subj'];
			$subject = preg_replace('/{sitename}/',$site_name,$subject);
			$subject = preg_replace('/{username}/',$user_login,$subject);
		}		
	}
	if(empty($message)){	
		$message = __( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
		$message .= sprintf( __( 'Site Name: %s'), $site_name ) . "\r\n\r\n";
		$message .= sprintf( __( 'Username: %s'), $user_login ) . "\r\n\r\n";
		$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "\r\n\r\n";
		$message .= '<a href="' . $url . '">' . __( 'To reset your password, click here.' ) . "</a>\r\n\r\n";
	}
	if(empty($subject)){
		$subject = sprintf(__( '[%s] Password Reset' ), $site_name);
	}
	if($message){                                                                                                   
		$from_name = get_bloginfo('name');                                                                      
		$from_email = get_option('admin_email');                                                                            
		$headers = "From: " . stripslashes_deep(html_entity_decode($from_name, ENT_COMPAT, 'UTF-8')) . " <" . $from_email . ">\r\n";
		$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";                                          
		$headers.= "Reply-To: " . $from_email . "\r\n";                                                         
		$ret = wp_mail($user_email, wp_specialchars_decode($subject), $message, $headers);                         
		if(!$ret){                                                                                              
			wp_die( __('The email could not be sent. Please contact site administrator'));                  
		}                                                                                                       
	}                                                                                                            
	/*if ( $message && !wp_mail( $user_email, wp_specialchars_decode($subject), $message)){
		wp_die( __('The email could not be sent. Please contact site administrator'));
	}*/
	return true;
}

add_filter('emd_notify_site_params','emd_login_notify_site_params',10,3);
function emd_login_notify_site_params($fields,$app){
	$login_settings = get_option($app . '_login_settings');
	if(!empty($login_settings['login_page'])){
		$fields  .= __('Login Page Link','emd-plugins') . " <b>{site_login}</b> ";
	}
	return $fields;
}
add_filter('emd_ext_parse_tags', 'emd_login_parse_tags', 10, 3 );
function emd_login_parse_tags($message,$pid,$app){
	$login_settings = get_option($app . '_login_settings');
	if(!empty($login_settings['login_page'])){
		if(preg_match('/{site_login}/',$message)){
			$message = str_replace('{site_login}',get_permalink($login_settings['login_page']),$message);
		}
	}
	return $message;
}

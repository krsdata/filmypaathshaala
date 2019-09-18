<div id="emd-login-container">
<form id="emd_login_form" class="emdloginreg-container emd_form" action="<?php echo get_permalink($post->ID); ?>" method="post">
<fieldset>
<legend><?php _e( 'Log into Your Account', 'emd-plugins' ); ?></legend>
<p class="emd-login-username">
<label for="emd_user_login"><?php _e( 'Username or Email', 'emd-plugins' ); ?></label>
<input name="emd_user_login" id="emd_user_login" class="required emd-input" type="text"/>
</p>
<p class="emd-login-password">
<label for="emd_user_pass"><?php _e( 'Password', 'emd-plugins' ); ?></label>
<input name="emd_user_pass" id="emd_user_pass" class="password required emd-input" type="password"/>
</p>
<p class="emd-login-remember">
<label><input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e( 'Remember Me', 'emd-plugins' ); ?></label>
</p>
<div>
<input type="hidden" name="emd_redirect" value="<?php echo esc_url(get_permalink($post->ID)); ?>"/>
<input type="hidden" name="emd_login_nonce" value="<?php echo wp_create_nonce( 'emd-login-nonce' ); ?>"/>
<input type="hidden" name="emd_action" value="youtube_showcase_user_login"/>

<input type="submit" class="emd_submit button" id="emd-login-submit" value="<?php _e( 'Log In', 'emd-plugins' ); ?>"/>
</div>
<div style="clear:both">
<p class="emd-lost-password" style="float:left">
<a href="<?php echo wp_lostpassword_url(); ?>">
<?php _e( 'Lost Password?', 'emd-plugins' ); ?>
</a>
</p>
<p class="emd-register-link" style="float:right">
<a href="">
<?php _e( 'Register', 'emd-plugins' ); ?>
</a>
</p>
</div>
</fieldset><!--end #emd_login_fields-->
</form>
</div>

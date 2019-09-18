<div id="emd-register-container" style="display:none;">
<form class="emd-register-form emdloginreg-container" id="emd_register_form" method="post" action="<?php echo get_permalink($post->ID); ?>">
<fieldset>
<legend><?php _e( 'Register', 'emd-plugins' ); ?></legend>
<p>
<label for="emd-user-login"><?php _e( 'Username', 'emd-plugins' ); ?></label>
<input id="emd-user-login" class="required emd-input" type="text" name="emd_user_login" />
</p>
<p>
<label for="emd-user-email"><?php _e( 'Email', 'emd-plugins' ); ?></label>
<input id="emd-user-email" class="required emd-input" type="email" name="emd_user_email" />
</p>
<p>
<label for="emd-user-fname"><?php _e( 'First Name', 'emd-plugins' ); ?></label>
<input id="emd-user-fname" class="required emd-input" type="text" name="emd_user_fname" />
</p>
<p>
<label for="emd-user-lname"><?php _e( 'Last Name', 'emd-plugins' ); ?></label>
<input id="emd-user-lname" class="emd-input" type="text" name="emd_user_lname" />
</p>
<p>
<label for="emd-user-pass"><?php _e( 'Password', 'emd-plugins' ); ?></label>
<input id="emd-user-pass" class="password required emd-input" type="password" name="emd_user_pass" />
</p>
<p>
<label for="emd-user-pass2"><?php _e( 'Confirm Password', 'emd-plugins' ); ?></label>
<input id="emd-user-pass2" class="password required emd-input" type="password" name="emd_user_pass2" />
</p>
<div>
<input type="hidden" name="emd_redirect" value="<?php echo esc_url(get_permalink($post->ID)); ?>"/>
<input type="hidden" name="emd_register_nonce" value="<?php echo wp_create_nonce( 'emd-register-nonce' ); ?>"/>
<input type="hidden" name="emd_action" value="youtube_showcase_user_register"/>

<input type="submit" id="emd-register-submit" class="emd_submit button" name="emd_register_submit" value="<?php _e( 'Register', 'emd-plugins' ); ?>"/>
</div>
<div style="clear:both">
<p class="emd-login-link" style="float:right">
<a href="">
<?php _e( 'Login', 'emd-plugins' ); ?>
</a>
</p>
</div>
</fieldset><!--end #emd_register_fields-->
</form>
</div>

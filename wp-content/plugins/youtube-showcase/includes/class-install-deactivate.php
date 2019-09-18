<?php
/**
 * Install and Deactivate Plugin Functions
 * @package YOUTUBE_SHOWCASE
 * @since WPAS 4.0
 */
if (!defined('ABSPATH')) exit;
if (!class_exists('Youtube_Showcase_Install_Deactivate')):
	/**
	 * Youtube_Showcase_Install_Deactivate Class
	 * @since WPAS 4.0
	 */
	class Youtube_Showcase_Install_Deactivate {
		private $option_name;
		/**
		 * Hooks for install and deactivation and create options
		 * @since WPAS 4.0
		 */
		public function __construct() {
			$this->option_name = 'youtube_showcase';
			add_action('admin_init', array(
				$this,
				'check_update'
			));
			register_activation_hook(YOUTUBE_SHOWCASE_PLUGIN_FILE, array(
				$this,
				'install'
			));
			register_deactivation_hook(YOUTUBE_SHOWCASE_PLUGIN_FILE, array(
				$this,
				'deactivate'
			));
			add_action('wp_head', array(
				$this,
				'version_in_header'
			));
			add_action('admin_init', array(
				$this,
				'setup_pages'
			));
			add_action('admin_notices', array(
				$this,
				'install_notice'
			));
			add_action('generate_rewrite_rules', 'emd_create_rewrite_rules');
			add_filter('query_vars', 'emd_query_vars');
			add_action('admin_init', array(
				$this,
				'register_settings'
			) , 0);
			add_action('init', array(
				$this,
				'init_extensions'
			) , 99);
			do_action('emd_ext_actions', $this->option_name);
			add_filter('tiny_mce_before_init', array(
				$this,
				'tinymce_fix'
			));
		}
		public function check_update() {
			$curr_version = get_option($this->option_name . '_version', 1);
			$new_version = constant(strtoupper($this->option_name) . '_VERSION');
			if (version_compare($curr_version, $new_version, '<')) {
				$this->set_options();
				$this->set_roles_caps();
				if (!get_option($this->option_name . '_activation_date')) {
					$triggerdate = mktime(0, 0, 0, date('m') , date('d') + 7, date('Y'));
					add_option($this->option_name . '_activation_date', $triggerdate);
				}
				set_transient($this->option_name . '_activate_redirect', true, 30);
				do_action($this->option_name . '_upgrade', $new_version);
				update_option($this->option_name . '_version', $new_version);
			}
		}
		public function version_in_header() {
			$version = constant(strtoupper($this->option_name) . '_VERSION');
			$name = constant(strtoupper($this->option_name) . '_NAME');
			echo '<meta name="generator" content="' . $name . ' v' . $version . ' - https://emdplugins.com" />' . "\n";
		}
		public function init_extensions() {
			do_action('emd_ext_init', $this->option_name);
		}
		/**
		 * Runs on plugin install to setup custom post types and taxonomies
		 * flushing rewrite rules, populates settings and options
		 * creates roles and assign capabilities
		 * @since WPAS 4.0
		 *
		 */
		public function install() {
			$this->set_options();
			Emd_Video::register();
			flush_rewrite_rules();
			$this->set_roles_caps();
			set_transient($this->option_name . '_activate_redirect', true, 30);
			do_action('emd_ext_install_hook', $this->option_name);
		}
		/**
		 * Runs on plugin deactivate to remove options, caps and roles
		 * flushing rewrite rules
		 * @since WPAS 4.0
		 *
		 */
		public function deactivate() {
			flush_rewrite_rules();
			$this->remove_caps_roles();
			$this->reset_options();
			do_action('emd_ext_deactivate', $this->option_name);
		}
		/**
		 * Register notification and/or license settings
		 * @since WPAS 4.0
		 *
		 */
		public function register_settings() {
			do_action('emd_ext_register', $this->option_name);
			if (!get_transient($this->option_name . '_activate_redirect')) {
				return;
			}
			// Delete the redirect transient.
			delete_transient($this->option_name . '_activate_redirect');
			$query_args = array(
				'page' => $this->option_name
			);
			wp_safe_redirect(add_query_arg($query_args, admin_url('admin.php')));
		}
		/**
		 * Sets caps and roles
		 *
		 * @since WPAS 4.0
		 *
		 */
		public function set_roles_caps() {
			global $wp_roles;
			$cust_roles = Array();
			update_option($this->option_name . '_cust_roles', $cust_roles);
			$add_caps = Array(
				'manage_operations_emd_videos' => Array(
					'administrator'
				) ,
				'view_youtube_showcase_dashboard' => Array(
					'administrator'
				) ,
				'edit_emd_videos' => Array(
					'administrator'
				) ,
			);
			update_option($this->option_name . '_add_caps', $add_caps);
			if (class_exists('WP_Roles')) {
				if (!isset($wp_roles)) {
					$wp_roles = new WP_Roles();
				}
			}
			if (is_object($wp_roles)) {
				if (!empty($cust_roles)) {
					foreach ($cust_roles as $krole => $vrole) {
						$myrole = get_role($krole);
						if (empty($myrole)) {
							$myrole = add_role($krole, $vrole);
						}
					}
				}
				$this->set_reset_caps($wp_roles, 'add');
			}
		}
		/**
		 * Removes caps and roles
		 *
		 * @since WPAS 4.0
		 *
		 */
		public function remove_caps_roles() {
			global $wp_roles;
			if (class_exists('WP_Roles')) {
				if (!isset($wp_roles)) {
					$wp_roles = new WP_Roles();
				}
			}
			if (is_object($wp_roles)) {
				$this->set_reset_caps($wp_roles, 'remove');
			}
		}
		/**
		 * Set  capabilities
		 *
		 * @since WPAS 4.0
		 * @param object $wp_roles
		 * @param string $type
		 *
		 */
		public function set_reset_caps($wp_roles, $type) {
			$caps['enable'] = get_option($this->option_name . '_add_caps', Array());
			$caps['enable'] = apply_filters('emd_ext_get_caps', $caps['enable'], $this->option_name);
			foreach ($caps as $stat => $role_caps) {
				foreach ($role_caps as $mycap => $roles) {
					foreach ($roles as $myrole) {
						if (($type == 'add' && $stat == 'enable') || ($stat == 'disable' && $type == 'remove')) {
							$wp_roles->add_cap($myrole, $mycap);
						} else if (($type == 'remove' && $stat == 'enable') || ($type == 'add' && $stat == 'disable')) {
							$wp_roles->remove_cap($myrole, $mycap);
						}
					}
				}
			}
		}
		/**
		 * Set app specific options
		 *
		 * @since WPAS 4.0
		 *
		 */
		private function set_options() {
			$access_views = Array();
			if (get_option($this->option_name . '_setup_pages', 0) == 0) {
				update_option($this->option_name . '_setup_pages', 1);
			}
			$ent_list = Array(
				'emd_video' => Array(
					'label' => __('Videos', 'youtube-showcase') ,
					'rewrite' => 'videos',
					'archive_view' => 0,
					'rest_api' => 0,
					'sortable' => 0,
					'searchable' => 1,
					'class_title' => Array(
						'emd_video_key'
					) ,
					'unique_keys' => Array(
						'emd_video_key'
					) ,
					'blt_list' => Array(
						'blt_excerpt' => __('Excerpt', 'youtube-showcase') ,
						'blt_content' => __('Content', 'youtube-showcase') ,
					) ,
					'req_blt' => Array(
						'blt_title' => Array(
							'msg' => __('Title', 'youtube-showcase')
						) ,
					) ,
				) ,
			);
			update_option($this->option_name . '_ent_list', $ent_list);
			$shc_list['app'] = 'Youtube Showcase';
			$shc_list['has_gmap'] = 0;
			$shc_list['has_bs'] = 1;
			$shc_list['has_autocomplete'] = 0;
			$shc_list['remove_vis'] = 1;
			$shc_list['forms']['video_search'] = Array(
				'name' => 'video_search',
				'type' => 'search',
				'ent' => 'emd_video',
				'targeted_device' => 'desktops',
				'label_position' => 'top',
				'element_size' => 'medium',
				'display_inline' => '0',
				'noaccess_msg' => 'You cannot use this feature.',
				'disable_submit' => '0',
				'submit_status' => 'publish',
				'visitor_submit_status' => 'publish',
				'submit_button_type' => 'btn-info',
				'submit_button_label' => 'Search',
				'submit_button_size' => 'btn-std',
				'submit_button_block' => '0',
				'submit_button_fa' => '',
				'submit_button_fa_size' => '',
				'submit_button_fa_pos' => 'left',
				'show_captcha' => 'never-show',
				'disable_after' => '0',
				'confirm_method' => 'text',
				'confirm_url' => '',
				'confirm_success_txt' => 'Thanks for your submission.',
				'confirm_error_txt' => 'There has been an error when submitting your entry. Please contact the site administrator.',
				'enable_ajax' => '0',
				'after_submit' => 'show',
				'schedule_start' => '',
				'schedule_end' => '',
				'enable_operators' => '0',
				'ajax_search' => '0',
				'noresult_msg' => 'No videos found.',
				'view_name' => 'video_search',
				'honeypot' => '1',
				'login_reg' => 'none'
			);
			$shc_list['integrations']['video_gallery'] = Array(
				'type' => 'integration',
				'app_dash' => 0,
				'shc_entities' => 'emd_video',
				'page_title' => __('Video Gallery', 'youtube-showcase')
			);
			$shc_list['shcs']['video_grid'] = Array(
				"class_name" => "emd_video",
				"type" => "std",
				'page_title' => __('Video Grid Gallery', 'youtube-showcase') ,
			);
			if (!empty($shc_list)) {
				update_option($this->option_name . '_shc_list', $shc_list);
			}
			$attr_list['emd_video']['emd_video_key'] = Array(
				'label' => __('Video Key', 'youtube-showcase') ,
				'display_type' => 'text',
				'required' => 1,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_video_info_emd_video_0',
				'desc' => __('<p>The unique 11 digit alphanumeric video key found on the YouTube video. For example; in https://www.youtube.com/watch?v=uVgWZd7oGOk. uVgWZd7oGOk is the video id.</p>', 'youtube-showcase') ,
				'type' => 'char',
				'minlength' => 11,
				'maxlength' => 11,
				'uniqueAttr' => true,
			);
			$attr_list['emd_video']['emd_video_featured'] = Array(
				'label' => __('Featured', 'youtube-showcase') ,
				'display_type' => 'checkbox',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 1,
				'list_visible' => 1,
				'mid' => 'emd_video_info_emd_video_0',
				'desc' => __('Adds the video to featured video list.', 'youtube-showcase') ,
				'type' => 'binary',
				'options' => array(
					1 => 1
				) ,
			);
			$attr_list['emd_video']['emd_video_thumbnail_resolution'] = Array(
				'label' => __('Video Image Resolution', 'youtube-showcase') ,
				'display_type' => 'select',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 1,
				'mid' => 'emd_video_info_emd_video_0',
				'desc' => __('<p>Sets the resolution of video thumbnail image. The image size for each option;<br />
<strong>Medium</strong> - 320 x 180, <strong>High</strong> - 480x360, <strong>Standard</strong> - 640 x 480, <strong>Max</strong> -1280 x 720</p>', 'youtube-showcase') ,
				'type' => 'char',
				'options' => array(
					'' => __('Please Select', 'youtube-showcase') ,
					'sd' => esc_attr(__('Standard', 'youtube-showcase')) ,
					'mq' => esc_attr(__('Medium', 'youtube-showcase')) ,
					'hq' => esc_attr(__('High', 'youtube-showcase')) ,
					'maxres' => esc_attr(__('Max', 'youtube-showcase'))
				) ,
				'std' => 'mq',
			);
			$attr_list['emd_video']['emd_video_autoplay'] = Array(
				'label' => __('Video Autoplay', 'youtube-showcase') ,
				'display_type' => 'checkbox',
				'required' => 0,
				'srequired' => 0,
				'filterable' => 0,
				'list_visible' => 1,
				'mid' => 'emd_video_info_emd_video_0',
				'desc' => __('When set the player starts video automatically. It may not work in all devices due to vendor preferences.', 'youtube-showcase') ,
				'type' => 'binary',
				'options' => array(
					1 => 1
				) ,
			);
			$attr_list = apply_filters('emd_ext_attr_list', $attr_list, $this->option_name);
			if (!empty($attr_list)) {
				update_option($this->option_name . '_attr_list', $attr_list);
			}
			$glob_list['glb_show_thumbs_xs'] = Array(
				'label' => __('Show thumbs in mobile', 'youtube-showcase') ,
				'type' => 'checkbox',
				'desc' => 'Shows thumbs in mobile devices when checked',
				'values' => '',
				'dflt' => '',
				'required' => 0,
				'shc_list' => Array(
					'video_indicators'
				) ,
			);
			if (!empty($glob_list)) {
				update_option($this->option_name . '_glob_init_list', $glob_list);
				if (get_option($this->option_name . '_glob_list') === false) {
					update_option($this->option_name . '_glob_list', $glob_list);
				}
			}
			$glob_forms_list['video_search']['captcha'] = 'never-show';
			$glob_forms_list['video_search']['noaccess_msg'] = 'You cannot use this feature.';
			$glob_forms_list['video_search']['login_reg'] = 'none';
			$glob_forms_list['video_search']['noresult_msg'] = 'No videos found.';
			$glob_forms_list['video_search']['csrf'] = 0;
			$glob_forms_list['video_search']['blt_title'] = Array(
				'show' => 1,
				'row' => 1,
				'req' => 0,
				'size' => 12,
				'label' => __('Title', 'youtube-showcase')
			);
			$glob_forms_list['video_search']['category'] = Array(
				'show' => 1,
				'row' => 2,
				'req' => 0,
				'size' => 12,
			);
			$glob_forms_list['video_search']['post_tag'] = Array(
				'show' => 1,
				'row' => 3,
				'req' => 0,
				'size' => 12,
			);
			if (!empty($glob_forms_list)) {
				update_option($this->option_name . '_glob_forms_init_list', $glob_forms_list);
				if (get_option($this->option_name . '_glob_forms_list') === false) {
					update_option($this->option_name . '_glob_forms_list', $glob_forms_list);
				}
			}
			$tax_list['emd_video']['category'] = Array(
				'archive_view' => 0,
				'label' => __('Categories', 'youtube-showcase') ,
				'default' => '',
				'type' => 'builtin',
				'hier' => 1,
				'sortable' => 0,
				'list_visible' => 0,
				'required' => 0,
				'srequired' => 0,
				'rewrite' => 'category'
			);
			$tax_list['emd_video']['post_tag'] = Array(
				'archive_view' => 0,
				'label' => __('Tags', 'youtube-showcase') ,
				'default' => '',
				'type' => 'builtin',
				'hier' => 1,
				'sortable' => 0,
				'list_visible' => 0,
				'required' => 0,
				'srequired' => 0,
				'rewrite' => 'post_tag'
			);
			if (!empty($tax_list)) {
				update_option($this->option_name . '_tax_list', $tax_list);
			}
			$emd_activated_plugins = get_option('emd_activated_plugins');
			if (!$emd_activated_plugins) {
				update_option('emd_activated_plugins', Array(
					'youtube-showcase'
				));
			} elseif (!in_array('youtube-showcase', $emd_activated_plugins)) {
				array_push($emd_activated_plugins, 'youtube-showcase');
				update_option('emd_activated_plugins', $emd_activated_plugins);
			}
			//conf parameters for incoming email
			//conf parameters for inline entity
			//conf parameters for calendar
			//action to configure different extension conf parameters for this plugin
			do_action('emd_ext_set_conf', 'youtube-showcase');
		}
		/**
		 * Reset app specific options
		 *
		 * @since WPAS 4.0
		 *
		 */
		private function reset_options() {
			delete_option($this->option_name . '_shc_list');
			do_action('emd_ext_reset_conf', 'youtube-showcase');
		}
		/**
		 * Show admin notices
		 *
		 * @since WPAS 4.0
		 *
		 * @return html
		 */
		public function install_notice() {
			if (isset($_GET[$this->option_name . '_adm_notice1'])) {
				update_option($this->option_name . '_adm_notice1', true);
			}
			if (current_user_can('manage_options') && get_option($this->option_name . '_adm_notice1') != 1) {
?>
<div class="updated">
<?php
				printf('<p><a href="%1s" target="_blank"> %2$s </a>%3$s<a style="float:right;" href="%4$s"><span class="dashicons dashicons-dismiss" style="font-size:15px;"></span>%5$s</a></p>', 'https://ytshowcasecom.emdplugins.com/?pk_campaign=youtube-showcase&pk_source=plugin&pk_medium=link&pk_content=notice', __('New To Youtube Showcase? Check out live demo!', 'wpas') , __('&#187;', 'wpas') , esc_url(add_query_arg($this->option_name . '_adm_notice1', true)) , __('Dismiss', 'wpas'));
?>
</div>
<?php
			}
			if (isset($_GET[$this->option_name . '_adm_notice2'])) {
				update_option($this->option_name . '_adm_notice2', true);
			}
			if (current_user_can('manage_options') && get_option($this->option_name . '_adm_notice2') != 1) {
?>
<div class="updated">
<?php
				printf('<p><a href="%1s" target="_blank"> %2$s </a>%3$s<a style="float:right;" href="%4$s"><span class="dashicons dashicons-dismiss" style="font-size:15px;"></span>%5$s</a></p>', 'https://emdplugins.com/plugins/youtube-showcase-wordpress-plugin?pk_campaign=youtube-showcase&pk_source=plugin&pk_medium=link&pk_content=notice', __('Want to Keep Your Visitors Longer on Your Site? Get Pro Now!', 'wpas') , __('&#187;', 'wpas') , esc_url(add_query_arg($this->option_name . '_adm_notice2', true)) , __('Dismiss', 'wpas'));
?>
</div>
<?php
			}
			if (current_user_can('manage_options') && get_option($this->option_name . '_setup_pages') == 1) {
				echo "<div id=\"message\" class=\"updated\"><p><strong>" . __('Welcome to Youtube Showcase', 'youtube-showcase') . "</strong></p>
           <p class=\"submit\"><a href=\"" . add_query_arg('setup_youtube_showcase_pages', 'true', admin_url('index.php')) . "\" class=\"button-primary\">" . __('Setup Youtube Showcase Pages', 'youtube-showcase') . "</a> <a class=\"skip button-primary\" href=\"" . add_query_arg('skip_setup_youtube_showcase_pages', 'true', admin_url('index.php')) . "\">" . __('Skip setup', 'youtube-showcase') . "</a></p>
         </div>";
			}
		}
		/**
		 * Setup pages for components and redirect to dashboard
		 *
		 * @since WPAS 4.0
		 *
		 */
		public function setup_pages() {
			if (!is_admin()) {
				return;
			}
			if (!empty($_GET['setup_' . $this->option_name . '_pages'])) {
				$shc_list = get_option($this->option_name . '_shc_list');
				emd_create_install_pages($this->option_name, $shc_list);
				update_option($this->option_name . '_setup_pages', 2);
				wp_redirect(admin_url('admin.php?page=' . $this->option_name . '_settings&youtube-showcase-installed=true'));
				exit;
			}
			if (!empty($_GET['skip_setup_' . $this->option_name . '_pages'])) {
				update_option($this->option_name . '_setup_pages', 2);
				wp_redirect(admin_url('admin.php?page=' . $this->option_name . '_settings'));
				exit;
			}
		}
		public function tinymce_fix($init) {
			global $post;
			$ent_list = get_option($this->option_name . '_ent_list', Array());
			if (!empty($post) && in_array($post->post_type, array_keys($ent_list))) {
				$init['wpautop'] = false;
				$init['indent'] = true;
			}
			return $init;
		}
	}
endif;
return new Youtube_Showcase_Install_Deactivate();

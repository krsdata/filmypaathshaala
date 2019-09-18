<?php
/**
 * EMD Session
 *
 * This is a wrapper class for WP_Session / PHP $_SESSION and handles the storage of login, file uplaod sessions, etc
 *
 * @package     EMD
 * @copyright   Copyright (c) 2016,  Emarket Design
 * @since       5.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Emd_Session Class
 *
 * @since WPAS 5.3
 */
class Emd_Session {

	/**
	 * Holds our session data
	 *
	 * @var array
	 * @access private
	 */
	private $session;

	/**
	 * Session index prefix
	 *
	 * @var string
	 * @access private
	 */
	private $prefix = '';
	/**
	 * Session for app_name
	 *
	 * @var string
	 * @access private
	 */
	private $app_name = '';

	/**
	 * Get things started
	 *
	 * Defines our WP_Session constants, includes the necessary libraries and
	 * retrieves the WP Session instance
	 *
	 */
	public function __construct($myapp) {

		if( ! $this->should_start_session() ) {
			return;
		}

		// Use WP_Session (default)
		if ( ! defined( 'WP_SESSION_COOKIE' ) ) {
			define( 'WP_SESSION_COOKIE', $myapp . '_wp_session' );
		}

		if ( ! class_exists( 'Recursive_ArrayAccess' ) ) {
			require_once constant(strtoupper($myapp) . "_PLUGIN_DIR") . 'assets/ext/wp-session/class-recursive-arrayaccess.php';
		}
		// Include utilities class
		if ( ! class_exists( 'WP_Session_Utils' ) ) {
			require_once constant(strtoupper($myapp) . "_PLUGIN_DIR") . 'assets/ext/wp-session/class-wp-session-utils.php';
		}

		if ( ! class_exists( 'WP_Session' ) ) {
			require_once constant(strtoupper($myapp) . "_PLUGIN_DIR") . 'assets/ext/wp-session/class-wp-session.php';
			require_once constant(strtoupper($myapp) . "_PLUGIN_DIR") . 'assets/ext/wp-session/wp-session.php';
		}

		add_filter( 'wp_session_expiration_variant', array( $this, 'set_expiration_variant_time' ), 99999 );
		add_filter( 'wp_session_expiration', array( $this, 'set_expiration_time' ), 99999 );
		$this->init();
	}

	/**
	 * Setup the WP_Session instance
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		$this->session = WP_Session::get_instance();
	}


	/**
	 * Retrieve session ID
	 *
	 * @access public
	 * @return string Session ID
	 */
	public function get_id() {
		return $this->session->session_id;
	}


	/**
	 * Retrieve a session variable
	 *
	 * @access public
	 * @param string $key Session key
	 * @return string Session variable
	 */
	public function get( $key ) {
		$key = sanitize_key( $key );
		return isset( $this->session[ $key ] ) ? maybe_unserialize( $this->session[ $key ] ) : false;
	}

	/**
	 * Set a session variable
	 *
	 *
	 * @param string $key Session key
	 * @param integer $value Session variable
	 * @return string Session variable
	 */
	public function set( $key, $value ) {
		$key = sanitize_key( $key );
		if ( is_array( $value ) ) {
			$this->session[ $key ] = serialize( $value );
		} else {
			$this->session[ $key ] = $value;
		}
		return $this->session[ $key ];
	}

	/**
	 * Force the cookie expiration variant time to 23 hours
	 *
	 * @access public
	 * @since 2.0
	 * @param int $exp Default expiration (1 hour)
	 * @return int
	 */
	public function set_expiration_variant_time( $exp ) {
		return ( 30 * 60 * 23 );
	}

	/**
	 * Force the cookie expiration time to 24 hours
	 *
	 * @access public
	 * @since 1.9
	 * @param int $exp Default expiration (1 hour)
	 * @return int Cookie expiration time
	 */
	public function set_expiration_time( $exp ) {
		return ( 30 * 60 * 24 );
	}

	/**
	 * Determines if we should start sessions
	 *
	 * @return bool
	 */
	public function should_start_session() {
		$start_session = true;
		if( ! empty( $_SERVER[ 'REQUEST_URI' ] ) ) {

			$blacklist = $this->get_blacklist();
			$uri       = ltrim( $_SERVER[ 'REQUEST_URI' ], '/' );
			$uri       = untrailingslashit( $uri );
			if( in_array( $uri, $blacklist ) ) {
				$start_session = false;
			}
			if( false !== strpos( $uri, 'feed=' ) ) {
				$start_session = false;
			}
		}
		return apply_filters( $this->app_name . '_start_session', $start_session );
	}

	/**
	 * Retrieve the URI blacklist
	 *
	 * These are the URIs where we never start sessions
	 *
	 * @since  2.5.11
	 * @return array
	 */
	public function get_blacklist() {
		$blacklist = apply_filters( $this->app_name . '_session_start_uri_blacklist', array(
			'feed',
			'feed/rss',
			'feed/rss2',
			'feed/rdf',
			'feed/atom',
			'comments/feed'
		) );
		// Look to see if WordPress is in a sub folder or this is a network site that uses sub folders
		$folder = str_replace( network_home_url(), '', get_site_url() );
		if( ! empty( $folder ) ) {
			foreach( $blacklist as $path ) {
				$blacklist[] = $folder . '/' . $path;
			}
		}
		return $blacklist;
	}
}

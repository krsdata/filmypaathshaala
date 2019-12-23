<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\CombineCssImports;

/**
 * Class FileSystem
 * @package WpAssetCleanUp
 */
class FileSystem
{
	/**
	 * @return bool|\WP_Filesystem_Direct
	 */
	public static function init()
	{
		global $wp_filesystem;

		// Set the permission constants if not already set.
		if ( ! defined('FS_CHMOD_DIR') ) {
			define('FS_CHMOD_DIR', fileperms(ABSPATH) & 0777 | 0755);
		}

		if ( ! defined('FS_CHMOD_FILE') ) {
			define('FS_CHMOD_FILE', fileperms(ABSPATH . 'index.php') & 0777 | 0644);
		}

		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';

			if (! function_exists('\WP_Filesystem')) {
				return false;
			}

			return WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * @param $localPathToFile
	 * @param string $alter
	 *
	 * @return false|string
	 */
	public static function file_get_contents($localPathToFile, $alter = '')
	{
		// Fallback
		if (! self::init()) {
			return @file_get_contents($localPathToFile);
		}

		global $wp_filesystem;

		if ($alter === 'combine_css_imports') {
			// This custom class does not minify as it's custom made for combining @import
			$optimizer = new CombineCssImports($localPathToFile);
			return $optimizer->minify();
		}

		return $wp_filesystem->get_contents($localPathToFile);
	}

	/**
	 * @param $localPathToFile
	 * @param $contents
	 *
	 * @return bool|int|void
	 */
	public static function file_put_contents($localPathToFile, $contents)
	{
		// Fallback
		if (! self::init()) {
			return @file_put_contents($localPathToFile, $contents);
		}

		global $wp_filesystem;
		return $wp_filesystem->put_contents($localPathToFile, $contents, FS_CHMOD_FILE);
	}
}

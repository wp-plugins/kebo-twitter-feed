<?php
/*
 * Plugin Name: Kebo Twitter Feed
 * Plugin URI: http://wordpress.org/plugins/kebo-twitter-feed/
 * Description: Connect your site to your Twitter account and display your Twitter Feed on your website effortlessly with a custom widget. 
 * Version: 0.3.2
 * Author: Kebo
 * Author URI: http://kebopowered.com
 */

 // Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

if ( !defined('KEBO_TWITTER_PLUGIN_VERSION' ) )
    define( 'KEBO_TWITTER_PLUGIN_VERSION', '0.3.2' );
if ( !defined( 'KEBO_TWITTER_PLUGIN_URL' ) )
    define( 'KEBO_TWITTER_PLUGIN_URL', plugin_dir_url(__FILE__) );
if ( !defined( 'KEBO_TWITTER_PLUGIN_PATH' ))
    define( 'KEBO_TWITTER_PLUGIN_PATH', plugin_dir_path(__FILE__) );

function kebo_twitter_plugin_setup() {

/**
 * Include Plugin Options.
 */
require_once( KEBO_TWITTER_PLUGIN_PATH . 'inc/options.php' );

/**
 * Include Menu Page.
 */
require_once( KEBO_TWITTER_PLUGIN_PATH . 'inc/menu.php' );

/**
 * Include Custom Widget.
 */
require_once( KEBO_TWITTER_PLUGIN_PATH . 'inc/widget.php' );

/**
 * Include Request for the Twitter Feed.
 */
require_once( KEBO_TWITTER_PLUGIN_PATH . 'inc/get_tweets.php' );

/**
 * Load Text Domain for Translations.
 */
load_plugin_textdomain( 'kebo_twitter', false, KEBO_TWITTER_PLUGIN_PATH . 'languages/' );

}
add_action( 'plugins_loaded', 'kebo_twitter_plugin_setup', 15 );

if (!function_exists('kebo_twitter_plugin_scripts')):

    /**
     * Enqueue plugin scripts and styles.
     */
    function kebo_twitter_scripts() {
            
        // Queues the main CSS file.
        wp_enqueue_style( 'kebo-twitter-plugin', KEBO_TWITTER_PLUGIN_URL . 'css/plugin.css', array(), KEBO_TWITTER_PLUGIN_VERSION, 'all' );
        
    }
    add_action('wp_enqueue_scripts', 'kebo_twitter_scripts');
    add_action('admin_enqueue_scripts', 'kebo_twitter_scripts');

endif;

/**
 * Add a link to the plugin screen, to allow users to jump straight to the settings page.
 */
function kebo_twitter_plugin_meta($links, $file) {
	
	$plugin = plugin_basename(__FILE__);

	// Add our custom link to the defaults.
	if ($file == $plugin) {
		return array_merge(
			$links,
			array( '<a href="' . admin_url('admin.php?page=kebo-twitter') . '">' . __('Settings') . '</a>' )
		);
	}

	return $links;
}
add_filter( 'plugin_row_meta', 'kebo_twitter_plugin_meta', 10, 2 );

/**
* ToDo List
*/



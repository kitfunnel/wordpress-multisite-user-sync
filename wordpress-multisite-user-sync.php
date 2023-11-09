<?php
/*
Plugin Name: WordPress Multisite User Sync/Unsync
Description: WordPress Multisite User Sync/Unsync plugin can sync/unsync users from one site (blog) to the other sites (blogs) in your WordPress Multisite Network.
Version:     2.1.7
Author:      Obtain Infotech
Author URI:  https://www.obtaininfotech.com/
License:     GPL2
Text Domain: wmus
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

/*
 * This is a constant variable for plugin path.
 */
define( 'WMUS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/*
 * This is a file for admin.
 */
include_once WMUS_PLUGIN_PATH . 'admin/admin.php';

/*
 * This is a file for functions.
 */
include_once WMUS_PLUGIN_PATH . 'public/functions.php';

/*
 * This is a function that run when activate plugin.
 */
if ( ! function_exists( 'wmus_register_activation_hook' ) ) {
    register_activation_hook( __FILE__, 'wmus_register_activation_hook' );
    function wmus_register_activation_hook() {
        
        $sync_type = get_site_option( 'wmus_auto_sync' );
        if ( ! $sync_type ) {
            update_site_option( 'wmus_auto_sync', 'auto' );
        }
        
        $auto_sync_type = get_site_option( 'wmus_auto_sync_type' );
        if ( ! $auto_sync_type ) {
            update_site_option( 'wmus_auto_sync_type', 'all-sites' );
        }
    }
}

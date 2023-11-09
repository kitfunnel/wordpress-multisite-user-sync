<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

/*
 * Deleted options when plugin uninstall.
 */
delete_site_option( 'wmus_auto_sync' );
delete_site_option( 'wmus_auto_sync_type' );
delete_site_option( 'wmus_auto_sync_sub_blogs' );
delete_site_option( 'wmus_auto_sync_main_blog' );
delete_site_option( 'wmus_auto_unsync' );
delete_site_option( 'wmus_exclude_user_roles' );
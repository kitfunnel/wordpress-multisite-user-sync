<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

/*
 * This is a function that remove content copier section.
 */
if ( ! function_exists( 'wmus_remove_action' ) ) {
    add_action( 'admin_head', 'wmus_remove_action' );
    function wmus_remove_action() {
        
        remove_action( 'show_user_profile', 'WMCC_user_content_copier' );
        remove_action( 'show_user_profile', 'wmcc_user_content_copier' );
        remove_action( 'edit_user_profile', 'WMCC_user_content_copier' );
        remove_action( 'edit_user_profile', 'wmcc_user_content_copier' );
    }
}

/*
 * This is a function that show sync/unsync section.
 * $profileuser variable return user.
 */
if ( ! function_exists( 'wmus_user_sync_unsync' ) ) {
    add_action( 'show_user_profile', 'wmus_user_sync_unsync', 10, 1 );
    add_action( 'edit_user_profile', 'wmus_user_sync_unsync', 10, 1 );
    function wmus_user_sync_unsync( $profileuser ) {
        
        global $wpdb;
        
        $user_id = $profileuser->ID;
        $sync_type = get_site_option( 'wmus_auto_sync' );
        if ( $sync_type == '1' ) {
            $sync_type = 'auto';
        } else if ( $sync_type == '0' ) {
            $sync_type = 'manual';
        } else {
            //
        }

        if ( $sync_type == 'manual' ) {
            $current_user = wp_get_current_user();  
            if ( $current_user != null ) {
                $current_user_role = reset( $current_user->roles );
            } 

            $wmus_user_roles = get_site_option( 'wmus_user_roles' );
            if ( ! $wmus_user_roles ) {
                $wmus_user_roles = array();
            }
            
            if ( is_super_admin() ) {
                ?>
                    <h2><?php esc_html_e( 'WordPress Multisite User Sync/Unsync', 'wmus' ); ?></h2>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th><label><?php esc_html_e( 'Sync/Unsync?', 'wmus' ); ?></label></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="radio" name="wmus_sync_unsync" value="1" checked="checked" /><?php esc_html_e( 'Sync', 'wmus' ); ?>
                                        </label>
                                        <label>
                                            <input type="radio" name="wmus_sync_unsync" value="0" /><?php esc_html_e( 'Unsync', 'wmus' ); ?>
                                        </label>
                                    </fieldset>
                                    <p class="description"><?php esc_html_e( 'Select sync/unsync.', 'wmus' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Sites', 'wmus' ); ?></label></th>
                                <td>
                                    <label><input class="wmus-check-uncheck" type="checkbox" /><?php esc_html_e( 'All', 'wmus' ); ?></label>
                                    <p class="description"><?php esc_html_e( 'Select/Deselect all sites.', 'wmus' ); ?></p>
                                    <br>
                                    <fieldset class="wmus-sites">
                                        <?php
                                            $sites = $wpdb->get_results( "SELECT * FROM ".$wpdb->base_prefix."blogs" );                                       
                                            if ( $sites != null ) {
                                                foreach ( $sites as $key => $value ) {
                                                    $checked = '';
                                                    if ( is_user_member_of_blog( $user_id, $value->blog_id ) ) {
                                                        $checked = ' checked="checked"';
                                                    }

                                                    $blog_details = get_blog_details( $value->blog_id );
                                                    if ( ( $value->blog_id != get_current_blog_id() ) || ( is_network_admin() ) ) {
                                                        ?>
                                                            <label><input name="wmus_blogs[]" type="checkbox" value="<?php echo intval( $value->blog_id ); ?>"<?php echo $checked; ?>><?php echo $value->domain; echo $value->path; echo ' ('.$blog_details->blogname.')'; ?></label><br>
                                                        <?php
                                                    }
                                                }
                                            }
                                        ?>
                                    </fieldset>
                                    <p class="description"><?php esc_html_e( 'Select destination sites you want to sync/unsync.', 'wmus' ); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <script>
                        jQuery( document ).ready( function( $ ) {
                            $( '.wmus-check-uncheck' ).on( 'change', function() {
                                var checked = $( this ).prop( 'checked' );
                                $( '.wmus-sites input[type="checkbox"]' ).each( function() {
                                    if ( checked ) {
                                        $( this ).prop( 'checked', true );
                                    } else {
                                        $( this ).prop( 'checked', false );
                                    }
                                });
                            });
                            
                            $( 'input[type="radio"][name="wmus_auto_sync_type"]' ).on( 'change', function() {
                                var type = $( this ).val();
                                if ( type == 'main-site-to-sub-sites' ) {
                                    $( '.wmus-hide-show' ).show();     
                                } else {
                                    $( '.wmus-hide-show' ).hide();
                                }
                            });
                        });
                    </script>
                <?php
            }
        }
    }
}

/*
 * This is a function that sync/unsync users on update user profile or edit user.
 * $user_id variable return user id.
 */
if ( ! function_exists( 'wmus_user_sync_unsync_update' ) ) {
    add_action( 'edit_user_profile_update', 'wmus_user_sync_unsync_update', 10, 1 );
    add_action( 'profile_update', 'wmus_user_sync_unsync_update', 10, 1 );
    function wmus_user_sync_unsync_update( $user_id ) {
        
        $sync_type = get_site_option( 'wmus_auto_sync' );
        if ( $sync_type == '1' ) {
            $sync_type = 'auto';
        } else if ( $sync_type == '0' ) {
            $sync_type = 'manual';
        } else {
            //
        }
        
        if ( $sync_type == 'manual' ) {
            $wmus_blogs = ( isset( $_POST['wmus_blogs'] ) ? $_POST['wmus_blogs'] : array() );
            $wmus_sync_unsync = ( isset( $_POST['wmus_sync_unsync'] ) ? (int) $_POST['wmus_sync_unsync'] : 1 );
            if ( $wmus_blogs != null ) {
                $user_info = get_userdata( $user_id );
                $role = reset( $user_info->roles );
                if ( ! $role ) {
                    $role = 'subscriber';
                }
                
                if ( isset( $_POST['role'] ) && $_POST['role'] != null ) {
                    $role = sanitize_text_field( $_POST['role'] );
                }
                
                $wmus_sync = 1;
                $exclude_user_roles = get_site_option( 'wmus_exclude_user_roles' );
                if ( $exclude_user_roles && in_array( $role, $exclude_user_roles ) ) {
                    $wmus_sync = 0;
                }
                
                if ( $wmus_sync ) {
                    foreach ( $wmus_blogs as $wmus_blog ) {                
                        $blog_id = $wmus_blog;
                        if ( $wmus_sync_unsync ) {
                            add_user_to_blog( $blog_id, $user_id, $role );
                        } else {
                            remove_user_from_blog( $user_id, $blog_id );
                        }
                    }
                }
            }
        }
    }
}

/*
 * This is a function that sync auto users on update user profile or edit user if auto sync enabled.
 * $user_id variable return user id.
 */
if ( ! function_exists( 'wmus_user_auto_sync' ) ) {
    add_action( 'user_register', 'wmus_user_auto_sync', 10, 1 );
    add_action( 'edit_user_profile_update', 'wmus_user_auto_sync', 10, 1 );
    add_action( 'profile_update', 'wmus_user_auto_sync', 10, 1 );
    add_action( 'set_user_role', 'wmus_user_auto_sync', 10, 1 );
    add_action( 'add_user_role', 'wmus_user_auto_sync', 10, 1 );
    function wmus_user_auto_sync( $user_id ) {
        
        global $wpdb;
        
        $sync_type = get_site_option( 'wmus_auto_sync' );
        if ( $sync_type == '1' ) {
            $sync_type = 'auto';
        } else if ( $sync_type == '0' ) {
            $sync_type = 'manual';
        } else {
            //
        }
        
        $current_blog_id = get_current_blog_id();
        if ( $sync_type == 'auto' ) {
            $wmus_auto_sync_type = get_site_option( 'wmus_auto_sync_type' );
            if ( $wmus_auto_sync_type == 'main-site-to-sub-sites' && is_main_site( $current_blog_id ) ) {
                $wmus_auto_sync_main_blog = get_site_option( 'wmus_auto_sync_main_blog' );
                $wmus_auto_sync_sub_blogs = get_site_option( 'wmus_auto_sync_sub_blogs' );
                
                if ( $current_blog_id == $wmus_auto_sync_main_blog ) {
                    $user_info = get_userdata( $user_id );            
                    $user_id = (int) $user_id;
                    $role = reset( $user_info->roles );
                    if ( ! $role ) {
                        $role = 'subscriber';
                    }
                    
                    if ( isset( $_POST['role'] ) && $_POST['role'] != null ) {
                        $role = sanitize_text_field( $_POST['role'] );
                    }
                    
                    $wmus_sync = 1;
                    $exclude_user_roles = get_site_option( 'wmus_exclude_user_roles' );
                    if ( $exclude_user_roles && in_array( $role, $exclude_user_roles ) ) {
                        $wmus_sync = 0;
                    }

                    if ( $wmus_sync ) {
                        foreach ( $wmus_auto_sync_sub_blogs as $wmus_auto_sync_sub_blog ) {                
                            $blog_id = (int) $wmus_auto_sync_sub_blog;
                            add_user_to_blog( $blog_id, $user_id, $role );
                        }
                    }
                }
            } else if ( $wmus_auto_sync_type == 'sub-sites-to-main-site' && !is_main_site( $current_blog_id ) ) {
                $wmus_registered_users = get_site_option( 'wmus_registered_users' );
                if ( $wmus_registered_users == null ) {
                    $wmus_registered_users = array();
                }
                
                $wmus_registered_users[$current_blog_id][$user_id] = $user_id;
                update_site_option( 'wmus_registered_users', $wmus_registered_users );
            } else if ( $wmus_auto_sync_type == 'all-sites' ) {
                $wmus_registered_users = get_site_option( 'wmus_registered_users' );
                if ( $wmus_registered_users == null ) {
                    $wmus_registered_users = array();
                }

                $user_info = get_userdata( $user_id );
                $user_id = (int) $user_id;
                $role = reset( $user_info->roles );
                if ( ! $role ) {
                    $role = 'subscriber';
                }
                
                if ( isset( $_POST['role'] ) && $_POST['role'] != null ) {
                    $role = sanitize_text_field( $_POST['role'] );
                }
                
                $wmus_sync = 1;
                $exclude_user_roles = get_site_option( 'wmus_exclude_user_roles' );
                if ( $exclude_user_roles && in_array( $role, $exclude_user_roles ) ) {
                    $wmus_sync = 0;
                }

                if ( $wmus_sync ) {
                    $blogs = $wpdb->get_results( "SELECT * FROM ".$wpdb->base_prefix."blogs" );
                    if ( $blogs != null ) {
                        foreach ( $blogs as $blog ) {
                            if ( $blog->blog_id != $current_blog_id ) {
                                $blog_id = (int) $blog->blog_id;
                                if ( $blog_id == 1 ) {
                                    $wmus_registered_users[$current_blog_id][$user_id] = $user_id;
                                } else {
                                    add_user_to_blog( $blog_id, $user_id, $role );
                                }
                            }
                        }
                    }
                }
                
                update_site_option( 'wmus_registered_users', $wmus_registered_users );
            } else {
                //
            }
        }
    }
}

/*
 * This is a function that add sub sites users to main site.
 */
if ( ! function_exists( 'sub_sites_to_main_site_registered_users_sync' ) ) {
    add_action('init', 'sub_sites_to_main_site_registered_users_sync');
    function sub_sites_to_main_site_registered_users_sync() {
        
        $wmus_registered_users = get_site_option( 'wmus_registered_users' );
        if ( $wmus_registered_users == null ) {
            $wmus_registered_users = array();
        }
        
        $wmus_auto_sync_main_blog = (int) get_site_option( 'wmus_auto_sync_main_blog' );
        if ( ! $wmus_auto_sync_main_blog ) {
            $wmus_auto_sync_main_blog = 1;
        }
        
        if ( $wmus_registered_users != null ) {
            $wmus_registered_users_filter = $wmus_registered_users;
            $exclude_user_roles = get_site_option( 'wmus_exclude_user_roles' );
            foreach( $wmus_registered_users as $blog_id => $blog_users ) {
                $blog_id = (int) $blog_id;
                
                switch_to_blog( $blog_id );
                    if ( $blog_users != null ) {
                        foreach ( $blog_users as $blog_user ) {
                            $user_id = (int) $blog_user;
                            $user_info = get_userdata( $user_id );
                            if ( $user_info ) {
                                $role = reset( $user_info->roles );
                                if ( ! $role ) {
                                    $role = 'subscriber';
                                }
                                
                                $wmus_sync = 1;
                                if ( $exclude_user_roles && in_array( $role, $exclude_user_roles ) ) {
                                    $wmus_sync = 0;
                                }
                                
                                if ( $wmus_sync ) {
                                    $add_user_to_blog = add_user_to_blog( $wmus_auto_sync_main_blog, $user_id, $role );
                                    if ( $add_user_to_blog ) {
                                        unset( $wmus_registered_users_filter[$blog_id][$user_id] );                                
                                    }
                                }
                            }
                        }
                    } else {
                        unset( $wmus_registered_users_filter[$blog_id] );
                    }
                restore_current_blog();
            }
            update_site_option( 'wmus_registered_users', $wmus_registered_users_filter );
        }
    }
}

/*
 * This is a function that unsync auto user.
 * $user_id variable return user id.
 */
if ( ! function_exists( 'wmus_user_auto_unsync' ) ) {
    add_action( 'remove_user_from_blog', 'wmus_user_auto_unsync', 10, 1 );
    function wmus_user_auto_unsync( $user_id ) {
        
        global $wpdb;
        
        $wmus_auto_unsync = get_site_option( 'wmus_auto_unsync' );
        if ( $wmus_auto_unsync && isset( $_POST['action'] ) && $_POST['action'] == 'doremove' ) {
            $sync_type = get_site_option( 'wmus_auto_sync' );
            if ( $sync_type == '1' ) {
                $sync_type = 'auto';
            } else if ( $sync_type == '0' ) {
                $sync_type = 'manual';
            } else {
                //
            }
            
            if ( $sync_type == 'auto' ) {
                $wmus_auto_sync_type = get_site_option( 'wmus_auto_sync_type' );
                $user_info = get_userdata( $user_id );
                if ( $user_info ) {
                    $role = reset( $user_info->roles );
                    $user_login = $user_info->data->user_login;
                    $wmus_unsync = 1;
                    $exclude_user_roles = get_site_option( 'wmus_exclude_user_roles' );
                    if ( $exclude_user_roles && in_array( $role, $exclude_user_roles ) ) {
                        $wmus_unsync = 0;
                    }

                    if ( $wmus_unsync ) {
                        if ( $wmus_auto_sync_type == 'main-site-to-sub-sites' ) {
                            $current_blog_id = get_current_blog_id();
                            $wmus_auto_sync_main_blog = get_site_option( 'wmus_auto_sync_main_blog' );        
                            if ( $current_blog_id == $wmus_auto_sync_main_blog ) {
                                $wmus_auto_sync_sub_blogs = get_site_option( 'wmus_auto_sync_sub_blogs' );
                                foreach ( $wmus_auto_sync_sub_blogs as $wmus_auto_sync_sub_blog ) {
                                    $blog_id = (int) $wmus_auto_sync_sub_blog;
                                    $wpdb->delete( $wpdb->base_prefix."usermeta", array( 'user_id' => $user_id, 'meta_key' => $wpdb->base_prefix.$blog_id.'_capabilities' ) );
                                    $wpdb->delete( $wpdb->base_prefix."usermeta", array( 'user_id' => $user_id, 'meta_key' => $wpdb->base_prefix.$blog_id.'_user_level' ) );
                                }
                            }
                        } else if ( $wmus_auto_sync_type == 'sub-sites-to-main-site' ) {
                            $current_blog_id = get_current_blog_id();
                            $wmus_auto_sync_main_blog = get_site_option( 'wmus_auto_sync_main_blog' );        
                            if ( $current_blog_id != $wmus_auto_sync_main_blog ) {
                                $wpdb->delete( $wpdb->base_prefix."usermeta", array( 'user_id' => $user_id, 'meta_key' => $wpdb->base_prefix.'capabilities' ) );
                                $wpdb->delete( $wpdb->base_prefix."usermeta", array( 'user_id' => $user_id, 'meta_key' => $wpdb->base_prefix.'user_level' ) );
                            }
                        } else if ( $wmus_auto_sync_type == 'all-sites' ) {
                            $wpdb->delete( $wpdb->base_prefix."users", array( 'ID' => $user_id ) );
                            $wpdb->delete( $wpdb->base_prefix."usermeta", array( 'user_id' => $user_id ) );
                            $wpdb->delete( $wpdb->base_prefix."signups", array( 'user_login' => $user_login ) );
                        } else {
                            //
                        }
                    }
                }
            }
        }
    }
}
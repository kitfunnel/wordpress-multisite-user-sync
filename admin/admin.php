<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

/*
 * This is a function that add network admin menu.
 */
if ( ! function_exists( 'wmus_network_admin_menu' ) ) {
    add_action( 'network_admin_menu', 'wmus_network_admin_menu' );
    function wmus_network_admin_menu() {
        
        add_menu_page( esc_html__( 'WordPress Multisite User Sync/Unsync', 'wmus' ), esc_html__( 'User Sync', 'wmus' ), 'manage_options', 'wmus', 'wmus_bulk_sync_callback', 'dashicons-update' );
        add_submenu_page( 'wmus', esc_html__( 'User Sync: Bulk Sync', 'wmus' ), esc_html__( 'Bulk Sync', 'wmus' ), 'manage_options', 'wmus', 'wmus_bulk_sync_callback' );
        add_submenu_page( 'wmus', esc_html__( 'User Sync: Settings', 'wmus' ), esc_html__( 'Settings', 'wmus' ), 'manage_options', 'wmus-settings', 'wmus_settings_callback' );        
        add_submenu_page( 'wmus', esc_html__( 'Licence Verification', 'wmus' ), esc_html__( 'Licence Verification', 'wmus' ), 'manage_options', 'wmus-licence-verification', 'wmus_licence_verification_callback' );
    }
}

/*
 * This is a function that call bulk sync/unsync functionality.
 */
if ( ! function_exists( 'wmus_bulk_sync_callback' ) ) {
    function wmus_bulk_sync_callback() {

        global $wpdb;
        
        $current_blog_id = get_current_blog_id();
        $page_url = network_admin_url( '/admin.php?page=wmus' );
        $wmus_source_blog = ( isset( $_REQUEST['wmus_source_blog'] ) ? (int) $_REQUEST['wmus_source_blog'] : 0 );
        $wmus_record_per_page = ( isset( $_REQUEST['wmus_record_per_page'] ) ? (int) $_REQUEST['wmus_record_per_page'] : 10 );        
        $wmus_records = ( isset( $_REQUEST['wmus_records'] ) ? $_REQUEST['wmus_records'] : array() );
        $wmus_destination_blogs = ( isset( $_REQUEST['wmus_destination_blogs'] ) ? $_REQUEST['wmus_destination_blogs'] : array() );
        $wmus_sync_unsync = ( isset( $_REQUEST['wmus_sync_unsync'] ) ? (int) $_REQUEST['wmus_sync_unsync'] : 1 );
        
        if ( $wmus_source_blog && $wmus_destination_blogs != null && $wmus_records != null && isset( $_REQUEST['submit'] ) ) {
            $blogs = $wmus_destination_blogs;   
            $current_blog_id = get_current_blog_id();
            $source_blog_id = (int) $wmus_source_blog;
            $exclude_user_roles = get_site_option( 'wmus_exclude_user_roles' );
            foreach ( $wmus_records as $wmus_record ) {
                if ( $blogs != null ) {
                    $wmus_record = (int) $wmus_record;
                    if ( $source_blog_id != $current_blog_id ) {                
                        switch_to_blog( $source_blog_id );
                    }
                    
                    $user_info = get_userdata( $wmus_record );
                    
                    if ( $source_blog_id != $current_blog_id ) {                
                        restore_current_blog();
                    }
                    
                    $user_id = $wmus_record;
                    $role = reset( $user_info->roles );
                    if ( ! $role ) {
                        $role = 'subscriber';
                    }
                    
                    $wmus_sync = 1;
                    if ( $exclude_user_roles && in_array( $role, $exclude_user_roles ) ) {
                        $wmus_sync = 0;
                    }
                    
                    if ( $wmus_sync ) {
                        foreach ( $blogs as $blog ) {
                            $blog_id = (int) $blog;
                            if ( $wmus_sync_unsync ) {
                                add_user_to_blog( $blog_id, $user_id, $role );
                            } else {
                                remove_user_from_blog( $user_id, $blog_id );
                            }
                        }
                    }
                }
            }
            
            ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Users successfully synced.', 'wmus' ); ?></p>
                </div>
            <?php
        }

        $licence = get_site_option( 'wmus_licence' );
        ?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Bulk Sync', 'wmus' ); ?></h2>
                <hr>
                <?php
                    if ( $licence ) {
                        ?>
                            <form method="post" action="<?php echo $page_url; ?>">                
                                <table class="form-table">
                                    <tbody>                        
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Source Site', 'wmus' ); ?></th>
                                            <td>     
                                                <select name="wmus_source_blog" required="required">
                                                <?php
                                                    $sites = $wpdb->get_results( "SELECT * FROM ".$wpdb->base_prefix."blogs" );
                                                    $blog_list = array();
                                                    if ( $sites != null ) {
                                                        ?><option value=""><?php esc_html_e( 'Select source site', 'wmus' ); ?></option><?php
                                                        foreach ( $sites as $key => $value ) {
                                                            $blog_list[$value->blog_id] = $value->domain;
                                                            $selected = '';
                                                            if ( $wmus_source_blog == $value->blog_id ) {
                                                                $selected = ' selected="$selected"';
                                                            }

                                                            $blog_details = get_blog_details( $value->blog_id );                                            
                                                            ?>
                                                                <option value="<?php echo intval( $value->blog_id ); ?>"<?php echo $selected; ?>><?php echo $value->domain; echo $value->path; echo ' ('.$blog_details->blogname.')'; ?></option>                                                
                                                            <?php
                                                        }
                                                    }
                                                ?> 
                                                </select>
                                            </td>
                                        </tr>    
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Number of users per page', 'wmus' ); ?></th>
                                            <td>
                                                <input type="number" name="wmus_record_per_page" min="1" value="<?php echo intval( $wmus_record_per_page ); ?>" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="submit">
                                    <input name="submit" class="button button-secondary" value="<?php esc_html_e( 'Filter', 'wmus' ); ?>" type="submit">
                                    &nbsp;&nbsp;&nbsp;&nbsp;<a class="button button-secondary" href="<?php echo $page_url; ?>"><?php esc_html_e( 'Clear', 'wmus' ); ?></a>
                                </p>
                            </form>
                        <?php
                        if ( $wmus_source_blog ) {
                            if ( $wmus_source_blog != get_current_blog_id() ) {
                                $wmus_source_blog = (int) $wmus_source_blog;
                                switch_to_blog( $wmus_source_blog );
                            }                 
                            
                            ?>
                            <form method="post">
                                <p class="search-box wmus-search-box">
                                    <label class="screen-reader-text" for="post-search-input"><?php esc_html_e( 'Search Users:', 'wmus' ); ?></label>
                                    <input id="post-search-input" name="s" value="<?php echo ( isset( $_REQUEST['s'] ) ? esc_attr__( $_REQUEST['s'] ) : ''  ); ?>" type="search">
                                    <input id="search-submit" class="button" value="<?php esc_html_e( 'Search Users', 'wmus' ); ?>" type="submit">
                                </p>                       
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column"><input type="checkbox"></td>
                                            <th><?php esc_html_e( 'Title', 'wmus' ); ?></th>                                  
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <td class="manage-column column-cb check-column"><input type="checkbox"></td>
                                            <th><?php esc_html_e( 'Title', 'wmus' ); ?></th>                                   
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                    <?php  
                                    $paged = ( isset( $_REQUEST['paged'] ) ) ? (int) $_REQUEST['paged'] : 1;
                                    $add_args = array(                                
                                        'wmus_source_blog'      => $wmus_source_blog,
                                        'wmus_record_per_page'  => $wmus_record_per_page,
                                    );
                                    
                                    $args = array(
                                        'number'    => $wmus_record_per_page,                                    
                                        'paged'     => $paged,
                                    );

                                    if ( isset( $_REQUEST['s'] ) ) {
                                        $args['search'] = sanitize_text_field( $_REQUEST['s'] );
                                        $args['search_columns'] = array(
                                            'ID',
                                            'user_login',
                                            'user_nicename',
                                            'user_email',
                                            'user_url',
                                        );
                                        $add_args['s'] = sanitize_text_field( $_REQUEST['s'] );
                                    }
                                    
                                    $user_query = new WP_User_Query( $args );
                                    $records = $user_query->get_results();                             
                                    if ( $records != null ) {
                                        $sites = $wpdb->get_results( "SELECT * FROM ".$wpdb->base_prefix."blogs" );
                                        foreach ( $records as $record ) {
                                            ?>
                                                <tr>
                                                    <th class="check-column"><input type="checkbox" name="wmus_records[]" value="<?php echo intval( $record->ID ); ?>"></th>
                                                    <td class="title column-title page-title">
                                                        <strong><a href="<?php echo get_edit_user_link( $record->ID ); ?>"><?php echo $record->data->display_name; ?></a></strong>                                                    
                                                        <?php
                                                            if ( $sites != null ) {
                                                                $user_synced = array();
                                                                foreach ( $sites as $user_site ) {                                                                
                                                                    if ( is_user_member_of_blog( $record->ID, $user_site->blog_id ) && $wmus_source_blog != $user_site->blog_id ) {
                                                                        $user_synced[] = $user_site->blog_id;
                                                                    }
                                                                }

                                                                if ( $user_synced != null ) {                                                                
                                                                    echo '<b>'; esc_html_e( 'Synced: ', 'wmus' ); echo '</b>';
                                                                    $count_blog_list = count( $user_synced );
                                                                    $count_blog = 0;
                                                                    foreach ( $user_synced as $user_synced_value ) {
                                                                        $blog_details = get_blog_details( $user_synced_value );
                                                                        echo $blog_list[$user_synced_value]; echo $blog_details->path; echo ' ('.$blog_details->blogname.')';
                                                                        if ( $count_blog != ( $count_blog_list - 1) ) {
                                                                            echo ', ';
                                                                        }
                                                                        $count_blog ++;
                                                                    }
                                                                }
                                                            }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php
                                        }                               
                                    } else {
                                        ?>
                                            <tr class="no-items">                                       
                                                <td class="colspanchange" colspan="2"><?php esc_html_e( 'No records found.', 'wmus' ); ?></td>
                                            </tr>
                                        <?php
                                    }
                                    $big = 999999999;                            
                                    ?>
                                    </tbody>
                                </table>
                                <div class="wmus-pagination">
                                    <span class="pagination-links">
                                        <?php
                                        $total = ceil( $user_query->get_total() / $wmus_record_per_page );
                                        
                                        $paginate_url = network_admin_url( '/admin.php?page=wmus&paged=%#%' );
                                        echo paginate_links( array(
                                            'base'      => str_replace( $big, '%#%', $paginate_url ),
                                            'format'    => '?paged=%#%',
                                            'current'   => max( 1, $paged ),
                                            'total'     => $total,
                                            'add_args'  => $add_args,    
                                            'prev_text' => '&laquo;',
                                            'next_text' => '&raquo;',
                                        ) );
                                        ?>
                                    </span>
                                </div>
                                <br class="clear">                        
                                <input type="hidden" name="wmus_source_blog" value="<?php echo intval( $wmus_source_blog ); ?>">
                                <input type="hidden" name="wmus_record_per_page" value="<?php echo intval( $wmus_record_per_page ); ?>">
                                <?php wp_reset_postdata(); ?>
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
                                            <th scope="row"><?php esc_html_e( 'Destination Sites', 'wmus' ); ?></th>
                                            <td>
                                                <label><input class="wmus-check-uncheck" type="checkbox" /><?php esc_html_e( 'All', 'wmus' ); ?></label>
                                                <p class="description"><?php esc_html_e( 'Select/Deselect all sites.', 'wmus' ); ?></p>
                                                <br>
                                                <fieldset class="wmus-sites">                                            
                                                    <?php                                                                                       
                                                        if ( $sites != null ) {
                                                            foreach ( $sites as $key => $value ) { 
                                                                if ( $wmus_source_blog != $value->blog_id ) {
                                                                    $blog_details = get_blog_details( $value->blog_id );
                                                                    ?>
                                                                        <label><input name="wmus_destination_blogs[]" type="checkbox" value="<?php echo intval( $value->blog_id ); ?>"><?php echo $value->domain; echo $value->path; echo ' ('.$blog_details->blogname.')'; ?></label><br>
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
                                <p>
                                    <input type="submit" name="submit" value="<?php esc_html_e( 'Sync/Unsync', 'wmus' ); ?>" class="button button-primary" />
                                </p>
                            </form>
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
                            <style>
                                .wmus-pagination {
                                    color: #555;
                                    cursor: default;
                                    float: right;
                                    height: 28px;
                                    margin-top: 3px;
                                }

                                .wmus-pagination .page-numbers {
                                    background: #e5e5e5;
                                    border: 1px solid #ddd;
                                    display: inline-block;
                                    font-size: 16px;
                                    font-weight: 400;
                                    line-height: 1;
                                    min-width: 17px;
                                    padding: 3px 5px 7px;
                                    text-align: center;
                                    text-decoration: none;
                                }

                                .wmus-pagination .page-numbers.current {
                                    background: #f7f7f7;
                                    border-color: #ddd;
                                    color: #a0a5aa;
                                    height: 16px;
                                    margin: 6px 0 4px;
                                }

                                .wmus-pagination a.page-numbers:hover {
                                    background: #00a0d2;
                                    border-color: #5b9dd9;
                                    box-shadow: none;
                                    color: #fff;
                                    outline: 0 none;
                                }

                                .wmus-search-box {
                                    margin-bottom: 8px !important;
                                }

                                @media screen and (max-width:782px) {
                                    .wmus-pagination {
                                        float: none;
                                        height: auto;
                                        text-align: center;
                                        margin-top: 7px;
                                    }
                                    
                                    .wmus-search-box {
                                        margin-bottom: 20px !important;
                                    }
                                }
                            </style>
                            <?php                    
                            if ( $wmus_source_blog != get_current_blog_id() ) {
                                restore_current_blog();
                            }
                        }
                    } else {
                        ?>
                            <div class="notice notice-error is-dismissible">
                                <p><?php esc_html_e( 'Please verify purchase code.', 'wmus' ); ?></p>
                            </div>
                        <?php
                    }
                ?>
            </div>
        <?php
    }
}

/*
 * This is a function that call plugin settings.
 */
if ( ! function_exists( 'wmus_settings_callback' ) ) {
    function wmus_settings_callback() {

        global $wpdb;

        if ( isset( $_POST['submit'] ) ) {
            if ( isset( $_POST['wmus_auto_sync'] ) ) {
                update_site_option( 'wmus_auto_sync', sanitize_text_field( $_POST['wmus_auto_sync'] ) );
            }

            if ( isset( $_POST['wmus_auto_sync_type'] ) ) {
                update_site_option( 'wmus_auto_sync_type', sanitize_text_field( $_POST['wmus_auto_sync_type'] ) );
            }

            if ( isset( $_POST['wmus_auto_sync_sub_blogs'] ) ) {
                if ( is_array( $_POST['wmus_auto_sync_sub_blogs'] ) && $_POST['wmus_auto_sync_sub_blogs'] != null ) {
                    foreach ( $_POST['wmus_auto_sync_sub_blogs'] as $key => $value ) {
                        $_POST['wmus_auto_sync_sub_blogs'][$key] = (int) $value;
                    }

                    update_site_option( 'wmus_auto_sync_sub_blogs', $_POST['wmus_auto_sync_sub_blogs'] );
                } else {
                    update_site_option( 'wmus_auto_sync_sub_blogs', (int) $_POST['wmus_auto_sync_sub_blogs'] );
                }
            }
            
            if ( isset( $_POST['wmus_auto_sync_main_blog'] ) ) {
                update_site_option( 'wmus_auto_sync_main_blog', (int) $_POST['wmus_auto_sync_main_blog'] );
            }

            if ( isset( $_POST['wmus_auto_unsync'] ) ) {
                update_site_option( 'wmus_auto_unsync', (int) $_POST['wmus_auto_unsync'] );
            }

            if ( isset( $_POST['wmus_exclude_user_roles'] ) ) {
                if ( $_POST['wmus_exclude_user_roles'] != null ) {
                    foreach ( $_POST['wmus_exclude_user_roles'] as $key => $value ) {
                        $_POST['wmus_exclude_user_roles'][$key] = sanitize_text_field( $value );
                    }
                }

                update_site_option( 'wmus_exclude_user_roles', $_POST['wmus_exclude_user_roles'] );
            } else {
                update_site_option( 'wmus_exclude_user_roles', array() );
            }

            ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Settings saved.', 'wmus' ); ?></p>
                </div>
            <?php
        }
        
        $sync_type = get_site_option( 'wmus_auto_sync' );
        if ( $sync_type == '1' ) {
            $sync_type = 'auto';
        } else if ( $sync_type == '0' ) {
            $sync_type = 'manual';
        } else {
            //
        }
        
        $auto_unsync = get_site_option( 'wmus_auto_unsync' );
        $auto_sync_type = get_site_option( 'wmus_auto_sync_type' );
        $auto_sync_main_blog = get_site_option( 'wmus_auto_sync_main_blog' );
        $auto_sync_sub_blogs = get_site_option( 'wmus_auto_sync_sub_blogs' );
        if ( ! $auto_sync_sub_blogs || $auto_sync_sub_blogs == null ) {
            $auto_sync_sub_blogs = array();
        }

        $exclude_user_roles = get_site_option( 'wmus_exclude_user_roles' );
        $licence = get_site_option( 'wmus_licence' );
        ?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Settings', 'wmus' ); ?></h2>
                <hr>
                <?php
                    if ( $licence ) {
                        ?>
                            <form method="post">
                                <table class="form-table">
                                    <tbody>                        
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Sync Type', 'wmus' ); ?></th>
                                            <td>
                                                <fieldset>
                                                    <label><input type="radio" name="wmus_auto_sync" value="auto"<?php echo ( $sync_type == 'auto' ? ' checked="checked"' : '' ); ?> /> <?php esc_html_e( 'Auto Sync', 'wmus' ); ?></label><br>
                                                    <label><input type="radio" name="wmus_auto_sync" value="manual"<?php echo ( $sync_type == 'manual' ? ' checked="checked"' : '' ); ?> /> <?php esc_html_e( 'Manual Sync', 'wmus' ); ?></label>
                                                </fieldset>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label><?php esc_html_e( 'Auto Sync Type' ); ?></label></th>
                                            <td>
                                                <fieldset>
                                                    <label>
                                                        <input type="radio" name="wmus_auto_sync_type" value="all-sites"<?php echo ( $auto_sync_type == 'all-sites' ? ' checked="checked"' : '' ); ?> /><?php esc_html_e( 'All sites', 'wmus' ); ?>
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="wmus_auto_sync_type" value="main-site-to-sub-sites"<?php echo ( $auto_sync_type == 'main-site-to-sub-sites' ? ' checked="checked"' : '' ); ?> /><?php esc_html_e( 'Main site to sub sites', 'wmus' ); ?>
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="wmus_auto_sync_type" value="sub-sites-to-main-site"<?php echo ( $auto_sync_type == 'sub-sites-to-main-site' ? ' checked="checked"' : '' ); ?> /><?php esc_html_e( 'Sub site to main site', 'wmus' ); ?>
                                                    </label>
                                                </fieldset>                                
                                            </td>
                                        </tr>
                                        <tr class="wmus-hide-show"<?php echo ( $auto_sync_type == 'sub-sites-to-main-site' || $auto_sync_type == 'all-sites' ? ' style="display:none"' : '' );?>>
                                            <th scope="row"></th>
                                            <td>
                                                <?php esc_html_e( 'Sub Sites', 'wmus' ); ?><br><br>
                                                <label><input class="wmus-check-uncheck" type="checkbox" /><?php esc_html_e( 'All', 'wmus' ); ?></label>
                                                <p class="description"><?php esc_html_e( 'Select/Deselect all sites.', 'wmus' ); ?></p>
                                                <br>
                                                <fieldset class="wmus-sites">  
                                                    <input type="hidden" name="wmus_auto_sync_sub_blogs" value="0" />
                                                    <?php                                        
                                                        $sites = $wpdb->get_results( "SELECT * FROM ".$wpdb->base_prefix."blogs" );
                                                        if ( $sites != null ) {
                                                            foreach ( $sites as $key => $value ) { 
                                                                if ( ! is_main_site( $value->blog_id ) ) {
                                                                    $blog_details = get_blog_details( $value->blog_id );
                                                                    ?>
                                                                        <label><input name="wmus_auto_sync_sub_blogs[]" type="checkbox" value="<?php echo intval( $value->blog_id ); ?>"<?php echo ( in_array( $value->blog_id, $auto_sync_sub_blogs ) ? ' checked="checked"' : '' ); ?>><?php echo $value->domain; echo $value->path; echo ' ('.$blog_details->blogname.')'; ?></label><br>
                                                                    <?php
                                                                } else {
                                                                    ?><input type="hidden" name="wmus_auto_sync_main_blog" value="<?php echo intval( $value->blog_id ); ?>"/><?php
                                                                }
                                                            }
                                                        }
                                                    ?>                                                                          				
                                                </fieldset>                                
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Auto Unsync?', 'wmus' ); ?></th>
                                            <td>
                                                <input type="hidden" name="wmus_auto_unsync" value="0" />
                                                <input type="checkbox" name="wmus_auto_unsync" value="1"<?php echo ( $auto_unsync ? ' checked="checked"' : '' ); ?> />
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Exclude User Roles', 'wmus' ); ?></th>
                                            <td>
                                                <fieldset>
                                                    <?php
                                                        $roles = get_editable_roles();
                                                        if ( $roles != null ) {
                                                            foreach ( $roles as $key => $value ) {
                                                                $checked = '';
                                                                if ( $exclude_user_roles && in_array( $key, $exclude_user_roles ) ) {
                                                                    $checked = ' checked="checked"';
                                                                }
                                                                ?>
                                                                    <label><input name="wmus_exclude_user_roles[]" type="checkbox" value="<?php echo esc_html( $key ); ?>"<?php echo $checked; ?>><?php echo $value['name']; ?></label><br>
                                                                <?php
                                                            }
                                                        }
                                                    ?>
                                                </fieldset>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p>
                                    <input type="submit" name="submit" value="<?php esc_html_e( 'Save Changes', 'wmus' ); ?>" class="button button-primary" />
                                </p>
                            </form>
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
                    } else {
                        ?>
                            <div class="notice notice-error is-dismissible">
                                <p><?php esc_html_e( 'Please verify purchase code.', 'wmus' ); ?></p>
                            </div>
                        <?php
                    }
                ?>
            </div>
        <?php
    }
}

/*
 * This is a function that verify plugin licence.
 */
if ( ! function_exists( 'wmus_licence_verification_callback' ) ) {
    function wmus_licence_verification_callback() {

        if ( isset( $_POST['verify'] ) ) {
            if ( isset( $_POST['wmus_purchase_code'] ) ) {
                update_site_option( 'wmus_purchase_code', sanitize_text_field( $_POST['wmus_purchase_code'] ) );
                
                $data = array(
                    'sku'           => '19660623',
                    'purchase_code' => $_POST['wmus_purchase_code'],
                    'domain'        => site_url(),
                    'status'        => 'verify',
                    'type'          => 'oi',
                );

                $ch = curl_init();
                curl_setopt( $ch, CURLOPT_URL, 'https://www.obtaininfotech.com/extension/' );
                curl_setopt( $ch, CURLOPT_POST, 1 );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
                $json_response = curl_exec( $ch );
                curl_close ($ch);
                
                $response = json_decode( $json_response );
                $response = json_decode( $json_response );
                if ( isset( $response->success ) ) {
                    if ( $response->success ) {
                        update_site_option( 'wmus_licence', 1 );
                    }
                }
            }
        } else if ( isset( $_POST['unverify'] ) ) {
            if ( isset( $_POST['wmus_purchase_code'] ) ) {
                $data = array(
                    'sku'           => '19660623',
                    'purchase_code' => $_POST['wmus_purchase_code'],
                    'domain'        => site_url(),
                    'status'        => 'unverify',
                    'type'          => 'oi',
                );

                $ch = curl_init();
                curl_setopt( $ch, CURLOPT_URL, 'https://www.obtaininfotech.com/extension/' );
                curl_setopt( $ch, CURLOPT_POST, 1 );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
                $json_response = curl_exec( $ch );
                curl_close ($ch);

                $response = json_decode( $json_response );
                if ( isset( $response->success ) ) {
                    if ( $response->success ) {
                        update_site_option( 'wmus_purchase_code', '' );
                        update_site_option( 'wmus_licence', 0 );
                    }
                }
            }
        }
        
        $wmus_purchase_code = get_site_option( 'wmus_purchase_code' );
        ?>
            <div class="wrap">      
                <h2><?php esc_html_e( 'Licence Verification', 'wmus' ); ?></h2>
                <hr>
                <?php
                    if ( isset( $response->success ) ) {
                        if ( $response->success ) {                            
                             ?>
                                <div class="notice notice-success is-dismissible">
                                    <p><?php echo $response->message; ?></p>
                                </div>
                            <?php
                        } else {
                            update_site_option( 'wmus_licence', 0 );
                            ?>
                                <div class="notice notice-error is-dismissible">
                                    <p><?php echo $response->message; ?></p>
                                </div>
                            <?php
                        }
                    }
                ?>
                <form method="post">
                    <table class="form-table">                    
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Purchase Code', 'wmus' ); ?></th>
                                <td>
                                    <input name="wmus_purchase_code" type="text" class="regular-text" value="<?php echo esc_html( $wmus_purchase_code ); ?>" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p>
                        <input type='submit' class='button-primary' name="verify" value="<?php esc_html_e( 'Verify', 'wmus' ); ?>" />
                        <input type='submit' class='button-primary' name="unverify" value="<?php esc_html_e( 'Unverify', 'wmus' ); ?>" />
                    </p>
                </form>
            </div>
        <?php
    }
}
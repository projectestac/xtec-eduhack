<?php
/**
 * @package XTEC Blocs
 * @subpackage Eduhack
 * @version 1.0
 */
/*
Plugin Name:    XTEC Eduhack
Description:    Eduhack plugin
Author:         Joan Sala Soler
Version:        1.0
Text Domain:    xtec-eduhack
Domain Path:    /languages
License:        GNU General Public License v2 or later
License URI:    http://www.gnu.org/licenses/gpl-2.0.html
*/

/** Project name */
const XTEH_NAME = 'EduHack';

/** Base path of the multisite blog */
const XTEH_BASE_PATH = 'eduhack-';


/**
 * Validates the requested form and returns the found errors or an empty
 * error object if the form was valid.
 *
 * @param $form     $_POST array
 * @return          WP_Error
 *
 * @since Eduhack 1.0
 */
function xteh_validate_form($form) {
    $errors = new WP_Error();
    $fields = ['title', 'slug', 'description', '_wpnonce'];
    $site = get_current_site();
    
    // Check that all the required fields where provided
    
    foreach ($fields as $field) {
        if (isset($form[$field]) === false) {
            $errors->add('missing_field', __(
                'All the fields must be filled.', 'xtec-eduhack'));
            
            return $errors;
        }
    }
    
    // Verify that the form was submited from our web site
    
    if (!wp_verify_nonce($form['_wpnonce'], 'clone_eduhack_template')) {
       $errors->add('invalid_nonce', __(
           'Could not process the form.', 'xtec-eduhack'));
    }
    
    // Check that the slug is valid and not already taken
    
    $slug = strtolower($form['slug']);
    $path = $site->path . XTEH_BASE_PATH . "$slug/";
    
    if (!preg_match('/^([a-z0-9-])+$/i', $slug)) {
        $errors->add('invalid_domain', __(
            'The web address must contain only letters and numbers.', 'xtec-eduhack'));
    }
    
    if (domain_exists($site->domain, $path)) {
        $errors->add('invalid_domain', __(
            'The choosen web address already exits.', 'xtec-eduhack'));
    }
    
    return $errors;
}


/**
 * Duplicates the template blog.
 *
 * @param $form     $_POST array
 * @return          Error or success message
 *
 * @since Eduhack 1.0
 */
function xteh_duplicate_site( $form ) {
    require_once(__DIR__ . '/../multisite-clone-duplicator/lib/duplicate.php');
    
    // Only logged in users can duplicate sites
    
    if ( ! is_user_logged_in() ) {
        exit(1);
    }
    
    // Clone the template site
    
    $template_id = get_site_option( 'eduhack_template_id' );
    $site = get_current_site();
    $user = wp_get_current_user();
    $slug = strtolower($form['slug']);
    
    $message = MUCD_Duplicate::duplicate_site([
        'title' => $form['title'],
        'email' => $user->user_email,
        'path' => $site->path . XTEH_BASE_PATH . "$slug/",
        'domain' => $site->domain,
        'newdomain' => $site->domain,
        'from_site_id' => $template_id,
        'network_id' => $site->id,
        'keep_users' => false,
        'copy_files' => true,
        'public' => true
    ]);
    
    // If the blog was cloned successfuly, update its description
    
    if ( !isset( $message['error'] ) && isset( $message['site_id'] ) ) {
        $site_id = $message['site_id'];
        $description = $form['description'];
        
        update_blog_option($site_id, 'blogdescription', $description);
        update_blog_option($site_id, 'blog_public', 1);
    }
    
    return $message;
}


/**
 * Create the template blog that will be cloned. The created template
 * identifier will be stored on the 'eduhack_template_id' site option.
 *
 * @since Eduhack 1.0
 */
function xteh_create_template() {
    $site = get_current_site();
    
    $blog_id = wpmu_create_blog(
        $site->domain,
        $site->path . XTEH_BASE_PATH . 'template',
        sprintf(__('%s Template', 'xtec-eduhack'), XTEH_NAME),
        wp_get_current_user()->ID,
        '',
        $site->id
    );
    
    switch_to_blog( $blog_id );
    activate_plugin( 'xtec-eduhack/xtec-eduhack.php', null, false, true);
    activate_plugin( 'widget-options/plugin.php', null, false, false);
    activate_plugin( 'tabs-responsive/tabs-responsive.php', null, false, false);
    switch_theme( 'eduhack' );
    restore_current_blog();
    
    update_blog_status( $blod_id, 'public', 0 );
    update_site_option( 'eduhack_template_id', $blog_id );
}


/**
 * Prevents access to the template site. Only members of the site and super
 * admins will be able to access the template pages.
 *
 * @since Eduhack 1.0
 */
add_action( 'wp_loaded', function() {
    $template_id = get_site_option( 'eduhack_template_id' );
    
    if ( get_current_blog_id() == $template_id ) {
        if ( !is_super_admin() && !is_user_member_of_blog() ) {
            wp_redirect( network_home_url() );
            exit(1);
        }
    }
});


/**
 * Activate this plugin. This plugin requires the Multisite Clone Duplicator
 * to be installed and active for the main site.
 *
 * @since Eduhack 1.0
 */
register_activation_hook( __FILE__, function() {
    // Check that all the requeriments for this plugin are fullfilled
    
    $mucd_slug = 'multisite-clone-duplicator';
    
    if ( ! is_plugin_active( "$mucd_slug/$mucd_slug.php" ) ) {
        wp_die(__(
          'This plugin requires Multisite Clone Duplicator to be insalled ' .
          'and active for the main site.', 'xtec-eduhack'
        ));
    }
    
    $theme = wp_get_theme( 'fukasawa' );
    
    if ( empty($theme) ) {
        wp_die(__(
            'This plugin requires the Fukasawa theme to be insalled.',
            'xtec-eduhack'
        ));
    }
    
    // Create the template blog template if we are activating this plugin
    // on the main site and the template does not exist
    
    if ( is_user_logged_in() && is_main_site() ) {
        $id = get_site_option( 'eduhack_template_id' );
        
        if ( $id === false || get_blog_details($id)->blog_id !== $id ) {
            xteh_create_template();
        }
    }
});


/**
 * Initialize this plugin.
 *
 * @since Eduhack 1.0
 */
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'xtec-eduhack', false, 'xtec-eduhack/languages' );
    load_plugin_textdomain( 'widget-options', false, 'xtec-eduhack/languages/' );
});


/**
 * Register the EduHack theme with this extension.
 * 
 * @since Eduhack 1.0
 */
register_theme_directory( dirname( __FILE__ ) . '/themes' );


/* Functions that modify common plugins behaviour */


/**
 * Remove plugin actions if they are registered. The removed actions
 * usually contain only advertisings.
 *
 * @since Eduhack 1.0
 */
add_action( 'wp_loaded', function() {
    // Plugin: widget-options
    
    remove_action( 'admin_notices', 'widgetopts_admin_notices' );
    
    remove_action( 'widgetopts_module_sidebar', 'widgetopts_settings_more_plugins', 25 );
    remove_action( 'widgetopts_module_sidebar', 'widgetopts_settings_sidebar_opt_in', 20 );
    remove_action( 'widgetopts_module_sidebar', 'widgetopts_settings_support_box', 30 );
    remove_action( 'widgetopts_module_sidebar', 'widgetopts_settings_upgrade_pro', 10 );
    
    remove_action( 'extended_widget_opts_tabs', 'widgetopts_tab_gopro', 100 );
    remove_action( 'extended_widget_opts_tabcontent', 'widgetopts_tabcontent_gopro' );
    
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_animation', 130 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_cache', 175 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_columns', 90 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_columns', 90 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_dates', 110 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_disable_widgets', 150 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_fixed', 80 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_links', 70 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_links', 70 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_logic', 60 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_permissions', 160 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_roles', 100 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_shortcodes', 170 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_siteorigin', 65 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_styling', 120 );
    remove_action( 'widgetopts_module_cards', 'widgetopts_settings_taxonomies', 140 );
    
    // Plugin: tabs-responsive
    
    remove_action( 'admin_menu' , 'wpsm_tabs_r_recom_menu' );
    remove_action( 'admin_notices', 'wpsm_tabs_r_review' );
    remove_action( 'in_admin_header','wpsm_tabs_respnsive_header_info' );
});


/**
 * Remove plugin metaboxes if they are registered. The removed metaboxes
 * usually contain only advertisings, so it's safe to remove them.
 *
 * @since Eduhack 1.0
 */
add_action( 'do_meta_boxes', function() {
    // Plugin: tabs-responsive
    
    remove_meta_box( 'tabs_r_more_pro', 'tabs_responsive', 'normal' );
    remove_meta_box( 'tabs_r_rateus', 'tabs_responsive', 'side' );
    remove_meta_box( 'tabs_r_shortcode', 'tabs_responsive', 'normal' );
    remove_meta_box( 'wpsm_tabs_r_pic_more_pro', 'tabs_responsive', 'normal' );
});


/**
 * Hides certain menus from the administration dashboard. Note that this
 * does not prevent the user from accessing the option pages directly.
 *
 * @since Eduhack 1.0
 */
add_action('admin_menu', function() {
    if ( !is_main_site() && !is_super_admin() ) {
        remove_submenu_page( 'themes.php', 'themes.php' );
        remove_submenu_page( 'options-general.php', 'widgetopts_plugin_settings' );
    }
}, 1000);


/**
 * Removes the themes picker option from the customize manager.
 *
 * @since Eduhack 1.0
 */
add_action( 'customize_register', function( $wp_customize ) {
    if ( !is_main_site() && !is_super_admin() ) {
        $wp_customize->remove_section( 'themes' );
    }
}, 1000);

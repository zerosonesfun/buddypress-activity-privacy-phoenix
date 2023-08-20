<?php
/**
 * Plugin Name: BuddyPress Activity Privacy (Phoenix)
 * Plugin URI: https://wilcosky.com/skywolf
 * Description: Add the ability for members to choose who can read/see their activities and media files.
 * Version: 1.4
 * Requires at least: WP 3.4, BuddyPress 1.5
 * Tested up to: BuddyPress 11.2.0
 * License: GNU General Public License 2.0 (GPL)
 * Author: Meg@Info, Boone Gorges, Billy Wilcosky
 * Author URI: https://wilcosky.com/skywolf
 * Text Domain: bp-activity-privacy
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Constant to determine if the plugin is installed
define('BP_ACTIVITY_PRIVACY_IS_INSTALLED', 1);

// Constant that holds the version number
define('BP_ACTIVITY_PRIVACY_VERSION', '1.4');

// Filepath constant
define('BP_ACTIVITY_PRIVACY_PLUGIN_DIR', dirname(__FILE__));

// File loading constant
define('BP_ACTIVITY_PRIVACY_PLUGIN_FILE_LOADER',  __FILE__);

// Plugin URL constant
define('BP_ACTIVITY_PRIVACY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Plugin basename constant
define('BP_ACTIVITY_PRIVACY_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Directory path constant
define('BP_ACTIVITY_PRIVACY_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));

// Textdomain loader
function bp_activity_privacy_load_textdomain() {
    $mofile = sprintf('buddypress-activity-privacy-%s.mo', get_locale());

    $mofile_global = trailingslashit(WP_LANG_DIR) . $mofile;
    $mofile_local = BP_ACTIVITY_PRIVACY_PLUGIN_DIR_PATH . 'languages/' . $mofile;

    if (is_readable($mofile_global)) {
        return load_textdomain('bp-activity-privacy', $mofile_global);
    } elseif (is_readable($mofile_local)) {
        return load_textdomain('bp-activity-privacy', $mofile_local);
    } else {
        return false;
    }
}
add_action('plugins_loaded', 'bp_activity_privacy_load_textdomain');

// Is it multi-site?
function bp_activity_privacy_check_config() {
    global $bp;

    $config = array(
        'blog_status' => false,
        'network_active' => false,
        'network_status' => true
    );
    if (get_current_blog_id() == bp_get_root_blog_id()) {
        $config['blog_status'] = true;
    }

    $network_plugins = get_site_option('active_sitewide_plugins', array());

    // No Network plugins
    if (empty($network_plugins)) {
        // Looking for BuddyPress and bp-activity plugin
        $check[] = $bp->basename;
        $check[] = BP_ACTIVITY_PRIVACY_PLUGIN_BASENAME;

        // Are they active on the network?
        $network_active = array_diff($check, array_keys($network_plugins));

        // If result is 1, your plugin is network activated
        // and not BuddyPress or vice versa. Config is not ok
        if (count($network_active) == 1) {
            $config['network_status'] = false;
        }
    }

    // We need to know if the plugin is network activated to choose the right
    // notice (admin or network_admin) to display the warning message.
    $config['network_active'] = isset($network_plugins[BP_ACTIVITY_PRIVACY_PLUGIN_BASENAME]);

    // if BuddyPress config is different from bp-activity plugin
    if (!$config['blog_status'] || !$config['network_status']) {

        $warnings = array();
        if (!bp_core_do_network_admin() && !$config['blog_status']) {
            $warnings[] = __('Buddypress Activity Privacy requires to be activated on the blog where BuddyPress is activated.', 'bp-activity-privacy');
        }

        if (bp_core_do_network_admin() && !$config['network_status']) {
            $warnings[] = __('Buddypress Activity Privacy and BuddyPress need to share the same network configuration.', 'bp-activity-privacy');
        }

        if (!empty($warnings)) :
            ?>
            <div id="message" class="error">
                <?php foreach ($warnings as $warning) : ?>
                    <p><?php echo esc_html($warning); ?></p>
                <?php endforeach; ?>
            </div>
        <?php
        endif;

        // Display a warning message in network admin or admin
        add_action($config['network_active'] ? 'network_admin_notices' : 'admin_notices', $warning);

        return false;
    }
    return true;
}

/* Only load the component if BuddyPress is loaded and initialized. */
function bp_activity_privacy_init() {
    // Because our loader file uses BP_Component, it requires BP 1.5 or greater.
    //if (version_compare(BP_VERSION, '1.3', '>'))
    if (bp_activity_privacy_check_config()) {
        require(dirname(__FILE__) . '/includes/bp-activity-privacy-loader.php');
    }
}
add_action('bp_include', 'bp_activity_privacy_init');

/* Put setup procedures to be run when the plugin is activated in the following function */
function bp_activity_privacy_activate() {
    // check if BuddyPress is active
    if (!defined('BP_VERSION')) {
        die(_e('You cannot enable BuddyPress Activity Privacy because <strong>BuddyPress</strong> is not active. Please install and activate BuddyPress before trying to activate Buddypress Activity Privacy again.', 'bp-activity-privacy'));
    }

    // Add the transient to redirect
    set_transient('_bp_activity_privacy_activation_redirect', true, 30);

    do_action('bp_activity_privacy_activation');
}
register_activation_hook(__FILE__, 'bp_activity_privacy_activate');

/* On deactivation, clean up anything your component has added. */
function bp_activity_privacy_deactivate() {
    /* You might want to delete any options or tables that your component created. */
    do_action('bp_activity_privacy_deactivation');
}
register_deactivation_hook(__FILE__, 'bp_activity_privacy_deactivate');
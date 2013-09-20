<?php
/*
Plugin Name: Orbisius Simple Notice
Plugin URI: http://club.orbisius.com/products/wordpress-plugins/orbisius-simple-notice/
Description: This plugin allows you to show a simple notice to alert your users about server maintenance, new product launches etc.
Version: 1.0.0
Author: Svetoslav Marinov (Slavi)
Author URI: http://orbisius.com
*/

/*  Copyright 2012 Svetoslav Marinov (Slavi) <slavi@orbisius.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Set up plugin
add_action( 'init', 'orbisius_simple_notice_init' );

add_action( 'admin_menu', 'orbisius_simple_notice_setup_admin' );
//add_action( 'wp_head', 'orbisius_simple_notice_config');
add_action( 'admin_head', 'orbisius_simple_notice_config');
add_action( 'wp_footer', 'orbisius_simple_notice_inject_notice' ); // be the last in the footer

/**
 * Outputs directly to the browser a json config file which is used by main.js
 * This contains the ajax endpoint & page id so we can include it in the feedback.
 */
function orbisius_simple_notice_config() {
    $queried_object = get_queried_object();

    $plugin_ajax_url = admin_url('admin-ajax.php'); // not always defined on the public side.
    $id = empty($queried_object->ID) ? 0 : $queried_object->ID;

    echo "\n<script> var orbisius_simple_notice_config = { plugin_ajax_url: '$plugin_ajax_url', page_id : $id };</script>\n";
}

/**
 * Adds the action link to settings. That's from Plugins. It is a nice thing.
 * @param type $links
 * @param type $file
 * @return type
 */
function orbisius_simple_notice_add_quick_settings_link($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $link = admin_url('options-general.php?page=' . plugin_basename(__FILE__));
        $dashboard_link = "<a href=\"{$link}\">Settings</a>";
        array_unshift($links, $dashboard_link);
    }

    return $links;
}

/**
 * Setups loading of assets (css, js).
 * for live servers we'll use the minified versions e.g. main.min.js otherwise .js or .css (dev)
 * @see http://jscompress.com/ - used for JS compression
 * @see http://refresh-sf.com/yui/ - used for CSS compression
 * @return type
 */
function orbisius_simple_notice_init() {
    /*$dev = empty($_SERVER['DEV_ENV']) ? 0 : 1;
    $suffix = $dev ? '' : '.min';

    wp_register_style( 'simple_notice', plugins_url("/assets/main{$suffix}.css", __FILE__) );
    wp_enqueue_style( 'simple_notice' );

    wp_register_script( 'simple_notice', plugins_url("/assets/main{$suffix}.js", __FILE__), array('jquery', ), '1.0', true );
    wp_enqueue_script( 'simple_notice');

    $opts = orbisius_simple_notice_get_options();*/
}

/**
 * Outputs the feedback form + container. if the user is logged in we'll take their email
 * requires: wp_footer
 */
function orbisius_simple_notice_inject_notice() {
    $opts = orbisius_simple_notice_get_options();
    $data = orbisius_simple_notice_get_plugin_data();

    // The user doesn't want to show the form.
    if (empty($opts['status'])) {
        echo "\n<!-- {$data['name']} | {$data['url']} : is disabled. Skipping rendering. -->\n";
        return ;
    }

    $xyz = "<a href='{$data['url']}' target='_blank'>{$data['name']}</a>";

    $powered_by_line = "<div class='powered_by'>Powered by $xyz</div>";

    // in case if somebody wants to get rid if the feedback link
    $powered_by_line = apply_filters('orbisius_simple_notice_filter_powered_by', $powered_by_line);

    $notice = empty($opts['notice']) ? '' : $opts['notice'];

    // if the user is logged in WP admin bar is obstructive the view.
    $top_pos = is_user_logged_in() ? '28px' : 0;

	$form_buff = <<<FORM_EOF
<div id="orbisius_simple_notice_container" class="orbisius_simple_notice_container">
    <div id="orbisius_simple_notice" class="orbisius_simple_notice" style='text-align:center;width:100%;z-index:99999;position:fixed;width:100%;top:$top_pos;left:0;margin:0;color:#555;background: none repeat scroll 0 0 #B4CFD3;font-family: arial;'>
        $notice
    </div> <!-- /orbisius_simple_notice -->
</div> <!-- /orbisius_simple_notice_container -->

FORM_EOF;
    // We will be doing some upgrades to the server later today. If you run into issues today or tomorrow let us know at <a href="http://twitter.com/qsandbox" target="_blank">@qsandbox (twitter)</a> or <a href="http://qsandbox.com/app/contact.php">http://qsandbox.com/app/contact.php</a>
    echo $form_buff;
}

/**
 * Set up administration
 *
 * @package Orbisius Simple Notice
 * @since 0.1
 */
function orbisius_simple_notice_setup_admin() {
	add_options_page( 'Orbisius Simple Notice', 'Orbisius Simple Notice', 'manage_options', __FILE__, 'orbisius_simple_notice_options_page' );

    add_filter('plugin_action_links', 'orbisius_simple_notice_add_quick_settings_link', 10, 2);
}

add_action('admin_init', 'orbisius_simple_notice_register_settings');

/**
 * Sets the setting variables
 */
function orbisius_simple_notice_register_settings() { // whitelist options
    register_setting('orbisius_simple_notice_settings', 'orbisius_simple_notice_options', 'orbisius_simple_notice_validate_settings');
}

/**
 * This is called by WP after the user hits the submit button.
 * The variables are trimmed first and then passed to the who ever wantsto filter them.
 * @param array the entered data from the settings page.
 * @return array the modified input array
 */
function orbisius_simple_notice_validate_settings($input) { // whitelist options
    $input = array_map('trim', $input);

    // let extensions do their thing
    $input_filtered = apply_filters('orbisius_simple_notice_ext_filter_settings', $input);

    // did the extension break stuff?
    $input = is_array($input_filtered) ? $input_filtered : $input;

    return $input;
}

/**
 * Retrieves the plugin options. It inserts some defaults.
 * The saving is handled by the settings page. Basically, we submit to WP and it takes
 * care of the saving.
 * 
 * @return array
 */
function orbisius_simple_notice_get_options() {
    $defaults = array(
        'status' => 0,
        'show_in_admin' => 0,
        'notice' => 'We have launched a new product ...',
    );
    
    $opts = get_option('orbisius_simple_notice_options');
    
    $opts = (array) $opts;
    $opts = array_merge($defaults, $opts);

    return $opts;
}

/**
 * Options page
 *
 * @package Orbisius Simple Notice
 * @since 1.0
 */
function orbisius_simple_notice_options_page() {
    $opts = orbisius_simple_notice_get_options();
	?>
	<div class="wrap orbisius_simple_notice_admin_wrapper">
        <h2>Orbisius Simple Notice</h2>
		<p>
            This plugin allows you to show a simple notice to alert your users about server maintenance, new product launches etc.
        </p>
		
        <h2>Settings</h2>
     
        <form method="post" action="options.php">
            <?php settings_fields('orbisius_simple_notice_settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Show Notice</th>
                    <td>
                        <label for="radio1">
                            <input type="radio" id="radio1" name="orbisius_simple_notice_options[status]"
                                value="1" <?php echo empty($opts['status']) ? '' : 'checked="checked"'; ?> /> Enabled
                        </label>
                        <br/>
                        <label for="radio2">
                            <input type="radio" id="radio2" name="orbisius_simple_notice_options[status]"
                                value="0" <?php echo!empty($opts['status']) ? '' : 'checked="checked"'; ?> /> Disabled
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Notice</th>
                    <td>
                        <label for="orbisius_simple_notice_options_notice">
                            <input type="text" id="orbisius_simple_notice_options_notice" class="widefat"
                                   name="orbisius_simple_notice_options[notice]"
                                value="<?php echo esc_attr($opts['notice']); ?>" />
                        </label>
                        <p>Example: We are going to be doing server maintenance at 9pm today.
                        <br/>Example: We have just launched a new product ...
                        <br/>Note: You can include HTML as well.</p>
                    </td>
                </tr>
                <?php if (has_action('orbisius_simple_notice_ext_action_render_settings')) : ?>
                    <tr valign="top">
                        <th scope="row"><strong>Extensions (see list)</strong></th>
                        <td colspan="1">
                        </td>
                    </tr>
                    <?php do_action('orbisius_simple_notice_ext_action_render_settings', $opts, $settings_key); ?>
                <?php else : ?>
                    <tr valign="top">
                        <!--<th scope="row">Extension Name</th>-->
                        <td colspan="2">
                            No extensions found.
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
            </p>
        </form>

        <h2>Mailing List</h2>
        <p>
            Get the latest news and updates about this and future cool <a href="http://profiles.wordpress.org/lordspace/"
                                                                            target="_blank" title="Opens a page with the pugins we developed. [New Window/Tab]">plugins we develop</a>.
        </p>
        <p>
            <!-- // MAILCHIMP SUBSCRIBE CODE \\ -->
            1) <a href="http://eepurl.com/guNzr" target="_blank">Subscribe to our newsletter</a>
            <!-- \\ MAILCHIMP SUBSCRIBE CODE // -->
        </p>
        <p>OR</p>
        <p>
            2) Subscribe using our QR code. [Scan it with your mobile device].<br/>
            <img src="<?php echo plugin_dir_url(__FILE__); ?>/i/guNzr.qr.2.png" alt="" />
        </p>

        <h2>Extensions</h2>
        <p>Extensions allow you to add an extra functionality to this plugin.</p>
        <div>
            <?php
               if (!has_action('orbisius_simple_notice_ext_action_extension_list')) {
                   echo "No extensions have been installed.";
               } else {
                   echo "The following extensions have been found.<br/><ul>";
                   do_action('orbisius_simple_notice_ext_action_extension_list');
                   echo "</ul>";
               }
               ?>
        </div>
        
        <?php
        $plugin_slug = basename(__FILE__);
        $plugin_slug = str_replace('.php', '', $plugin_slug);
        ?>
        <iframe style="width:100%;min-height:300px;height: auto;" width="640" height="480"
                src="http://club.orbisius.com/wpu/content/wp/<?php echo $plugin_slug;?>/" frameborder="0" allowfullscreen></iframe>

        <h2>Support & Feature Requests</h2>
        <div class="updated"><p>
            ** NOTE: ** Support is handled on our site: <a href="http://club.orbisius.com/support/" target="_blank" title="[new window]">http://club.orbisius.com/support/</a>.
            Please do NOT use the WordPress forums or other places to seek support.
        </p></div>

        <?php
            $plugin_data = get_plugin_data(__FILE__);

            $app_link = urlencode($plugin_data['PluginURI']);
            $app_title = urlencode($plugin_data['Name']);
            $app_descr = urlencode($plugin_data['Description']);
        ?>
        <h2>Share</h2>
        <p>
            <!-- AddThis Button BEGIN -->
            <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                <a class="addthis_button_facebook" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_twitter" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_google_plusone" g:plusone:count="false" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_linkedin" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_email" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_myspace" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_google" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_digg" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_delicious" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_stumbleupon" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_tumblr" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_favorites" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                <a class="addthis_button_compact"></a>
            </div>
            <!-- The JS code is in the footer -->

            <script type="text/javascript">
            var addthis_config = {"data_track_clickback":true};
            var addthis_share = {
              templates: { twitter: 'Check out {{title}} @ {{lurl}} (from @orbisius)' }
            }
            </script>
            <!-- AddThis Button START part2 -->
            <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=lordspace"></script>
            <!-- AddThis Button END part2 -->
        </p>

	</div>
	<?php
}

function orbisius_simple_notice_get_plugin_data() {
 // pull only these vars
    $default_headers = array(
		'Name' => 'Plugin Name',
		'PluginURI' => 'Plugin URI',
	);

    $plugin_data = get_file_data(__FILE__, $default_headers, 'plugin');

    $url = $plugin_data['PluginURI'];
    $name = $plugin_data['Name'];

    $data['name'] = $name;
    $data['url'] = $url;
    
    return $data;
}

/**
* adds some HTML comments in the page so people would know that this plugin powers their site.
*/
function orbisius_simple_notice_add_plugin_credits() {
    // pull only these vars
    $default_headers = array(
		'Name' => 'Plugin Name',
		'PluginURI' => 'Plugin URI',
	);

    $plugin_data = get_file_data(__FILE__, $default_headers, 'plugin');

    $url = $plugin_data['PluginURI'];
    $name = $plugin_data['Name'];

    printf(PHP_EOL . PHP_EOL . '<!-- ' . "Powered by $name | URL: $url " . '-->' . PHP_EOL . PHP_EOL);
}

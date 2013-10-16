<?php
/*
  Plugin Name: Orbisius Simple Notice
  Plugin URI: http://club.orbisius.com/products/wordpress-plugins/orbisius-simple-notice/
  Description: This plugin allows you to show a simple notice to alert your users about server maintenance, new product launches etc.
  Version: 1.0.1
  Author: Svetoslav Marinov (Slavi)
  Author URI: http://orbisius.com
 */

/*  Copyright 2012-2050 Svetoslav Marinov (Slavi) <slavi@orbisius.com>

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
add_action('init', 'orbisius_simple_notice_init');
add_action('wp_footer', 'orbisius_simple_notice_inject_notice', 50, 0); // be the last in the footer
add_action('admin_init', 'orbisius_simple_notice_admin_init');
add_action('admin_menu', 'orbisius_simple_notice_setup_admin');

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
    $dev = empty($_SERVER['DEV_ENV']) ? 0 : 1;
    $suffix = $dev ? '' : '.min';

    wp_register_style('simple_notice', plugins_url("/assets/main{$suffix}.css", __FILE__));
    wp_enqueue_style('simple_notice');

    //$opts = orbisius_simple_notice_get_options();
}

function orbisius_simple_notice_admin_init() {
    orbisius_simple_notice_register_settings();

    global $wp_version;

    $color_picker = version_compare($wp_version, '3.5') >= 0 ? 'wp-color-picker' // new WP
            : 'farbtastic'; // old WP

    wp_enqueue_style($color_picker);
    wp_enqueue_script($color_picker);

    wp_register_script('simple_notice_admin', plugins_url("/assets/admin_main.js", __FILE__), array('jquery',), '1.0', true);
    wp_enqueue_script('simple_notice_admin');
}

/**
 * Outputs the feedback form + container. if the user is logged in we'll take their email
 * requires: wp_footer
 */
function orbisius_simple_notice_inject_notice($live_site = 1) {
    $opts = orbisius_simple_notice_get_options();
    $data = orbisius_simple_notice_get_plugin_data();

    // The user doesn't want to show the notice.
    // This applies only for the live site.
    if (empty($opts['status']) && !empty($live_site)) {
        echo "\n<!-- {$data['name']} | {$data['url']} : is disabled. Skipping rendering. -->\n";
        return;
    }

    $powered_by_line = 
            "<div class='orbisius_simple_notice_powered_by'>"
            . "<a title='Powered by {$data['name']}. Free hellobar WordPress Plugin, hellobar,alert notice,notice bar'"
            . " href='{$data['url']}' class='little_info' target='_blank'>i</a>"
            . "</div>\n";

    if (empty($opts['show_powered_by'])) {
        $powered_by_line = '';
    } else {
        // in case if somebody wants to get rid if the feedback link
        $powered_by_line = apply_filters('orbisius_simple_notice_filter_powered_by', $powered_by_line);
    }

    $notice = empty($opts['notice']) ? '' : $opts['notice'];
    $text_color = empty($opts['text_color']) ? '#000' : $opts['text_color'];
    $text_bg_color = empty($opts['text_bg_color']) ? '#B4CFD3' : $opts['text_bg_color'];
    $link_color = empty($opts['link_color']) ? '' : $opts['link_color'];

    // if the user is logged in WP admin bar is obstructive the view.
    $top_pos = is_user_logged_in() ? '28px' : 0;
    $left_pos = 0;

    $inline_css_arr = array(
        // configurable
        "color:$text_color",
        "background:none repeat scroll 0 0 $text_bg_color",
        // static
        "margin:0",
        "padding:3px",
        "text-align:center",
        "width:100%",
        //"z-index:99999",
        "font-family:arial",
    );

    $js_first = '';

    if (!empty($live_site)) {
        if ($opts['show_notice_method'] == 'on_top') {
            $inline_css_arr[] = "top:$top_pos";
            $inline_css_arr[] = "left:$left_pos";
            $inline_css_arr[] = "position:fixed";
        } else {
            $js_first .= <<<FORM_EOF
<script>
    jQuery(document).ready(function ($) {
        jQuery('#orbisius_simple_notice_container').prependTo('body'); // .css('postion', '')
    });
</script>
FORM_EOF;
        }
    }

    $js_first .= <<<FORM_EOF
<script>
    jQuery(document).ready(function ($) {
        jQuery('#orbisius_simple_notice_container .little_info').hover(function() {
            jQuery('.orbisius_simple_notice_powered_by').show();
        }, function() {
            setTimeOut(function () {
                jQuery('.orbisius_simple_notice_powered_by').hide();
            }, 1000);
        });
    });
</script>
FORM_EOF;

    $link_color_css = '';
    $inline_css = join(";\n", $inline_css_arr);

    // do we have a specific color for links?. Yep. Include its CSS then.
    if (!empty($link_color)) {
        $link_color_css = ".orbisius_simple_notice_container .orbisius_simple_notice a,
                .orbisius_simple_notice_container .orbisius_simple_notice a:visited {
                color: $link_color;
            }\n";
    }

    $form_buff = <<<FORM_EOF
<!-- orbisius_simple_notice : -->
<style>
.orbisius_simple_notice_container .orbisius_simple_notice {
    $inline_css
}

$link_color_css

.orbisius_simple_notice_powered_by_container {
    float:left;
    text-decoration:none;
}

.orbisius_simple_notice_powered_by_container .little_info {
    text-decoration:none;
    display:block-inline;
    padding:3px 3px;
}
</style>
$js_first
<div id="orbisius_simple_notice_container" class="orbisius_simple_notice_container">
    <div id="orbisius_simple_notice" class="orbisius_simple_notice">
        <span class='orbisius_simple_notice_powered_by_container'>
            $powered_by_line
        </span>
        $notice
    </div> <!-- /orbisius_simple_notice -->
</div> <!-- /orbisius_simple_notice_container -->
<!-- /orbisius_simple_notice -->
FORM_EOF;

//show_notice_method

    echo $form_buff;
}

/**
 * Set up administration
 *
 * @package Orbisius Simple Notice
 * @since 0.1
 */
function orbisius_simple_notice_setup_admin() {
    add_options_page('Orbisius Simple Notice', 'Orbisius Simple Notice', 'manage_options', __FILE__, 'orbisius_simple_notice_options_page');

    add_filter('plugin_action_links', 'orbisius_simple_notice_add_quick_settings_link', 10, 2);
}

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
        'show_powered_by' => 1,
        'show_in_admin' => 0,
        'show_notice_method' => 'on_top',
        'text_color' => '#555',
        'text_bg_color' => '#B4CFD3',
        'link_color' => '',
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
    <div id="orbisius_simple_notice_admin_wrapper" class="wrap orbisius_simple_notice_admin_wrapper">
        <h2>Orbisius Simple Notice</h2>
        <p>
            This plugin allows you to show a simple notice to alert your users about server maintenance, new product launches etc.
        </p>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">

                <!-- main content -->
                <div id="post-body-content">

                    <div class="meta-box-sortables ui-sortable">

                        <div class="postbox">
                            <h3><span>Settings</span></h3>
                            <div class="inside">
                                <form method="post" action="options.php">
                                    <?php settings_fields('orbisius_simple_notice_settings'); ?>
                                    <table class="form-table">
                                        <tr valign="top">
                                            <th scope="row">Show Notice</th>
                                            <td>
                                                <label for="orbisius_simple_notice_options_radio1">
                                                    <input type="radio" id="orbisius_simple_notice_options_radio1" name="orbisius_simple_notice_options[status]"
                                                           value="1" <?php echo empty($opts['status']) ? '' : 'checked="checked"'; ?> /> Enabled
                                                </label>
                                                <br/>
                                                <label for="orbisius_simple_notice_options_radio2">
                                                    <input type="radio" id="orbisius_simple_notice_options_radio2" name="orbisius_simple_notice_options[status]"
                                                           value="0" <?php echo!empty($opts['status']) ? '' : 'checked="checked"'; ?> /> Disabled
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Notice</th>
                                            <td>
                                                <?php if (1) : ?>
                                                    <?php
                                                    wp_editor($opts['notice'], "orbisius_simple_notice_options[notice]", array('teeny' => true, 'media_buttons' => false, 'textarea_rows' => 2));
                                                    ?>
                                                <?php else : // simple editor ?>
                                                    <input type="text" id="orbisius_simple_notice_options_notice" class="widefat"
                                                           name="orbisius_simple_notice_options[notice]"
                                                           value="<?php echo esc_attr($opts['notice']); ?>" />
                                                       <?php endif; ?>
                                                <p>
                                                    Example: We are going to be doing server maintenance at 9pm today.
                                                    <br/>Example: We have just launched a new product ...
                                                </p>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">How to Show the Notice Text</th>
                                            <td>
                                                <label for="show_notice_method_radio1">
                                                    <input type="radio" id="show_notice_method_radio1" name="orbisius_simple_notice_options[show_notice_method]"
                                                           value='on_top' <?php checked('on_top', $opts['show_notice_method']); ?> /> On top of existing content
                                                    <br/><small>It will be added on top of all content (with higher z-index value)</small>
                                                </label>
                                                <br/>
                                                <label for="show_notice_method_radio2">
                                                    <input type="radio" id="show_notice_method_radio2" name="orbisius_simple_notice_options[show_notice_method]"
                                                           value='push_down' <?php checked('push_down', $opts['show_notice_method']); ?> /> Push down existing content
                                                    <br/><small>It will be added as first element in the &lt;body&gt; tag</small>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Text Color</th>
                                            <td>
                                                <label for="orbisius_simple_notice_options_text_color">
                                                    <input type="text" id="orbisius_simple_notice_options_text_color" size="7"
                                                           name="orbisius_simple_notice_options[text_color]"
                                                           value="<?php echo esc_attr($opts['text_color']); ?>" />

                                                    <div id="text_color_picker"></div> <!-- Used for old WP color picker WP < 3.5 -->
                                                </label>
                                                <p>Once you open the color picker, you will need to click outside of it to close it</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Text Background Color</th>
                                            <td>
                                                <label for="orbisius_simple_notice_options_text_bg_color">
                                                    <input type="text" id="orbisius_simple_notice_options_text_bg_color" size="7"
                                                           name="orbisius_simple_notice_options[text_bg_color]"
                                                           value="<?php echo esc_attr($opts['text_bg_color']); ?>" />
                                                    <div id="text_bg_color_picker"></div>
                                                </label>
                                                <p>Once you open the color picker, you will need to click outside of it to close it</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Link Color (optional)</th>
                                            <td>
                                                <label for="orbisius_simple_notice_options_link_color">
                                                    <input type="text" id="orbisius_simple_notice_options_link_color" size="7"
                                                           name="orbisius_simple_notice_options[link_color]"
                                                           value="<?php echo esc_attr($opts['link_color']); ?>" />
                                                    <div id="link_color_picker"></div>
                                                </label>
                                                <p>Use this if links don't look good on a selected background.
                                                    <br/>Once you open the color picker, you will need to click outside of it to close it</p>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">Show Little Powered By Icon</th>
                                            <td>
                                                <div>
                                                    This is the small <strong>i</strong> icon displayed in the far left of the message.
                                                    We'd appreciate if you leave it enabled.
                                                </div>
                                                <label for="show_powered_by_radio1">
                                                    <input type="radio" id="show_powered_by_radio1" name="orbisius_simple_notice_options[show_powered_by]"
                                                           value='1' <?php checked(1, $opts['show_powered_by']); ?> /> Enabled
                                                </label>
                                                <br/>
                                                <label for="show_powered_by_radio2">
                                                    <input type="radio" id="show_powered_by_radio2" name="orbisius_simple_notice_options[show_powered_by]"
                                                           value='0' <?php checked(0, $opts['show_powered_by']); ?> /> Disabled
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Preview </th>
                                            <td>
                                                <?php echo orbisius_simple_notice_inject_notice(0); ?>
                                                <div>Save changes to see a new preview</div>
                                            </td>
                                        </tr>
                                        <?php if (0) : ?>
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
                                        <?php endif; ?>
                                    </table>

                                    <p class="submit">
                                        <input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
                                    </p>
                                </form>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                    </div> <!-- .meta-box-sortables .ui-sortable -->

                </div> <!-- post-body-content -->

                <!-- sidebar -->
                <div id="postbox-container-1" class="postbox-container">

                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <h3><span>Free Quote | Hire Us</span></h3>
                            <div class="inside">
                                Do you need any Programming (web/mobile app) or WordPress work (custom themes/plugins, speed/security improvements).

                                <br/><a href='http://orbisius.com/page/free-quote/?utm_source=orbisius-simple-notice&utm_medium=plugin-settings-about&utm_campaign=plugin-update'
                                        target="_blank" class="button-primary">Get a Free Quote</a>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                        <div class="postbox">
                            <h3><span>Newsletter</span></h3>
                            <div class="inside">
                                <?php echo orbisius_simple_notice_generate_newsletter_box(); ?>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                        <div class="postbox">
                            <h3><span>Donation</span></h3>
                            <div class="inside">
                                Donations help us dedicate more resources to improve this and create new plugins.

                                <br/>
                                <!--<a href='http://orbisius.com/page/donate/?utm_source=orbisius-simple-notice&utm_medium=plugin-settings-about&utm_campaign=plugin-update'
                                   target="_blank" class="button-primary">Donate</a>-->

                                <a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7APYDVPBCSY9A'
                                   target="_blank" class="button-primary">Donate</a>

                                | <a href='http://orbisius.com/page/why-donate/?utm_source=orbisius-simple-notice&utm_medium=plugin-settings-about&utm_campaign=plugin-update'
                                     target="_blank">Why Donate</a>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                        <div class="postbox">
                            <h3><span>Want to see more products of ours? </span></h3>
                            <div class="inside">
                                <p>
                                    Go to <a href="http://club.orbisius.com/products/" target="_blank"
                                             title="Opens a page with the pugins we developed. [New Window/Tab]">http://club.orbisius.com/products/</a>.
                                </p>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                        <div class="postbox">
                            <h3><span>Support & Feature Requests</span></h3>
                            <div class="inside">
                                Support is handled on our site: <a href="http://club.orbisius.com/support/?utm_source=orbisius-simple-notice&utm_medium=settings-top-bar&utm_campaign=plugin-update" target="_blank" title="[new window]">http://club.orbisius.com/support/</a>.
                                Please do NOT use the WordPress forums or other places to seek support.
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                        <div class="postbox">
                            <h3><span>About the Author</span></h3>
                            <div class="inside">
                                <p>
                                    <a href="http://slavi.biz/linkedin" target="_blank" title="Click to see Slavi's linkedin profile. Opens in a new tab/window">
                                        <img width="64" height="64" class="avatar avatar-64 photo" alt="" style="float: left;padding-right: 3px;"
                                             src="http://1.gravatar.com/avatar/fd5bd959efce7d8c5c40518276bb3998?s=64&amp;d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D64&amp;r=G" /></a>

                                    This plugin was created by Svetoslav Marinov (Slavi),
                                    Founder of <a href="http://orbisius.com/?utm_source=orbisius-simple-notice&utm_medium=plugin-settings-about&utm_campaign=plugin-update" target="_blank">orbisius.com</a>.
                                    Connect with him on <a href="http://slavi.biz/linkedin" target="_blank"
                                                           title="Click to see Slavi's linkedin profile. Opens in a new tab/window">Linkedin</a>
                                    and
                                    <a href="http://slavi.biz/twitter" target="_blank"
                                       title="Click to see Slavi's twitter profile. Opens in a new tab/window">Twitter</a>.
                                </p>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                    </div> <!-- .meta-box-sortables -->

                </div> <!-- #postbox-container-1 .postbox-container -->

            </div> <!-- #post-body .metabox-holder .columns-2 -->

            <br class="clear">
        </div> <!-- #poststuff -->

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

        <!-- share -->
        <?php
        $plugin_data = orbisius_simple_notice_get_plugin_data();

        $app_link = urlencode($plugin_data['url']);
        $app_title = urlencode($plugin_data['name']);
        $app_descr = urlencode($plugin_data['description']);
        ?>

        <h2>Share</h2>
        <p>
            <!-- AddThis Button BEGIN -->
        <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
            <a class="addthis_button_facebook" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_twitter" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_google_plusone" g:plusone:count="false" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_linkedin" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_email" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_myspace" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_google" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_digg" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_delicious" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_stumbleupon" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_tumblr" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_favorites" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
            <a class="addthis_button_compact"></a>
        </div>
        <!-- The JS code is in the footer -->

        <script type="text/javascript">
            var addthis_config = {"data_track_clickback": true};
            var addthis_share = {
                templates: {twitter: 'Check out {{title}} @ {{lurl}} (from @orbisius)'}
            }
        </script>
        <!-- AddThis Button START part2 -->
        <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=lordspace"></script>
        <!-- AddThis Button END part2 -->
    </p>
    <!-- /share -->

    <?php
    $plugin_slug = basename(__FILE__);
    $plugin_slug = str_replace('.php', '', $plugin_slug);
    ?>
    <iframe style="width:100%;min-height:300px;height: auto;" width="640" height="480"
            src="http://club.orbisius.com/wpu/content/wp/<?php echo $plugin_slug; ?>/" frameborder="0" allowfullscreen></iframe>

    </div>
    <?php
}

function orbisius_simple_notice_generate_newsletter_box() {
    $current_user = wp_get_current_user();
    $email = empty($current_user->user_email) ? '' : $current_user->user_email;

    $plugin_data = orbisius_simple_notice_get_plugin_data();
    $plugin_name = $plugin_data['name'];
    ?>

    <table>
        <tr>
            <td valign="top">
                <div id='app-plugin-notice' class='app_mailing_list_box' width="100%">
                    <!-- Begin MailChimp Signup Form -->
                    <div id="mc_embed_signup">
                        <form action="http://orbisius.us2.list-manage.com/subscribe/post?u=005070a78d0e52a7b567e96df&amp;id=1b83cd2093" method="post"
                              id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">
                            <input type="hidden" value="<?php echo esc_attr($plugin_name); ?>" name="SRC" id="mce-SRC" />
                            <input type="hidden" value="<?php echo esc_attr($plugin_name); ?>" name="MERGE3" id="mce-MERGE3" />

                            <p>Join our newsletter and we will be notify you when we release cool plugins.</p>
                            <div class="mc-field-group">
                                <label for="mce-EMAIL">Email <span class="app_asterisk">*</span>
                                </label>
                                <input type="email" value="<?php echo esc_attr($email); ?>" name="EMAIL" class="required email" id="mce-EMAIL" />
                            </div>
                            <div id="mce-responses" class="clear">
                                <div class="response" id="mce-error-response" style="display:none"></div>
                                <div class="response" id="mce-success-response" style="display:none"></div>
                            </div>
                            <div class="clear"><input type="submit" value="Join" name="subscribe" id="mc-embedded-subscribe" class="button button-primary"></div>
                        </form>
                    </div>
                    <!--End mc_embed_signup-->
                </div>
            </td>
            <!--<td valign="top">
                <p>
                    You can also signup using this link: <a href="http://eepurl.com/guNzr" target="_blank">http://eepurl.com/guNzr</a> <br/>
                    You can also signup using this QR code: <br/>
                    <img src="%%PLUGIN_URL%%/zzz_media/qr_code.png" alt="" width="100"/>
                </p>
            </td>-->
        </tr>
    </table>

    <?php
}

function orbisius_simple_notice_get_plugin_data() {
    // pull only these vars
    $default_headers = array(
        'Name' => 'Plugin Name',
        'PluginURI' => 'Plugin URI',
        'Description' => 'Description',
    );

    $plugin_data = get_file_data(__FILE__, $default_headers, 'plugin');

    $data['name'] = $plugin_data['Name'];
    $data['url'] = $plugin_data['PluginURI'];
    $data['description'] = $plugin_data['Description'];

    return $data;
}

/**
 * adds some HTML comments in the page so people would know that this plugin powers their site.
 */
function orbisius_simple_notice_add_plugin_credits() {
    $plugin_data = orbisius_simple_notice_get_plugin_data();

    $url = $plugin_data['url'];
    $name = $plugin_data['name'];

    printf(PHP_EOL . PHP_EOL . '<!-- ' . "Powered by $name | URL: $url " . '-->' . PHP_EOL . PHP_EOL);
}

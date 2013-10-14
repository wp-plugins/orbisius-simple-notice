=== Orbisius Simple Notice ===

Contributors: lordspace,orbisius
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7APYDVPBCSY9A
Tags: wp,orbisius,notice,alert,hellobar,hello bar,beforesite, heads up, heads up bar, headsup, headsupbar, notification, notification bar, popup, Toolbar
Requires at least: 2.6
Tested up to: 3.6.1
Stable tag: 1.0.1
License: GPLv2 or later

This plugin allows you to show a simple notice to alert your users about server maintenance, new product launches etc.

== Description ==

= Support =
> Support is handled on our site: <a href="http://club.orbisius.com/" target="_blank" title="[new window]">http://club.orbisius.com/</a>
> Please do NOT use the WordPress forums or other places to seek support.

This plugin allows you to show a simple notice to alert your users about server maintenance, new product launches etc.

= Features / Benefits =
* Enter text an the message will be shown to your users.
* For logged in users the notice will be shifted by 28px (because WP admin bar is obstructing the notice)
* Rich text editor to enter notice text
* Use nice color pickers to select the colors for notice text, background and link color (if any).
* Supports text and HTML

== Demo ==
TODO

Bugs? Suggestions? If you want a faster response contact us through our website's contact form [ orbisius.com ] and not through the support tab of this plugin or WordPress forums.
We don't get notified when such requests get posted in the forums.

> Support is handled on our site: <a href="http://club.orbisius.com/" target="_blank" title="[new window]">http://club.orbisius.com/</a>
> Please do NOT use the WordPress forums or other places to seek support.

= Author =

Svetoslav Marinov (Slavi) | <a href="http://orbisius.com" title="Custom Web Programming, Web Design, e-commerce, e-store, Wordpress Plugin Development, Facebook and Mobile App Development in Niagara Falls, St. Catharines, Ontario, Canada" target="_blank">Custom Web and Mobile Programming by Orbisius.com</a>

== Upgrade Notice ==
n/a

== Screenshots ==
1. The Notice shown by the plugin
2. Plugin's Settings Page

== Installation ==

1. Unzip the package, and upload `orbisius-simple-notice` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to use this plugin? =
Just install the plugin and activate it. The feedback text appear in the public area

= How to remove the powered by? =

If you don't want to give us credit :( add this line to your functions.php
add_filter('orbisius_simple_notice_filter_powered_by', '__return_false', 10);

== Changelog ==

= 1.0.2 =
* Added the old color picker for WordPress installs older than 3.5

= 1.0.1 =
* Added a color picker for text, text background color and for links.
* Added preview in the settings page.
* Added uninstall.php to cleanup settings.

= 1.0.0 =
* Initial release

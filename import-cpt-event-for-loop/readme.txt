=== Loop Events CPT ===
Contributors: pankajkh
Donate link: https://example.com/
Tags: custom post type, import, wp-cli
Requires at least: 5.8.2
Tested up to: 5.8.2
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin will create a custom post type to store events for Loop

== Description ==

Plugin provides below functinality
1. Import if event is not exist else update same event
1. To import event need to fire 'wp loop event import /path/to/data.json'


== Installation ==
1. Need to setup the WP-CLI in your enviroment
1. First install and activate Loop Events CPT Plugin
1. Upload the plugin file to \wp-content\plugins\import-cpt-event-for-loop\import-cpt-event-for-loop.php
1. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 0.1.0 =
* Initial realease which provide wp-cli command to import event to CPT.
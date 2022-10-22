# Loop Events CPT
### This plugin will create a custom post type to store Loop events.

**Description**

*Plugin provides 3 functinality*
1. Create custom post type ***loop_events*** and taxonomy ***event-tag***
1. List all the upcoming events
1. API endpoint to get all upcoming events

**Installation**
1. First install and activate Advanced Custom Fields(ACF) Plugin.
1. Import acf-export-2022-10-21.json file present inside plugin folder into the ACF field group.
1. Upload the plugin file to `\wp-content\plugins\custom-event-for-loop\custom-event-for-loop.php`
1. Activate the plugin through the 'Plugins' menu in WordPress



# WP CLI Import Events
### This plugin will import events to LOOP events CPT if event not exits else update the exiting event.

**Description**

*Plugin provides below functinality*
1. Import if event is not exist else update same event
1. To import event need to fire `wp loop event import /path/to/data.json`


**Installation**
1. Need to setup the WP-CLI in your enviroment
1. First install and activate **Loop Events CPT** Plugin
1. Upload the plugin file to `\wp-content\plugins\import-cpt-event-for-loop\import-cpt-event-for-loop.php`
1. Activate the plugin through the 'Plugins' menu in WordPress

## Import
1. To import event need to fire `wp loop event import /path/to/data.json`
![ImportOutput](https://github.com/pankajkh-07/Wordpress-Plugins/blob/main/Output/Import-output.png)

## Listing
1. Create WordPress page
1. Add shortcode to the page `[loop_event_listing]`
![Listing](https://github.com/pankajkh-07/Wordpress-Plugins/blob/main/Output/Listing.png)

## Export
1. To export the upcoming events in JSON format access below endpoint
1. `https://www.yourdomain.com/wp-json/loop_events_cpt/v1/latest-events/`
![Export](https://github.com/pankajkh-07/Wordpress-Plugins/blob/main/Output/export-output.png)

## Admin Screens
![ACF](https://github.com/pankajkh-07/Wordpress-Plugins/blob/main/Output/ACF_Field_Group.png)
![EventEdit](https://github.com/pankajkh-07/Wordpress-Plugins/blob/main/Output/admin-event-edit-screen.png)
![EventListAddmin](https://github.com/pankajkh-07/Wordpress-Plugins/blob/main/Output/admin-loop-event-list.png)

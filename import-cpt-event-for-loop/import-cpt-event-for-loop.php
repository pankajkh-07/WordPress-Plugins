<?php
/**
 * Plugin Name:     WP CLI Import Events
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     This plugin will import events to LOOP events CPT if event not exits else update the exiting event.
 * Author:          Pankaj Khedekar
 * Author URI:      YOUR SITE HERE
 * Text Domain:     import-cpt-event-for-loop
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Import_Cpt_Event_For_Loop
 */

// Your code starts here.

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ){
	exit;
}

if ( ! defined( 'WP_CLI' ) ){
	return;
}

class Loop_Event_CLI_Command extends \WP_CLI_Command {

    /*
     * Import event records into   
     */
	public function import( $args, $assoc_args ) {

		// Get first associative argument from command as file path
		$file = array_shift( $args );

        //Check is file readable or exit
		if ( ! is_readable( $file ) ) {
			WP_CLI::error( "Can't read '$file' file." );
		}

		$updated = $errors = $imported = 0;

        //Read JSON file and decode it before insert
        $data =  wp_json_file_decode($file);
        
        //Check file has data before insert data to CPT
        if (!$data || empty($data)) {           
            WP_CLI::warning( "Empty file given, please provide file with data.");
            return; 
        }        
        
        foreach($data as $row){
            $return = $this->create_loop_event_post('loop_events', $row);
            if( is_wp_error( $return ) ) {
                WP_CLI::warning( $return->get_error_message());
                $errors++;
            } else if($return == 'Inserted') {
                $imported++;    
                    
            } else if($return == 'Updated'){ 
                $updated++;
            }    
            //WP_CLI::success( "Event title: ". get_current_user_id() );
        }
		
    	if ( $errors > 0 ) {
    		WP_CLI::warning( "Errors while creating or updating {$errors} events." );
    	} 
        if ( $imported > 0) {
    		WP_CLI::success( "{$imported} events created successfully!!" );
    	} 
        if ( $updated > 0) {
    		WP_CLI::success( "{$updated} events updated successfully!!" );
    	}

        if($errors > 0 || $imported > 0 ||$updated > 0){
            $email = "Khedekarpa@gmail.com";
            $to = 'logging@agentur-loop.com';
            $subject = 'Event import statistics';
            $body  = "<p>Errors while creating or updating {$errors} events.</p><br/>";
            $body .= "<p>{$imported} events created successfully!!</p><br/>";
            $body .= "<p>{$updated} events updated successfully!!</p><br/>";
            $headers = 'From: '. $email . "\r\n".
                        'Reply-To: '. $email . "\r\n";

            $mail = mail( $to, $subject, $body, $headers );
            
            if($mail){
                WP_CLI::success("Email sent");
            } else {
                WP_CLI::warning("Email not sent");
            }
        }
	}

    /* 
     * Creates Events one at a time
     */
    public static function create_loop_event_post($post_type, $data)
    {        
        //[ 'id', 'title', 'organizer', 'timestamp','email', 'address', 'latitude', 'longitude', 'tags']
        $title = sanitize_text_field(wp_strip_all_tags($data->title)); 
        $title = esc_html(wp_unslash($title));
        $slug = sanitize_title_with_dashes($title); // converts to a usable post_name
        $post_type = post_type_exists($post_type) ? $post_type : ''; // make sure it exists

        if(!$post_type){
            WP_CLI::warning("Please activate the Loop Events CPT plugin OR check post type slug");
            die;
        }

        $post_data = array(
            'post_name' => $slug,
            'post_title' => $title,
            'post_content' => $data->about,
            'post_type' => $post_type,
            'post_author' => 1,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_status' => 'publish',
        );
        
        $existing_event = get_page_by_title($title,OBJECT,$post_type);

        // If the page doesn't already exist, then create it (by title & slug)
        if (is_null($existing_event) && empty(get_posts(array('name' => $slug,'post_type'=>$post_type)))) {           
            $post_id = wp_insert_post($post_data);

            if($post_id == 0 || is_wp_error($post_id)){
                return new WP_Error("insert_error","Event creation failure for:". $title);
            }

            $rec_status = "Inserted";                       
        } else {
            $post_data = ['ID' => $existing_event->ID, 'post_content' => $data->about];            
            $post_id = wp_update_post( $post_data );

            if($post_id == 0 || is_wp_error($post_id)){
                return new WP_Error("update_error","Event updation failure for:". $title);
            }
            $rec_status = "Updated";
        } 

        if ($post_id && $post_id > 0) {
            $google_map_field = [
                'address' => $data->address,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
            ];
            update_field( 'event_organizer', $data->organizer, $post_id );
            update_field( 'event_date_and_time', $data->timestamp, $post_id );
            update_field( 'organizer_email', $data->email, $post_id );
            update_field( 'event_location_coordinates', $google_map_field, $post_id );
            
            wp_set_object_terms( $post_id, $data->tags,'event-tag');
        }

        return $rec_status;
    } 
}

WP_CLI::add_command( 'loop event', 'Loop_Event_CLI_Command' );

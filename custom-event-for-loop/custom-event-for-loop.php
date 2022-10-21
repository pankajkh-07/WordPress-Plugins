<?php
/**
 * Plugin Name:     Loop Events CPT
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     This plugin will create a custom post type to store events for Loop
 * Author:          Pankaj Khedekar
 * Author URI:      YOUR SITE HERE
 * Text Domain:     custom-event-for-loop
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Custom_Event_For_Loop
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ){
	exit;
}

// Your code starts here.
if (!class_exists('Loop_Events_CPT')) :
    class Loop_Events_CPT
    {
        /**
         * The unique instance of the plugin.
         *
         * @var Loop_Events_CPT
         */
        private static $instance;

        /**
         * Gets an instance of our plugin.
         *
         * @return Loop_Events_CPT
         */
        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Constructor.
         */
        private function __construct()
        {                  
            register_activation_hook( __FILE__, array($this,'lecpt_acf_activate') );  
            add_action( 'init', array($this,'lecpt_create_posttype') );
            add_shortcode( 'loop_event_listing', array($this,'lecpt_event_listing') );            
            add_action( 'wp_enqueue_scripts', array($this,'lecpt_loop_event_scripts'));
            add_action( 'rest_api_init', function(){
                 register_rest_route( 'loop_events_cpt/v1', 'latest-events/', array(
                'methods' => 'GET',
                'callback' => array($this,'lecpt_get_event_list'),
            ) );
            } );
        }

        /* 
        * Check Advance custom field plugin active before activating this plugin
        */
        function lecpt_acf_activate() {
            if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
              include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }
            if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'ACF' ) ) {
              // Deactivate the plugin.
              deactivate_plugins( plugin_basename( __FILE__ ) );
              // Throw an error in the WordPress admin console.
              $error_message = '<p style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif;font-size: 13px;line-height: 1.5;color:#444;">' . esc_html__( 'This plugin requires ', 'advanced-custom-fields' ) . '<a href="' . esc_url( 'https://wordpress.org/plugins/advanced-custom-fields/' ) . '">Advanced Custom Fields</a>' . esc_html__( ' plugin to be active.', 'advanced-custom-fields' ) . '</p>';
              die( $error_message ); // WPCS: XSS ok.
            }
        }

        /* 
        * Enqueue the Boostrap need  for frontend
        */
        function lecpt_loop_event_scripts() {
            global $post;            
            if (has_shortcode( $post->post_content, 'loop_event_listing') ) {
                wp_enqueue_style( 'bootstrapcss','https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css', 'jQuery', null );
                wp_enqueue_script( 'bootstapjs', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', 'jQuery', null );
            }
        }

        /* 
        * Create events custom post type function
        */
        function lecpt_create_posttype() {
        
             //register taxonomy for custom post tags
            register_taxonomy( 
                'event-tag', //taxonomy 
                'loop_events', //post-type
                array( 
                    'hierarchical'  => false, 
                    'label'         => __( 'Event Tags','taxonomy general name'), 
                    'singular_name' => __( 'Event Tag', 'taxonomy general name' ), 
                    'rewrite'       => true, 
                    'query_var'     => true 
                )
            );

            register_post_type( 'loop_events',
            // CPT Options
                array(
                    'labels' => array(
                        'name' => __( 'Loop Events' ),
                        'singular_name' => __( 'Loop Event' )
                    ),
                    'public' => true,
                    'has_archive' => true,
                    'hierarchical' => false,
                    'rewrite' => array('slug' => 'loop_events'),
                    'show_in_rest' => true,
                    'supports' => array( 'title', 'editor', 'custom-fields' ),    
                )
            );
        } 


        /* 
        * This function renders the list of events on frontend
        */
        function lecpt_event_listing( $atts ) {
            ob_start();
            $query = new WP_Query(array(
                'post_type' => 'loop_events',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_key'   => 'event_date_and_time',
                'orderby'    => 'meta_value',
                'order'      => 'ASC',
                'meta_query'    => array(                    
                    array(
                        'key'       => 'event_date_and_time',
                        'value'     => date('Y-m-d H:i:s', time()),
                        'compare'   => '>='
                    )
                ),               
            ));
            
            $output = '<div class="row row-cols-1 row-cols-md-3 g-4">';
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $datetime1 = new DateTime(get_field( "event_date_and_time", $post_id ));
                $datetime2 = new DateTime(date('Y-m-d H:i:s', time()));
                $interval = $datetime1->diff($datetime2);
                

                $output .= '
                <div class="col">
                  <div class="card h-100">                    
                    <div class="card-body">
                      <h5 class="card-title text-capitalize">'.get_the_title().'</h5>
                      <p class="card-text">'.$this->lecpt_truncate(get_the_content()).'</p>
                    </div>
                    <div class="card-footer">
                      <h5> Event Start in: </h5>
                      <small class="text-muted">'. $this->lecpt_get_time_diff($interval).'</small>
                    </div>
                  </div>
                </div>';                
            }
            echo $output .= '</div>';
            wp_reset_query();   
            return ob_get_clean();         
        }

        /* 
        * Calculates the diffence between todays date and future event date
        */
        function lecpt_get_time_diff($interval){
            switch ($interval) {
                case $interval->y > 0:
                    $diff_string = $interval->format('%Y Years %m Months %d Days %H Hours %i Minutes %s Seconds');
                    break;
                case $interval->m > 0:
                    $diff_string = $interval->format('%m Months %d Days %H Hours %i Minutes %s Seconds');
                    break;
                case $interval->d > 0:
                    $diff_string = $interval->format('%d Days %H Hours %i Minutes %s Seconds');
                    break; 
                case $interval->h > 0:
                    $diff_string = $interval->format('%H hours %i Minutes %s Seconds');
                    break;
                case $interval->i > 0:
                    $diff_string = $interval->format('%i Minutes %s Seconds');
                    break;
                case $interval->s > 0:
                    $diff_string = $interval->format('%s Seconds');
                    break;
                default:                    
                    break;
            }
            return $diff_string;
        }

        /* 
        * Truncate the event description for frontend
        */
        function lecpt_truncate($string,$length=100,$append="&hellip;") {
            $string = trim($string);
          
            if(strlen($string) > $length) {
              $string = wordwrap($string, $length);
              $string = explode("\n", $string, 2);
              $string = $string[0] . $append;
            }
          
            return $string;
        }

        /* 
        * Callback function for API endpoint
        */
        function lecpt_get_event_list($request){
            $query = new WP_Query(array(
                'post_type' => 'loop_events',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_key'   => 'event_date_and_time',
                'orderby'    => 'meta_value',
                'order'      => 'ASC',
                'meta_query'    => array(                    
                    array(
                        'key'       => 'event_date_and_time',
                        'value'     => date('Y-m-d H:i:s', time()),
                        'compare'   => '>='
                    )
                ),               
            ));
                    

            $posts = $query->posts;
            if (empty($posts)) {
            return new WP_Error( 'empty_events', 'there is no upcoming events', array('status' => 404) );
        
            }
        
            $response = new WP_REST_Response($posts);
            $response->set_status(200);
        
            return $response;    
        }

    }

    $loop_events_cpt = Loop_Events_CPT::get_instance();

endif;

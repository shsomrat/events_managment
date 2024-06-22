<?php

namespace Shsom\EventsManagment\Frontend;

class Frontend_View {
    public function __construct() {
        // Hook into WordPress actions
        add_action('wp_enqueue_scripts', [$this, 'ep_enqueue_frontend_assets']);
        add_shortcode('ep_fullcalendar', [$this, 'ep_fullcalendar_shortcode']);
    }

    // Fetch events from the database
    private function ep_get_events() {
        $events = array();
        $query = new \WP_Query(array(
            'post_type' => 'events',     // Custom post type
            'posts_per_page' => -1       // Retrieve all events
        ));
        while ($query->have_posts()) {
            $query->the_post();
            $start_date = get_post_meta(get_the_ID(), 'ep_event_start_date', true);
            $end_date = get_post_meta(get_the_ID(), 'ep_event_end_date', true);
            $event = array(
                'title' => get_the_title(),         // Event title
                'start' => $start_date,             // Event start date
                'description' => get_the_content()  // Event description
            );
            if ($end_date) {
                $event['end'] = $end_date;         // Event end date (optional)
            }
            $events[] = $event;
        }
        \wp_reset_postdata(); // Reset the global post object
        return $events;
    }

    // Enqueue styles and scripts for frontend
    public function ep_enqueue_frontend_assets() {
        // Enqueue custom styles and scripts
        wp_enqueue_style('ep-styles', plugin_dir_url(__FILE__) . 'assets/styles.css');
        wp_enqueue_script('ep-scripts', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), null, true);
        wp_enqueue_script('index-global-js', plugin_dir_url(__FILE__) . 'assets/index.global.min.js', array('jquery'), null, true);

        // Fetch events and localize the script with event data
        $events = $this->ep_get_events();
        wp_localize_script('ep-scripts', 'epEventsData', array(
            'events' => $events
        ));
    }

    // Shortcode to display the calendar in the frontend
    public function ep_fullcalendar_shortcode() {
        return '<div id="calendar-container"><div id="calendar"></div></div>';
    }
}
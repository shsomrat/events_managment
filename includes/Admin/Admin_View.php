<?php

namespace Shsom\EventsManagment\Admin;

class Admin_View {

    public function __construct() {
        // Hook into WordPress actions
        add_action('admin_menu', [$this, 'ep_register_event_calendar_page']);
        add_action('admin_enqueue_scripts', [$this, 'ep_enqueue_admin_assets']);
    }

    // Register custom admin page for event calendar
    public function ep_register_event_calendar_page() {
        add_menu_page(
            'Event Calendar',             // Page title
            'Event Calendar',             // Menu title
            'manage_options',             // Capability required to access this menu
            'event-calendar',             // Menu slug
            [$this, 'ep_event_calendar_page_callback'], // Callback function to display the page content
            'dashicons-calendar-alt',     // Menu icon
            26                            // Position in the menu
        );
    }

    // Callback function for the event calendar page content
    public function ep_event_calendar_page_callback() {
        echo '
        <div class="wrap" id="calendar-container">
            <div id="calendar"></div>
        </div>';
    }

    // Enqueue styles and scripts for admin
    public function ep_enqueue_admin_assets($hook) {
        // Check if the current admin page is the event calendar page
        if ($hook !== 'toplevel_page_event-calendar') {
            return;
        }

        // Enqueue admin-specific styles and scripts
        wp_enqueue_style('ep-admin-styles', plugin_dir_url(__FILE__) . 'assets/admin-styles.css');
        wp_enqueue_script('ep-admin-scripts', plugin_dir_url(__FILE__) . 'assets/admin-script.js', array('jquery'), null, true);
        wp_enqueue_script('index-global-js-admin', plugin_dir_url(__FILE__) . 'assets/index.global.min.js', array('jquery'), null, true);

        // Fetch events and localize the script with event data
        $events = $this->ep_get_events();
        wp_localize_script('ep-admin-scripts', 'epAdminEventsData', array(
            'events' => $events
        ));
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
}
<?php
/*
 * Plugin Name:       SHS's Events Management Plugin
 * Plugin URI:        https://example.com/plugins/shs-events-management/
 * Description:       Create and show events.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            S H Somrat
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/shs-events-management/
 * Text Domain:       em
 * Domain Path:       /languages
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class Events_Management {
    public function __construct() {
        // Hook into WordPress actions
        add_action('init', [$this, 'ep_register_event_post_type']);
        add_action('add_meta_boxes', [$this, 'ep_add_event_meta_boxes']);
        add_action('save_post', [$this, 'ep_save_event_meta']);
        add_filter('manage_events_posts_columns', [$this, 'ep_add_event_columns']);
        add_action('manage_events_posts_custom_column', [$this, 'ep_event_custom_column'], 10, 2);
        add_filter('manage_edit-events_sortable_columns', [$this, 'ep_make_event_date_columns_sortable']);
        add_action('pre_get_posts', [$this, 'ep_sort_event_date_columns']);

        // Initialize admin and frontend views
        new Shsom\EventsManagment\Admin\Admin_View();
        new Shsom\EventsManagment\Frontend\Frontend_View();
    }

    // Register custom post type
    public function ep_register_event_post_type() {
        $labels = array(
            'name'               => 'Events',
            'singular_name'      => 'Event',
            'menu_name'          => 'Events',
            'name_admin_bar'     => 'Event',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Event',
            'new_item'           => 'New Event',
            'edit_item'          => 'Edit Event',
            'view_item'          => 'View Event',
            'all_items'          => 'All Events',
            'search_items'       => 'Search Events',
            'parent_item_colon'  => 'Parent Events:',
            'not_found'          => 'No events found.',
            'not_found_in_trash' => 'No events found in Trash.'
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'events'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-calendar-alt',
            'supports'           => array('title')
        );

        register_post_type('events', $args);
    }

    // Add meta boxes
    public function ep_add_event_meta_boxes() {
        add_meta_box(
            'ep_event_date_meta_box',
            'Event Dates',
            [$this, 'ep_event_date_meta_box_callback'],
            'events',
            'advanced',
            'default'
        );
    }

    // Callback for the event date meta box
    public function ep_event_date_meta_box_callback($post) {
        // Add nonce for security
        wp_nonce_field(basename(__FILE__), 'ep_event_nonce');
        $ep_stored_meta = get_post_meta($post->ID);
        ?>
        <label for="ep-event-start-date">Event Start Date</label>
        <input type="date" name="ep-event-start-date" id="ep-event-start-date" value="<?php if (isset($ep_stored_meta['ep_event_start_date'])) echo esc_attr($ep_stored_meta['ep_event_start_date'][0]); ?>" required>
        <br><br>
        <label for="ep-event-end-date">Event End Date (optional)</label>
        <input type="date" name="ep-event-end-date" id="ep-event-end-date" value="<?php if (isset($ep_stored_meta['ep_event_end_date'])) echo esc_attr($ep_stored_meta['ep_event_end_date'][0]); ?>">
        <?php
    }

    // Save meta box data
    public function ep_save_event_meta($post_id) {
        // Check save status
        $is_autosave = wp_is_post_autosave($post_id);
        $is_revision = wp_is_post_revision($post_id);
        $is_valid_nonce = (isset($_POST['ep_event_nonce']) && wp_verify_nonce($_POST['ep_event_nonce'], basename(__FILE__))) ? 'true' : 'false';

        // Exit script depending on save status
        if ($is_autosave || $is_revision || !$is_valid_nonce) {
            return;
        }

        // Check for input and sanitize/save
        if (isset($_POST['ep-event-start-date'])) {
            update_post_meta($post_id, 'ep_event_start_date', sanitize_text_field($_POST['ep-event-start-date']));
        }

        if (isset($_POST['ep-event-end-date'])) {
            update_post_meta($post_id, 'ep_event_end_date', sanitize_text_field($_POST['ep-event-end-date']));
        } else {
            delete_post_meta($post_id, 'ep_event_end_date');
        }
    }

    // Add custom admin columns to show event start and end dates
    public function ep_add_event_columns($columns) {
        $columns['event_start_date'] = 'Event Start Date';
        $columns['event_end_date'] = 'Event End Date';
        return $columns;
    }

    // Populate custom columns with data
    public function ep_event_custom_column($column, $post_id) {
        switch ($column) {
            case 'event_start_date':
                $event_start_date = get_post_meta($post_id, 'ep_event_start_date', true);
                if ($event_start_date) {
                    echo date('F j, Y', strtotime($event_start_date));
                } else {
                    echo 'Unknown';
                }
                break;
            case 'event_end_date':
                $event_end_date = get_post_meta($post_id, 'ep_event_end_date', true);
                if ($event_end_date) {
                    echo date('F j, Y', strtotime($event_end_date));
                } else {
                    echo 'N/A';
                }
                break;
        }
    }

    // Make the event start date and end date columns sortable
    public function ep_make_event_date_columns_sortable($columns) {
        $columns['event_start_date'] = 'event_start_date';
        $columns['event_end_date'] = 'event_end_date';
        return $columns;
    }

    // Sort the event start date and end date columns
    public function ep_sort_event_date_columns($query) {
        if (!is_admin()) return;

        $orderby = $query->get('orderby');
        if ($orderby == 'event_start_date') {
            $query->set('meta_key', 'ep_event_start_date');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby == 'event_end_date') {
            $query->set('meta_key', 'ep_event_end_date');
            $query->set('orderby', 'meta_value');
        }
    }
}

// Instantiate the main class
new Events_Management();
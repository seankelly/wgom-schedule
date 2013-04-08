<?php
/*
Plugin name: WGOM Schedule
Version: 0.1
Description: Plugin for managing sport schedules.
Author: sean
Author URI:
*/

namespace WGOM;

class Schedule extends \WP_Widget {
    const DB_VERSION = 1;

    public function __construct() {
        $widget_ops = array(
            'classname' => 'WGOM Schedule',
            'description' => 'Handle team schedules'
        );
        $control_ops = array('width' => 350, 'height' => 450);

        parent::__construct('wgom-schedule', 'WGOM Schedule', $widget_ops, $control_ops);
    }

    public function form($instance) {
        $defaults = array(
            'title' => 'Team Schedule',
            'schedule' => ''
        );

        $instance = wp_parse_args((array) $instance, $defaults);
?>
<p>
    <label for="<?php echo $this->get_field_id('title'); ?>">Title</label>
    <input id="<?php echo $this->get_field_id('title'); ?>" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" />
</p>
<textarea id="<?php echo $this->get_field_id('schedule'); ?>" name="<?php echo $this->get_field_name('schedule'); ?>" class="widefat" cols="15" rows="20"><?php echo esc_textarea($instance['schedule']); ?></textarea>
<?php
    }

    public function update($new_instance, $old_instance) {
    }

    public function widget($args, $instance) {
    }

    /* Method to handle plugin activation. */
    public function plugin_install() {
        $schedule_db_version = intval(get_option('wgom_schedule_db_version'));

        if (Schedule::DB_VERSION !== $schedule_db_version) {
            Schedule::update_table();
        }
    }

    /* Method to create database table. */
    public function update_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'schedule';
        $table_sql = "
            CREATE TABLE $table (
                gametime datetime NOT NULL,
                team varchar NOT NULL,
                opponent varchar NOT NULL,
                home boolean NOT NULL,
                tv varchar NOT NULL,
                KEY ${table}_team_idx (team)
            );
        ";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($table_sql);

        update_option('wgom_schedule_db_version', Schedule::DB_VERSION);
    }

    public function plugin_remove() {
        global $wpdb;

        $table = $wpdb->prefix . 'schedule';
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

add_action('widgets_init', create_function('', 'return register_widget("WGOM\\Schedule");'));

register_activation_hook(__FILE__, 'WGOM\\Schedule::plugin_install');
register_deactivation_hook(__FILE__, 'WGOM\\Schedule::plugin_remove');

?>

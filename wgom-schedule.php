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
    const OPTION_NAME = 'wgom_schedule';
    const DB_VERSION = 1;
    const DB_OPTION_NAME = 'wgom_schedule_db_version';

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
<p>
    <label for="<?php echo $this->get_field_id('team'); ?>">Title</label>
    <input id="<?php echo $this->get_field_id('team'); ?>" class="widefat" name="<?php echo $this->get_field_name('team'); ?>" value="<?php echo $instance['team']; ?>" />
</p>
<textarea id="<?php echo $this->get_field_id('schedule'); ?>" name="<?php echo $this->get_field_name('schedule'); ?>" class="widefat" cols="15" rows="20"><?php echo esc_textarea($instance['schedule']); ?></textarea>
<?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $options = get_option(Schedule::OPTION_NAME, array());

        $team = strip_tags($new_instance['team']);
        if ($team === '')
            return;

        $title = strip_tags($new_instance['title']);
        $schedule = strip_tags($new_instance['schedule']);

        $instance['title'] = $title;
        $instance['team'] = $team;
        $instance['schedule'] = $schedule;

        $options[$team] = array(
            'title' => $title,
            'team' => $team,
            'schedule' => $schedule
        );

        update_option(Schedule::OPTION_NAME, $options);

        return $instance;
    }

    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $content = $this->generate($instance);

        extract($args, EXTR_SKIP);
        echo $before_widget;
        if ($title) {
            echo $before_title . $title . $after_title;
        }
        echo $content;
        echo $after_widget;
    }

    // Find all games from today onward, with a max limit of five games.
    private function generate($instance) {
        global $wpdb;

        $team = $instance['team'];
        $today = date('c', mktime(0, 0, 0));
        $table = $wpdb->prefix . 'schedule';
        $get_games_sql = "SELECT gametime,team,opponent,home,tv FROM $table WHERE team = %s AND gametime > %s LIMIT 5";

        $prepared_sql = $wpdb->prepare($get_games_sql, $team, $today);
        $games = $wpdb->get_results($prepared_sql, 'ARRAY_N');
        $content = '';
        foreach ($games as $game) {
            $opponent = $game[2];
            $tv = $game[4];
            $content .= "<li>DATE $opponent (TIME) $tv</li>";
        }

        return $content;
    }

    /* Method to handle plugin activation. */
    public function plugin_install() {
        $schedule_db_version = intval(get_option(Schedule::DB_OPTION_NAME));

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
                team varchar(64) NOT NULL,
                opponent varchar(64) NOT NULL,
                home boolean NOT NULL,
                tv varchar(256) NOT NULL,
                KEY ${table}_team_idx (team)
            );
        ";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($table_sql);

        update_option(Schedule::DB_OPTION_NAME, Schedule::DB_VERSION);
    }

    public function plugin_remove() {
        global $wpdb;

        $table = $wpdb->prefix . 'schedule';
        $wpdb->query("DROP TABLE IF EXISTS $table");
        delete_option(Schedule::DB_OPTION_NAME);
    }
}

add_action('widgets_init', create_function('', 'return register_widget("WGOM\\Schedule");'));

register_activation_hook(__FILE__, 'WGOM\\Schedule::plugin_install');
register_deactivation_hook(__FILE__, 'WGOM\\Schedule::plugin_remove');

?>

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
        $csv_schedule = $this->reverse_schedule($instance['schedule']);
?>
<p>
    <label for="<?php echo $this->get_field_id('title'); ?>">Title</label>
    <input id="<?php echo $this->get_field_id('title'); ?>" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" />
</p>
<p>
    <label for="<?php echo $this->get_field_id('team'); ?>">Title</label>
    <input id="<?php echo $this->get_field_id('team'); ?>" class="widefat" name="<?php echo $this->get_field_name('team'); ?>" value="<?php echo $instance['team']; ?>" />
</p>
<textarea id="<?php echo $this->get_field_id('schedule'); ?>" name="<?php echo $this->get_field_name('schedule'); ?>" class="widefat" cols="15" rows="20"><?php echo esc_textarea($csv_schedule); ?></textarea>
<?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $options = get_option(Schedule::OPTION_NAME, array());

        $team = strip_tags($new_instance['team']);
        if ($team === '')
            return;

        $title = strip_tags($new_instance['title']);
        $csv_schedule = strip_tags($new_instance['schedule']);
        $schedule = $this->parse_schedule($csv_schedule);

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
        $schedule = $instance['schedule'];
        $content = '';
        $found = 0;
        // Number of games in the schedule to show.
        $limit = 5;
        $today = mktime(0, 0, 0);
        foreach ($schedule as $game) {
            if ($today > $game[0]) {
                continue;
            }

            $date = getdate($game[0]);
            $gamedate = $date['mon'] . '/' . $date['mday'];
            $gametime = $date['hours'] . ':' . $date['minutes'];
            $opponent = $game[2];
            $tv = $game[4];
            $content .= "<li>$gamedate $opponent ($gametime) $tv</li>";

            $limit++;
        }

        return $content;
    }

    private function parse_schedule($csv_schedule) {
        $schedule = array();

        /*
         * Input format:
         *  YYYY-MM-DD,HH:MM,Opponent,Home,TV channels
         */
        $rows = str_getcsv($csv_schedule, "\n");
        foreach ($rows as &$row) {
            $len = strlen($row);
            if ($len === 0) {
                continue;
            }

            $fields = str_getcsv($row);
            // Check if the time ends with AM or PM. Append PM to the time if
            // nothing is found.
            $time = trim(strtoupper($fields[1]));
            $ending = substr($time, -2);
            if ($ending != 'AM' && $ending != 'PM') {
                $time .= ' PM';
            }
            $gametime = strtotime($fields[0] . ' ' . $time);

            $opponent = $fields[2];
            $home = (bool) $fields[3];
            $tv = $fields[4];

            $schedule[] = array($gametime, $opponent, $home, $tv);
        }
    }

    private function reverse_schedule($schedule) {
    }

    public function plugin_remove() {
        delete_option(Schedule::OPTION_NAME);
    }
}

add_action('widgets_init', create_function('', 'return register_widget("WGOM\\Schedule");'));

register_deactivation_hook(__FILE__, 'WGOM\\Schedule::plugin_remove');

?>

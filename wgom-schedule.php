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
    public function __construct() {
        $widget_ops = array(
            'classname' => 'WGOM Schedule',
            'description' => 'Handle team schedules'
        );
        $control_ops = array('width' => 400, 'height' => 350);

        parent::__construct('wgom-schedule', 'WGOM Schedule', $widget_ops, $control_ops);
    }

    /* Method to handle plugin activation. */
    public function plugin_install() {
    }

    /* Method to create database table. */
    public function create_table() {
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
    }
}

add_action('widgets_init', create_function('', 'return register_widget("WGOM\\Schedule");'));

?>

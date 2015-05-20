<?php
/*
    Plugin Name: WPMU Reading Time
    Plugin URI: https://github.com/belkincapital/wpmu-reading-time
    Description: Estimate time a reader will need to go through an article. On Multisite, you may activate this plugin on a subsite basis or you may Network Activate it. To display the read time on your posts, either add our 'Reading Time' sidebar widget, or add our php code to your template &lt;?php wpmu_readingtime(); ?&gt;
    
    Author: Jason Jersey
    Author URI: https://www.twitter.com.com/degersey
    Version: 1.0
    Text Domain: wpmu-reading-time
    Domain Path: /languages/
    License: GNU General Public License 2.0 
    License URI: http://www.gnu.org/licenses/gpl-2.0.txt
    
    Copyright 2015 Belkin Capital Ltd (contact: https://belkincapital.com/contact/)
    Based on the Post Reading Time plugin v1.2 by Bostjan Cigan.

    This plugin is opensource; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License,
    or (at your option) any later version (if applicable).

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111 USA
*/


/** Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/** First we register all the functions */
register_activation_hook(__FILE__, 'wpmu_reading_time_install');
register_deactivation_hook(__FILE__, 'wpmu_reading_time_uninstall');
add_action('admin_menu', 'wpmu_reading_time_admin_menu_create');

/** Register the widget */
add_action('widgets_init', create_function('', 'return register_widget("wpmu_reading_time_widget");'));
	
/** Add options when activating */
function wpmu_reading_time_install() {
    /** Add the words per second option (default 200 wps) */
    add_option('wpmu_reading_time_wpm', '200', '', 'yes');
    /** Type of time output (minutes or minutes and seconds) */
    add_option('wpmu_reading_time_time', '1', '', 'yes');
}
	
/** Delete options from DB when deactivating */
function wpmu_reading_time_uninstall() {
    delete_option('wpmu_reading_time_wpm');
    delete_option('wpmu_reading_time_time');
}	

/** Add menu under wp-admin > settings */
function wpmu_reading_time_admin_menu_create() {   
    add_options_page('Reading Time', 'Reading Time', 'manage_options', __FILE__, 'wpmu_reading_time_settings');  
}

/** The admin interface (wp-admin > settings) */
function wpmu_reading_time_settings() {
	
    $message = "";

    if ( is_admin() && current_user_can('manage_options') ) {

        if(isset($_POST['wpmu_wpm'])) {

            $wpm = $_POST['wpmu_wpm'];
            $time = $_POST['pr_time'];
            
            update_option('wpmu_reading_time_wpm', $wpm);
            update_option('wpmu_reading_time_time', $time);            
            
            $message = "Successfully updated options.";
            
        }
		  
        $wpm = get_option('wpmu_reading_time_wpm');
        $time = get_option('wpmu_reading_time_time');
		  
?>
<div class="wrap">
    <h2>Reading Time</h2>
    <form method="post" action="">
        <p><b><span style="color:rgb(92, 184, 92);font-weight:bold"><?php echo $message; ?></span></b></p>
        <p><b>WPM</b><br /><input type="text" name="wpmu_wpm" value="<?php echo $wpm; ?>" /> Words per minute.<br /><small>Default: <b>200</b></small></p>
        <p><b>Output</b><br />
            <select id="pr_time" name="pr_time">
                <option value="1" <?php if($time == "1") { echo 'selected="selected"'; } ?>>Minutes</option>
                <option value="2" <?php if($time == "2") { echo 'selected="selected"'; } ?>>Minutes and seconds</option>
            </select> Type of time output.<br /><small>Default: <b>Minutes</b></small></p><br />
        <p><input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" /></p>
    </form>
</div>
<?php
  
    }
			
}
			
/** Outputs the estimated reading time of the post */
function wpmu_readingtime() {
	
    if ( ! get_option('wpmu_reading_time_wpm') ) {
        $words_per_second_option = '200';
    } else {
        $words_per_second_option = get_option('wpmu_reading_time_wpm');
    }

    if ( ! get_option('wpmu_reading_time_time') ) {
        $time = '1';
    } else {
        $time = get_option('wpmu_reading_time_time');
    }
    
    $post_id = get_the_ID();		
    $content = apply_filters('the_content', get_post_field('post_content', $post_id));
    $num_words = str_word_count(strip_tags($content));
    $minutes = floor($num_words / $words_per_second_option);
    $seconds = floor($num_words % $words_per_second_option / ($words_per_second_option / 60));

    if($time == "1") {

        if($seconds >= 30) {       
            $minutes = $minutes + 1;           
        }

        $estimated_time = $estimated_time.' '.$minutes . ' min read';

    } else {

        $estimated_time = $estimated_time.' '.$minutes . ' min '. ', ' . $seconds . ' sec read';

    }

    if($minutes < 1) {
        $estimated_time = "Less than 1 min read";
    }
	
    $clock_image = "&#x1f558;";
    echo $clock_image.' '.$estimated_time;

}

/** The widget code */
class wpmu_reading_time_widget extends WP_Widget {
		
    function wpmu_reading_time_widget() {   
        parent::WP_Widget(false, $name="Reading Time");       
    }
		
    function widget($args, $instance) {
			
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
			
        if(is_single()) {

            echo $before_widget;
  
            if($title) {
                echo $before_title . $title . $after_title;
            }
			  
            wpmu_readingtime();			  
            echo $after_widget;

        }
			
    }
		
    function update($new_instance, $old_instance) { 	
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        return $instance;    
    }
		
    function form($instance) {	
        $title = esc_attr($instance['title']);
	
?>
<p>
<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title: '); ?></label> 
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</p>
<?php

    }

}

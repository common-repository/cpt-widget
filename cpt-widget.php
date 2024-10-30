<?php
/*
Plugin Name: CPT Widget
Plugin URI: http://www.1980media/cpt-widget/
Description: Plugin provides a widget for displaying quick archive lists for custom post types.
Version: 1.0
Author: 1980 Creative Media LLC
Author URI: http://www.1980media.com
License: GPL2
*/
?>
<?php
/*  Copyright 2011  1980 Creative Media LLC  (email : tori@1980media.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php




/**
 * Installs Plugin
 *
 * @project ntety_cpt
 */
function ntety_cpt_install() {
	
	if( version_compare( get_bloginfo('version'), '3.1', '<' ) ) {
		deactivate_plugins(basename( __FILE__ ) ); // Deactivates our plugin
	}
	
	//load internationalization
	load_plugin_textdomain( 'cpt-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	
	
	//register the uninstall function
	register_uninstall_hook(__FILE__, 'ntety_cpt_uninstaller');
}



/**
 * Uninstalls Plugin Plugin
 *
 * Clears out saved options upon uninstall
 *
 * @project ntety_cpt
 */
function ntety_cpt_uninstaller() {
	//delete plugin options from database
	delete_option( 'ntety_cpt_options' );
}



/**
 * Registers the Widget Defined Below
 */
 function ntety_cpt_register_widgets() {
	register_widget( 'ntety_cpt_post_list' );
}




/**
 * Custom Post Type Widget
 *
 * This class describes a widget to be used within the Wordpress Framework. It creates the html code to
 * be displayed on the public facing site as well as the options form for the widget area in the WordPress
 * control panel.
 *
 * @version 1.0
 * @project ntety_cpt
 */
class ntety_cpt_post_list extends WP_Widget {


	/** constructor */
	function ntety_cpt_post_list() {
		$widget_ops = array(
			'classname' => 'ntety_cpt_post_list',
			'description' => __('Display a list of posts of a custom type.', 'cpt-widget')
			);
		
		$this->WP_Widget( 'ntety_cpt_post_list', 'CPT Widget', $widget_ops );
	}


	/** @see WP_Widget::form */
	function form( $instance ) {
		$defaults = array( 'title' => 'My Posts', 'show_count' => 5, 'post_type' => '', 'show_description' => true );
		$ordering_methods = array( 'asc' => __('Ascending','cpt-widget'), 'desc' => __('Descending','cpt-widget'), 'rand' => __('Random','cpt-widget') );
		
		$instance = wp_parse_args( (array)$instance, $defaults );
		
		$title = $instance['title'];
		$show_count = $instance['show_count'];
		$order_method = $instance['order_method'];
		$post_type = $instance['post_type'];
		$show_excerpt = $instance['show_excerpt'];
		
		$args = array(
			'public'   => true,
			'_builtin' => false
		);
		$post_types=get_post_types($args,'names'); 
		?>
		
		<p><?php _e('Title:','cpt-widget'); ?> <input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
		
		<p><?php _e('Count:','cpt-widget'); ?> <input name="<?php echo $this->get_field_name( 'show_count' ); ?>" type="text" value="<?php echo esc_attr( $show_count ); ?>" size="5" /></p>
        
        <p><?php _e('Order By:','cpt-widget'); ?>
        <select name="<?php echo $this->get_field_name( 'order_method' ); ?>">
        <?php
			foreach ($ordering_methods as $omkey => $omvalue ) {
				echo "<option value=\"$omkey\" ";
				echo selected($order_method,$omkey);
				echo ">$omvalue</option>";
			}
		?>
        </select>
		</p>
		
		<p><?php _e('Post Type:','cpt-widget'); ?> 
		<select name="<?php echo $this->get_field_name( 'post_type' ); ?>">
		<?php
			foreach ($post_types as $ptype ) {
				echo "<option value=\"$ptype\" ";
				echo selected($post_type,$ptype);
				echo ">$ptype</option>";
			}
		?>
		</select>
		</p>
		<p><?php _e('Show Excerpt?:','cpt-widget'); ?>  <input name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" type="checkbox" <?php checked( $show_excerpt, 'on' ); ?> /></p>
		
		<?
		
	}


	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['show_count'] = strip_tags( $new_instance['show_count'] );
		$instance['order_method'] = strip_tags( $new_instance['order_method'] );
		$instance['post_type'] = strip_tags( $new_instance['post_type'] );
		$instance['show_excerpt'] = strip_tags( $new_instance['show_excerpt'] );
		
		return $instance;
	}
	
	
	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract($args);
		
		echo $before_widget;
		
		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( !empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		$args = array(
			'post_type'			=> $instance['post_type'],
			'posts_per_page' 	=> $instance['show_count'],
			'orderby'				=> $instance['order_method']
		);
		query_posts( $args );
		while ( have_posts() ) : the_post();
			$post_title = '<h3>'.get_the_title().'</h3>';
			apply_filters('ntety_cpt_post_title', $post_title);
			echo $post_title;
			if ( $instance['show_excerpt'] ) {
				echo '<div class="entry-content">';
				the_excerpt();
				echo '</div>';
			}
		endwhile;
		
		$obj = get_post_type_object($instance['post_type']);
		
		if( !empty($obj->rewrite['slug']) ) {
			echo '<p class="readmore"><a href="'.site_url($obj->rewrite['slug']).'">'.sprintf( __('View More %s','ctp-widget'), $obj->labels->name).'</a></p>';
		}
		
		echo $after_widget;
	}
	

}


// Setup Install Function
register_activation_hook( __FILE__, 'ntety_cpt_install' );

// Register the Widget with Wordpress
add_action( 'widgets_init', 'ntety_cpt_register_widgets');


?>
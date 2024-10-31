<?php
/**
 * Plugin Name: Oculolinctus
 * Plugin URI: http://angelawang.me/
 * Description: Show appreciation by licking adorable cats!
 * Author: Angela
 * Version: 2.0
 * Author URI: http://angelawang.me/
 * License: GPL2
 *
 * Copyright 2013 Angela Wang (email : idu.angela@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Feline_Oculolinctus {

	function __construct() {

		$this->lick_key 	= "feline_oculolinctus_count";
		$this->licker_key 	= "feline_oculolinctus_users";

		$this->post_type 	= array( array(
			"id"	=> "all",
			"label"	=> __("All Post Types", "Feline_Oculolinctus")
		) );

		$temp = get_post_types( array(), 'names' );
		foreach( $temp as $id => $one_post_type ) {
			array_push( $this->post_type, array(
				"id"	=> $id,
				"label"	=> $id
			) );
		}
		unset( $temp );

		$this->options 		= array(
			"options"	=> array(
				"page_name"		=> "general",
				"setting_name"	=> "oculolinctus",
				"section_id"	=> "oculolinctus_setting",
				"section_label"	=> __("Oculolinctus Setting", "Feline_Oculolinctus")
			),
			"settings"	=> array(
				"show_on"		=> array(
					"id"		=> "show_on",
					"type"		=> "dropdown",
					"label"		=> __("Show Licker On", "Feline_Oculolinctus"),
					"values"	=> $this->post_type,
					"default"	=> "post",
				),
				"display"	=> array(
					"id"		=> "display",
					"type"		=> "dropdown",
					"label"		=> __("Display Position", "Feline_Oculolinctus"),
					"values"	=> array(
						array(
							"id"	=> "bottom",
							"label"	=> __("After Content", "Feline_Oculolinctus")
						),
						array(
							"id"	=> "top",
							"label"	=> __("Before Content", "Feline_Oculolinctus")
						),
						array(
							"id"	=> "both",
							"label"	=> __("Before and After Content", "Feline_Oculolinctus")
						)
					),
					"default"	=> "bottom",
				),
				"sound"		=> array(
					"id"		=> "sound",
					"type"		=> "checkbox",
					"label"		=> __("Play Sound", "Feline_Oculolinctus"),
					"default"	=> "0",
				)
			)
		);

		$this->config 		= get_option( $this->options["options"]["setting_name"] );
		foreach( $this->options["settings"] as $id => $setting ) {
			$this->config[ $setting["id"] ] = !empty( $this->config[ $setting["id"] ] ) ? $this->config[ $setting["id"] ] : $setting["default"];
		}


		load_plugin_textdomain( "Feline_Oculolinctus", false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		add_action( "admin_init", array($this, "admin_init_callback") );
		add_action( "the_content", array($this, "the_content_callback") );
		add_action( "wp_enqueue_scripts", array($this, "wp_enqueue_scripts_callback") );
		add_action( "wp_ajax_the_licking", array($this, "wp_ajax_the_licking_callback") );
		add_action( "wp_ajax_nopriv_the_licking", array($this, "wp_ajax_the_licking_callback") );

		register_deactivation_hook( __FILE__, array($this, "uninstall") );		

	}

	function get_lick_sentence( $count ) {

		$lick_sentence 	= !empty( $count ) ? __( "Already liked by <span class='feline_oculolinctus_count'>" . $count . "</span> lickers.", "Feline_Oculolinctus" ) : __( "No one has licked yet!", "Feline_Oculolinctus" );

		return sprintf( __( 'Show your appreciation. %s ', "Feline_Oculolinctus" ), $lick_sentence );

	}

	function field_callback( $setting ) {

		$value = $this->config[ $setting["id"] ];

		switch( $setting["type"] ) {

			case "dropdown":
				echo '<select id="', $setting["id"], '" name="', $this->options["options"]["setting_name"], "[", $setting["id"], ']">';

				foreach( $setting["values"] as $one_value ) {
					echo '<option value="', $one_value["id"], '"';
					selected( $value, $one_value["id"] );
					echo '>', $one_value["label"], '</option>';
				}

				echo '</select>';
				break;
			case "checkbox":
				?>
				<input id="<?php echo $setting["id"]; ?>" name="<?php echo $this->options["options"]["setting_name"], "[", $setting["id"], "]"; ?>" type="checkbox" class="checkbox" value="1" <?php checked( $value, "1" ); ?>> <?php echo $setting["label"]; ?>
				<?php

		}

	}

	function admin_init_callback() {

		register_setting( $this->options["options"]["page_name"], $this->options["options"]["setting_name"] );

		add_settings_section( $this->options["options"]["section_id"], $this->options["options"]["section_label"], false, $this->options["options"]["page_name"] );

		foreach( $this->options["settings"] as $id => $setting ) {

			add_settings_field( $setting["id"], $setting["label"], array($this, "field_callback"), $this->options["options"]["page_name"], $this->options["options"]["section_id"], $setting );

		}

	}

	function the_content_callback( $content ) {

		global $post;

		$lick_val 	= get_post_meta( $post->ID, $this->lick_key, true );
		$licker_val = json_decode( get_post_meta( $post->ID, $this->licker_key, true ) );

		$output 	= '
			<div class="feline_oculolinctus">

				<input type="hidden" id="feline_oculolinctus_ajaxurl" name="feline_oculolinctus_ajaxurl" value="' . admin_url('admin-ajax.php') . '">
				<input type="hidden" id="feline_oculolinctus_post_id" name="feline_oculolinctus_post_id" value="' . $post->ID . '">
				<input type="hidden" id="feline_oculolinctus_user_id" name="feline_oculolinctus_user_id" value="' . get_current_user_id() . '">

				<button class="feline_oculolinctus_button">
					<img src="' . plugins_url("assets/oculolinctus.png", __FILE__) . '" class="feline_oculolinctus_img" />
				</button>

				<div class="feline_oculolinctus_user_list">
					<p>' . $this->get_lick_sentence($lick_val) . '</p>';
		if($licker_val) {
			foreach ($licker_val as $licker_id) {
				$output .= get_avatar( $licker_id, 75 );
			}
		}
		$output 	.= '
				</div><!--.feline_oculolinctus_user_list-->';

		if( $this->config["sound"] ) {
			$output .= '
				<audio class="feline_oculolinctus_sound" preload="auto" hidden="true">
					<source src="' . plugins_url("assets/oculolinctus.wav", __FILE__) . '" type="audio/wav">
					<source src="' . plugins_url("assets/oculolinctus.ogg", __FILE__) . '" type="audio/ogg">
					<source src="' . plugins_url("assets/oculolinctus.mp3", __FILE__) . '" type="audio/mpeg">
					' . __("Sorry, Your Browser Does not Support This Audio", "Feline_Oculolinctus") . '
				</audio>';
		}

		$output 	.= '
			</div><!--.feline_oculolinctus-->
			<div class="clear clearfix" style="clear: both;"></div>
		';

		if( "all" == $this->config["show_on"] ||
			$post->post_type == $this->config["show_on"] ) {

			if( "bottom" == $this->config["display"] ) {
				$content = $content . $output;
			} else if( "top" == $this->config["display"] ) {
				$content = $output . $content;
			} else {
				$content = $output . $content . $output;
			}

		}

		return $content;

	}

	function wp_enqueue_scripts_callback() {

		wp_enqueue_script( 'feline_oculolinctus', plugins_url("assets/oculolinctus.min.js", __FILE__), array("jquery"), false, true );
		wp_enqueue_style( 'feline_oculolinctus', plugins_url("assets/oculolinctus.min.css", __FILE__), false, false, 'all' );

		return;

	}

	function wp_ajax_the_licking_callback() {

		//Get the ajax info
		$post_id 				= $_POST["post_id"];
		$user_id 				= $_POST["user_id"];

		//Update Lick Count
		$lick_val 			= get_post_meta( $post_id, $this->lick_key, true );
		$licker_val 		= json_decode( get_post_meta( $post_id, $this->licker_key, true ) );

		if( !empty($user_id) ) {

			//Update Existing User Lick
			if($licker_val == "") { //First Lick

				$licker_val 	= array($user_id);
				$lick_val 		= 1;

			} else if( !in_array($user_id, $licker_val) ) { //Subsequent Lick

				$licker_val[] = $user_id;
				$lick_val 		= ++$lick_val;

			}

			update_post_meta( $post_id, $this->licker_key, json_encode($licker_val) );
			update_post_meta( $post_id, $this->lick_key, $lick_val );

		} else {

			//Updating Anonymous User Lick
			$lick_val = empty($lick_val) ? 1 : ++$lick_val;
			update_post_meta( $post_id, $this->lick_key, $lick_val );

		}

		echo $this->get_lick_sentence($lick_val);
		die();

		return;

	}

	function uninstall() {
		delete_option( $this->options["options"]["setting_name"] );
	}
}

class Feline_Oculolinctus_Widget extends WP_Widget {

	function __construct() {

		$this->lick_key 	= "feline_oculolinctus_count";
		$this->default 		= array(
			"title"			=> __("Most Licked Posts", "Feline_Oculolinctus"),
			"post_no"		=> 5,
			"show_count"	=> "on",
		);

		parent::__construct(
			"feline_oculolinctus_widget",
			__("Feline Oculolinctus Widget", "Feline_Oculolinctus"),
			array(
				"description" => __("Display Most Licked Posts", "Feline_Oculolinctus"),
			)
		);

	}

	function widget( $args, $instance ) {
		
		$rank 		= 1;
		$title 		= apply_filters( 'widget_title', $instance['title'] );
		$instance 	= shortcode_atts( $this->default, $instance );

		echo $args['before_widget'];
		if ( !empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$FO_Posts = new WP_Query( array(
			"posts_per_page" 	=> $instance["post_no"],
			"post_status" 		=> "published",
			"meta_key" 			=> $this->lick_key,
			"orderby"			=> "meta_value_num",
			"order"				=> "DESC",
		) );

		echo '<ul class="feline_oculolinctus_list">';
		while( $FO_Posts->have_posts() ) {
			$FO_Posts->the_post();

			$post_id 	= get_the_ID();
			$post_title = get_the_title();
			?>
			<li>
				<span class="list_item_count">
					<?php
						echo $instance["show_count"] == "on" ? get_post_meta($post_id, $this->lick_key, true) : $rank++;
					?>
				</span>
				<a href="<?php the_permalink(); ?>" title="<?php echo $post_title; ?>"><?php echo $post_title; ?></a>
			</li>
			<?php
		}

		wp_reset_postdata();

		echo '</ul>';

		echo $args['after_widget'];

	}

	function form( $instance ) {

		$instance = shortcode_atts( $this->default, $instance );

		$id_title 			= $this->get_field_id("title");
		$id_post_no 		= $this->get_field_id("post_no");
		$id_show_count		= $this->get_field_id("show_count");
		$name_title 		= $this->get_field_name("title");
		$name_post_no 		= $this->get_field_name("post_no");
		$name_show_count	= $this->get_field_name("show_count");
		?>
		<p>
			<label for="<?php echo $name_title; ?>"><?php _e( "Title:", "Feline_Oculolinctus" ); ?></label> 
			<input class="widefat" id="<?php echo $id_title; ?>" name="<?php echo $name_title; ?>" type="text" value="<?php echo esc_attr( $instance["title"] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $name_post_no; ?>"><?php _e( "Post Count:", "Feline_Oculolinctus" ); ?></label> 
			<input class="widefat" id="<?php echo $id_post_no; ?>" name="<?php echo $name_post_no; ?>" type="text" value="<?php echo esc_attr( $instance["post_no"] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $name_show_count; ?>"><?php _e( "Show Count:", "Feline_Oculolinctus" ); ?></label> 
			<input class="widefat" id="<?php echo $id_show_count; ?>" name="<?php echo $name_show_count; ?>" type="checkbox" <?php checked( $instance["show_count"], "on" ); ?> />
		</p>
		<p><?php _e("If checked, number of licks is shown. If not, rank is show instead.", "Feline_Oculolinctus" ); ?></p>
		<?php

		return;

	}

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		foreach ($new_instance as $key => $value) {

			if( $new_instance[$key] != $instance[$key] ) {
				$instance[$key] = strip_tags( $new_instance[$key] );
			}

			continue;
		}

		return $instance;

	}

}

$Feline_Oculolinctus = new Feline_Oculolinctus();
add_action( 'widgets_init', create_function( '', 'register_widget( "Feline_Oculolinctus_Widget" );') );
?>
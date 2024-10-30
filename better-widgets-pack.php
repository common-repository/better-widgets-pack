<?php
/*
Plugin Name: Better Widgets Pack
Plugin URI: http://catapultthemes.com/better-widgets-pack/
Description: A set of widgets to give you more functionality
Version: 1.0.3
Author: Catapult Themes
Author URI: http://catapultthemes.com/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define constants
 **/

if ( ! defined( 'BWP_PLUGIN_URL' ) ) {
	define( 'BWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}


/**
 * Plugin class
 **/
if ( ! class_exists( 'CT_Better_Widgets_Pack' ) ) { // Don't initialise if there's already one activated

	class CT_Better_Widgets_Pack {

		public function __construct() {
			// Nothing here... yet.
		}
		
		/*
		 * Initialize the class
		 * @since 1.0.0
		 */
		public function init() {
			require_once dirname( __FILE__ ) . '/widgets/class-wp-widget-author-profile.php';
			require_once dirname( __FILE__ ) . '/widgets/class-wp-widget-image-text-banner.php';
			require_once dirname( __FILE__ ) . '/widgets/class-wp-widget-mailchimp-form.php';
			require_once dirname( __FILE__ ) . '/widgets/class-wp-widget-recent-comments-gravatars.php';
			require_once dirname( __FILE__ ) . '/widgets/class-wp-widget-recent-posts-horizontal.php';
			require_once dirname( __FILE__ ) . '/widgets/class-wp-widget-recent-posts-thumbnails.php';
			require_once dirname( __FILE__ ) . '/widgets/class-wp-widget-shortcode-widget.php';
			
			add_action ( 'widgets_init', array ( $this, 'ct_widgets_init' ) );
			add_action ( 'wp_enqueue_scripts', array ( $this, 'enqueue_scripts' ) );
			
			add_action( 'show_user_profile',  array ( $this, 'user_profile_social' ) );
			add_action( 'edit_user_profile',  array ( $this, 'user_profile_social' ) );
			
			add_action( 'personal_options_update',  array ( $this, 'save_user_profile_social' ) );
			add_action( 'edit_user_profile_update',  array ( $this, 'save_user_profile_social' ) );
		}
		
		/*
		 * Register the widgets
		 * @since 1.0.0
		 */
		public function ct_widgets_init() {
			register_widget ( 'WP_Widget_Author_Profile' );
			register_widget ( 'WP_Widget_Image_Text_Banner' );
			register_widget ( 'WP_Widget_Mailchimp_Form' );
			register_widget ( 'WP_Widget_Recent_Comments_Gravatars' );
			register_widget ( 'WP_Widget_Recent_Posts_Horizontal' );
			register_widget ( 'WP_Widget_Recent_Posts_Thumbnails' );
			register_widget ( 'WP_Widget_Shortcode_Widget' );
		}
		
		/*
		 * Any scripts and styles
		 * @todo Setting to dequeue styles
		 * @since 1.0.0
		 */
		public function enqueue_scripts() {
			wp_enqueue_style ( 'ctbwp-style', BWP_PLUGIN_URL . 'css/style.css' );
			wp_enqueue_style ( 'font-awesome', BWP_PLUGIN_URL . 'css/font-awesome.min.css' );
		}
		
		/**
		 * Add social media profile fields to user profile for Author Profile
		 */
		public function user_profile_social ( $user ) { ?>
				
			<h3>Author Profile Social Links</h3>

			<table class="form-table">
				
				<tr>
					<th><label for="facebook_profile">Facebook Profile</label></th>
					<td><input type="text" name="facebook_profile" value="<?php echo esc_attr ( get_the_author_meta( 'facebook_profile', $user->ID ) ); ?>" class="regular-text" /></td>
				</tr>

				<tr>
					<th><label for="twitter_profile">Twitter Profile</label></th>
					<td><input type="text" name="twitter_profile" value="<?php echo esc_attr ( get_the_author_meta( 'twitter_profile', $user->ID ) ); ?>" class="regular-text" /></td>
				</tr>

				<tr>
					<th><label for="google_profile">Google+ Profile</label></th>
					<td><input type="text" name="google_profile" value="<?php echo esc_attr ( get_the_author_meta( 'google_profile', $user->ID ) ); ?>" class="regular-text" /></td>
				</tr>
				
				<tr>
					<th><label for="instagram_profile">Instagram Profile</label></th>
					<td><input type="text" name="instagram_profile" value="<?php echo esc_attr ( get_the_author_meta( 'instagram_profile', $user->ID ) ); ?>" class="regular-text" /></td>
				</tr>
				
			</table>
		<?php 
		}

		public function save_user_profile_social( $user_id ) {
			update_user_meta ( $user_id, 'facebook_profile', sanitize_text_field( $_POST['facebook_profile'] ) );
			update_user_meta ( $user_id, 'twitter_profile', sanitize_text_field( $_POST['twitter_profile'] ) );
			update_user_meta ( $user_id, 'google_profile', sanitize_text_field( $_POST['google_profile'] ) );
			update_user_meta ( $user_id, 'instagram_profile', sanitize_text_field( $_POST['instagram_profile'] ) );
		}
		
	}
	
	$CT_Better_Widgets_Pack = new CT_Better_Widgets_Pack();
	$CT_Better_Widgets_Pack -> init();
	
}

// Stop editing here
if( ! class_exists( 'Plugin_Usage_Tracker') ) {
	require_once dirname( __FILE__ ) . '/tracking/class-plugin-usage-tracker.php';
}
/**
 * Rename the function below so that it is unique and it won't conflict with any other plugins using this tracker
 */
if( ! function_exists( 'bwp_start_plugin_tracking' ) ) { 	// Replace function name here
	function bwp_start_plugin_tracking() { 					// Replace function name
		$PUT = new Plugin_Usage_Tracker(
			__FILE__,
			'http://put.catapultthemes.com/',								// Replace with the URL to the site where you will track your plugins
			array(),														// You can specify options here
			true,															// End-user opt-in is required by default
			true															// Include deactivation form
		);
	}
}
add_action( 'init', 'bwp_start_plugin_tracking' ); 			// Replace function name

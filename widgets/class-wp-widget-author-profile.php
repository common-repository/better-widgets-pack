<?php
/**
 * Widget API: WP_Widget_Author_Profile class
 *
 * @package Better Widgets Pack
 * @since 1.0.0
 */

/**
 * Class used to implement an author profile widget.
 *
 * @see WP_Widget
 */
class WP_Widget_Author_Profile extends WP_Widget {

	/**
	 * Sets up a new widget instance.
	 *
	 * @since 2.8.0
	 * @access public
	 */
	public function __construct() {
		$widget_ops = array('classname' => 'widget_author_profile', 'description' => __( 'Display an author profile.' ) );
		parent::__construct('author-profile', __('Author Profile'), $widget_ops);
		$this->alt_option_name = 'widget_author_profile';
	}

	/**
	 * Outputs the content for the current Recent Comments widget instance.
	 *
	 * @since 2.8.0
	 * @access public
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Recent Comments widget instance.
	 */
	public function widget( $args, $instance ) {
		
		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		$output = '';
		
		// Don't display if we're not on a post page
		if ( 'post' != get_post_type() ) {
			return;
		}

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : '';

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		
		/**
		 * Filter the arguments for the widget.
		 *
		 */
		$author_ID = get_the_author_meta( 'ID' );

		$output .= $args['before_widget'];
		if ( $title ) {
			$output .= $args['before_title'] . $title . $args['after_title'];
		}

		$output .= '<div class="ctbwp-author-profile">';
		
		$output .= '<div class="gravatar">' . get_avatar ( $author_ID, 128 ) . '</div>';
			$output .= '<div class="ctbwp-author-wrap">';
				
				$output .= '<h4>' . get_the_author_meta ( 'display_name', $author_ID ) . '</h4>';
				
				$social_array = array (
					'url'				=>	'link',
					'facebook_profile'	=>	'facebook',
					'twitter_profile'	=>	'twitter',
					'google_profile'	=>	'google',
					'instagram_profile'	=>	'instagram'
				);
				
				$icons = '';
				foreach ( $social_array as $field => $icon ) {
					if ( get_the_author_meta ( $field, $author_ID ) ) {
						$icons .= '<a href="' . esc_url ( get_the_author_meta ( $field, $author_ID ) ) . '" target="_blank"><span class="fa fa-' . $icon . '"></span></a>';
					}
				}
				
				if ( $icons ) {
					$output .= '<p class="ctbwp-author-social">';
					$output .= $icons;
					$output .= '</p><!-- .ctbwp-author-social -->';
				}
				
				$output .= '<p>' . get_the_author_meta ( 'description', $author_ID ) . '</p>';
				
				$output .= sprintf (
					'<p><a href="%s">%s</a></p>',
					get_author_posts_url ( $author_ID ),
					__ ( 'View all my posts', 'ctbwp' )
				);
				
			$output .= '</div>';
			
		$output .= '</div>';
		
		$output .= $args['after_widget'];

		echo $output;
	}

	/**
	 * Handles updating settings for the current Recent Comments widget instance.
	 *
	 * @since 2.8.0
	 * @access public
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 * @return array Updated settings to save.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] 	= sanitize_text_field( $new_instance['title'] );
		return $instance;
	}

	/**
	 * Outputs the settings form for the Recent Comments widget.
	 *
	 * @since 2.8.0
	 * @access public
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		?>
		
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

		<?php
	}

	/**
	 * Flushes the Recent Comments widget cache.
	 *
	 * @since 2.8.0
	 * @access public
	 *
	 * @deprecated 4.4.0 Fragment caching was removed in favor of split queries.
	 */
	public function flush_widget_cache() {
		_deprecated_function( __METHOD__, '4.4' );
	}
}

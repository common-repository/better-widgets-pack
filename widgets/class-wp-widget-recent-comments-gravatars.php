<?php
/**
 * Widget API: WP_Widget_Recent_Comments class
 *
 * @package Better Widgets Pack
 * @since 1.0.0
 */

/**
 * Class used to implement a Recent Comments with Gravatars widget.
 *
 * @since 2.8.0
 *
 * @see WP_Widget
 */
class WP_Widget_Recent_Comments_Gravatars extends WP_Widget {

	/**
	 * Sets up a new Recent Comments Gravatars widget instance.
	 *
	 * @since 2.8.0
	 * @access public
	 */
	public function __construct() {
		$widget_ops = array('classname' => 'widget_recent_comments_gravatars', 'description' => __( 'Your site&#8217;s most recent comments with commenter gravatar.' ) );
		parent::__construct('recent-comments-gravatars', __('Recent Comments with Gravatars'), $widget_ops);
		$this->alt_option_name = 'widget_recent_comments_gravatars';
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

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : '';

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number )
			$number = 5;

		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
		$excerpt = ( ! empty( $instance['excerpt'] ) ) ? absint( $instance['excerpt'] ) : 20;
		$style = ( ! empty( $instance['style'] ) ) ? esc_attr ( $instance['style'] ) : 'circle';
		
		/**
		 * Filter the arguments for the Recent Comments widget.
		 *
		 * @since 3.4.0
		 *
		 * @see WP_Comment_Query::query() for information on accepted arguments.
		 *
		 * @param array $comment_args An array of arguments used to retrieve the recent comments.
		 */
		$comments = get_comments( apply_filters( 'widget_comments_args', array(
			'number'      => $number,
			'status'      => 'approve',
			'post_status' => 'publish'
		) ) );

		$output .= $args['before_widget'];
		if ( $title ) {
			$output .= $args['before_title'] . $title . $args['after_title'];
		}

		$output .= '<ul id="recentcomments" class="ctbwp-widget-list recent-comments-gravatars ' . $style . '">';
		if ( is_array( $comments ) && $comments ) {
			// Prime cache for associated posts. (Prime post term cache if we need it for permalinks.)
			$post_ids = array_unique( wp_list_pluck( $comments, 'comment_post_ID' ) );
			_prime_post_caches( $post_ids, strpos( get_option( 'permalink_structure' ), '%category%' ), false );

			foreach ( (array) $comments as $comment ) {
				$output .= '<li class="recentcomments">';
				/* translators: comments widget: 1: comment author, 2: post link */
				$output .= '<div class="ctbwp-thumb-wrap">' . get_avatar ( $comment ) . '</div>';
				
				$output .= '<div class="ctbwp-content-wrap">';
				
				if ( $excerpt > 0 ) :
					$content = get_comment_text ( $comment );
					$output .= '<p>' . wp_trim_words ( $content, $excerpt ) . '</p>';
				endif;
				
				$output .= sprintf( _x( '%1$s on %2$s', 'widgets' ),
					'<span class="comment-author-link">' . get_comment_author_link( $comment ) . '</span>',
					'<a href="' . esc_url( get_comment_link( $comment ) ) . '">' . get_the_title( $comment->comment_post_ID ) . '</a>'
				);
				
				$output .= '</div>';
				$output .= '</li>';
			}
		}
		$output .= '</ul>';
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
		$instance['title'] 		= sanitize_text_field( $new_instance['title'] );
		$instance['number'] 	= absint( $new_instance['number'] );
		$instance['show_date']	= isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		$instance['style'] 		= isset( $new_instance['style'] ) ? esc_attr ( $new_instance['style'] ) : 'circle';
		$instance['excerpt']	= isset( $new_instance['excerpt'] ) ? absint( $new_instance['excerpt'] ) : 20;
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
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_date	= isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		$style = isset( $instance['style'] ) ? esc_attr ( $instance['style'] ) : 'circle';
		$excerpt = isset( $instance['excerpt'] ) ? absint( $instance['excerpt'] ) : 20;
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of comments to show:' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>
		
		<p><label for="<?php echo $this->get_field_id( 'excerpt' ); ?>"><?php _e( 'Excerpt length:' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'excerpt' ); ?>" name="<?php echo $this->get_field_name( 'excerpt' ); ?>" type="number" step="1" min="0" max="100" value="<?php echo $excerpt; ?>" size="4" /></p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'style' ); ?>"><?php _e( 'Style' ); ?></label>
			<?php $styles = array ( 'square' => 'Square', 'circle' => 'Circle' ); ?>
			<select id="<?php echo $this->get_field_id( 'style' ); ?>" name="<?php echo $this->get_field_name( 'style' ); ?>">
				<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
				<?php foreach ( $styles as $key => $value ) : ?>
				
					<option value="<?php echo $key; ?>" <?php selected( $style, $key ); ?>>
						<?php echo esc_html( $value ); ?>
					</option>
					
				<?php endforeach; ?>
			</select>
		</p>

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

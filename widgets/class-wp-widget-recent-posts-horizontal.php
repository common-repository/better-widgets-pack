<?php
/**
 * Widget API: WP_Widget_Recent_Posts_Horizontal class
 *
 * @package Better Widgets Pack
 * @since 1.0.0
 */

/**
 * Class used to implement a Recent Posts with Horizontal widget.
 *
 * @since 1.0.0
 *
 * @see WP_Widget
 */
class WP_Widget_Recent_Posts_Horizontal extends WP_Widget {

	/**
	 * Sets up a new Recent Posts widget instance.
	 *
	 * @since 2.8.0
	 * @access public
	 */
	public function __construct() {
		$widget_ops = array('classname' => 'widget_recent_entries_horizontal', 'description' => __( "Recent Posts displayed horizontally.") );
		parent::__construct('recent-posts-horizontal', __('Recent Posts Horizontal'), $widget_ops);
		$this->alt_option_name = 'widget_recent_entries_horizontal';
	}

	/**
	 * Outputs the content for the current Recent Posts Horizontal widget instance.
	 *
	 * @since 2.8.0
	 * @access public
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Recent Posts widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : '';

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 3;
		if ( ! $number )
			$number = 5;
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
		// $columns works with Bootstrap Grid - a value of 4 means 3 columns across
		$columns = ( ! empty( $instance['columns'] ) ) ? absint( $instance['columns'] ) : 4;
		$excerpt = ( ! empty( $instance['excerpt'] ) ) ? absint( $instance['excerpt'] ) : 20;
		$crop_height = ( ! empty( $instance['crop_height'] ) ) ? ( $instance['crop_height'] ) : 0;

		/**
		 * Filter the arguments for the Recent Posts widget.
		 *
		 * @since 3.4.0
		 *
		 * @see WP_Query::get_posts()
		 *
		 * @param array $args An array of arguments used to retrieve the recent posts.
		 */
		$r = new WP_Query( apply_filters( 'widget_posts_args', array(
			'posts_per_page'      => $number,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true
		) ) );

		/**
		 * Calculate the column widths
		 */
		
		 
		if ($r->have_posts()) :
		?>
		<?php echo $args['before_widget']; ?>
		<?php if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		$img_style = '';
		if ( $crop_height > 0 ) {
			$img_style = 'style="max-height: ' . intval ( $crop_height ) . 'px;"';
		} ?>
		<div class="ctbwp-row">
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<li class="ctbwp-col-lg-<?php echo $columns; ?> ctbwp-col-md-<?php echo $columns; ?>">
				<div class="ctbwp-flex-wrap">
					<div class="ctbwp-post-thumb" <?php echo $img_style; ?>>
						<a href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail ( 'medium' ); ?>
						</a>
					</div>	
					<div class="ctbwp-post-title">
						<?php printf (
							'<h5><a href="%s">%s</a></h5>',
							esc_url ( get_permalink() ),
							get_the_title()
						); ?>
					</div>
					<?php if ( $show_date ) : ?>
						<div class="ctbwp-post-date"><?php echo get_the_date(); ?></div>
					<?php endif; ?>
					<?php if ( $excerpt > 0 ) :
						$content = get_the_content();
						echo '<p>' . wp_trim_words ( $content, $excerpt ) . '</p>';
					endif; ?>
					<div class="read-more">
						<?php printf (
							'<a href="%s">%s</a>',
							esc_url ( get_permalink() ),
							__ ( 'Continue Reading', 'mode-theme' )
						); ?>
					</div>
				</div>
			</li>
		<?php endwhile; ?>
		</ul>
		</div>
		<?php echo $args['after_widget']; ?>
		<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;
	}

	/**
	 * Handles updating the settings for the current Recent Posts widget instance.
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
		$instance['title'] 			= sanitize_text_field( $new_instance['title'] );
		$instance['number'] 		= (int) $new_instance['number'];
		$instance['show_date'] 		= isset ( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		$instance['columns'] 		= (int) $new_instance['columns'];
		$instance['excerpt'] 		= (int) $new_instance['excerpt'];
		$instance['crop_height'] 	= (int) $new_instance['crop_height'];
		return $instance;
	}

	/**
	 * Outputs the settings form for the Recent Posts widget.
	 *
	 * @since 2.8.0
	 * @access public
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		$title     		= isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    		= isset( $instance['number'] ) ? absint( $instance['number'] ) : 3;
		$show_date		= isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		$columns 		= isset( $instance['columns'] ) ? absint( $instance['columns'] ) : 4;
		$excerpt 		= isset( $instance['excerpt'] ) ? absint( $instance['excerpt'] ) : 20;
		$crop_height 	= isset( $instance['crop_height'] ) ? absint( $instance['crop_height'] ) : 0;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>

		<p><label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date? ' ); ?></label><input class="checkbox" type="checkbox"<?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'columns' ); ?>"><?php _e( 'Number of Columns' ); ?></label>
			<?php $colspans = array ( 2 => 6, 3 => 4, 4 => 3, 6 => 2 ); ?>
			<select id="<?php echo $this->get_field_id( 'columns' ); ?>" name="<?php echo $this->get_field_name( 'columns' ); ?>">
				<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
				<?php foreach ( $colspans as $cols => $span ) : ?>
				
					<option value="<?php echo $span; ?>" <?php selected( $columns, $span ); ?>>
						<?php echo esc_html( $cols ); ?>
					</option>
					
				<?php endforeach; ?>
			</select>
			
		</p>
		
		<p><label for="<?php echo $this->get_field_id( 'excerpt' ); ?>"><?php _e( 'Number of words in excerpt:' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'excerpt' ); ?>" name="<?php echo $this->get_field_name( 'excerpt' ); ?>" type="number" step="1" min="1" value="<?php echo $excerpt; ?>" size="3" /></p>
		
		<p><label for="<?php echo $this->get_field_id( 'crop_height' ); ?>"><?php _e( 'Crop images to this height:' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'crop_height' ); ?>" name="<?php echo $this->get_field_name( 'crop_height' ); ?>" type="number" step="1" min="1" value="<?php echo $crop_height; ?>" /></p>
		
<?php
	}
}

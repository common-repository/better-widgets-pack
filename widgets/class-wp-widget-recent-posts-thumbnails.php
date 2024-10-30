<?php
/**
 * Widget API: WP_Widget_Recent_Posts_Thumbnails class
 *
 * @package Better Widgets Pack
 * @since 1.0.0
 */

class WP_Widget_Recent_Posts_Thumbnails extends WP_Widget {

	/**
	 * Sets up a new Recent Posts widget instance.
	 *
	 * @since 2.8.0
	 * @access public
	 */
	public function __construct() {
		$widget_ops = array (
			'classname' => 'widget_recent_entries_thumbnails',
			'description' => __( 'Recent Posts with thumbnails.', 'ctbwp' )
		);
		parent::__construct ( 
			'recent-posts-thumbnails',
			__( 'Recent Posts with Thumbnails', 'ctbwp' ),
			$widget_ops
		);
		$this->alt_option_name = 'widget_recent_entries_thumbnails';
	}

	/**
	 * Outputs the content for the current Recent Posts widget instance.
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

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number )
			$number = 5;
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
		$crop_image = isset( $instance['crop_image'] ) ? $instance['crop_image'] : false;
		if ( $crop_image ) {
			$crop_image = 'ctbwp-crop-image';
		}
		
		$style = ( ! empty( $instance['style'] ) ) ? esc_attr ( $instance['style'] ) : 'square';
		
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

		if ($r->have_posts()) :
		?>
		<?php echo $args['before_widget']; ?>
		<?php if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} ?>
		<ul class="ctbwp-widget-list <?php echo $crop_image . ' ' . $style; ?>">
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<li>
				<div class="ctbwp-thumb-wrap">
				<a href="<?php the_permalink(); ?>">
					<?php the_post_thumbnail( 'thumb' ); ?>
				</a>
				</div>
				<div class="ctbwp-content-wrap">
					<a href="<?php the_permalink(); ?>">
						<?php get_the_title() ? the_title() : the_ID(); ?><br>
					</a>
					<?php if ( $show_date ) : ?>
						<span class="post-date"><?php echo get_the_date(); ?></span>
					<?php endif; ?>
				</div>
				
			</li>
		<?php endwhile; ?>
		</ul>
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
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		$instance['crop_image'] = isset( $new_instance['crop_image'] ) ? (bool) $new_instance['crop_image'] : false;
		$instance['style'] 		= isset( $new_instance['style'] ) ? esc_attr ( $new_instance['style'] ) : 'square';
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
		$title     	= isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    	= isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_date 	= isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		$crop_image	= isset( $instance['crop_image'] ) ? (bool) $instance['crop_image'] : false;
		$style 		= isset( $instance['style'] ) ? esc_attr ( $instance['style'] ) : 'square';
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>

		<p><input class="checkbox" type="checkbox"<?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?' ); ?></label></p>
		
		<p><input class="checkbox" type="checkbox"<?php checked( $crop_image ); ?> id="<?php echo $this->get_field_id( 'crop_image' ); ?>" name="<?php echo $this->get_field_name( 'crop_image' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'crop_image' ); ?>"><?php _e( 'Crop image?' ); ?></label></p>
		
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
}

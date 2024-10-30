<?php
/**
 * Widget API: WP_Widget_Image_Text_Banner class
 *
 * @package Better Widgets Pack
 * @since 1.0.0
 */

/**
 * Class used to implement the widget.
 *
 * @see WP_Widget
 */
class WP_Widget_Image_Text_Banner extends WP_Widget {

	/**
	 * Sets up a new widget instance.
	 *
	 * @since 2.8.0
	 * @access public
	 */
	public function __construct() {
		$widget_ops = array('classname' => 'widget_image_text_banner', 'description' => __( 'Display an image banner with text and link.' ) );
		parent::__construct('image-text-banner', __('Image Text Banner'), $widget_ops);
		$this->alt_option_name = 'widget_image_text_banner';
		
		// Add media upload scripts
		add_action ( 'admin_enqueue_scripts', array ( $this, 'upload_scripts' ) );
	}
	
	public function upload_scripts() {
		wp_enqueue_media();
        wp_enqueue_script ( 'upload_media_widget',  BWP_PLUGIN_URL . 'js/upload-media.js', array ( 'jquery' ) );
	}

	/**
	 * Outputs the content for the current widget instance.
	 *
	 * @since 2.8.0
	 * @access public
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current instance.
	 */
	public function widget( $args, $instance ) {
		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		$output = '';

		$title 			= ( ! empty ( $instance['title'] ) ) ? $instance['title'] : '';
		$heading_style	= ( ! empty ( $instance['heading_style'] ) ) ? $instance['heading_style'] : 'h2';
		$sub 			= ( ! empty ( $instance['sub'] ) ) ? $instance['sub'] : '';
		$sub_style 		= ( ! empty ( $instance['sub_style'] ) ) ? $instance['sub_style'] : 'h3';
		$button			= ( ! empty ( $instance['button'] ) ) ? $instance['button'] : '';
		$button_class	= ( ! empty ( $instance['button_class'] ) ) ? $instance['button_class'] : '';
		$button_url		= ( ! empty ( $instance['button_url'] ) ) ? $instance['button_url'] : '';
		$image 			= ( ! empty ( $instance['image_uri'] ) ) ? $instance['image_uri'] : '';
		
		/**
		 * Filter the arguments for the widget.
		 *
		 */

		echo $args['before_widget']; ?>

			<div class="ctbwp-banner-wrap">
				<?php if ( $image ) { ?>
					<img src="<?php echo esc_url ( $image ); ?>" alt="" >
				<?php } ?>
				<div class="ctbwp-banner-content">
					<?php if ( $title ) {
						echo '<' . $heading_style . '>' . sanitize_text_field ( $title ) . '</' . $heading_style . '>';
					} ?>
					<?php if ( $sub ) {
						echo '<' . $sub_style . '>' . sanitize_text_field ( $sub ) . '</' . $sub_style . '>';
					} ?>
					<?php if ( $button && $button_url ) { ?>
						<a href="<?php echo esc_url ( $button_url ); ?>" class="<?php echo esc_attr ( str_replace ( '.', '', $button_class ) ); ?>"><?php echo sanitize_text_field ( $button ); ?></a>
					<?php } ?>
				</div><!-- ctbwp-banner-content -->
				
			</div><!-- .ctbwp-banner-wrap -->

		<?php echo $args['after_widget'];

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
		$instance['title'] 			= sanitize_text_field ( $new_instance['title'] );
		$instance['heading_style'] 	= sanitize_text_field ( $new_instance['heading_style'] );
		$instance['sub'] 			= sanitize_text_field ( $new_instance['sub'] );
		$instance['sub_style'] 		= sanitize_text_field ( $new_instance['sub_style'] );
		$instance['button'] 		= sanitize_text_field ( $new_instance['button'] );
		$instance['button_class'] 	= sanitize_text_field ( $new_instance['button_class'] );
		$instance['button_url'] 	= sanitize_text_field ( $new_instance['button_url'] );
		$instance['image_uri'] 		= ( $new_instance['image_uri'] );
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
		$title			= isset( $instance['title'] ) ? $instance['title'] : '';
		$heading_style	= isset( $instance['heading_style'] ) ? $instance['heading_style'] : 'h2';
		$sub 			= isset( $instance['sub'] ) ? $instance['sub'] : '';
		$sub_style 		= isset( $instance['sub_style'] ) ? $instance['sub_style'] : 'h3';
		$button			= isset( $instance['button'] ) ? $instance['button'] : '';
		$button_class	= isset( $instance['button_class'] ) ? $instance['button_class'] : '';
		$button_url		= isset( $instance['button_url'] ) ? $instance['button_url'] : '';
		$image 			= isset( $instance['image_uri'] ) ? $instance['image_uri'] : '';
		?>
		
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Heading:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo sanitize_text_field( $title ); ?>" /></p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'heading_style' ); ?>"><?php _e( 'Heading style:' ); ?></label>
			<?php $styles = array ( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'p' => 'p' ); ?>
			<select id="<?php echo $this->get_field_id( 'heading_style' ); ?>" name="<?php echo $this->get_field_name( 'heading_style' ); ?>">
				<?php foreach ( $styles as $key => $value ) : ?>
				
					<option value="<?php echo $key; ?>" <?php selected( $heading_style, $key ); ?>>
						<?php echo esc_html( $value ); ?>
					</option>
					
				<?php endforeach; ?>
			</select>
		</p>
		
		<p><label for="<?php echo $this->get_field_id( 'sub' ); ?>"><?php _e( 'Sub heading:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'sub' ); ?>" name="<?php echo $this->get_field_name( 'sub' ); ?>" type="text" value="<?php echo sanitize_text_field( $sub ); ?>" /></p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'sub_style' ); ?>"><?php _e( 'Sub heading style:' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'sub_style' ); ?>" name="<?php echo $this->get_field_name( 'sub_style' ); ?>">
				<?php foreach ( $styles as $key => $value ) : ?>
				
					<option value="<?php echo $key; ?>" <?php selected( $sub_style, $key ); ?>>
						<?php echo esc_html( $value ); ?>
					</option>
					
				<?php endforeach; ?>
			</select>
		</p>
		
		
		<p><label for="<?php echo $this->get_field_id( 'button' ); ?>"><?php _e( 'Button text:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'button' ); ?>" name="<?php echo $this->get_field_name( 'button' ); ?>" type="text" value="<?php echo sanitize_text_field( $button ); ?>" /></p>
			
		<p><label for="<?php echo $this->get_field_id( 'button_class' ); ?>"><?php _e( 'Button class:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'button_class' ); ?>" name="<?php echo $this->get_field_name( 'button_class' ); ?>" type="text" value="<?php echo esc_attr( $button_class ); ?>" /></p>
		
		<p><label for="<?php echo $this->get_field_id( 'button_url' ); ?>"><?php _e( 'Button URL:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'button_url' ); ?>" name="<?php echo $this->get_field_name( 'button_url' ); ?>" type="text" value="<?php echo esc_url( $button_url ); ?>" /></p>

		<p>
			<label for="<?php echo $this->get_field_id('image_uri'); ?>">Image</label>
			<div id="<?php echo $this->get_field_id('image_uri'); ?>-img">
			<?php
				if ( $image != '' ) { ?>
					<img class="custom_media_image" src="<?php echo $image; ?>" style="margin:0;padding:0;max-height:100px;float:none;" />
				<?php }
			?>
			</div>
			<input type="hidden" class="widefat custom_media_url" name="<?php echo $this->get_field_name('image_uri'); ?>" id="<?php echo $this->get_field_id('image_uri'); ?>" value="<?php echo $image; ?>" style="margin-top:5px;">
		</p>
		<p style="clear: left;">
			<input type="button" class="button button-secondary custom_media_button" id="<?php echo $this->get_field_id('image_uri'); ?>_button" name="<?php echo $this->get_field_name('image_uri'); ?>" value="Upload Image" style="margin-top:5px;" />
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

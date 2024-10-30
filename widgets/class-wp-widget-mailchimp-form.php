<?php
/**
 * Widget API: WP_Widget_Mailchimp_Form class
 *
 * @package Better Widgets Pack
 * @since 1.0.0
 */

// Prevent direct file access
if ( ! defined ( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class used to implement an author profile widget.
 *
 * @see WP_Widget
 */
class WP_Widget_Mailchimp_Form extends WP_Widget {
		
	/**
	 * Register widget with WordPress.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		$widget_ops = array('classname' => 'widget_mailchimp_form', 'description' => __( 'A subscription form for a Mailchimp list.' ) );
		parent::__construct('mailchimp-form', __('Mailchimp Form'), $widget_ops);
		$this->alt_option_name = 'widget_mailchimp_form';
	}

	/**
	 * Outputs the content for the widget instance.
	 *
	 * @since 2.8.0
	 * @access public
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Recent Comments widget instance.
	 */
	public function widget( $args, $instance ) {

		// Extract args
		extract( $args );
		
		$output = '';

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : '';

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		
		$name_field       = ! empty( $instance['name_field'] ) ? true : false;
		$description      = isset( $instance['description'] ) ? $instance['description'] : '';
		$form_action      = isset( $instance['form_action'] ) ? $instance['form_action'] : '';
		$button_text      = ! empty( $instance['button_text'] ) ? $instance['button_text'] : __( 'Subscribe', 'ctbwp' );

		$output .= $args['before_widget'];
		if ( $title ) {
			$output .= $args['before_title'] . $title . $args['after_title'];
		}

		if ( $form_action ) {

		$output .= '<div class="ctbwp-newsletter-form">';
				
			if ( $description ) {
				$output .= sprintf ( 
					'<p class="ctbwp-newsletter-widget-description">%s</p>',
					esc_html ( $description )
				);
			}

			$output .= '<form action="' . $form_action .'" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>';

				if ( $name_field ) {
					$output .= '<input type="text" placeholder="' . __( 'Your first name', 'ctbwp' ) .'" name="FNAME" id="mce-FNAME">';
				}

				$output .= '<input type="email" placeholder="' . __( 'Your email address', 'ctbwp' ) . '" name="EMAIL" id="mce-EMAIL">';

					// echo apply_filters( 'ctbwp_mailchimp_widget_form_extras', null );

				$output .= '<button type="submit" value="" name="subscribe">' . $button_text . '</button>';
				
			$output .= '</form>';

		$output .= '</div>';

		} else {
			
			$output = __ ( 'Don\'t forget to add your Mailchimp form action link.', 'ctbwp' );

		}

		echo $output;
		// After widget hook
		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 * @since 1.0.0
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                     = $old_instance;
		$instance['title']            = strip_tags( $new_instance['title'] );
		$instance['description']      = $new_instance['description'];
		$instance['form_action']      = strip_tags( $new_instance['form_action'] );
		$instance['button_text']      = strip_tags( $new_instance['button_text'] );
		$instance['name_field']       = $new_instance['name_field'] ? 1 : 0;
		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 * @since 1.0.0
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( ( array ) $instance, array(
			'title'                 => '',
			'description'           => '',
			'form_action'           => '//catapultthemes.us6.list-manage.com/subscribe/post?u=36e047ff4af9438d7db81168e&amp;id=8782023345',
			'button_text'           => __( 'Subscribe', 'ctbwp' ),
			'name_field'            => 0

		) );
		$title                 = esc_attr( $instance['title'] );
		$description           = esc_attr( $instance['description'] );
		$form_action           = esc_attr( $instance['form_action'] );
		$name_field            = $instance['name_field'];
		$button_text           = esc_attr( $instance['button_text'] );
		?>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'ctbwp' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title','ctbwp' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'form_action' ); ?>"><?php _e( 'Form Action', 'ctbwp' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'form_action' ); ?>" name="<?php echo $this->get_field_name( 'form_action' ); ?>" type="text" value="<?php echo $form_action; ?>" />
			<span style="display:block;padding:5px 0" class="description">
				<a href="http://docs.shopify.com/support/configuration/store-customization/where-do-i-get-my-mailchimp-form-action?ref=ctbwpplorer" target="_blank"><?php _e( 'Learn more', 'ctbwp' ); ?>&rarr;</a>
			</span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'description' ); ?>"><?php _e( 'Description:','ctbwp' ); ?></label>
			<textarea class="widefat" rows="5" cols="20" id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>"><?php echo $instance['description']; ?></textarea>
		</p>
		<p>
			<input id="<?php echo $this->get_field_id( 'name_field' ); ?>" name="<?php echo $this->get_field_name( 'name_field','ctbwp' ); ?>" <?php checked( $name_field, 1, true ); ?> type="checkbox" />
			<label for="<?php echo $this->get_field_id( 'name_field' ); ?>"><?php _e( 'Display Name Field?', 'ctbwp' ); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'button_text' ); ?>"><?php _e( 'Button Text', 'ctbwp' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'button_text' ); ?>" name="<?php echo $this->get_field_name( 'button_text','ctbwp' ); ?>" type="text" value="<?php echo $button_text; ?>" />
		</p>
		<?php
	}
}
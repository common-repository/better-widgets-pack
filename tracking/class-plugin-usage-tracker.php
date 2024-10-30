<?php
/**
 * This is the class that sends all the data back to the home site
 * It also handles opting in and deactivation
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//require_once dirname( PUT_THIS_PLUGIN ) . '/tracking/optin.php';
//require_once dirname( PUT_THIS_PLUGIN ) . '/tracking/goodbye.php';


if( ! class_exists( 'Plugin_Usage_Tracker') ) {
	
	class Plugin_Usage_Tracker {
		
		private $home_url = '';
		private $plugin_file = '';
		private $plugin_name = '';
		private $options = array();
		private $put_version = '1.1.0';
		private $require_optin = true;
		private $include_goodbye_form = true;
		
		/**
		 * Class constructor
		 *
		 * @param $_home_url				The URL to the site we're sending data to
		 * @param $_plugin_file				The file path for this plugin
		 * @param $_options					Plugin options to track
		 * @param $_require_optin			Whether user opt-in is required (always required on WordPress.org)
		 * @param $_include_goodbye_form	Whether to include a form when the user deactivates
		 */
		public function __construct( $_plugin_file, $_home_url, $_options, $_require_optin=true, $_include_goodbye_form=true ) {	

			$this->plugin_file = $_plugin_file;
			$this->home_url = trailingslashit( $_home_url );
			$this->plugin_name = basename( $this->plugin_file, '.php' );
			$this->options = $_options;
			$this->require_optin = $_require_optin;
			$this->include_goodbye_form = $_include_goodbye_form;

			// Get it going
			$this->init();
			
		}
		
		public function init() {
			
			// Schedule some tracking when activated
			register_activation_hook( $this->plugin_file, array( $this, 'schedule_tracking' ) );
			
			// Hook our do_tracking function to the weekly action
			add_action( 'put_do_weekly_action', array( $this, 'do_tracking' ) );

			// Use this action for local testing
		//	add_action( 'admin_init', array( $this, 'do_tracking' ) );
			 
			// Display the admin notice on activation
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
			// AJAX for dismissing admin notice
			add_action( 'wp_ajax_dismiss_admin_notice', array( $this, 'dismiss_admin_notice_callback' ) );

			// Deactivation
			add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), array( $this, 'filter_action_links' ) );
			add_action( 'admin_footer-plugins.php', array( $this, 'goodbye_ajax' ) );
			add_action( 'wp_ajax_goodbye_form', array( $this, 'goodbye_form_callback' ) );
			
			// Deactivation hook
			register_deactivation_hook( $this->plugin_file, array( $this, 'deactivate_this_plugin' ) );
			
		}
		
		/**
		 * First time that plugin loads
		 * Create scheduled event
		 *
		 * @since 1.0.0
		 */
		public function schedule_tracking() {
			wp_schedule_event( time(), 'daily', 'put_do_weekly_action' );
		}
		
		/**
		 * This is our function to get everything going
		 * Check that user has opted in
		 * Collect data
		 * Then send it back
		 *
		 * @since 1.0.0
		 */
		public function do_tracking() {
			// If the home site hasn't been defined, we just drop out. Nothing much we can do.
			if ( ! $this->home_url ) {
				return;
			}
	
			// Check to see if the user has opted in to tracking
			$allow_tracking = $this->get_is_tracking_allowed();
			if( ! $allow_tracking ) {
				return;
			}
	
			// Get our data
			$body = $this->get_data();

			// Send the data
			$this->send_data( $body );

		}
		
		/**
		 * Send the data to the home site
		 *
		 * @since 1.0.0
		 */
		public function send_data( $body ) {

			$request = wp_remote_post( 
				esc_url( $this->home_url . '?usage_tracker=hello' ),
				array(
					'method'      => 'POST',
					'timeout'     => 20,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking'    => true,
					'body'        => $body,
					'user-agent'  => 'PUT/1.0.0; ' . get_bloginfo( 'url' )
				)
			);
	
			if( is_wp_error( $request ) ) {
				return $request;
			}

		}
		
		/**
		 * Here we collect most of the data
		 * 
		 * @since 1.0.0
		 */
		public function get_data() {
	
			// Use this to pass error messages back if necessary
			$body['message'] = '';
	
			// Use this array to send data back
			$body = array(
				'plugin_slug'		=> sanitize_text_field( $this->plugin_name ),
				'url'				=> get_bloginfo( 'url' ),
				'email'				=> get_bloginfo( 'admin_email' ),
				'site_name' 		=> get_bloginfo( 'name' ),
				'site_version'		=> get_bloginfo( 'version' ),
				'site_language'		=> get_bloginfo( 'language' ),
				'charset'			=> get_bloginfo( 'charset' ),
				'put_version'		=> $this->put_version,
				'php_version'		=> phpversion(),
				'multisite'			=> is_multisite()
			);
	
			$body['server'] = isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '';

			// Retrieve current plugin information
			if( ! function_exists( 'get_plugins' ) ) {
				include ABSPATH . '/wp-admin/includes/plugin.php';
			}

			$plugins = array_keys( get_plugins() );
			$active_plugins = get_option( 'active_plugins', array() );

			foreach ( $plugins as $key => $plugin ) {
				if ( in_array( $plugin, $active_plugins ) ) {
					// Remove active plugins from list so we can show active and inactive separately
					unset( $plugins[$key] );
				}
			}

			$body['active_plugins'] = $active_plugins;
			$body['inactive_plugins'] = $plugins;
	
			// Check text direction
			$body['text_direction']	= 'LTR';
			if( is_rtl() ) {
				$body['text_direction']	= 'RTL';
			}
	
			/**
			 * Get our plugin data
			 * Currently we grab plugin name and version
			 * Or, return a message if the plugin data is not available
			 * @since 1.0.0
			 */
			$plugin = $this->plugin_data();
			if( empty( $plugin ) ) {
				// We can't find the plugin data
				// Send a message back to our home site
				$body['message'] .= __( 'We can\'t detect any plugin information. This is most probably because you have not included the code in the plugin main file.', 'plugin-usage-tracker' );
				$body['status'] = 'Data not found'; // Never translated
			} else {
				if( isset( $plugin['Name'] ) ) {
					$body['plugin'] = sanitize_text_field( $plugin['Name'] );
				}
				if( isset( $plugin['Version'] ) ) {
					$body['version'] = sanitize_text_field( $plugin['Version'] );
				}
				$body['status'] = 'Active'; // Never translated
			}

			/**
			 * Get our plugin options
			 * @since 1.0.0
			 */
			$options = $this->options;
			if( ! empty( $options ) && is_array( $options ) ) {
				$plugin_options = array();
				foreach( $options as $option ) {
					$fields = get_option( $option );
					// Check for permission to send this option
					if( isset( $fields['put_registered_setting'] ) ) {
						$plugin_options[esc_attr( $option )] = $fields;
					}
				}
			}
			$body['plugin_options'] = json_encode( $options ); // Returns array
			$body['plugin_options_fields'] = json_encode( $this->options ); // Returns object
	
			/**
			 * Get our theme data
			 * Currently we grab theme name and version
			 * @since 1.0.0
			 */
			$theme = wp_get_theme();
			if( $theme->Name ) {
				$body['theme'] = sanitize_text_field( $theme->Name );
			}
			if( $theme->Version ) {
				$body['theme_version'] = sanitize_text_field( $theme->Version );
			}
	
			// Add deactivation form data
			$body['deactivation_reason'] = get_option( 'put_deactivation_reason' );
			$body['deactivation_details'] = get_option( 'put_deactivation_details' );

			// Return the data
			return $body;
	
		}
		
		/**
		 * Return plugin data
		 * @since 1.0.0
		 */
		public function plugin_data() {
			// Being cautious here
			if( ! function_exists( 'get_plugin_data' ) ) {
				include ABSPATH . '/wp-admin/includes/plugin.php';
			}
			// Retrieve current plugin information
			$plugin = get_plugin_data( $this->plugin_file );
			return $plugin;
		}

		/**
		 * Deactivating plugin
		 * @since 1.0.0
		 */
		public function deactivate_this_plugin() {
			// Check to see if the user has opted in to tracking
			$allow_tracking = $this->get_is_tracking_allowed();
			if( ! $allow_tracking ) {
				return;
			}
			$body = $this->get_data();
			$body['status'] = 'Deactivated'; // Never translated
			$body['deactivated_date'] = time();
			$this->send_data( $body );
			// Clear scheduled update
			wp_clear_scheduled_hook( 'put_do_weekly_action' );
		}
		
		/**
		 * Is tracking allowed?
		 * @since 1.0.0
		 */
		public function get_is_tracking_allowed() {
			// The put_allow_tracking option is an array of plugin's that are being tracked
			$allow_tracking = get_option( 'put_allow_tracking' );
			// If this plugin is in the array, then tracking is allowed
			if( isset( $allow_tracking[$this->plugin_name] ) ) {
				return true;
			}
			return false;
		}
		
		/**
		 * Set if tracking is allowed
		 * Option is an array of all plugins with tracking permitted
		 * More than one plugin may be using the tracker
		 * @since 1.0.0
		 * @param $is_allowed	Boolean		true if tracking is allowed, false if not
		 */
		public function set_is_tracking_allowed( $is_allowed, $plugin=null ) {

			if( empty( $plugin ) ) {
				$plugin = $this->plugin_name;
			}
			// The put_allow_tracking option is an array of plugin's that are being tracked
			$allow_tracking = get_option( 'put_allow_tracking' );
			// If the user has agreed to allow tracking or if opt-in is not required
			if( $is_allowed || ! $this->require_optin ) {
				if( empty( $allow_tracking ) || ! is_array( $allow_tracking ) ) {
					// If nothing exists in the option yet, start a new array with the plugin name
					$allow_tracking = array( $plugin => $plugin );
				} else {
					// Else add the plugin name to the array
					$allow_tracking[$plugin] = $plugin;
				}
				
			} else {
				if( isset( $allow_tracking[$plugin] ) ) {
					unset( $allow_tracking[$plugin] );
				}
			}
			update_option( 'put_allow_tracking', $allow_tracking );
		}
		
		/**
		 * Set if we should block the opt-in notice for this plugin
		 * Option is an array of all plugins that have received a response from the user
		 * @since 1.0.0
		 */
		public function update_block_notice( $plugin=null ) {
			if( empty( $plugin ) ) {
				$plugin = $this->plugin_name;
			}
			$block_notice = get_option( 'put_block_notice' );
			if( empty( $block_notice ) || ! is_array( $block_notice ) ) {
				// If nothing exists in the option yet, start a new array with the plugin name
				$block_notice = array( $plugin => $plugin );
			} else {
				// Else add the plugin name to the array
				$block_notice[$plugin] = $plugin;
			}
			update_option( 'put_block_notice', $block_notice );
		}
		
		/**
		 * Display the admin notice to users to allow them to opt in
		 *
		 * @since 1.0.0
		 */
		public function admin_notice() {
			
			// Check for plugin args
			if( isset( $_GET['plugin'] ) && isset( $_GET['plugin_action'] ) ) {
				$plugin = sanitize_text_field( $_GET['plugin'] );
				$action = sanitize_text_field( $_GET['plugin_action'] );
				if( $action == 'yes' ) {
					$this->set_is_tracking_allowed( true, $plugin );
					$this->do_tracking(); // Run this straightaway
				} else {
					$this->set_is_tracking_allowed( false, $plugin );
				}
				$this->update_block_notice( $plugin );
			}
			
			// Check whether to block the notice, e.g. because we're in a local environment
			// put_block_notice works the same as put_allow_tracking, an array of plugin names
			$block_notice = get_option( 'put_block_notice' );
			if( isset( $block_notice[$this->plugin_name] ) ) {
				return;
			}
	
			// Check whether opt-in is required
			// If not, then tracking is allowed
			if( ! $this->require_optin ) {
				$this->set_is_tracking_allowed( true );
				return;
			}
	
			// Check if tracking is allowed - either yes or no
			// If this option has been set either way then no need to display the notice
			if ( ! $this->get_is_tracking_allowed() ) {
	//			return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// @credit EDD (Thanks Pippin)
			// Don't bother asking user to opt in if they're in local dev
			if ( stristr( network_site_url( '/' ), 'dev' ) !== false || stristr( network_site_url( '/' ), 'localhost' ) !== false || stristr( network_site_url( '/' ), ':8888' ) !== false ) {
				$this->update_block_notice();
			} else {
				// Display the notice requesting permission to track
				// Retrieve current plugin information
				$plugin = $this->plugin_data();
				$plugin_name = $plugin['Name'];
				$url_yes = add_query_arg( array(
					'plugin' 		=> $this->plugin_name,
					'plugin_action'	=> 'yes'
				) );
				$url_no = add_query_arg( array(
					'plugin' 		=> $this->plugin_name,
					'plugin_action'	=> 'no'
				) );
				$notice_text = __( 'Thank you for installing our plugin. We would like to track its usage on your site. We don\'t record any sensitive data, only information regarding the WordPress environment and plugin settings, which we will use to help us make improvements to the plugin. Tracking is completely optional.', 'plugin-usage-tracker' ); 
				$notice_text = apply_filters( 'put_notice_text_' . esc_attr( $this->plugin_name ), $notice_text ); ?>
				<div class="notice notice-info updated put-dismiss-notice">
					<p><?php echo '<strong>' . esc_html( $plugin_name ) . '</strong>'; ?></p>
					<p><?php echo esc_html( $notice_text ); ?></p>
					<p>
						<a href="<?php echo esc_url( $url_yes ); ?>" data-putnotice="yes" class="button-secondary"><?php _e( 'Allow', 'plugin-usage-tracker' ); ?></a>
						<a href="<?php echo esc_url( $url_no ); ?>" data-putnotice="no" class="button-secondary"><?php _e( 'Do Not Allow', 'plugin-usage-tracker' ); ?></a>
					</p>
				</div>
				<script>
					jQuery(document).ready(function($){
						$('body').on('click','.put-dismiss',function(){
							var notice = $(this).attr('data-putnotice');
							var data = {
								'action': 'dismiss_admin_notice',
								'notice': notice,
								'plugin': '<?php echo $this->plugin_name; ?>',
								'file': '<?php echo $this->plugin_file; ?>',
								'security': "<?php echo wp_create_nonce( 'put_dismiss_notice' ); ?>",
							}
							$.post(ajaxurl,data,function(response){
								alert(response);
							});
							$('.put-dismiss-notice').fadeOut();
						});
					});
				</script>
			<?php
			}
			
		}
		
		/**
		 * Update put_allow_tracking option with the name of this plugin
		 * @since 1.0.0
		*/
		public function dismiss_admin_notice_callback() {
			check_ajax_referer( 'put_dismiss_notice', 'security' );
			$notice = sanitize_text_field( $_POST['notice'] );
			$plugin = sanitize_text_field( $_POST['plugin'] );
			$file = sanitize_text_field( $_POST['file'] );
			$response = 'ok';
			if( $notice == 'yes' ) {
				$this->set_is_tracking_allowed( true, $plugin );
				$this->do_tracking( $file ); // Run this straightaway
			}
			// Whatever the answer, the notice isn't required any longer for this plugin
			$this->update_block_notice( $plugin );
			echo $response;
			wp_die();
		}
		
		/**
		 * Filter the deactivation link to allow us to present a form when the user deactivates the plugin
		 * @since 1.0.0
		 */
		public function filter_action_links( $links ) {
			// Check to see if the user has opted in to tracking
			if( ! $this->get_is_tracking_allowed() ) {
				return $links;
			}
			if( isset( $links['deactivate'] ) && $this->include_goodbye_form ) {
				$deactivation_link = $links['deactivate'];
				// Insert an onClick action to allow form before deactivating
				$deactivation_link = str_replace( '<a ', '<div class="put-goodbye-form-wrapper"><span class="put-goodbye-form" id="put-goodbye-form-' . esc_attr( $this->plugin_name ) . '"></span></div><a onclick="javascript:event.preventDefault();" id="put-goodbye-link-' . esc_attr( $this->plugin_name ) . '" ', $deactivation_link );
				$links['deactivate'] = $deactivation_link;
			}
			return $links;
		}
		
		/*
		 * Form text strings
		 * These are non-filterable and used as fallback in case filtered strings aren't set correctly
		 * @since 1.0.0
		 */
		public function form_default_text() {
			$form = array();
			$form['heading'] = __( 'Sorry to see you go', 'plugin-usage-tracker' );
			$form['body'] = __( 'Before you deactivate the plugin, would you quickly give us your reason for doing so?', 'plugin-usage-tracker' );
			$form['options'] = array(
				__( 'Set up is too difficult', 'plugin-usage-tracker' ),
				__( 'Lack of documentation', 'plugin-usage-tracker' ),
				__( 'Not the features I wanted', 'plugin-usage-tracker' ),
				__( 'Found a better plugin', 'plugin-usage-tracker' ),
				__( 'Installed by mistake', 'plugin-usage-tracker' ),
				__( 'Only required temporarily', 'plugin-usage-tracker' ),
				__( 'Didn\'t work', 'plugin-usage-tracker' )
			);
			$form['details'] = __( 'Details (optional)', 'plugin-usage-tracker' );
			return $form;
		}
		
		/**
		 * Form text strings
		 * These can be filtered
		 * The filter hook must be unique to the plugin
		 * @since 1.0.0
		 */
		public function form_filterable_text() {
			$form = $this->form_default_text();
			return apply_filters( 'put_form_text_' . esc_attr( $this->plugin_name ), $form );
		}
		
		/**
		 * Form text strings
		 * These can be filtered
		 * @since 1.0.0
		 */
		public function goodbye_ajax() {
			// Get our strings for the form
			$form = $this->form_filterable_text();
			if( ! isset( $form['heading'] ) || ! isset( $form['body'] ) || ! isset( $form['options'] ) || ! is_array( $form['options'] ) || ! isset( $form['details'] ) ) {
				// If the form hasn't been filtered correctly, we revert to the default form
				$form = $this->form_default_text();
			}
			// Build the HTML to go in the form
			$html = '<div class="put-goodbye-form-head"><strong>' . esc_html( $form['heading'] ) . '</strong></div>';
			$html .= '<div class="put-goodbye-form-body"><p>' . esc_html( $form['body'] ) . '</p>';
			if( is_array( $form['options'] ) ) {
				$html .= '<div class="put-goodbye-options"><p>';
				foreach( $form['options'] as $option ) {
					$html .= '<input type="checkbox" name="put-goodbye-options[]" value="' . esc_html( $option ) . '"> <label>' . esc_html( $option ) . '</label><br>';
				}
				$html .= '</p><label for="put-goodbye-reasons">' . esc_html( $form['details'] ) .'</label><textarea name="put-goodbye-reasons" id="put-goodbye-reasons" rows="2" style="width:100%"></textarea>';
				$html .= '</div><!-- .put-goodbye-options -->';
			}
			$html .= '</div><!-- .put-goodbye-form-body -->';
			?>
			<div class="put-goodbye-form-bg"></div>
			<style type="text/css">
				.put-form-active .put-goodbye-form-bg {
					background: rgba( 0, 0, 0, .5 );
					position: fixed;
					top: 0;
					left: 0;
					width: 100%;
					height: 100%;
				}
				.put-goodbye-form-wrapper {
					position: relative;
					z-index: 999;
					display: none;
				}
				.put-form-active .put-goodbye-form-wrapper {
					display: block;
				}
				.put-goodbye-form {
					display: none;
				}
				.put-form-active .put-goodbye-form {
					position: absolute;
				    bottom: 30px;
				    left: 0;
					max-width: 400px;
				    background: #fff;
					white-space: normal;
				}
				.put-goodbye-form-head {
					background: #0073aa;
					color: #fff;
					padding: 8px 18px;
				}
				.put-goodbye-form-body {
					padding: 8px 18px;
					color: #444;
				}
				.put-goodbye-form-footer {
					padding: 8px 18px;
				}
			</style>
			<script>
				jQuery(document).ready(function($){
					$("#put-goodbye-link-<?php echo esc_attr( $this->plugin_name ); ?>").on("click",function(){
						// We'll send the user to this deactivation link when they've completed or dismissed the form
						var url = document.getElementById("put-goodbye-link-<?php echo esc_attr( $this->plugin_name ); ?>");
						$('body').toggleClass('put-form-active');
						$("#put-goodbye-form-<?php echo esc_attr( $this->plugin_name ); ?>").fadeIn();
						$("#put-goodbye-form-<?php echo esc_attr( $this->plugin_name ); ?>").html( '<?php echo $html; ?>' + '<div class="put-goodbye-form-footer"><p><a id="put-submit-form" class="button primary" href="#">Submit and Deactivate</a>&nbsp;<a class="secondary button" href="'+url+'">Just Deactivate</a></p></div>');
						$('#put-submit-form').on('click', function(e){
							e.preventDefault();
							var values = new Array();
							$.each($("input[name='put-goodbye-options[]']:checked"), function(){
								values.push($(this).val());
							});
							var details = $('#put-goodbye-reasons').val();
							var data = {
								'action': 'goodbye_form',
								'values': values,
								'details': details,
								'security': "<?php echo wp_create_nonce ( 'put_goodbye_form' ); ?>",
								'dataType': "json"
							}
							$.post(
								ajaxurl,
								data,
								function(response){
									// Redirect to original deactivation URL
									window.location.href = url;
								}
							);
						});
						// If we click outside the form, the form will close
						$('.put-goodbye-form-bg').on('click',function(){
							$("#put-goodbye-form-<?php echo esc_attr( $this->plugin_name ); ?>").fadeOut();
							$('body').removeClass('put-form-active');
						});
					});
				});
			</script>
		<?php }
		
		/**
		 * AJAX callback when the form is submitted
		 * @since 1.0.0
		 */
		public function goodbye_form_callback() {
			check_ajax_referer( 'put_goodbye_form', 'security' );
			if( isset( $_POST['values'] ) ) {
				$values = json_encode( wp_unslash( $_POST['values'] ) );
				update_option( 'put_deactivation_reason', $values );
			}
			if( isset( $_POST['details'] ) ) {
				$details = sanitize_text_field( $_POST['details'] );
				update_option( 'put_deactivation_details', $details );
			}
			$this->do_tracking(); // Run this straightaway
			echo 'success';
			wp_die();
		}
		
	}
	
}
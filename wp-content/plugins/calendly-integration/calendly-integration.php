<?php
/**
 * Plugin Name: Calendly Integration
 * Plugin URI: https://calendly.com
 * Description: Integrate Calendly booking widget into your WordPress site. Display booking forms on your homepage and anywhere using shortcodes.
 * Version: 1.0.0
 * Author: Ramandeep
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: calendly-integration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'CALENDLY_INTEGRATION_VERSION', '1.0.0' );
define( 'CALENDLY_INTEGRATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CALENDLY_INTEGRATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Calendly Integration Class
 */
class Calendly_Integration {
	
	/**
	 * Instance of this class
	 */
	private static $instance = null;
	
	/**
	 * Flag to track if Calendly was already added to content
	 */
	private static $added_to_content = false;
	
	/**
	 * Get instance of this class
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Enqueue Calendly embed script
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_calendly_script' ) );
		
		// Register shortcode
		add_shortcode( 'calendly', array( $this, 'calendly_shortcode' ) );
		
		// Add to homepage
		add_action( 'wp', array( $this, 'add_to_homepage' ) );
		
		// Admin menu
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		// AJAX endpoints for API calls
		add_action( 'wp_ajax_calendly_get_availability', array( $this, 'ajax_get_availability' ) );
		add_action( 'wp_ajax_nopriv_calendly_get_availability', array( $this, 'ajax_get_availability' ) );
		add_action( 'wp_ajax_calendly_get_time_slots', array( $this, 'ajax_get_time_slots' ) );
		add_action( 'wp_ajax_nopriv_calendly_get_time_slots', array( $this, 'ajax_get_time_slots' ) );
	}
	
	/**
	 * Enqueue Calendly embed script
	 */
	public function enqueue_calendly_script() {
		wp_enqueue_script(
			'calendly-embed',
			'https://assets.calendly.com/assets/external/widget.js',
			array(),
			CALENDLY_INTEGRATION_VERSION,
			true
		);
		wp_enqueue_style(
			'calendly-integration',
			CALENDLY_INTEGRATION_PLUGIN_URL . 'assets/css/style.css',
			array(),
			CALENDLY_INTEGRATION_VERSION
		);
		// Ensure jQuery is loaded
		wp_enqueue_script( 'jquery' );
		
		wp_enqueue_script(
			'calendly-custom',
			CALENDLY_INTEGRATION_PLUGIN_URL . 'assets/js/calendly-custom.js',
			array( 'jquery', 'calendly-embed' ),
			CALENDLY_INTEGRATION_VERSION,
			true
		);
		
		// Localize script for AJAX
		wp_localize_script(
			'calendly-custom',
			'calendlyAjax',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'calendly_nonce' ),
			)
		);
	}
	
	/**
	 * Calendly shortcode handler
	 * 
	 * Usage: [calendly url="your-calendly-url" height="600"]
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function calendly_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'url'         => $this->get_default_calendly_url(),
				'height'      => '650',
				'width'       => '100%',
				'text'        => '',
				'title'       => get_option( 'calendly_homepage_title', 'Pick Your Slot Instantly' ),
				'description' => get_option( 'calendly_homepage_description', 'Book your masterclass or one-to-one session with real-time availability. Flexible scheduling to fit your lifestyle.' ),
				'show_header' => 'true',
				'type'        => 'custom', // 'custom' or 'embed'
			),
			$atts,
			'calendly'
		);
		
		// Validate Calendly URL
		if ( empty( $atts['url'] ) ) {
			return '<p>' . esc_html__( 'Please configure your Calendly URL in the plugin settings.', 'calendly-integration' ) . '</p>';
		}
		
		// Ensure URL is a valid Calendly URL
		if ( strpos( $atts['url'], 'calendly.com' ) === false ) {
			$atts['url'] = 'https://calendly.com/' . sanitize_text_field( $atts['url'] );
		}
		
		// Ensure URL starts with https://
		if ( strpos( $atts['url'], 'http' ) !== 0 ) {
			$atts['url'] = 'https://' . $atts['url'];
		}
		
		// Extract event type slug from URL
		$event_slug = $this->extract_event_slug( $atts['url'] );
		
		$output = '<div class="calendly-booking-wrapper" data-calendly-url="' . esc_url( $atts['url'] ) . '" data-event-slug="' . esc_attr( $event_slug ) . '">';
		
		// Show header if enabled
		if ( $atts['show_header'] !== 'false' && ( ! empty( $atts['title'] ) || ! empty( $atts['description'] ) ) ) {
			$output .= '<div class="calendly-booking-header">';
			if ( ! empty( $atts['title'] ) ) {
				$output .= '<h2 class="calendly-booking-title">' . esc_html( $atts['title'] ) . '</h2>';
			}
			if ( ! empty( $atts['description'] ) ) {
				$output .= '<p class="calendly-booking-description">' . esc_html( $atts['description'] ) . '</p>';
			}
			$output .= '</div>';
		}
		
		// Custom calendar component
		if ( $atts['type'] === 'custom' ) {
			$output .= $this->render_custom_calendar( $atts['url'], $event_slug );
		} else {
			// Legacy embed widget
			if ( ! empty( $atts['text'] ) ) {
				$output .= '<div class="calendly-text">' . esc_html( $atts['text'] ) . '</div>';
			}
			$output .= '<div class="calendly-widget-wrapper">';
			$output .= '<div class="calendly-inline-widget" data-url="' . esc_url( $atts['url'] ) . '" style="min-width:320px;height:' . esc_attr( $atts['height'] ) . 'px;width:' . esc_attr( $atts['width'] ) . ';"></div>';
			$output .= '</div>';
		}
		
		$output .= '</div>'; // Close booking-wrapper
		
		return $output;
	}
	
	/**
	 * Extract event slug from Calendly URL
	 */
	private function extract_event_slug( $url ) {
		$parsed = parse_url( $url );
		if ( isset( $parsed['path'] ) ) {
			$path = trim( $parsed['path'], '/' );
			$parts = explode( '/', $path );
			if ( count( $parts ) >= 2 ) {
				return $parts[1]; // Return event type slug
			}
		}
		return '';
	}
	
	/**
	 * Render custom calendar component
	 */
	private function render_custom_calendar( $calendly_url, $event_slug ) {
		$output = '<div class="calendly-custom-calendar">';
		$output .= '<div class="calendly-calendar-grid" id="calendly-calendar-grid">';
		$output .= '<div class="calendly-calendar-loading">' . esc_html__( 'Loading available dates...', 'calendly-integration' ) . '</div>';
		$output .= '</div>';
		$output .= '<div class="calendly-time-slots-container" id="calendly-time-slots" style="display:none;">';
		$output .= '<h3 class="calendly-selected-date-title"></h3>';
		$output .= '<div class="calendly-time-slots-grid"></div>';
		$output .= '</div>';
		$output .= '<div class="calendly-booking-actions">';
		$output .= '<button type="button" class="calendly-button calendly-button-primary" id="calendly-open-booking" style="display:none;">' . esc_html__( 'Open Booking', 'calendly-integration' ) . '</button>';
		$output .= '</div>';
		$output .= '</div>';
		return $output;
	}
	
	/**
	 * Add Calendly to homepage
	 */
	public function add_to_homepage() {
		// Check if we're on the homepage
		if ( is_front_page() || is_home() ) {
			$calendly_url = $this->get_default_calendly_url();
			
			if ( ! empty( $calendly_url ) && get_option( 'calendly_show_on_homepage', true ) ) {
				// Add Calendly widget to homepage content (for classic themes)
				add_filter( 'the_content', array( $this, 'append_to_homepage_content' ), 20 );
				
				// Add it before footer (works for both classic and block themes)
				add_action( 'wp_footer', array( $this, 'display_homepage_calendly' ), 5 );
			}
		}
	}
	
	/**
	 * Append Calendly to homepage content
	 */
	public function append_to_homepage_content( $content ) {
		if ( ( is_front_page() || is_home() ) && ! self::$added_to_content ) {
			$calendly_html = do_shortcode( '[calendly]' );
			$content .= '<div class="calendly-homepage-section">' . $calendly_html . '</div>';
			self::$added_to_content = true;
		}
		return $content;
	}
	
	/**
	 * Display Calendly on homepage footer
	 * This works for block themes and classic themes
	 */
	public function display_homepage_calendly() {
		if ( is_front_page() || is_home() ) {
			$calendly_url = $this->get_default_calendly_url();
			if ( ! empty( $calendly_url ) && get_option( 'calendly_show_on_homepage', true ) ) {
				// Only add if not already added to content (for block themes)
				if ( ! self::$added_to_content ) {
					echo '<div class="calendly-homepage-container">';
					echo do_shortcode( '[calendly]' );
					echo '</div>';
				}
			}
		}
	}
	
	/**
	 * Get default Calendly URL from settings
	 */
	private function get_default_calendly_url() {
		return get_option( 'calendly_url', '' );
	}
	
	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Calendly Integration', 'calendly-integration' ),
			__( 'Calendly', 'calendly-integration' ),
			'manage_options',
			'calendly-integration',
			array( $this, 'render_admin_page' )
		);
	}
	
	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'calendly_settings', 'calendly_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
		register_setting( 'calendly_settings', 'calendly_show_on_homepage', array( 'default' => true ) );
		register_setting( 'calendly_settings', 'calendly_homepage_text', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'calendly_settings', 'calendly_homepage_title', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'calendly_settings', 'calendly_homepage_description', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
		register_setting( 'calendly_settings', 'calendly_api_token', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	}
	
	/**
	 * Render admin settings page
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Save settings
		if ( isset( $_POST['calendly_save_settings'] ) && check_admin_referer( 'calendly_save_settings' ) ) {
			update_option( 'calendly_url', sanitize_text_field( $_POST['calendly_url'] ) );
			update_option( 'calendly_show_on_homepage', isset( $_POST['calendly_show_on_homepage'] ) );
			update_option( 'calendly_homepage_text', sanitize_text_field( $_POST['calendly_homepage_text'] ) );
			update_option( 'calendly_homepage_title', sanitize_text_field( $_POST['calendly_homepage_title'] ) );
			update_option( 'calendly_homepage_description', sanitize_textarea_field( $_POST['calendly_homepage_description'] ) );
			update_option( 'calendly_api_token', sanitize_text_field( $_POST['calendly_api_token'] ) );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved!', 'calendly-integration' ) . '</p></div>';
		}
		
		$calendly_url = get_option( 'calendly_url', '' );
		$show_on_homepage = get_option( 'calendly_show_on_homepage', true );
		$homepage_text = get_option( 'calendly_homepage_text', '' );
		$homepage_title = get_option( 'calendly_homepage_title', 'Pick Your Slot Instantly' );
		$homepage_description = get_option( 'calendly_homepage_description', 'Book your masterclass or one-to-one session with real-time availability. Flexible scheduling to fit your lifestyle.' );
		$api_token = get_option( 'calendly_api_token', '' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'calendly_save_settings' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="calendly_url"><?php esc_html_e( 'Calendly URL', 'calendly-integration' ); ?></label>
						</th>
						<td>
							<input type="text" 
								   id="calendly_url" 
								   name="calendly_url" 
								   value="<?php echo esc_attr( $calendly_url ); ?>" 
								   class="regular-text" 
								   placeholder="https://calendly.com/your-username/meeting-type" />
							<p class="description">
								<?php esc_html_e( 'Enter your Calendly URL. You can find this in your Calendly account under "Share" > "Add to website".', 'calendly-integration' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="calendly_api_token"><?php esc_html_e( 'Calendly API Token (Optional)', 'calendly-integration' ); ?></label>
						</th>
						<td>
							<input type="password" 
								   id="calendly_api_token" 
								   name="calendly_api_token" 
								   value="<?php echo esc_attr( $api_token ); ?>" 
								   class="regular-text" 
								   placeholder="Your Calendly API token" />
							<p class="description">
								<?php esc_html_e( 'Optional: Enter your Calendly API token for real-time availability. Get it from https://calendly.com/integrations/api_webhooks. Leave empty to use simulated availability.', 'calendly-integration' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="calendly_show_on_homepage"><?php esc_html_e( 'Show on Homepage', 'calendly-integration' ); ?></label>
						</th>
						<td>
							<input type="checkbox" 
								   id="calendly_show_on_homepage" 
								   name="calendly_show_on_homepage" 
								   value="1" 
								   <?php checked( $show_on_homepage, true ); ?> />
							<label for="calendly_show_on_homepage">
								<?php esc_html_e( 'Automatically display Calendly widget on the homepage', 'calendly-integration' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="calendly_homepage_title"><?php esc_html_e( 'Booking Title', 'calendly-integration' ); ?></label>
						</th>
						<td>
							<input type="text" 
								   id="calendly_homepage_title" 
								   name="calendly_homepage_title" 
								   value="<?php echo esc_attr( $homepage_title ); ?>" 
								   class="regular-text" 
								   placeholder="<?php esc_attr_e( 'Pick Your Slot Instantly', 'calendly-integration' ); ?>" />
							<p class="description">
								<?php esc_html_e( 'Main title displayed above the booking widget.', 'calendly-integration' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="calendly_homepage_description"><?php esc_html_e( 'Booking Description', 'calendly-integration' ); ?></label>
						</th>
						<td>
							<textarea id="calendly_homepage_description" 
									  name="calendly_homepage_description" 
									  rows="3" 
									  class="large-text" 
									  placeholder="<?php esc_attr_e( 'Book your masterclass or one-to-one session with real-time availability. Flexible scheduling to fit your lifestyle.', 'calendly-integration' ); ?>"><?php echo esc_textarea( $homepage_description ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Description text displayed below the title.', 'calendly-integration' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="calendly_homepage_text"><?php esc_html_e( 'Legacy Text (Optional)', 'calendly-integration' ); ?></label>
						</th>
						<td>
							<input type="text" 
								   id="calendly_homepage_text" 
								   name="calendly_homepage_text" 
								   value="<?php echo esc_attr( $homepage_text ); ?>" 
								   class="regular-text" 
								   placeholder="<?php esc_attr_e( 'Schedule a meeting with us!', 'calendly-integration' ); ?>" />
							<p class="description">
								<?php esc_html_e( 'Optional legacy text field (deprecated - use Title and Description instead).', 'calendly-integration' ); ?>
							</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button( __( 'Save Settings', 'calendly-integration' ), 'primary', 'calendly_save_settings' ); ?>
			</form>
			
			<hr>
			
			<h2><?php esc_html_e( 'Shortcode Usage', 'calendly-integration' ); ?></h2>
			<p><?php esc_html_e( 'You can also display the Calendly widget anywhere on your site using the shortcode:', 'calendly-integration' ); ?></p>
			<code>[calendly]</code>
			<p><?php esc_html_e( 'Or with custom options:', 'calendly-integration' ); ?></p>
			<code>[calendly url="https://calendly.com/your-username/meeting-type" height="700" text="Schedule your appointment"]</code>
			
			<h3><?php esc_html_e( 'Shortcode Parameters', 'calendly-integration' ); ?></h3>
			<ul>
				<li><strong>url</strong>: <?php esc_html_e( 'Your Calendly URL (optional, uses default if not provided)', 'calendly-integration' ); ?></li>
				<li><strong>height</strong>: <?php esc_html_e( 'Height of the widget in pixels (default: 650)', 'calendly-integration' ); ?></li>
				<li><strong>width</strong>: <?php esc_html_e( 'Width of the widget (default: 100%)', 'calendly-integration' ); ?></li>
				<li><strong>title</strong>: <?php esc_html_e( 'Custom title for the booking section', 'calendly-integration' ); ?></li>
				<li><strong>description</strong>: <?php esc_html_e( 'Custom description text', 'calendly-integration' ); ?></li>
				<li><strong>show_header</strong>: <?php esc_html_e( 'Show/hide header section (true/false, default: true)', 'calendly-integration' ); ?></li>
				<li><strong>text</strong>: <?php esc_html_e( 'Legacy text field (deprecated)', 'calendly-integration' ); ?></li>
			</ul>
		</div>
		<?php
	}
	
	/**
	 * AJAX handler to get availability
	 */
	public function ajax_get_availability() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'calendly_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'calendly-integration' ) ) );
			return;
		}
		
		$calendly_url = isset( $_POST['calendly_url'] ) ? sanitize_text_field( $_POST['calendly_url'] ) : '';
		$event_slug = isset( $_POST['event_slug'] ) ? sanitize_text_field( $_POST['event_slug'] ) : '';
		
		if ( empty( $calendly_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Calendly URL is required', 'calendly-integration' ) ) );
			return;
		}
		
		// Generate calendar dates for next 2 weeks
		$dates = $this->generate_calendar_dates();
		
		// For now, mark some dates as available (in real implementation, fetch from Calendly API)
		// In production, you would call Calendly API here
		$available_dates = $this->get_available_dates_from_api( $calendly_url, $event_slug, $dates );
		
		wp_send_json_success( array(
			'dates' => $dates,
			'available_dates' => $available_dates,
		) );
	}
	
	/**
	 * AJAX handler to get time slots for a specific date
	 */
	public function ajax_get_time_slots() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'calendly_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'calendly-integration' ) ) );
			return;
		}
		
		$calendly_url = isset( $_POST['calendly_url'] ) ? sanitize_text_field( $_POST['calendly_url'] ) : '';
		$event_slug = isset( $_POST['event_slug'] ) ? sanitize_text_field( $_POST['event_slug'] ) : '';
		$date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
		
		if ( empty( $calendly_url ) || empty( $date ) ) {
			wp_send_json_error( array( 'message' => __( 'Calendly URL and date are required', 'calendly-integration' ) ) );
			return;
		}
		
		// Fetch time slots from Calendly API
		$time_slots = $this->get_time_slots_from_api( $calendly_url, $event_slug, $date );
		
		wp_send_json_success( array(
			'time_slots' => $time_slots,
			'date' => $date,
		) );
	}
	
	/**
	 * Generate calendar dates for display
	 */
	private function generate_calendar_dates() {
		$dates = array();
		$today = new DateTime();
		$start_date = clone $today;
		
		// Start from today or next Monday
		$day_of_week = (int) $start_date->format( 'w' );
		if ( $day_of_week === 0 ) {
			$day_of_week = 7; // Sunday = 7
		}
		$days_to_monday = $day_of_week - 1;
		$start_date->modify( '-' . $days_to_monday . ' days' );
		
		// Generate 14 days (2 weeks)
		for ( $i = 0; $i < 14; $i++ ) {
			$current_date = clone $start_date;
			$current_date->modify( '+' . $i . ' days' );
			
			$dates[] = array(
				'date' => $current_date->format( 'Y-m-d' ),
				'day' => $current_date->format( 'D' ),
				'day_short' => $current_date->format( 'D' ),
				'day_number' => (int) $current_date->format( 'd' ),
				'day_name' => $current_date->format( 'l' ),
			);
		}
		
		return $dates;
	}
	
	/**
	 * Get available dates from Calendly API
	 * Note: This is a placeholder - implement actual API call
	 */
	private function get_available_dates_from_api( $calendly_url, $event_slug, $dates ) {
		// In production, you would:
		// 1. Extract username from URL
		// 2. Call Calendly API: GET /users/{username}/event_types
		// 3. Get event type UUID
		// 4. Call: GET /event_type_available_times?event_type={uuid}&start_time={start}&end_time={end}
		// 5. Parse available dates
		
		// For now, mark multiple dates as available for testing
		// Mark dates that are not weekends (Saturday=6, Sunday=0) and not in the past
		$available = array();
		$today = new DateTime();
		$today->setTime(0, 0, 0);
		
		foreach ( $dates as $date_info ) {
			$date_obj = new DateTime( $date_info['date'] );
			$date_obj->setTime(0, 0, 0);
			
			// Skip past dates
			if ( $date_obj < $today ) {
				continue;
			}
			
			// Get day of week (0=Sunday, 6=Saturday)
			$day_of_week = (int) $date_obj->format( 'w' );
			
			// Mark weekdays (Monday-Friday) as available
			// Also include some weekends for better UX
			if ( $day_of_week >= 1 && $day_of_week <= 5 ) {
				// Monday to Friday - all available
				$available[] = $date_info['date'];
			} elseif ( $day_of_week == 6 || $day_of_week == 0 ) {
				// Saturday or Sunday - mark some as available
				// Mark every other weekend day
				if ( $date_info['day_number'] % 2 == 0 ) {
					$available[] = $date_info['date'];
				}
			}
		}
		
		// If no dates marked, mark at least a few for testing
		if ( empty( $available ) && ! empty( $dates ) ) {
			// Mark first 5 future dates as available
			$count = 0;
			foreach ( $dates as $date_info ) {
				$date_obj = new DateTime( $date_info['date'] );
				$date_obj->setTime(0, 0, 0);
				if ( $date_obj >= $today && $count < 5 ) {
					$available[] = $date_info['date'];
					$count++;
				}
			}
		}
		
		return $available;
	}
	
	/**
	 * Get time slots for a specific date from Calendly API
	 * Note: This is a placeholder - implement actual API call
	 */
	private function get_time_slots_from_api( $calendly_url, $event_slug, $date ) {
		// In production, you would:
		// 1. Extract username and event type from URL
		// 2. Call Calendly API: GET /event_type_available_times
		// 3. Filter by date
		// 4. Return time slots
		
		// For now, simulate some time slots
		$time_slots = array(
			'09:00',
			'10:30',
			'14:00',
			'15:30',
			'17:00',
		);
		
		return $time_slots;
	}
}

// Initialize the plugin
function calendly_integration_init() {
	return Calendly_Integration::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'calendly_integration_init' );

<?php
/**
 * Plugin Name: AI Chatbot (Predefined & Auto-Response)
 * Plugin URI: https://example.com
 * Description: Add a basic, fast-response AI/chatbot to the homepage with predefined questions and answers. Auto-responds when users select options and shares relevant service page URLs.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-chatbot
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'AI_CHATBOT_VERSION', '1.0.0' );
define( 'AI_CHATBOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_CHATBOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main AI Chatbot Class
 */
class AI_Chatbot {
	
	/**
	 * Instance of this class
	 */
	private static $instance = null;
	
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
		// Enqueue scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Register shortcode
		add_shortcode( 'ai_chatbot', array( $this, 'chatbot_shortcode' ) );
		
		// Add chatbot to homepage (optional, can be disabled)
		if ( get_option( 'ai_chatbot_show_on_homepage', false ) ) {
			add_action( 'wp_footer', array( $this, 'display_chatbot' ) );
		}
		
		// Admin menu
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		// AJAX handler for chatbot responses
		add_action( 'wp_ajax_ai_chatbot_response', array( $this, 'handle_chatbot_response' ) );
		add_action( 'wp_ajax_nopriv_ai_chatbot_response', array( $this, 'handle_chatbot_response' ) );
	}
	
	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		// Load styles and scripts on all pages (for shortcode usage)
		wp_enqueue_style(
			'ai-chatbot-style',
			AI_CHATBOT_PLUGIN_URL . 'assets/css/style.css',
			array(),
			AI_CHATBOT_VERSION
		);
		
		wp_enqueue_script(
			'ai-chatbot-script',
			AI_CHATBOT_PLUGIN_URL . 'assets/js/chatbot.js',
			array( 'jquery' ),
			AI_CHATBOT_VERSION,
			true
		);
		
		// Get initial greeting response
		$responses = $this->get_predefined_responses();
		$initial_response = isset( $responses['greeting'] ) ? $responses['greeting'] : null;
		
		// Process initial options to add URLs where needed
		$initial_options = array();
		if ( $initial_response && isset( $initial_response['options'] ) ) {
			foreach ( $initial_response['options'] as $option ) {
				$processed_option = $option;
				// Add URL for link-type options that need it
				if ( isset( $option['id'] ) && $option['id'] === 'view_bridal' && ! isset( $option['url'] ) ) {
					$processed_option['url'] = $this->get_service_url( 'bridal_makeup' );
				} elseif ( isset( $option['id'] ) && $option['id'] === 'view_learn' && ! isset( $option['url'] ) ) {
					$processed_option['url'] = $this->get_service_url( 'learn_makeup' );
				}
				$initial_options[] = $processed_option;
			}
		}
		
		// Localize script with AJAX URL and settings
		wp_localize_script(
			'ai-chatbot-script',
			'aiChatbot',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ai_chatbot_nonce' ),
				'serviceUrls' => array(
					'bridal_makeup' => $this->get_service_url( 'bridal_makeup' ),
					'learn_makeup'  => $this->get_service_url( 'learn_makeup' ),
				),
				'initialOptions' => $initial_options,
			)
		);
	}
	
	/**
	 * Chatbot shortcode handler
	 * 
	 * Usage: [ai_chatbot]
	 * 
	 * @return string HTML output
	 */
	public function chatbot_shortcode() {
		ob_start();
		include AI_CHATBOT_PLUGIN_DIR . 'templates/chatbot-widget.php';
		return ob_get_clean();
	}
	
	/**
	 * Get service URL from settings or try to find page by slug
	 */
	private function get_service_url( $service_key ) {
		$url = get_option( 'ai_chatbot_' . $service_key . '_url', '' );
		
		// If no custom URL, try to find page by slug
		if ( empty( $url ) ) {
			$slug_map = array(
				'bridal_makeup' => 'bridal-makeup',
				'learn_makeup'  => 'learn-makeup',
			);
			
			if ( isset( $slug_map[ $service_key ] ) ) {
				$page = get_page_by_path( $slug_map[ $service_key ] );
				if ( $page ) {
					$url = get_permalink( $page->ID );
				}
			}
		}
		
		// Fallback to homepage if still empty
		if ( empty( $url ) ) {
			$url = home_url( '/' );
		}
		
		return $url;
	}
	
	/**
	 * Display chatbot widget (for footer hook - optional)
	 */
	public function display_chatbot() {
		// Only show on homepage if enabled
		if ( ! is_front_page() && ! is_home() ) {
			return;
		}
		
		// Check if chatbot is enabled
		if ( ! get_option( 'ai_chatbot_enabled', true ) ) {
			return;
		}
		
		echo do_shortcode( '[ai_chatbot]' );
	}
	
	/**
	 * Handle AJAX chatbot response
	 */
	public function handle_chatbot_response() {
		check_ajax_referer( 'ai_chatbot_nonce', 'nonce' );
		
		$question_id = isset( $_POST['question_id'] ) ? sanitize_text_field( $_POST['question_id'] ) : '';
		
		$responses = $this->get_predefined_responses();
		
		if ( isset( $responses[ $question_id ] ) ) {
			wp_send_json_success( $responses[ $question_id ] );
		} else {
			wp_send_json_error( array( 'message' => 'Response not found.' ) );
		}
	}
	
	/**
	 * Get predefined questions and responses
	 */
	private function get_predefined_responses() {
		return array(
			'greeting' => array(
				'message' => 'Hello! I\'m here to help you with our makeup services.',
				'options' => array(
					array(
						'text' => 'Bridal Makeup Services',
						'id'   => 'bridal_makeup',
					),
					array(
						'text' => 'Learn Makeup Classes',
						'id'   => 'learn_makeup',
					),
					array(
						'text' => 'Pricing Information',
						'id'   => 'pricing',
					),
					array(
						'text' => 'Contact Us',
						'id'   => 'contact',
					),
				),
			),
			'bridal_makeup' => array(
				'message' => 'Our bridal makeup services are perfect for your special day! We offer professional bridal makeup packages tailored to your style and preferences.',
				'options' => array(
					array(
						'text' => 'View Bridal Makeup Page',
						'id'   => 'view_bridal',
						'type' => 'link',
						'url'  => $this->get_service_url( 'bridal_makeup' ),
					),
					array(
						'text' => 'Back to Main Menu',
						'id'   => 'greeting',
					),
				),
			),
			'bridal_learning' => array(
				'message' => 'Great! I recommend our Intermediate Masterclass. It covers advanced bridal techniques, contouring, and airbrush. Would you like to see available dates?',
				'options' => array(
					array(
						'text' => 'View Learn Makeup Page',
						'id'   => 'view_learn',
						'type' => 'link',
						'url'  => $this->get_service_url( 'learn_makeup' ),
					),
					array(
						'text' => 'See Available Dates',
						'id'   => 'contact',
					),
					array(
						'text' => 'Back to Main Menu',
						'id'   => 'greeting',
					),
				),
			),
			'intermediate_masterclass' => array(
				'message' => 'Perfect choice! Our Intermediate Masterclass is ideal for those looking to master bridal makeup techniques. The course includes advanced contouring, airbrush application, and long-lasting makeup techniques perfect for weddings.',
				'options' => array(
					array(
						'text' => 'View Learn Makeup Page',
						'id'   => 'view_learn',
						'type' => 'link',
						'url'  => $this->get_service_url( 'learn_makeup' ),
					),
					array(
						'text' => 'Check Pricing',
						'id'   => 'pricing',
					),
					array(
						'text' => 'Book a Class',
						'id'   => 'contact',
					),
					array(
						'text' => 'Back to Main Menu',
						'id'   => 'greeting',
					),
				),
			),
			'learn_makeup' => array(
				'message' => 'Learn makeup with our expert instructors! We offer comprehensive makeup classes for all skill levels, from beginners to advanced techniques. Our Intermediate Masterclass is perfect for bridal makeup techniques.',
				'options' => array(
					array(
						'text' => 'View Learn Makeup Page',
						'id'   => 'view_learn',
						'type' => 'link',
						'url'  => $this->get_service_url( 'learn_makeup' ),
					),
					array(
						'text' => 'Intermediate Masterclass',
						'id'   => 'intermediate_masterclass',
					),
					array(
						'text' => 'Back to Main Menu',
						'id'   => 'greeting',
					),
				),
			),
			'pricing' => array(
				'message' => 'Our pricing varies based on the service and package you choose. For detailed pricing information, please visit our service pages or contact us directly.',
				'options' => array(
					array(
						'text' => 'Bridal Makeup Pricing',
						'id'   => 'bridal_makeup',
					),
					array(
						'text' => 'Learn Makeup Pricing',
						'id'   => 'learn_makeup',
					),
					array(
						'text' => 'Back to Main Menu',
						'id'   => 'greeting',
					),
				),
			),
			'contact' => array(
				'message' => 'You can contact us through our booking system, email, or phone. Would you like to schedule a consultation?',
				'options' => array(
					array(
						'text' => 'Book a Consultation',
						'id'   => 'book',
					),
					array(
						'text' => 'Back to Main Menu',
						'id'   => 'greeting',
					),
				),
			),
			'book' => array(
				'message' => 'Great! You can book your consultation using our Calendly booking system on this page. Scroll down to find the booking widget.',
				'options' => array(
					array(
						'text' => 'Back to Main Menu',
						'id'   => 'greeting',
					),
				),
			),
			'view_bridal' => array(
				'message' => 'Redirecting you to our Bridal Makeup page...',
				'options' => array(),
				'redirect' => $this->get_service_url( 'bridal_makeup' ),
			),
			'view_learn' => array(
				'message' => 'Redirecting you to our Learn Makeup page...',
				'options' => array(),
				'redirect' => $this->get_service_url( 'learn_makeup' ),
			),
		);
	}
	
	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'AI Chatbot Settings', 'ai-chatbot' ),
			__( 'AI Chatbot', 'ai-chatbot' ),
			'manage_options',
			'ai-chatbot',
			array( $this, 'render_admin_page' )
		);
	}
	
	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'ai_chatbot_settings', 'ai_chatbot_enabled', array( 'default' => true ) );
		register_setting( 'ai_chatbot_settings', 'ai_chatbot_show_on_homepage', array( 'default' => false ) );
		register_setting( 'ai_chatbot_settings', 'ai_chatbot_bridal_makeup_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
		register_setting( 'ai_chatbot_settings', 'ai_chatbot_learn_makeup_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
		register_setting( 'ai_chatbot_settings', 'ai_chatbot_welcome_message', array( 'sanitize_textarea_field' ) );
		register_setting( 'ai_chatbot_settings', 'ai_chatbot_assistant_name', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'Visage Assistant' ) );
	}
	
	/**
	 * Render admin settings page
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Save settings
		if ( isset( $_POST['ai_chatbot_save_settings'] ) && check_admin_referer( 'ai_chatbot_save_settings' ) ) {
			update_option( 'ai_chatbot_enabled', isset( $_POST['ai_chatbot_enabled'] ) );
			update_option( 'ai_chatbot_show_on_homepage', isset( $_POST['ai_chatbot_show_on_homepage'] ) );
			update_option( 'ai_chatbot_bridal_makeup_url', sanitize_text_field( $_POST['ai_chatbot_bridal_makeup_url'] ) );
			update_option( 'ai_chatbot_learn_makeup_url', sanitize_text_field( $_POST['ai_chatbot_learn_makeup_url'] ) );
			update_option( 'ai_chatbot_welcome_message', sanitize_textarea_field( $_POST['ai_chatbot_welcome_message'] ) );
			update_option( 'ai_chatbot_assistant_name', sanitize_text_field( $_POST['ai_chatbot_assistant_name'] ) );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved!', 'ai-chatbot' ) . '</p></div>';
		}
		
		$enabled = get_option( 'ai_chatbot_enabled', true );
		$show_on_homepage = get_option( 'ai_chatbot_show_on_homepage', false );
		$bridal_url = get_option( 'ai_chatbot_bridal_makeup_url', '' );
		$learn_url = get_option( 'ai_chatbot_learn_makeup_url', '' );
		$welcome_message = get_option( 'ai_chatbot_welcome_message', 'Hello! I\'m here to help you with our makeup services.' );
		$assistant_name = get_option( 'ai_chatbot_assistant_name', 'Visage Assistant' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'ai_chatbot_save_settings' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="ai_chatbot_enabled"><?php esc_html_e( 'Enable Chatbot', 'ai-chatbot' ); ?></label>
						</th>
						<td>
							<input type="checkbox" 
								   id="ai_chatbot_enabled" 
								   name="ai_chatbot_enabled" 
								   value="1" 
								   <?php checked( $enabled, true ); ?> />
							<label for="ai_chatbot_enabled">
								<?php esc_html_e( 'Enable chatbot functionality', 'ai-chatbot' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="ai_chatbot_show_on_homepage"><?php esc_html_e( 'Show on Homepage', 'ai-chatbot' ); ?></label>
						</th>
						<td>
							<input type="checkbox" 
								   id="ai_chatbot_show_on_homepage" 
								   name="ai_chatbot_show_on_homepage" 
								   value="1" 
								   <?php checked( $show_on_homepage, true ); ?> />
							<label for="ai_chatbot_show_on_homepage">
								<?php esc_html_e( 'Automatically show chatbot on homepage footer (or use shortcode [ai_chatbot] in your page)', 'ai-chatbot' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="ai_chatbot_assistant_name"><?php esc_html_e( 'Assistant Name', 'ai-chatbot' ); ?></label>
						</th>
						<td>
							<input type="text" 
								   id="ai_chatbot_assistant_name" 
								   name="ai_chatbot_assistant_name" 
								   value="<?php echo esc_attr( $assistant_name ); ?>" 
								   class="regular-text" 
								   placeholder="<?php esc_attr_e( 'Visage Assistant', 'ai-chatbot' ); ?>" />
							<p class="description">
								<?php esc_html_e( 'Name displayed in the chatbot header.', 'ai-chatbot' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="ai_chatbot_bridal_makeup_url"><?php esc_html_e( 'Bridal Makeup Page URL', 'ai-chatbot' ); ?></label>
						</th>
						<td>
							<input type="url" 
								   id="ai_chatbot_bridal_makeup_url" 
								   name="ai_chatbot_bridal_makeup_url" 
								   value="<?php echo esc_attr( $bridal_url ); ?>" 
								   class="regular-text" 
								   placeholder="<?php echo esc_attr( home_url( '/bridal-makeup' ) ); ?>" />
							<p class="description">
								<?php esc_html_e( 'Leave empty to auto-detect page by slug "bridal-makeup".', 'ai-chatbot' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="ai_chatbot_learn_makeup_url"><?php esc_html_e( 'Learn Makeup Page URL', 'ai-chatbot' ); ?></label>
						</th>
						<td>
							<input type="url" 
								   id="ai_chatbot_learn_makeup_url" 
								   name="ai_chatbot_learn_makeup_url" 
								   value="<?php echo esc_attr( $learn_url ); ?>" 
								   class="regular-text" 
								   placeholder="<?php echo esc_attr( home_url( '/learn-makeup' ) ); ?>" />
							<p class="description">
								<?php esc_html_e( 'Leave empty to auto-detect page by slug "learn-makeup".', 'ai-chatbot' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="ai_chatbot_welcome_message"><?php esc_html_e( 'Welcome Message', 'ai-chatbot' ); ?></label>
						</th>
						<td>
							<textarea id="ai_chatbot_welcome_message" 
									  name="ai_chatbot_welcome_message" 
									  rows="3" 
									  class="large-text"><?php echo esc_textarea( $welcome_message ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Initial message displayed when chatbot opens.', 'ai-chatbot' ); ?>
							</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button( __( 'Save Settings', 'ai-chatbot' ), 'primary', 'ai_chatbot_save_settings' ); ?>
			</form>
			
			<hr>
			
			<h2><?php esc_html_e( 'Shortcode Usage', 'ai-chatbot' ); ?></h2>
			<p><?php esc_html_e( 'You can display the chatbot anywhere on your site using the shortcode:', 'ai-chatbot' ); ?></p>
			<code>[ai_chatbot]</code>
			<p class="description">
				<?php esc_html_e( 'Place this shortcode in any page, post, or widget where you want the chatbot to appear.', 'ai-chatbot' ); ?>
			</p>
		</div>
		<?php
	}
}

// Initialize the plugin
function ai_chatbot_init() {
	return AI_Chatbot::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'ai_chatbot_init' );

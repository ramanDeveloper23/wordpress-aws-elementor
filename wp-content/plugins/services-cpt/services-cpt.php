<?php
/**
 * Plugin Name: Services Custom Post Type
 * Plugin URI: https://example.com/services-cpt
 * Description: Creates a custom post type for Services to manage dynamic content on the homepage.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: services-cpt
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Services Custom Post Type
 */
function services_cpt_register_post_type() {
	$labels = array(
		'name'                  => _x( 'Services', 'Post Type General Name', 'services-cpt' ),
		'singular_name'         => _x( 'Service', 'Post Type Singular Name', 'services-cpt' ),
		'menu_name'             => __( 'Services', 'services-cpt' ),
		'name_admin_bar'        => __( 'Service', 'services-cpt' ),
		'archives'              => __( 'Service Archives', 'services-cpt' ),
		'attributes'            => __( 'Service Attributes', 'services-cpt' ),
		'parent_item_colon'     => __( 'Parent Service:', 'services-cpt' ),
		'all_items'             => __( 'All Services', 'services-cpt' ),
		'add_new_item'          => __( 'Add New Service', 'services-cpt' ),
		'add_new'               => __( 'Add New', 'services-cpt' ),
		'new_item'              => __( 'New Service', 'services-cpt' ),
		'edit_item'              => __( 'Edit Service', 'services-cpt' ),
		'update_item'            => __( 'Update Service', 'services-cpt' ),
		'view_item'              => __( 'View Service', 'services-cpt' ),
		'view_items'             => __( 'View Services', 'services-cpt' ),
		'search_items'           => __( 'Search Service', 'services-cpt' ),
		'not_found'              => __( 'Not found', 'services-cpt' ),
		'not_found_in_trash'     => __( 'Not found in Trash', 'services-cpt' ),
		'featured_image'         => __( 'Featured Image', 'services-cpt' ),
		'set_featured_image'     => __( 'Set featured image', 'services-cpt' ),
		'remove_featured_image'  => __( 'Remove featured image', 'services-cpt' ),
		'use_featured_image'     => __( 'Use as featured image', 'services-cpt' ),
		'insert_into_item'       => __( 'Insert into service', 'services-cpt' ),
		'uploaded_to_this_item'  => __( 'Uploaded to this service', 'services-cpt' ),
		'items_list'             => __( 'Services list', 'services-cpt' ),
		'items_list_navigation'  => __( 'Services list navigation', 'services-cpt' ),
		'filter_items_list'      => __( 'Filter services list', 'services-cpt' ),
	);

	$args = array(
		'label'                 => __( 'Service', 'services-cpt' ),
		'description'           => __( 'Services offered by the business', 'services-cpt' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
		'taxonomies'            => array( 'service_category' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 20,
		'menu_icon'             => 'dashicons-admin-tools',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
		'rest_base'             => 'services',
		'rest_controller_class' => 'WP_REST_Posts_Controller',
	);

	register_post_type( 'service', $args );
}
add_action( 'init', 'services_cpt_register_post_type', 0 );

/**
 * Register Service Category Taxonomy
 */
function services_cpt_register_taxonomy() {
	$labels = array(
		'name'              => _x( 'Service Categories', 'taxonomy general name', 'services-cpt' ),
		'singular_name'     => _x( 'Service Category', 'taxonomy singular name', 'services-cpt' ),
		'search_items'      => __( 'Search Categories', 'services-cpt' ),
		'all_items'         => __( 'All Categories', 'services-cpt' ),
		'parent_item'       => __( 'Parent Category', 'services-cpt' ),
		'parent_item_colon' => __( 'Parent Category:', 'services-cpt' ),
		'edit_item'         => __( 'Edit Category', 'services-cpt' ),
		'update_item'       => __( 'Update Category', 'services-cpt' ),
		'add_new_item'      => __( 'Add New Category', 'services-cpt' ),
		'new_item_name'     => __( 'New Category Name', 'services-cpt' ),
		'menu_name'         => __( 'Categories', 'services-cpt' ),
	);

	$args = array(
		'labels'            => $labels,
		'hierarchical'      => true,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud'     => false,
		'show_in_rest'       => true,
	);

	register_taxonomy( 'service_category', array( 'service' ), $args );
}
add_action( 'init', 'services_cpt_register_taxonomy', 0 );

/**
 * Add custom meta box for service icon/order
 */
function services_cpt_add_meta_boxes() {
	add_meta_box(
		'service_details',
		__( 'Service Details', 'services-cpt' ),
		'services_cpt_meta_box_callback',
		'service',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'services_cpt_add_meta_boxes' );

/**
 * Meta box callback
 */
function services_cpt_meta_box_callback( $post ) {
	wp_nonce_field( 'services_cpt_meta_box', 'services_cpt_meta_box_nonce' );
	
	$service_order = get_post_meta( $post->ID, '_service_order', true );
	$service_icon = get_post_meta( $post->ID, '_service_icon', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="service_order"><?php _e( 'Display Order', 'services-cpt' ); ?></label></th>
			<td>
				<input type="number" id="service_order" name="service_order" value="<?php echo esc_attr( $service_order ); ?>" min="0" />
				<p class="description"><?php _e( 'Lower numbers appear first. Leave empty for default ordering.', 'services-cpt' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="service_icon"><?php _e( 'Icon Class', 'services-cpt' ); ?></label></th>
			<td>
				<input type="text" id="service_icon" name="service_icon" value="<?php echo esc_attr( $service_icon ); ?>" class="regular-text" />
				<p class="description"><?php _e( 'Optional: CSS class name for an icon (e.g., dashicons-admin-tools)', 'services-cpt' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="service_button_text"><?php _e( 'Button Text', 'services-cpt' ); ?></label></th>
			<td>
				<?php
				$service_button_text = get_post_meta( $post->ID, '_service_button_text', true );
				$service_button_url = get_post_meta( $post->ID, '_service_button_url', true );
				?>
				<input type="text" id="service_button_text" name="service_button_text" value="<?php echo esc_attr( $service_button_text ); ?>" class="regular-text" placeholder="<?php _e( 'Learn More', 'services-cpt' ); ?>" />
				<p class="description"><?php _e( 'Optional: Custom button text for this service', 'services-cpt' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="service_button_url"><?php _e( 'Button URL', 'services-cpt' ); ?></label></th>
			<td>
				<input type="url" id="service_button_url" name="service_button_url" value="<?php echo esc_attr( $service_button_url ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_permalink() ); ?>" />
				<p class="description"><?php _e( 'Optional: Custom URL for the button. Leave empty to use service permalink.', 'services-cpt' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save meta box data
 */
function services_cpt_save_meta_box( $post_id ) {
	// Check if nonce is set
	if ( ! isset( $_POST['services_cpt_meta_box_nonce'] ) ) {
		return;
	}

	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['services_cpt_meta_box_nonce'], 'services_cpt_meta_box' ) ) {
		return;
	}

	// Check if autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check user permissions
	if ( isset( $_POST['post_type'] ) && 'service' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	// Save service order
	if ( isset( $_POST['service_order'] ) ) {
		update_post_meta( $post_id, '_service_order', sanitize_text_field( $_POST['service_order'] ) );
	}

	// Save service icon
	if ( isset( $_POST['service_icon'] ) ) {
		update_post_meta( $post_id, '_service_icon', sanitize_text_field( $_POST['service_icon'] ) );
	}

	// Save service button text
	if ( isset( $_POST['service_button_text'] ) ) {
		update_post_meta( $post_id, '_service_button_text', sanitize_text_field( $_POST['service_button_text'] ) );
	}

	// Save service button URL
	if ( isset( $_POST['service_button_url'] ) ) {
		update_post_meta( $post_id, '_service_button_url', esc_url_raw( $_POST['service_button_url'] ) );
	}
}
add_action( 'save_post', 'services_cpt_save_meta_box' );

/**
 * Flush rewrite rules on activation
 */
function services_cpt_activation() {
	services_cpt_register_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'services_cpt_activation' );

/**
 * Flush rewrite rules on deactivation
 */
function services_cpt_deactivation() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'services_cpt_deactivation' );

/**
 * Query Services
 */
function services_cpt_get_services( $args = array() ) {
	$defaults = array(
		'posts_per_page' => 6,
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
	);

	$args = wp_parse_args( $args, $defaults );

	$query_args = array(
		'post_type'      => 'service',
		'posts_per_page' => intval( $args['posts_per_page'] ),
		'post_status'    => 'publish',
	);

	if ( $args['orderby'] === 'meta_value_num' ) {
		$query_args['orderby'] = 'meta_value_num';
		$query_args['meta_key'] = '_service_order';
		$query_args['order'] = $args['order'];
		$query_args['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => '_service_order',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => '_service_order',
				'compare' => 'NOT EXISTS',
			),
		);
	} else {
		$query_args['orderby'] = $args['orderby'];
		$query_args['order'] = $args['order'];
	}

	$services_query = new WP_Query( $query_args );

	// If no posts with custom order, fallback to date
	if ( ! $services_query->have_posts() && $args['orderby'] === 'meta_value_num' ) {
		wp_reset_postdata();
		$services_query = new WP_Query( array(
			'post_type'      => 'service',
			'posts_per_page' => intval( $args['posts_per_page'] ),
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
	}

	return $services_query;
}

/**
 * Render Services Section HTML
 */
function services_cpt_render_section( $query_args = array() ) {
	// Query services
	$services_query = services_cpt_get_services( $query_args );
	$services = $services_query->have_posts() ? $services_query->posts : array();

	// Get settings
	$services_heading = get_theme_mod( 'services_heading', __( 'Services', 'services-cpt' ) );
	$services_subheading = get_theme_mod( 'services_subheading', __( 'We create elevated beauty experiences with studio precision and bold creative direction across various platforms.', 'services-cpt' ) );
	$services_button_text = get_theme_mod( 'services_button_text', __( 'Explore Services', 'services-cpt' ) );
	$services_button_url = get_theme_mod( 'services_button_url', '' );

	if ( empty( $services_button_url ) && function_exists( 'get_post_type_archive_link' ) ) {
		$services_button_url = get_post_type_archive_link( 'service' );
	}
	if ( empty( $services_button_url ) ) {
		$services_button_url = '#';
	}

	ob_start();
	?>
	<div class="services-section-wrapper" id="services-section">
		<div class="services-section-container">
			<!-- Left Side: Dark Brown Background -->
			<div class="services-left-panel">
				<h2 class="services-heading"><?php echo esc_html( $services_heading ); ?></h2>
				<p class="services-description"><?php echo esc_html( $services_subheading ); ?></p>
				<a href="<?php echo esc_url( $services_button_url ); ?>" class="services-explore-button">
					<?php echo esc_html( $services_button_text ); ?>
				</a>
			</div>

			<!-- Right Side: White Background with Service Details -->
			<div class="services-right-panel">
				<?php if ( ! empty( $services ) ) : ?>
					<div class="services-carousel" data-current="0">
						<?php foreach ( $services as $index => $service ) : 
							setup_postdata( $service );
							$service_image = get_the_post_thumbnail_url( $service->ID, 'large' );
							$service_excerpt = has_excerpt( $service->ID ) ? get_the_excerpt( $service->ID ) : wp_trim_words( $service->post_content, 15 );
							$service_categories = get_the_terms( $service->ID, 'service_category' );
							$service_button_text = get_post_meta( $service->ID, '_service_button_text', true );
							$service_button_url = get_post_meta( $service->ID, '_service_button_url', true );
							
							if ( empty( $service_button_url ) ) {
								$service_button_url = get_permalink( $service->ID );
							}
							if ( empty( $service_button_text ) ) {
								$service_button_text = __( 'Learn More', 'services-cpt' );
							}
						?>
							<div class="service-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo esc_attr( $index ); ?>">
								<div class="service-content">
									<h3 class="service-title"><?php echo esc_html( $service->post_title ); ?></h3>
									<p class="service-description"><?php echo esc_html( $service_excerpt ); ?></p>
									
									<?php if ( ! empty( $service_categories ) && ! is_wp_error( $service_categories ) ) : ?>
										<div class="service-tags">
											<?php foreach ( $service_categories as $category ) : ?>
												<span class="service-tag"><?php echo esc_html( $category->name ); ?></span>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
									
									<a href="<?php echo esc_url( $service_button_url ); ?>" class="service-link-button">
										<span class="service-link-icon">→</span>
									</a>
								</div>
								
								<?php if ( $service_image ) : ?>
									<div class="service-image">
										<img src="<?php echo esc_url( $service_image ); ?>" alt="<?php echo esc_attr( $service->post_title ); ?>" />
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
						<?php wp_reset_postdata(); ?>
					</div>
					
					<?php if ( count( $services ) > 1 ) : ?>
						<div class="services-navigation">
							<button class="services-nav-button services-nav-prev" aria-label="<?php esc_attr_e( 'Previous service', 'services-cpt' ); ?>">
								<span>←</span>
							</button>
							<button class="services-nav-button services-nav-next" aria-label="<?php esc_attr_e( 'Next service', 'services-cpt' ); ?>">
								<span>→</span>
							</button>
						</div>
					<?php endif; ?>
				<?php else : ?>
					<div class="services-empty">
						<p><?php _e( 'No services found. Please add some services from the Services menu.', 'services-cpt' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Register Shortcode
 */
function services_cpt_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'posts_per_page' => 6,
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
	), $atts, 'services_section' );

	return services_cpt_render_section( $atts );
}
add_shortcode( 'services_section', 'services_cpt_shortcode' );

/**
 * Enqueue Assets
 */
function services_cpt_enqueue_assets() {
	$plugin_url = plugin_dir_url( __FILE__ );
	$plugin_version = '1.0.0';

	wp_enqueue_style( 
		'services-cpt-style', 
		$plugin_url . 'assets/css/services-section.css', 
		array(), 
		$plugin_version 
	);
	
	wp_enqueue_script( 
		'services-cpt-script', 
		$plugin_url . 'assets/js/services-section.js', 
		array( 'jquery' ), 
		$plugin_version, 
		true 
	);
}
add_action( 'wp_enqueue_scripts', 'services_cpt_enqueue_assets' );

/**
 * Add Customizer Settings
 */
function services_cpt_customize_register( $wp_customize ) {
	// Add Services section
	$wp_customize->add_section( 'services_section', array(
		'title'    => __( 'Services Section', 'services-cpt' ),
		'priority' => 160,
	) );

	// Services heading
	$wp_customize->add_setting( 'services_heading', array(
		'default'           => __( 'Services', 'services-cpt' ),
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	) );

	$wp_customize->add_control( 'services_heading', array(
		'label'    => __( 'Services Heading', 'services-cpt' ),
		'section'  => 'services_section',
		'type'     => 'text',
	) );

	// Services subheading
	$wp_customize->add_setting( 'services_subheading', array(
		'default'           => __( 'We create elevated beauty experiences with studio precision and bold creative direction across various platforms.', 'services-cpt' ),
		'sanitize_callback' => 'sanitize_textarea_field',
		'transport'         => 'refresh',
	) );

	$wp_customize->add_control( 'services_subheading', array(
		'label'    => __( 'Services Subheading', 'services-cpt' ),
		'section'  => 'services_section',
		'type'     => 'textarea',
	) );

	// Services button text
	$wp_customize->add_setting( 'services_button_text', array(
		'default'           => __( 'Explore Services', 'services-cpt' ),
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	) );

	$wp_customize->add_control( 'services_button_text', array(
		'label'    => __( 'Button Text', 'services-cpt' ),
		'section'  => 'services_section',
		'type'     => 'text',
	) );

	// Services button URL
	$wp_customize->add_setting( 'services_button_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport'         => 'refresh',
	) );

	$wp_customize->add_control( 'services_button_url', array(
		'label'       => __( 'Button URL', 'services-cpt' ),
		'section'     => 'services_section',
		'type'        => 'url',
		'description' => __( 'Leave empty to link to services archive page', 'services-cpt' ),
	) );
}
add_action( 'customize_register', 'services_cpt_customize_register' );

/**
 * Register Custom Block Pattern for Services Section
 */
function services_cpt_register_block_pattern() {
	register_block_pattern(
		'services-cpt/services-section',
		array(
			'title'       => __( 'Services Section', 'services-cpt' ),
			'description' => __( 'A dynamic services section with split-screen design', 'services-cpt' ),
			'categories'  => array( 'featured', 'call-to-action' ),
			'content'     => '<!-- wp:shortcode -->' . "\n" . '[services_section]' . "\n" . '<!-- /wp:shortcode -->',
		)
	);
}
add_action( 'init', 'services_cpt_register_block_pattern' );

/**
 * Auto-inject Services Section into Homepage (Optional)
 * This can be enabled/disabled via filter
 */
function services_cpt_auto_inject_homepage( $content ) {
	// Only on homepage/front page
	if ( ! is_front_page() && ! is_home() ) {
		return $content;
	}

	// Check if plugin is active
	if ( ! function_exists( 'services_cpt_render_section' ) ) {
		return $content;
	}

	// Check if services section already exists
	if ( strpos( $content, 'services-section-wrapper' ) !== false || strpos( $content, '[services_section]' ) !== false ) {
		return $content;
	}

	// Allow filtering to enable/disable auto-injection
	if ( ! apply_filters( 'services_cpt_auto_inject_homepage', true ) ) {
		return $content;
	}

	// Inject after hero pattern or at the beginning
	$services_html = '<!-- wp:shortcode -->' . "\n" . '[services_section]' . "\n" . '<!-- /wp:shortcode -->';
	
	// Try to inject after banner-hero pattern
	if ( strpos( $content, 'twentytwentyfour/banner-hero' ) !== false ) {
		$content = str_replace( 
			'<!-- wp:pattern {"slug":"twentytwentyfour/banner-hero"} /-->',
			'<!-- wp:pattern {"slug":"twentytwentyfour/banner-hero"} /-->' . "\n" . $services_html,
			$content
		);
	} else {
		// Inject at the beginning
		$content = $services_html . "\n" . $content;
	}

	return $content;
}
// Uncomment the line below to enable auto-injection
// add_filter( 'the_content', 'services_cpt_auto_inject_homepage', 20 );

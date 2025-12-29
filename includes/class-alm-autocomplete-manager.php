<?php
/**
 * Autocomplete manager for ALM devices.
 *
 * Handles REST API endpoint for frontend autocomplete search.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class that registers and manages the Autcomplete feature.
 */
class ALM_Autocomplete_Manager {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Register the REST route.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue autocomplete scripts.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_script(
			'alm-device-autocomplete',
			ALM_PLUGIN_URL . 'assets/js/alm-device-autocomplete.js',
			array( 'jquery' ),
			ALM_VERSION,
			true
		);
		wp_enqueue_style(
			'alm-device-autocomplete',
			ALM_PLUGIN_URL . 'assets/css/alm-device-autocomplete.css',
			array(),
			ALM_VERSION
		);
		wp_localize_script(
			'alm-device-autocomplete',
			'almAutocomplete',
			array(
				'restUrl'   => esc_url( rest_url( 'alm/v1/devices/autocomplete' ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'minChars'  => 3,
			)
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'alm/v1',
			'/devices/autocomplete',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_autocomplete' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'term' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					// 'nonce' => array(
					// 	'required' => true,
					// ),
				),
			)
		);
	}

	/**
	 * Handle autocomplete request via POST.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function handle_autocomplete( WP_REST_Request $request ) {
		// // Read nonce from POST.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		error_log( '*** Nonce in handle_autocomplete:' . $nonce );
		// if ( ! wp_verify_nonce( $nonce, 'alm_autocomplete_nonce' ) ) {
		// 	return new WP_REST_Response(
		// 		array(
		// 			'error' => __( 'Invalid nonce.', 'asset-lending-manager' ),
		// 		),
		// 		403
		// 	);
		// }
		// Read search term.
		$term = isset( $_POST['term'] ) ? trim( wp_unslash( $_POST['term'] ) ) : '';
		// Require at least 3 characters.
		if ( strlen( $term ) < 3 ) {
			return rest_ensure_response( array() );
		}
		// Query devices.
		$query_args = array(
			'post_type'      => ALM_DEVICE_CPT_SLUG,
			'post_status'    => 'publish',
			's'              => $term,
			'posts_per_page' => ALM_AUTOCOMPLETE_MAX_RESULTS,
		);
		$query   = new WP_Query( $query_args );
		$results = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$wrapper = ALM_Device_Manager::get_device_wrapper( $post->ID );
				if ( ! $wrapper ) {
					continue;
				}
				$results[] = array(
					'id'          => $post->ID,
					'title'       => $wrapper->title,
					'description' => wp_trim_words( wp_strip_all_tags( $post->post_content ), ALM_AUTOCOMPLETE_DESC_LENGTH, '…' ),
					'structure'   => ! empty( $wrapper->alm_structure ) ? implode( ', ', $wrapper->alm_structure ) : '',
					'type'        => ! empty( $wrapper->alm_type ) ? implode( ', ', $wrapper->alm_type ) : '',
					'permalink'   => $wrapper->permalink,
				);
			}
		}
		wp_reset_postdata();
		return rest_ensure_response( $results );
	}
}

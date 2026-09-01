<?php
/**
 * The report page and its assets.
 *
 * @package SalesByStateReportForEasyCart
 */

namespace SBSEC\Admin;

use SBSEC\Filters;
use SBSEC\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the report under WP EasyCart.
 */
class Page {

	/**
	 * Menu slug.
	 */
	const SLUG = 'sbsec-sales-by-state';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_head', array( $this, 'print_css' ) );
	}

	/**
	 * Add the report under EasyCart.
	 *
	 * @return void
	 */
	public function register_page() {
		$cap = current_user_can( 'wpec_reports' ) ? 'wpec_reports' : 'manage_options';

		add_submenu_page(
			'wp-easycart-dashboard',
			__( 'Sales by State', 'sales-by-state-report-for-easycart' ),
			__( 'Sales by State', 'sales-by-state-report-for-easycart' ),
			$cap,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the root element for the standalone page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! Plugin::can_view() ) {
			return;
		}

		$this->register_assets();
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'sbsec-report' );
		wp_enqueue_script( 'sbsec-report' );

		printf(
			'<div class="wrap sbsec-wrap">
				<div class="sbsec-page-header"><h1 class="sbsec-page-header__title">%s</h1></div>
				<div id="sbsec-root"></div>
			</div>',
			esc_html__( 'Sales by State', 'sales-by-state-report-for-easycart' )
		);

		wp_print_scripts( array( 'sbsec-report' ) );
	}

	/**
	 * Register script and style handles.
	 *
	 * @return void
	 */
	private function register_assets() {
		$script = SBSEC_DIR . 'assets/js/report.js';
		$style  = SBSEC_DIR . 'assets/css/report.css';

		wp_register_script(
			'sbsec-report',
			SBSEC_URL . 'assets/js/report.js',
			array(
				'wp-hooks',
				'wp-element',
				'wp-i18n',
				'wp-api-fetch',
				'wp-url',
				'wp-components',
			),
			file_exists( $script ) ? (string) filemtime( $script ) : SBSEC_VERSION,
			true
		);

		wp_set_script_translations( 'sbsec-report', 'sales-by-state-report-for-easycart', SBSEC_DIR . 'languages' );
		wp_localize_script( 'sbsec-report', 'sbsecConfig', $this->config() );

		wp_register_style(
			'sbsec-report',
			SBSEC_URL . 'assets/css/report.css',
			array( 'wp-components' ),
			file_exists( $style ) ? (string) filemtime( $style ) : SBSEC_VERSION
		);
	}

	/**
	 * Enqueue the report bundle on this screen only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( ! $this->is_screen( $hook ) ) {
			return;
		}

		$this->register_assets();
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'sbsec-report' );
		wp_enqueue_script( 'sbsec-report' );
	}

	/**
	 * Print styles in the head if the enqueue hook did not run.
	 *
	 * @return void
	 */
	public function print_css() {
		if ( ! $this->is_screen() ) {
			return;
		}

		$this->register_assets();
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'sbsec-report' );
		wp_print_styles( array( 'wp-components', 'sbsec-report' ) );
	}

	/**
	 * Whether this screen is showing.
	 *
	 * @param string $hook Optional enqueue hook.
	 * @return bool
	 */
	private function is_screen( $hook = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading the current screen, not acting on it.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( self::SLUG === $page ) {
			return true;
		}

		return is_string( $hook ) && false !== strpos( $hook, self::SLUG );
	}

	/**
	 * Data the bundle needs to draw its controls.
	 *
	 * @return array
	 */
	private function config() {
		$measures = array();

		foreach ( Filters::measures() as $key => $measure ) {
			$measures[] = array(
				'key'   => $key,
				'label' => $measure['label'],
				'type'  => $measure['type'],
			);
		}

		$statuses = array();

		foreach ( Filters::order_statuses() as $key => $label ) {
			$statuses[] = array(
				// PHP casts numeric string keys to ints; keep a string so the
				// checkbox list can match URL / default values with ===.
				'value' => (string) $key,
				'label' => $label,
			);
		}

		$years = array();

		foreach ( Filters::years() as $year ) {
			$years[] = array(
				'value' => (string) $year,
				'label' => (string) $year,
			);
		}

		$countries = array();

		foreach ( Filters::countries_with_states() as $code => $label ) {
			$countries[] = array(
				'value' => $code,
				'label' => $label,
			);
		}

		return array(
			'measures'        => $measures,
			'statuses'        => $statuses,
			'years'           => $years,
			'countries'       => $countries,
			'defaultCountry'  => Filters::default_country(),
			'defaultYear'     => (string) Filters::default_year(),
			'defaultStatuses' => Filters::default_statuses(),
			'perPageOptions'  => array( 10, 25, 50, 100 ),
			'title'           => __( 'Sales by State', 'sales-by-state-report-for-easycart' ),
			'canBuild'        => Plugin::can_manage(),
			'mode'            => 'standalone',
		);
	}
}

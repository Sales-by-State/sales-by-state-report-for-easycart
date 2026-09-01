<?php
/**
 * Plugin bootstrap.
 *
 * @package SalesByStateReportForEasyCart
 */

namespace SBSEC;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin's features.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Whether the current user may open the report.
	 *
	 * Matches EasyCart's Reports screen (`wpec_reports`).
	 *
	 * @return bool
	 */
	public static function can_view() {
		return current_user_can( 'wpec_reports' ) || self::can_manage();
	}

	/**
	 * Whether the current user may rebuild the report table.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( 'wpec_settings' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		Install\Schema::maybe_install();
		Data\Backfill::heal_if_empty();

		( new Data\Sync() )->register();
		( new Data\Scheduler() )->register();
		( new Api\Controller() )->register();
		( new Admin\Page() )->register();
		( new Admin\Tools() )->register();
	}
}

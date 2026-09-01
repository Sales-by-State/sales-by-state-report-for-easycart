<?php
/**
 * Removes the plugin's data when it is deleted.
 *
 * @package SalesByStateReportForEasyCart
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$sbsec_options = array(
	'sbsec_db_version',
	'sbsec_backfill_cursor',
	'sbsec_year_start',
);

foreach ( $sbsec_options as $sbsec_option ) {
	delete_option( $sbsec_option );
}

if ( is_multisite() ) {
	foreach ( $sbsec_options as $sbsec_option ) {
		delete_site_option( $sbsec_option );
	}
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sbsec_order_state" );

if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'sbsec_backfill_batch', array(), 'sales-by-state-report-for-easycart' );
}

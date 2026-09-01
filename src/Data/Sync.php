<?php
/**
 * Keeps the report table in step with EasyCart orders.
 *
 * @package SalesByStateReportForEasyCart
 */

namespace SBSEC\Data;

use SBSEC\Install\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Writes one row per order.
 */
class Sync {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wpeasycart_order_inserted', array( $this, 'on_order' ), 20, 1 );
		add_action( 'wpeasycart_order_paid', array( $this, 'on_order' ), 20, 1 );
		add_action( 'wpeasycart_order_complete', array( $this, 'on_order' ), 20, 1 );
		add_action( 'wpeasycart_order_updated', array( $this, 'on_order' ), 20, 1 );
		add_action( 'wpeasycart_order_status_update', array( $this, 'on_status' ), 20, 2 );
		add_action( 'wpeasycart_order_deleted', array( $this, 'on_delete' ), 20, 1 );
	}

	/**
	 * Handle an order ID from an add/update hook.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function on_order( $order_id ) {
		$this->upsert( (int) $order_id );
	}

	/**
	 * Handle a status change.
	 *
	 * @param int $order_id      Order ID.
	 * @param int $orderstatus_id New status ID.
	 * @return void
	 */
	public function on_status( $order_id, $orderstatus_id = 0 ) {
		unset( $orderstatus_id );
		$this->upsert( (int) $order_id );
	}

	/**
	 * Remove the row when an order is deleted.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function on_delete( $order_id ) {
		$this->delete( (int) $order_id );
	}

	/**
	 * Insert or update the row for one order.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public function upsert( $order_id ) {
		global $wpdb;

		$order_id = (int) $order_id;

		if ( ! $order_id ) {
			return false;
		}

		$row = self::build_row( $order_id );

		if ( ! $row ) {
			$this->delete( $order_id );
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $wpdb->replace(
			Schema::table(),
			$row,
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f' )
		);
	}

	/**
	 * Remove the row for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function delete( $order_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( Schema::table(), array( 'order_id' => (int) $order_id ), array( '%d' ) );
	}

	/**
	 * Build the row for an order from EasyCart tables.
	 *
	 * EasyCart stores money as decimals. The report groups by shipping region,
	 * falling back to billing when the order has no ship-to address (downloads).
	 *
	 * @param int $order_id Order ID.
	 * @return array<string,mixed>|false
	 */
	public static function build_row( $order_id ) {
		global $wpdb;

		$order_id = (int) $order_id;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$order = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT o.order_id, o.orderstatus_id, o.order_date, o.last_updated,
				        o.grand_total, o.tax_total, o.shipping_total, o.vat_total,
				        o.gst_total, o.pst_total, o.hst_total,
				        o.billing_country, o.billing_state,
				        o.shipping_country, o.shipping_state,
				        s.is_approved
				 FROM ec_order o
				 LEFT JOIN ec_orderstatus s ON s.status_id = o.orderstatus_id
				 WHERE o.order_id = %d',
				$order_id
			)
		);

		if ( ! $order ) {
			return false;
		}

		$billing_country  = strtoupper( substr( (string) $order->billing_country, 0, 2 ) );
		$billing_state    = (string) $order->billing_state;
		$shipping_country = strtoupper( substr( (string) $order->shipping_country, 0, 2 ) );
		$shipping_state   = (string) $order->shipping_state;

		if ( '' === $shipping_country ) {
			$shipping_country = $billing_country;
			$shipping_state   = $billing_state;
		}

		$total    = round( (float) $order->grand_total, 2 );
		$tax      = round( (float) $order->tax_total + (float) $order->vat_total + (float) $order->gst_total + (float) $order->pst_total + (float) $order->hst_total, 2 );
		$shipping = round( (float) $order->shipping_total, 2 );
		$created  = self::normalize_datetime( $order->order_date );
		$paid     = ! empty( $order->is_approved ) ? $created : null;

		return array(
			'order_id'         => (int) $order->order_id,
			'status'           => substr( (string) (int) $order->orderstatus_id, 0, 32 ),
			'date_created'     => $created ? $created : '0000-00-00 00:00:00',
			'date_paid'        => $paid,
			'billing_country'  => $billing_country,
			'billing_state'    => substr( $billing_state, 0, 50 ),
			'shipping_country' => $shipping_country,
			'shipping_state'   => substr( $shipping_state, 0, 50 ),
			'currency'         => self::store_currency(),
			'total_sales'      => $total,
			'tax_total'        => $tax,
			'shipping_total'   => $shipping,
			'net_total'        => $total - $tax - $shipping,
		);
	}

	/**
	 * Store currency as a three-letter code.
	 *
	 * EasyCart stores a symbol, not an ISO code.
	 *
	 * @return string
	 */
	private static function store_currency() {
		$symbol  = (string) get_option( 'ec_option_currency', '$' );
		$country = strtoupper( (string) get_option( 'ec_option_default_country', 'US' ) );

		if ( false !== strpos( $symbol, '£' ) || 'GB' === $country ) {
			return 'GBP';
		}

		if ( false !== strpos( $symbol, '€' ) ) {
			return 'EUR';
		}

		if ( 'CA' === $country ) {
			return 'CAD';
		}

		return 'USD';
	}

	/**
	 * Normalise a datetime string.
	 *
	 * @param mixed $value Datetime.
	 * @return string|null
	 */
	private static function normalize_datetime( $value ) {
		$value = (string) $value;

		if ( '' === $value || '0000-00-00 00:00:00' === $value ) {
			return null;
		}

		$ts = strtotime( $value );

		return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null;
	}
}

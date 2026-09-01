<?php
/**
 * The values the report's three filters can take.
 *
 * @package SalesByStateReportForEasyCart
 */

namespace SBSEC;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the filter options and validates what comes back from the browser.
 */
class Filters {

	/**
	 * Option holding the first year offered by the year filter.
	 */
	const YEAR_START_OPTION = 'sbsec_year_start';

	/**
	 * Number of years offered when the list is first created.
	 */
	const YEAR_WINDOW = 10;

	/**
	 * The columns shown in the report table and summary.
	 *
	 * @return array<string,array{label:string,type:string}>
	 */
	public static function measures() {
		return array(
			'net_revenue'   => array(
				'label' => __( 'Net Sales', 'sales-by-state-report-for-easycart' ),
				'type'  => 'currency',
			),
			'gross_revenue' => array(
				'label' => __( 'Gross Sales', 'sales-by-state-report-for-easycart' ),
				'type'  => 'currency',
			),
		);
	}

	/**
	 * Measure keys.
	 *
	 * @return string[]
	 */
	public static function measure_keys() {
		return array_keys( self::measures() );
	}

	/**
	 * Order statuses the filter offers.
	 *
	 * Keys are EasyCart `ec_orderstatus.status_id` values as strings.
	 *
	 * @return array<string,string>
	 */
	public static function order_statuses() {
		global $wpdb;

		$offered = array();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( 'SELECT status_id, order_status FROM ec_orderstatus ORDER BY status_id ASC', ARRAY_A );

		foreach ( (array) $rows as $row ) {
			$key = (string) (int) $row['status_id'];

			if ( '0' === $key ) {
				continue;
			}

			$offered[ $key ] = html_entity_decode( (string) $row['order_status'], ENT_QUOTES, 'UTF-8' );
		}

		if ( $offered ) {
			return $offered;
		}

		return array(
			'2'  => __( 'Order Shipped', 'sales-by-state-report-for-easycart' ),
			'3'  => __( 'Order Confirmed', 'sales-by-state-report-for-easycart' ),
			'6'  => __( 'Card Approved', 'sales-by-state-report-for-easycart' ),
			'12' => __( 'Pending Approval', 'sales-by-state-report-for-easycart' ),
			'16' => __( 'Refunded Order', 'sales-by-state-report-for-easycart' ),
			'19' => __( 'Order Cancelled', 'sales-by-state-report-for-easycart' ),
		);
	}

	/**
	 * The statuses ticked when the report is opened with no explicit filter.
	 *
	 * Defaults to EasyCart statuses marked approved (`is_approved = 1`).
	 *
	 * @return string[]
	 */
	public static function default_statuses() {
		global $wpdb;

		$defaults = array();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col( 'SELECT status_id FROM ec_orderstatus WHERE is_approved = 1 ORDER BY status_id ASC' );

		foreach ( (array) $ids as $id ) {
			$defaults[] = (string) (int) $id;
		}

		if ( ! $defaults ) {
			$defaults = array( '2', '3', '6' );
		}

		/**
		 * Filters the order statuses the report starts on.
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $statuses Status keys.
		 */
		$statuses = (array) apply_filters( 'sbsec_default_statuses', $defaults );

		return array_values( array_intersect( array_map( 'strval', $statuses ), self::status_keys() ) );
	}

	/**
	 * Status IDs as strings. PHP stores numeric array keys as ints.
	 *
	 * @return string[]
	 */
	public static function status_keys() {
		return array_map( 'strval', array_keys( self::order_statuses() ) );
	}

	/**
	 * Reduce a request value to statuses this report recognises.
	 *
	 * @param string|array $value Comma-separated list or array of statuses.
	 * @return string[]
	 */
	public static function normalize_statuses( $value ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$offered  = self::status_keys();
		$accepted = array();

		foreach ( $value as $status ) {
			$status = sanitize_key( trim( (string) $status ) );

			if ( in_array( $status, $offered, true ) ) {
				$accepted[] = $status;
			}
		}

		return array_values( array_unique( $accepted ) );
	}

	/**
	 * Years the filter offers, newest first.
	 *
	 * @return int[]
	 */
	public static function years() {
		$current  = self::default_year();
		$earliest = (int) get_option( self::YEAR_START_OPTION, 0 );

		if ( $earliest <= 0 ) {
			$earliest = $current - ( self::YEAR_WINDOW - 1 );
			update_option( self::YEAR_START_OPTION, $earliest, false );
		}

		if ( $earliest > $current ) {
			$earliest = $current;
		}

		return array_map( 'intval', range( $current, $earliest ) );
	}

	/**
	 * The year the report opens on.
	 *
	 * @return int
	 */
	public static function default_year() {
		return (int) current_time( 'Y' );
	}

	/**
	 * Reduce a request value to a year the filter offers.
	 *
	 * @param mixed $year Requested year.
	 * @return int
	 */
	public static function normalize_year( $year ) {
		$year = (int) $year;

		return in_array( $year, self::years(), true ) ? $year : self::default_year();
	}

	/**
	 * The country the report opens on.
	 *
	 * @return string Two-letter country code.
	 */
	public static function default_country() {
		$code = strtoupper( (string) get_option( 'ec_option_default_country', 'US' ) );

		if ( ! preg_match( '/^[A-Z]{2}$/', $code ) ) {
			$code = 'US';
		}

		if ( self::states_for( $code ) ) {
			return $code;
		}

		foreach ( array_keys( self::countries_with_states() ) as $candidate ) {
			return $candidate;
		}

		return $code;
	}

	/**
	 * Reduce a request value to a two-letter country code.
	 *
	 * @param mixed $country Requested country.
	 * @return string
	 */
	public static function normalize_country( $country ) {
		$country = strtoupper( (string) $country );

		return preg_match( '/^[A-Z]{2}$/', $country ) ? $country : self::default_country();
	}

	/**
	 * Countries the country dropdown always offers.
	 *
	 * @return array<string,string>
	 */
	public static function countries_with_states() {
		return array(
			'US' => __( 'United States', 'sales-by-state-report-for-easycart' ),
			'CA' => __( 'Canada', 'sales-by-state-report-for-easycart' ),
			'GB' => __( 'United Kingdom', 'sales-by-state-report-for-easycart' ),
		);
	}

	/**
	 * State code => name for a country, from EasyCart's country/state tables.
	 *
	 * @param string $country Country code.
	 * @return array<string,string>
	 */
	public static function states_for( $country ) {
		global $wpdb;

		$country = strtoupper( (string) $country );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT s.code_sta, s.name_sta
				 FROM ec_state s
				 INNER JOIN ec_country c ON c.id_cnt = s.idcnt_sta
				 WHERE c.iso2_cnt = %s
				 ORDER BY s.sort_order ASC, s.name_sta ASC',
				$country
			),
			ARRAY_A
		);

		$out = array();

		foreach ( (array) $rows as $row ) {
			$code = (string) $row['code_sta'];

			if ( '' === $code ) {
				continue;
			}

			$out[ $code ] = html_entity_decode( (string) $row['name_sta'], ENT_QUOTES, 'UTF-8' );
		}

		return $out;
	}
}

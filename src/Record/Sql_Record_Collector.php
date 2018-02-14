<?php

namespace Rarst\Laps\Record;

/**
 * Processes SQL events from data logged by wpdb.
 */
class Sql_Record_Collector implements Record_Collector_Interface {

	/** @var array $query_starts Log of query start times. */
	protected $query_starts = [];

	/**
	 * Sets up the query start time log.
	 */
	public function __construct() {

		if ( $this->is_savequeries() ) {
			add_filter( 'query', [ $this, 'query' ], 20 );
		}
	}

	/**
	 * Capture SQL queries start times
	 *
	 * @param string $query SQL query.
	 *
	 * @return string
	 */
	public function query( $query ) {

		global $wpdb;

		if ( empty( $this->query_starts ) && ! empty( $wpdb->queries ) ) {
			$this->query_starts[ count( $wpdb->queries ) ] = microtime( true ) * 1000;
		} else {
			$this->query_starts[] = microtime( true ) * 1000;
		}

		return $query;
	}

	/**
	 * @return Sql_Record[]
	 */
	public function get_records() {

		if ( ! $this->is_savequeries() ) {
			return [];
		}

		global $wpdb;

		$query_data     = [];
		$last_query_end = 0;

		foreach ( $wpdb->queries as $key => list( $sql, $duration ) ) {
			$query_start = isset( $this->query_starts[ $key ] ) ? $this->query_starts[ $key ] : $last_query_end;
			$sql         = trim( $sql );
			$category    = 'query-read';

			if ( 0 === stripos( $sql, 'INSERT' ) || 0 === stripos( $sql, 'UPDATE' ) ) {
				$category = 'query-write';
			}

			$duration      *= 1000;
			$last_query_end = $query_start + $duration;

			$query_data[] = new Sql_Record( $sql, $query_start, $duration, $category );
		}

		return $query_data;
	}

	/**
	 * @return bool
	 */
	protected function is_savequeries() {

		return defined( 'SAVEQUERIES' ) && SAVEQUERIES;
	}
}

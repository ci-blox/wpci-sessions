<?php

namespace WpCI_Sessions;

class List_Table extends \WP_List_Table {

	/**
	 * Prepare the items for the list table
	 */
	public function prepare_items() {
		global $wpdb;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);

		$per_page = 20;
		$paged = ( isset( $_GET['paged'] ) ) ? (int)$_GET['paged'] : 1;
		$offset = $per_page * ( $paged - 1 );

		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->wpci_sessions ORDER BY timestamp DESC LIMIT %d,%d", $offset, $per_page ) );
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->wpci_sessions" );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page,
		) );

	}

	/**
	 * Message for no items found
	 */
	public function no_items() {
		_e( 'No sessions found.', 'wpci-sessions' );
	}

	/**
	 * Get the columns in the list table
	 */
	public function get_columns() {
		return array(
			'id'            => __( 'Session ID', 'wpci-sessions' ),
			//'user_id'               => __( 'User ID', 'wpci-sessions' ),
			'ip_address'            => __( 'IP Address', 'wpci-sessions' ),
			'timestamp'              => __( 'Last Active', 'wpci-sessions' ),
			'data'                  => __( 'Data', 'wpci-sessions' ),
			);
	}

	/**
	 * Render a column value
	 */
	public function column_default( $item, $column_name ) {
		if ( $column_name == 'data' ) {
			return '<code>' . esc_html( $item->data ) . '</code>';
		} else if ( $column_name == 'id' ) {
			$query_args = array(
				'action'       => 'wpci_clear_session',
				'nonce'        => wp_create_nonce( 'wpci_clear_session' ),
				'session'      => $item->id,
			);
			$actions = array(
				'clear'           => '<a href="' . esc_url( add_query_arg( $query_args, admin_url( 'admin-ajax.php' ) ) ) . '">' . esc_html__( 'Clear', 'wpci-sessions' ) . '</a>',
				);
			return esc_html( $item->id ) . $this->row_actions( $actions );
		} else if ( $column_name == 'timestamp' ) {
			return esc_html( sprintf( esc_html__( '%s ago', 'wpci-sessions' ), human_time_diff( $item->timestamp ) ) );
		} else {
			return esc_html( $item->$column_name );
		}
	}

}

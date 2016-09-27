<?php

class Test_Init_Plugin extends WP_UnitTestCase {

	public function test_plugin_loaded() {
		$this->assertTrue( class_exists( 'WpCI_Sessions' ) );
	}

	public function test_database_created() {
		global $wpdb, $table_prefix;

		$table_name = "{$table_prefix}wpci_sessions";
		$this->assertEquals( $table_name, $wpdb->wpci_sessions );

		$column_data = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );
		$columns = wp_list_pluck( $column_data, 'Field' );
		$this->assertEquals( array(
			'user_id',
			'session_id',
			'secure_session_id',
			'ip_address',
			'datetime',
			'data',
			), $columns );

	}

}


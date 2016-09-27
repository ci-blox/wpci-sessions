<?php

namespace WpCI_Sessions;

use WP_CLI;

/**
 * Interact with WpCI Sessions
 */
class CLI_Command extends \WP_CLI_Command {

	/**
	 * List all registered sessions.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count, ids. Default: table
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		global $wpdb;

		if ( ! WP_CI_SESSIONS_ENABLED ) {
			WP_CLI::error( "WpCI Sessions is currently disabled." );
		}

		$defaults = array(
			'format'      => 'table',
			'fields'      => 'session_id,timestamp,ip_address,data',
			);
		$assoc_args = array_merge( $defaults, $assoc_args );

		$sessions = array();
		foreach( new \WP_CLI\Iterators\Query( "SELECT * FROM {$wpdb->wpci_sessions} ORDER BY `timestamp` DESC" ) as $row ) {
			$sessions[] = $row;
		}

		\WP_CLI\Utils\Format_Items( $assoc_args['format'], $sessions, $assoc_args['fields'] );

	}

	/**
	 * Delete one or more sessions.
	 *
	 * [<session-id>...]
	 * : One or more session IDs
	 *
	 * [--all]
	 * : Delete all sessions.
	 *
	 * @subcommand delete
	 */
	public function delete( $args, $assoc_args ) {
		global $wpdb;

		if ( ! WP_CI_SESSIONS_ENABLED ) {
			WP_CLI::error( "WpCI Sessions is currently disabled." );
		}

		if ( isset( $assoc_args['all'] ) ) {
			$args = $wpdb->get_col( "SELECT id FROM {$wpdb->wpci_sessions}" );
			if ( empty( $args ) ) {
				WP_CLI::warning( "No sessions to delete." );
			}
		}

		foreach( $args as $session_id ) {
			$session = \WpCI_Sessions\Session::get_by_sid( $session_id );
			if ( $session ) {
				$session->destroy();
				WP_CLI::log( sprintf( "Session destroyed: %s", $session_id ) );
			} else {
				WP_CLI::warning( sprintf( "Session doesn't exist: %s", $session_id ) );
			}
		}

	}

}

\WP_CLI::add_command( 'wpci session', '\WpCI_Sessions\CLI_Command' );

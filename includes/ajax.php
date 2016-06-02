<?php
/**
 * Ajax functions
 *
 * @since  1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Fetch all reactions for a given activity
 *
 * This is to avoid overheads in queries. Reactions will only
 * be fetched once the user clicks on the React button.
 *
 * @since  1.0.0
 *
 * @return string a JSON encoded reply.
 */
function bp_activity_reactions_fetch() {
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		wp_send_json_error( array(
			'message' => __( 'The action was not sent correctly.', 'bp-reactions' ),
		) );
	}

	$not_allowed = array( 'message' => __( 'You are not allowed to perform this action.', 'bp-reactions' ) );

	// Nonce check
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_reactions_fetch' ) ) {
		wp_send_json_error( $not_allowed );
	}

	$reactions = array();

	if ( ! empty( $_POST['activity_id'] ) ) {
		$reactions_data = bp_reactions_activity_get_users( (int) $_POST['activity_id'] );
	}

	foreach ( (array) bp_reactions_get_reactions() as $key => $reaction ) {
		$users = array();

		if ( isset( $reactions_data[ $reaction->reaction_type ]['users'] ) ) {
			$users = $reactions_data[ $reaction->reaction_type ]['users'];
		}

		$reactions[ $key ] = array(
			'reacted' => in_array( bp_loggedin_user_id(), $users ),
			'emoji'   => $reaction->emoji,
			'count'   => count( $users ),
		);
	}

	if ( empty( $reactions ) ) {
		wp_send_json_error( array(
			'message' => __( 'No registered reactions.', 'bp-reactions' ),
		) );
	}

	wp_send_json_success( $reactions );
}
add_action( 'wp_ajax_bp_activity_reactions_fetch', 'bp_activity_reactions_fetch' );
add_action( 'wp_ajax_nopriv_bp_activity_reactions_fetch', 'bp_activity_reactions_fetch' );

/**
 * Add or remove a reaction.
 *
 * @since 1.0.0
 *
 * @return string a JSON encoded reply.
 */
function bp_activity_reactions_save() {
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		wp_send_json_error( array(
			'message' => __( 'The action was not sent correctly.', 'bp-reactions' ),
		) );
	}

	$not_allowed = array( 'message' => __( 'You are not allowed to perform this action.', 'bp-reactions' ) );
	$unknown     = array( 'message' => __( 'Oops unknown action.', 'bp-reactions' ) );

	// Nonce check
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_reactions_save' ) ) {
		wp_send_json_error( $not_allowed );
	}

	$react = wp_parse_args( $_POST, array(
		'activity_id' => 0,
		'reaction'    => '',
		'doaction'    => 'add',
	) );

	if ( empty( $react['reaction'] ) || empty( $react['action'] ) || empty( $react['activity_id'] ) ) {
		wp_send_json_error( $unknown );
	}

	$reaction = bp_reactions_get_reaction( $react['reaction'] );

	if ( empty( $reaction->reaction_type ) ) {
		wp_send_json_error( $unknown );
	}

	if ( 'add' === $react['doaction'] ) {
		$reacted = bp_activity_reactions_add( $react['activity_id'], array( 'type' => $reaction->reaction_type ) );
	} else {
		$reacted = bp_activity_reactions_remove( $react['activity_id'], $reaction->reaction_name );
	}

	if ( empty( $reacted ) ) {
		wp_send_json_error( array(
			'message' => __( 'Saving the reaction failed.', 'bp-reactions' ),
		) );
	}

	wp_send_json_success( $reacted );
}
add_action( 'wp_ajax_bp_activity_reactions_save', 'bp_activity_reactions_save' );

/**
 * Migrate the BP Favorites to favorite reactions from the
 * BuddyPress tools screen.
 *
 * @since  1.0.0
 *
 * @return string a JSON encoded reply.
 */
function bp_reactions_migrate() {
	$error = array(
		'message'   => __( 'The task could not process due to an error', 'bp-reactions' ),
		'type'      => 'error'
	);

	if ( empty( $_POST['id'] ) || ! isset( $_POST['count'] ) || ! isset( $_POST['done'] ) || ! isset( $_POST['step'] ) ) {
		wp_send_json_error( $error );
	}

	// Add the action to the error
	$callback          = sanitize_key( $_POST['id'] );
	$error['callback'] = $callback;

	// Check nonce
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp-reactions-migrate' ) ) {
		wp_send_json_error( $error );
	}

	// Check capability
	if ( ! current_user_can( 'manage_options' ) || ! is_callable( $callback ) ) {
		wp_send_json_error( $error );
	}

	$step   = (int) $_POST['step'];
	$number = 1;
	if ( ! empty( $_POST['number'] ) ) {
		$number = (int) $_POST['number'];
	}

	$did = call_user_func_array( $callback, array( $step, $number ) );
	wp_send_json_success( array( 'done' => $did, 'callback' => $callback ) );
}
add_action( 'wp_ajax_bp_reactions_migrate', 'bp_reactions_migrate' );

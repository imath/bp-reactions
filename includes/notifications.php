<?php
/**
 * Reaction notifications!
 *
 * @since  1.1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Simulate a BuddyPress component to register the notification callback
 *
 * @since 1.1.0
 */
function bp_reactions_set_up_notification_component() {
	buddypress()->reactions = (object) array(
		'id'                    => 'reactions',
		'notification_callback' => 'bp_reactions_format_notifications',
	);
}
add_action( 'bp_notifications_setup_globals', 'bp_reactions_set_up_notification_component' );

/**
 * Include our simulated component in the notifications allowed ones.
 *
 * @since 1.1.0
 *
 * @param  array $components The allowed components for notifications' feature.
 * @return array             The allowed components for notifications' feature.
 */
function bp_reactions_register_component_name( $components = array() ) {
	return array_merge( $components, array( 'reactions' ) );
}
add_filter( 'bp_notifications_get_registered_components', 'bp_reactions_register_component_name' );

/**
 * Notify a user when one of his activities has been "reacted".
 *
 * @since 1.1.0
 *
 * @param int|false  $activity_id The ID of the reaction created. False if no reaction was created.
 * @param array      $params      The reaction parameters.
 */
function bp_reactions_notify_user( $activity_id = false, $params = array() ) {
	if ( ! $activity_id || empty( $params ) || (int) bp_loggedin_user_id() === (int) $params['secondary_item_id'] ) {
		return;
	}

	bp_notifications_add_notification( array(
		'user_id'           => $params['secondary_item_id'], // The activity author.
		'item_id'           => $params['item_id'],           // The reacted activity ID.
		'secondary_item_id' => $params['user_id'],           // The reaction author.
		'component_name'    => 'reactions',
		'component_action'  => $params['type'],              // The reaction type.
		'is_new'            => 1,
	) );
}
add_action( 'bp_activity_reactions_added', 'bp_reactions_notify_user', 10, 2 );

/**
 * Format the reaction notifications for display.
 *
 * @since 1.1.0
 *
 * @param string $action            The kind of notification being rendered.
 * @param int    $item_id           The primary item id.
 * @param int    $secondary_item_id The secondary item id.
 * @param int    $total_items       The total number of messaging-related notifications waiting for the user.
 * @param string $format            'string' for the user's notifications screen, 'array' for WP Toolbar.
 * @return string|array             The notification's content.
 */
function bp_reactions_format_notifications( $component_action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {
	$bpr = bp_reactions();

	$reaction = bp_reactions_get_reaction_by_activity_type( $component_action );

	if ( is_null( $reaction ) ) {
		return;
	}

	if ( $total_items > 1 ) {
		$link = add_query_arg( 'type', $component_action, bp_get_notifications_permalink() );
		$text = __( 'Some users reacted to one of your activities', 'bp-reactions' );

		if ( ! empty( $reaction->notification_texts['plural'] ) ) {
			$text = $reaction->notification_texts['plural'];
		}
	} else {
		$link        = add_query_arg( 'reaction', 'read', bp_activity_get_permalink( $item_id ) );
		$displayname = bp_core_get_user_displayname( $secondary_item_id );
		$text        = sprintf( __( '%s reacted to one of your activities', 'bp-reactions' ), $displayname );

		if ( ! empty( $reaction->notification_texts['singular'] ) ) {
			$text = sprintf( $reaction->notification_texts['singular'], $displayname );
		}
	}

	if ( 'string' === $format ) {
		$return = sprintf( '<a href="%1$s">%2$s</a>',
			esc_url( $link ),
			esc_html( $text )
		);
	} else {
		$return = array(
			'text' => esc_html( $text ),
			'link' => esc_url( $link ),
		);
	}

	return $return;
}

/**
 * Mark as read screen notifications about reactions.
 *
 * @since 1.1.0
 *
 * @param BP_Activity_Activity $activity The displayed activity object.
 */
function bp_reactions_remove_screen_notifications( $activity ) {
	if ( ! is_user_logged_in() || ! isset( $_GET['reaction'] ) || empty( $activity->id ) ) {
		return;
	}

	BP_Notifications_Notification::update(
		array(
			'is_new' => false,
		),
		array(
			'user_id'          => bp_loggedin_user_id(),
			'item_id'          => $activity->id,
			'component_name'   => 'reactions',
		)
	);
}
add_action( 'bp_activity_screen_single_activity_permalink', 'bp_reactions_remove_screen_notifications' );

/**
 * Delete reation notifications when the parent activity is deleted.
 *
 * @since 1.1.0.
 *
 * @param array $activity_ids_deleted The list of deleted activity IDs.
 */
function bp_reactions_delete_notifications( $activity_ids_deleted = array() ) {
	if ( empty( $activity_ids_deleted ) ) {
		return;
	}

	foreach ( $activity_ids_deleted as $activity_id ) {
		BP_Notifications_Notification::delete( array(
			'item_id'           => $activity_id,
			'component_name'    => 'reactions',
		) );
	}
}
add_action( 'bp_activity_deleted_activities', 'bp_reactions_delete_notifications', 10 );

/**
 * Remove a notification when the reaction has been removed.
 *
 * @since 1.1.0
 *
 * @param bool                 $deleted  True if the reaction was deleted. False otherwise.
 * @param BP_Activity_Activity $activity The parent activity object.
 */
function bp_reactions_remove_notification( $deleted = false, $activity = null ) {
	if ( ! $deleted || empty( $activity->item_id ) ) {
		return;
	}

	BP_Notifications_Notification::delete( array(
		'item_id'           => $activity->item_id,
		'secondary_item_id' => $activity->user_id,
		'component_name'    => 'reactions',
		'component_action'  => $activity->type,
	) );
}
add_action( 'bp_activity_reactions_removed', 'bp_reactions_remove_notification', 10, 2 );

<?php
/**
 * add_filter() Hooks
 *
 * @since  1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Disable BuddyPress Activity favorites.
 *
 * One of the reasons for this plugin is to try avoid a persistent bug with
 * BuddyPress activity favorites.
 * @see https://buddypress.trac.wordpress.org/ticket/3794
 *
 * @since  1.0.0
 *
 * @param  bool $can_favorite Whether the use can "BP" favorite an activity.
 * @return bool               False to disable favorites. True otherwise.
 */
function bp_reactions_override_favorites( $can_favorite ) {
	return 1 === bp_reactions_disable_replace_favorites();
}
add_filter( 'bp_activity_can_favorite', 'bp_reactions_override_favorites' );

/**
 * Add a reactions item to Loggedin user's activity WP Admin Bar
 *
 * @since  1.0.0
 *
 * @param  array  $wp_admin_nav The WP Admin Bar items.
 * @return array                The WP Admin Bar items.
 */
function bp_reactions_setup_admin_nav( $wp_admin_nav = array() ) {
	$position = 30;

	// A unique "Reactions" admin nav
	if ( bp_reactions_is_unique_subnav() ) {
		$wp_admin_nav[] = array(
			'parent'   => 'my-account-activity',
			'id'       => 'my-account-activity-reactions',
			'title'    => _x( 'Reactions', 'My Account Activity Reactions sub nav', 'bp-reactions' ),
			'href'     => trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() ) . 'reactions/',
			'position' => $position
		);

	// An admin nav for each registered reactions.
	} else {
		foreach ( (array) bp_reactions_get_reactions() as $reaction ) {
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-activity',
				'id'       => 'my-account-activity-' . $reaction->reaction_name,
				'title'    => $reaction->label,
				'href'     => trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() ) . $reaction->reaction_name . '/',
				'position' => $position
			);

			$position += 1;
		}
	}

	return $wp_admin_nav;
}
add_filter( 'bp_activity_admin_nav', 'bp_reactions_setup_admin_nav', 10, 1 );

/**
 * Make sure Reactions cannot be commented.
 *
 * @since  1.0.0
 *
 * @param  bool   $can_comment Whether the user can comment the activity.
 * @param  string $type        The activity type.
 * @return bool                True if user can comment. False otherwise.
 */
function bp_activity_reaction_can_comment( $can_comment, $type = '' ) {
	if ( is_array( $type ) ) {
		$type = $type['type'];
	}

	$reaction = bp_reactions_get_reaction_by_activity_type( $type );

	if ( isset( $reaction->can_comment ) ) {
		$can_comment = $reaction->can_comment;
	}

	return $can_comment;
}
add_filter( 'bp_activity_can_comment', 'bp_activity_reaction_can_comment', 10, 2 );
add_filter( 'bp_activity_list_table_can_comment','bp_activity_reaction_can_comment', 10, 2 );

/**
 * Make sure Reactions will not be fetched as activities into the stream.
 *
 * @since  1.0.0
 *
 * @param  array  $where_conditions An array of SQL WHERE clauses for the activity stream.
 * @param  array  $r                The arguments used to get the activities.
 * @return array                    The array of SQL WHERE clauses.
 */
function bp_activity_reaction_excluded_components( $where_conditions = array(), $r = array() ) {
	global $wpdb;

	// Do nothing if we want the activity the user has reacted to
	if ( ! empty( $r['scope'] ) && 'reactions' === $r['scope'] ) {
		return $where_conditions;
	}

	// Do nothing if we want all reactions!
	if ( ! empty( $r['filter']['object'] ) && 'reactions' === $r['filter']['object'] ) {
		return $where_conditions;
	}

	if ( ! empty( $r['filter']['action'] ) ) {
		$filtered_action = $r['filter']['action'];
	}

	$reaction_types = wp_list_pluck( bp_reactions_get_reactions(), 'reaction_type' );

	if ( ! empty( $filtered_action ) && in_array( $filtered_action, $reaction_types ) ) {
		return $where_conditions;

	/**
	 * If you wish to list reactions on the user's profile, filter here.
	 *
	 * @since  1.0.0
	 *
	 * @param bool $value True to show reactions on user's stream. False otherwise.
	 */
	} elseif ( ! bp_is_activity_directory() && ! bp_is_group_activity() && true === apply_filters( 'bp_reactions_user_stream_show', false ) ) {
		return $where_conditions;

	// Regular behavior is to hide reactions.
	} else {
		$where_conditions['excluded_components'] = $wpdb->prepare( 'a.component != %s', 'reactions' );
	}

	return $where_conditions;
}
add_filter( 'bp_activity_get_where_conditions', 'bp_activity_reaction_excluded_components', 10, 2 );

/**
 * Make sure the Primary link to the parent activity will be used
 * if the reaction is displayed into the stream. (By default it's not).
 *
 * @since  1.0.0
 *
 * @param  string               $link     The link to the reaction.
 * @param  BP_Activity_Activity $activity The activity object for the reaction.
 * @return string               The link to the parent activity if needed. Unchanged otherwise.
 */
function bp_reactions_activity_permalink( $link = '', $activity = null ) {
	if ( ! isset( $activity->type ) ) {
		return $link;
	}

	$reaction = bp_reactions_get_reaction_by_activity_type( $activity->type );

	if ( ! empty( $reaction ) ) {
		$link = $activity->primary_link;
	}

	return $link;
}
add_filter( 'bp_activity_get_permalink', 'bp_reactions_activity_permalink', 10, 2 );

/**
 * Filter the activity stream to only fetch activities having a reaction.
 *
 * @since  1.0.0
 *
 * @param  array  $args The Activity stream arguments.
 * @return array        The Activity stream arguments.
 */
function bp_reactions_set_popular_scope_args( $args = array() ) {
	if ( isset( $args['scope'] ) && 'popular' === $args['scope'] ) {
		$args['meta_query'] = array(
			array(
				'key' => 'bp_reactions_count',
				'compare' => 'exists',
			)
		);
	}

	return $args;
}
add_filter( 'bp_before_has_activities_parse_args', 'bp_reactions_set_popular_scope_args', 10, 1 );

/**
 * Order activities according to the number of reactions they got.
 *
 * @since  1.0.0
 *
 * @param  string $sql  The activity stream query.
 * @param  array  $args The activity stream arguments.
 * @return string       The activity stream query.
 */
function bp_reactions_set_popular_scope_order( $sql = '', $args = array() ) {
	if ( isset( $args['scope'] ) && 'popular' === $args['scope'] ) {
		// @todo improve..
		preg_match( '/(.*?) = \'bp_reactions_count\'/', $sql, $matches );

		if ( ! empty( $matches[1] ) ) {
			$field = str_replace( 'meta_key', 'meta_value', trim( $matches[1] ) );

			if ( ! empty( $field ) && false !== strpos( $field, 'meta_value' ) ) {
				$sql = str_replace( 'ORDER BY', 'ORDER BY ' . $field . ' + 0 DESC,', $sql );
			}
		}
	}

	return $sql;
}
add_filter( 'bp_activity_paged_activities_sql', 'bp_reactions_set_popular_scope_order', 10, 2 );

/**
 * As popular activities are not displayed according to their date of creation
 * but the number of reactions, we need to trick the heartbeat feature to make sure
 * it won't change this order.
 *
 * @since  1.0.0
 *
 * @param  array  $args Activity Heartbeat arguments.
 * @return array        Activity Heartbeat arguments.
 */
function bp_reactions_disable_heartbeat_for_popular_scope( $args = array() ) {
	if ( isset( $args['scope'] ) && 'popular' === $args['scope'] ) {
		$args['since'] = bp_core_current_time();
	}

	return $args;
}
add_filter( 'bp_before_activity_latest_args_parse_args', 'bp_reactions_disable_heartbeat_for_popular_scope', 10, 1 );

/**
 * Make sure the parent activity the displayed user reacted to will be displayed
 * into the reactions activity subnav of their profile.
 *
 * @since  1.0.0
 *
 * @param  array  $retval The original activity stream attributes.
 * @param  array  $filter Activity stream filters.
 * @return array          Activity stream overrides for the reactions scope.
 */
function bp_reactions_filter_user_scope( $retval = array(), $filter = array() ) {
	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$scope = bp_current_action();

	// Determine the reactions.
	if ( 'reactions' === $scope ) {
		$reactions = bp_reactions_get_user_reactions( $user_id );
	} else {
		$reaction = bp_reactions_get_reaction( $scope );

		if ( ! empty( $reaction->reaction_type ) ) {
			$reactions = bp_reactions_get_user_reactions( $user_id, $reaction->reaction_type );
		}
	}

	if ( empty( $reactions ) ) {
		$reactions = array( 0 );
	}

	// Should we show all items regardless of sitewide visibility?
	$show_hidden = array();
	if ( ! empty( $user_id ) && ( $user_id !== bp_loggedin_user_id() ) ) {
		$show_hidden = array(
			'column' => 'hide_sitewide',
			'value'  => 0
		);
	}

	$retval = array(
		'relation' => 'AND',
		array(
			'column'  => 'id',
			'compare' => 'IN',
			'value'   => (array) $reactions
		),
		$show_hidden,

		// Overrides.
		'override' => array(
			'scope'            => $scope,
			'display_comments' => true,
			'filter'           => array( 'user_id' => 0 ),
			'show_hidden'      => true
		),
	);

	return $retval;
}
add_filter( 'bp_activity_set_reactions_scope_args', 'bp_reactions_filter_user_scope', 10, 2 );

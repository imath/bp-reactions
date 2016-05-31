<?php
/**
 * add_action() Hooks
 *
 * @since  1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Register Default reactions (favorites/likes)
add_action( 'bp_init', 'bp_reactions_register_default_reactions', 1 );

// Reactions are specific activities we need to set into the corresponding BuddyPress global
add_action( 'bp_register_activity_actions', 'bp_reactions_register_activity_actions' );

// Update the reactions for an activity when one of its reaction has been deleted or if the parent activity was deleted
add_action( 'bp_activity_after_delete', 'bp_reactions_update_activity_reactions', 10, 1 );

/** Template tag overrides ***************************************************/

/**
 * Add a React button to Activity entries action buttons.
 *
 * @since  1.0.0
 *
 * @return string HTML Output.
 */
function bp_reactions_button() {
	if ( ! bp_reactions_activity_can_react() ) {
		return;
	}

	$count = (int) bp_activity_get_meta( bp_get_activity_id(), 'bp_reactions_count' );

	printf( '<a href="#" class="button react bp-primary-action" title="%1$s" data-bp-activity-id="%2$s">%3$s</a>',
		esc_attr__( 'React', 'bp-reactions' ),
		esc_attr( bp_get_activity_id() ),
		esc_html__( 'React', 'bp-reactions' ) . ' <span>' . $count . '</span>'
	);
}
add_action( 'bp_activity_entry_meta', 'bp_reactions_button' );

/**
 * Add the container for reactions before activity comments.
 *
 * @since  1.0.0
 *
 * @return string HTML Output.
 */
function bp_reactions_container() {
	print( '<div class="activity-reactions"></div>' );
}
add_action( 'bp_before_activity_entry_comments', 'bp_reactions_container' );

/**
 * Add a reactions subnav to BuddyPress Diplayed User's Activity nav
 *
 * @since 1.0.0
 */
function bp_reactions_setup_subnav() {
	$slug     = bp_get_activity_slug();
	$position = 30;

	// A unique "Reactions" subnav
	if ( bp_reactions_is_unique_subnav() ) {
		bp_core_new_subnav_item( array(
			'name'            => _x( 'Reactions', 'Displayed member activity reations sub nav', 'bp-reactions' ),
			'slug'            => 'reactions',
			'parent_url'      => trailingslashit( bp_displayed_user_domain() . $slug ),
			'parent_slug'     => $slug,
			'screen_function' => 'bp_activity_screen_my_activity',
			'position'        => 30,
			'item_css_id'     => 'activity-reactions'
		), 'members' );

	// A subnav for each registered reactions.
	} else {
		foreach ( (array) bp_reactions_get_reactions() as $reaction ) {
			bp_core_new_subnav_item( array(
				'name'            => $reaction->label,
				'slug'            => $reaction->reaction_name,
				'parent_url'      => trailingslashit( bp_displayed_user_domain() . $slug ),
				'parent_slug'     => $slug,
				'screen_function' => 'bp_activity_screen_my_activity',
				'position'        => $position,
				'item_css_id'     => 'activity-' . $reaction->reaction_name
			), 'members' );

			$position += 1;
		}
	}
}
add_action( 'bp_activity_setup_nav', 'bp_reactions_setup_subnav' );

/**
 * Add a "Popular" tab to the activity directory to list
 * the activity users reacted ordered by the number of these reactions.
 *
 * @since  1.0.0
 *
 * @return string HTML Output.
 */
function bp_reactions_activity_directory_tab() {
	printf( '<li id="activity-popular"><a href="#" title="%1$s">%2$s</a></li>',
		esc_attr__( 'Popular updates', 'bp-reactions' ),
		esc_html__( 'Popular', 'bp-reactions' )
	);
}
add_action( 'bp_before_activity_type_tab_friends', 'bp_reactions_activity_directory_tab' );

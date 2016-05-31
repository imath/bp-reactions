<?php
/**
 * Admin functions
 *
 * @since  1.0.0
 */

/**
 * Display the option checkbox
 *
 * @since 1.0.0
 */
function bp_reactions_setting_callback_unique_subnav() {
	?>
	<input id="bp_reactions_use_unique_subnav" name="_bp_reactions_use_unique_subnav" type="checkbox"value="1" <?php checked( 1 === bp_reactions_is_unique_subnav() ); ?> />
	<label for="bp_reactions_use_unique_subnav"><?php _e( 'Use a unique &quot;Reactions&quot; subnav for user profiles.', 'bp-reactions' ); ?></label>
	<p class="description"><?php _e( 'When you have registered a lot of custom reactions, it can be interesting to group them into this unique subnav.', 'bp-reactions' ); ?></p>
	<?php
}

/**
 * Regiter a specific settings for the Activity section
 *
 * @since 1.0.0
 */
function bp_reactions_admin_settings() {
	add_settings_field( '_bp_reactions_use_unique_subnav', __( 'User\'s profile reactions subnav', 'bp-reactions' ), 'bp_reactions_setting_callback_unique_subnav', 'buddypress', 'bp_activity' );
	register_setting( 'buddypress', '_bp_reactions_use_unique_subnav', 'intval' );
}
add_action( 'bp_register_admin_settings', 'bp_reactions_admin_settings', 20 );

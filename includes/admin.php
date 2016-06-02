<?php
/**
 * Admin functions
 *
 * @since  1.0.0
 */

/**
 * Display the option checkbox for the favorite replace callback
 *
 * @since 1.0.0
 */
function bp_reactions_setting_callback_replace_favorites() {
	?>
	<input id="bp_reactions_replace_favorites" name="_bp_reactions_disable_replace_favorites" type="checkbox" value="1" <?php checked( 1 === bp_reactions_disable_replace_favorites() ); ?> />
	<label for="bp_reactions_replace_favorites"><?php _e( 'Do not replace the BuddyPress favorites with the BP Reactions favorites', 'bp-reactions' ); ?></label>
	<?php
}

/**
 * Display the option checkbox for the unique subnav callback
 *
 * @since 1.0.0
 */
function bp_reactions_setting_callback_unique_subnav() {
	?>
	<input id="bp_reactions_use_unique_subnav" name="_bp_reactions_use_unique_subnav" type="checkbox" value="1" <?php checked( 1 === bp_reactions_is_unique_subnav() ); ?> />
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
	add_settings_field( '_bp_reactions_disable_replace_favorites', __( 'Activity favorites', 'bp-reactions' ), 'bp_reactions_setting_callback_replace_favorites', 'buddypress', 'bp_activity' );
	register_setting( 'buddypress', '_bp_reactions_disable_replace_favorites', 'intval' );

	add_settings_field( '_bp_reactions_use_unique_subnav', __( 'User\'s profile reactions subnav', 'bp-reactions' ), 'bp_reactions_setting_callback_unique_subnav', 'buddypress', 'bp_activity' );
	register_setting( 'buddypress', '_bp_reactions_use_unique_subnav', 'intval' );
}
add_action( 'bp_register_admin_settings', 'bp_reactions_admin_settings', 20 );

/**
 * Plugin's Upgrader
 *
 * @since 1.0.0
 */
function bp_reactions_upgrade() {
	$db_version = bp_get_option( '_bp_reactions_version', '' );

	if ( version_compare( $db_version, bp_reactions()->version, '=' ) ) {
		return;
	}

	// Finally upgrade plugin version
	bp_update_option( '_bp_reactions_version', bp_reactions()->version );
}
add_action( 'bp_admin_init', 'bp_reactions_upgrade', 1000 );

/**
 * Add a new Area to the BuddyPress Admin Tools to let site owners
 * migrate the BP Favorites to BP Reactions favorites.
 *
 * @since  1.0.0
 *
 * @return string HTML output
 */
function bp_reactions_migrate_tool() {
	// If BuddyPress favorites are in use, don't show.
	if ( bp_activity_can_favorite() ) {
		return;
	}

	$users_favorites = bp_reactions_get_users_to_migrate();

	if ( ! $users_favorites ) {
		return;
	}
	?>
	<div class="wrap">
		<form class="settings" method="post">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Migration tools', 'bp-reactions' ) ?></th>
						<td>
							<fieldset id="bp-reactions-migrate">
								<legend class="screen-reader-text"><span><?php
									/* translators: accessibility text */
									esc_html_e( 'Migrate', 'bp-reactions' );
								?></span></legend>

								<label for="bp_reactions_migrate_favorites">
									<input type="checkbox" class="checkbox" data-number="1" data-message="<?php esc_attr_e( 'Migrating user favorites to favorite reactions', 'bp-reactions' ); ?>" id="bp_reactions_migrate_favorites" value="<?php echo esc_attr( $users_favorites ); ?>" />
									<?php _e( 'Migrate BuddyPress favorites to BP Reactions favorites.', 'bp-reactions' ) ?>
								</label><br />

								<?php do_action( 'bp_reactions_migrate_tasks' ) ;?>

							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<fieldset class="submit">
				<input class="button-primary" type="submit" id="bp-reactions-migrate-submit" value="<?php esc_attr_e( 'Migrate Items', 'bp-reactions' ); ?>" />
			</fieldset>

			<?php
				wp_enqueue_style  ( 'bp-reactions-migrates' );
				wp_enqueue_script ( 'bp-reactions-migrates' );
				wp_localize_script( 'bp-reactions-migrates', 'BP_Reaction_Migrate', array(
					'notask'  => __( 'Please select a migration task.', 'bp-reactions' ),
					'success' => __( 'Task completed with success.', 'bp-reactions' ),
					'nonce'   => wp_create_nonce( 'bp-reactions-migrate' ),
				) );
			?>

			<div id="bp-reactions-migrates"></div>

			<script type="text/html" id="tmpl-progress-window">
				<div id="{{data.id}}">
					<div class="task-description">{{data.message}}</div>
					<div class="bp-reactions-progress">
						<div class="bp-reactions-bar"></div>
					</div>
				</div>
			</script>
		</form>
	</div>
	<?php
}
add_action( 'tools_page_bp-tools', 'bp_reactions_migrate_tool', 20 );

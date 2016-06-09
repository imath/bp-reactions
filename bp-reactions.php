<?php
/**
 * React to BuddyPress activities!
 *
 * @package   BP Reactions
 * @author    imath
 * @license   GPL-2.0+
 * @link      http://imathi.eu/tag/bp-reactions/
 *
 * @buddypress-plugin {
 * Plugin Name:       BP Reactions
 * Plugin URI:        http://imathi.eu/tag/bp-reactions/
 * Description:       React to BuddyPress activities!
 * Version:           1.0.1
 * Author:            imath
 * Author URI:        http://imathi.eu
 * Text Domain:       bp-reactions
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages/
 * GitHub Plugin URI: https://github.com/imath/bp-reactions
 * }}
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Main Class
 *
 * @since 1.0.0
 */
final class BP_Reactions {
	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	/**
	 * BuddyPress version
	 */
	public static $required_bp_version = '2.5.0';

	/**
	 * Initialize the plugin
	 */
	private function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_hooks();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Sets some globals for the plugin
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {
		/** Plugin globals ********************************************/
		$this->version       = '1.0.1';
		$this->domain        = 'bp-reactions';
		$this->name          = 'BP Reactions';
		$this->file          = __FILE__;
		$this->basename      = plugin_basename( $this->file );
		$this->plugin_dir    = plugin_dir_path( $this->file );
		$this->plugin_url    = plugin_dir_url ( $this->file );
		$this->includes_dir  = trailingslashit( $this->plugin_dir . 'includes'  );
		$this->lang_dir      = trailingslashit( $this->plugin_dir . 'languages' );

		$this->js_url        = trailingslashit( $this->plugin_url . 'js'  );
		$this->css_url       = trailingslashit( $this->plugin_url . 'css' );
		$this->minified      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$this->reactions           = array();
		$this->is_unique_subnav    = bp_get_option( '_bp_reactions_use_unique_subnav', 0 );
		$this->disable_fav_replace = bp_get_option( '_bp_reactions_disable_replace_favorites', 0 );
	}

	/**
	 * Checks BuddyPress version
	 *
	 * @since 1.0.0
	 */
	public function version_check() {
		// taking no risk
		if ( ! function_exists( 'bp_get_version' ) ) {
			return false;
		}

		return version_compare( bp_get_version(), self::$required_bp_version, '>=' );
	}

	/**
	 * Include needed files
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		if ( ! $this->version_check() ) {
			return;
		}

		if ( bp_is_active( 'activity' ) ) {
			require( $this->includes_dir . 'functions.php' );
			require( $this->includes_dir . 'emojis.php'    );
			require( $this->includes_dir . 'ajax.php'      );
			require( $this->includes_dir . 'filters.php'   );
			require( $this->includes_dir . 'actions.php'   );

			if ( is_admin() ) {
				require( $this->includes_dir . 'admin.php'   );
			}
		}
	}

	/**
	 * Set hooks
	 *
	 * @since 1.0.0
	 */
	private function setup_hooks() {
		// BuddyPress version is ok
		if ( $this->version_check() && bp_is_active( 'activity' ) ) {

			// Register scripts and css.
			add_action( 'bp_enqueue_scripts',       array( $this, 'register_cssjs'       ), 1 );
			add_action( 'bp_admin_enqueue_scripts', array( $this, 'register_admin_cssjs' ), 1 );

			// Enqueue scripts and css.
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_script' ), 8 );

			// Set the activity scope filter according to reactions
			add_action( 'bp_register_activity_actions', array( $this, 'activity_scope_filters' ), 20 );

			// Plugin's ready!
			do_action( 'bp_reactions_ready' );

		// There's something wrong, inform the Administrator
		} else {
			add_action( bp_core_do_network_admin() ? 'network_admin_notices' : 'admin_notices', array( $this, 'admin_warning' ) );
		}

		// load the languages..
		add_action( 'bp_loaded', array( $this, 'load_textdomain' ), 5 );
	}

	/**
	 * Register Front End Scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function register_cssjs() {
		// Credits: @mathias https://mths.be/fromcodepoint
		wp_register_script(
			'fromcodepoint',
			$this->js_url . "fromcodepoint{$this->minified}.js",
			array(),
			'0.2.1',
			true
		);

		wp_register_script(
			'bp-reactions-script',
			$this->js_url . "script{$this->minified}.js",
			array( 'jquery', 'jquery-atwho', 'fromcodepoint' ),
			$this->version,
			true
		);

		wp_register_style(
			'bp-reactions-style',
			$this->css_url . "style{$this->minified}.css",
			array(),
			$this->version
		);
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_script() {
		if ( did_action( 'bp_reactions_enqueued') || ! bp_reactions_is_activity() ) {
			return;
		}

		wp_enqueue_script ( 'bp-reactions-script' );

		$localization = array(
			'ajaxurl'           => admin_url( 'admin-ajax.php', 'relative' ),
			'nonces'            => array(
				'fetch'         => wp_create_nonce( 'bp_reactions_fetch' ),
				'save'          => wp_create_nonce( 'bp_reactions_save' ),
			),
			'emojis'            => bp_reactions_get_emojis(),
			'is_user_logged_in' => is_user_logged_in(),
			'reaction_labels'   => wp_list_pluck( bp_reactions_get_reactions(), 'label' ),
		);

		if ( bp_is_user() ) {
			$localization['user_scope'] = bp_current_action();
		}

		wp_localize_script( 'bp-reactions-script', 'BP_Reactions', $localization );

		wp_enqueue_style( 'bp-reactions-style' );

		do_action( 'bp_reactions_enqueued' );
	}

	/**
	 * Register Back End Scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function register_admin_cssjs() {
		$current_screen = get_current_screen();

		if ( ! isset( $current_screen->id ) || false === strpos( $current_screen->id, 'tools_page_bp-tools' ) ) {
			return;
		}

		wp_register_script(
			'bp-reactions-migrates',
			$this->js_url . "admin{$this->minified}.js",
			array( 'jquery', 'json2', 'wp-backbone' ),
			$this->version,
			true
		);

		wp_register_style(
			'bp-reactions-migrates',
			$this->css_url . "admin{$this->minified}.css",
			array(),
			$this->version
		);
	}

	/**
	 * Filter Activity scopes for each registered reactions.
	 *
	 * @since 1.0.0
	 */
	public function activity_scope_filters() {
		// Don't need to filter scopes when a unique Reactions subnav is used.
		if ( bp_reactions_is_unique_subnav() ) {
			return;
		}

		$reactions = array_keys( (array) bp_reactions_get_reactions() );

		foreach ( $reactions as $reaction ) {
			add_filter( "bp_activity_set_{$reaction}_scope_args", 'bp_reactions_filter_user_scope', 10, 2 );
		}
	}

	/**
	 * Display a message to admin in case config is not as expected
	 *
	 * @since 1.0.0
	 */
	public function admin_warning() {
		$warnings = array();

		if ( ! $this->version_check() ) {
			$warnings[] = sprintf( __( '%s requires at least version %s of BuddyPress.', 'bp-reactions' ), $this->name, '2.5.0' );
		}

		if ( ! bp_is_active( 'activity' ) ) {
			$warnings[] = sprintf( __( '%s requires the BuddyPress Activity component to be active.', 'bp-reactions' ), $this->name );
		}

		if ( ! empty( $warnings ) ) :
		?>
		<div id="message" class="error">
			<?php foreach ( $warnings as $warning ) : ?>
				<p><?php echo esc_html( $warning ) ; ?>
			<?php endforeach ; ?>
		</div>
		<?php
		endif;
	}

	/**
	 * Loads the translation files
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/bp-reactions/' . $mofile;

		// Look in global /wp-content/languages/bp-reactions folder
		if ( ! load_textdomain( $this->domain, $mofile_global ) ) {

			// Look in local /wp-content/plugins/bp-reactions/languages/ folder
			// or /wp-content/languages/plugins/
			load_plugin_textdomain( $this->domain, false, basename( $this->plugin_dir ) . '/languages' );
		}
	}
}

// Let's start !
function bp_reactions() {
	return BP_Reactions::start();
}
add_action( 'bp_include', 'bp_reactions', 9 );

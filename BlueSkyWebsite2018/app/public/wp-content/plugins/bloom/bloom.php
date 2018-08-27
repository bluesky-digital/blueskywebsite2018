<?php
/*
 * Plugin Name: Bloom
 * Plugin URI: http://www.elegantthemes.com/plugins/bloom/
 * Version: 1.3.4
 * Description: A simple, comprehensive and beautifully constructed email opt-in plugin built to help you quickly grow your mailing list.
 * Author: Elegant Themes
 * Author URI: http://www.elegantthemes.com
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'ET_BLOOM_PLUGIN_DIR', trailingslashit( dirname(__FILE__) ) );
define( 'ET_BLOOM_PLUGIN_URI', plugins_url('', __FILE__) );

if ( ! class_exists( 'ET_Dashboard' ) ) {
	require_once( ET_BLOOM_PLUGIN_DIR . 'dashboard/dashboard.php' );
}

class ET_Bloom extends ET_Dashboard {
	var $plugin_version = '1.3.4';
	var $db_version = '1.2';
	var $_options_pagename = 'et_bloom_options';
	var $menu_page;
	var $protocol;

	private $options_version = 1;

	private static $_this;

	public static $scripts_enqueued = false;

	/**
	 * @var ET_Core_Data_Utils
	 */
	protected static $_;

	/**
	 * @var \ET_Core_API_Email_Providers
	 */
	public $providers;

	function __construct() {
		// Don't allow more than one instance of the class
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( esc_html__( '%s is a singleton class and you cannot create a second instance.', 'bloom' ),
				get_class( $this ) )
			);
		}

		self::$_this = $this;

		$this->protocol = is_ssl() ? 'https' : 'http';
		$this->providers = null;

		add_action( 'admin_menu', array( $this, 'add_menu_link' ) );

		add_action( 'plugins_loaded', array( $this, 'add_localization' ), 1 );

		add_filter( 'et_bloom_import_sub_array', array( $this, 'import_settings' ) );
		add_filter( 'et_bloom_import_array', array( $this, 'import_filter' ) );
		add_filter( 'et_bloom_export_exclude', array( $this, 'filter_export_settings' ) );
		add_filter( 'et_bloom_save_button_class', array( $this, 'save_btn_class' ) );

		// generate home tab in dashboard
		add_action( 'et_bloom_after_header_options', array( $this, 'generate_home_tab' ) );

		add_action( 'et_bloom_after_main_options', array( $this, 'generate_premade_templates' ) );

		add_action( 'et_bloom_after_save_button', array( $this, 'add_next_button') );

		$plugin_file = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_{$plugin_file}", array( $this, 'add_settings_link' ) );

		add_action( 'after_setup_theme', array( $this, 'construct_dashboard' ), 11 );

		// Register save settings function for ajax request
		add_action( 'wp_ajax_et_bloom_save_settings', array( $this, 'bloom_save_settings' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 99 );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_scripts' ) );

		add_action( 'wp_ajax_reset_options_page', array( $this, 'reset_options_page' ) );

		add_action( 'wp_ajax_bloom_remove_optin', array( $this, 'remove_optin' ) );

		add_action( 'wp_ajax_bloom_duplicate_optin', array( $this, 'duplicate_optin' ) );

		add_action( 'wp_ajax_bloom_add_variant', array( $this, 'add_variant' ) );

		add_action( 'wp_ajax_bloom_home_tab_tables', array( $this, 'home_tab_tables' ) );

		add_action( 'wp_ajax_bloom_toggle_optin_status', array( $this, 'toggle_optin_status' ) );

		add_action( 'wp_ajax_bloom_authorize_account', array( $this, 'authorize_account' ) );

		add_action( 'wp_ajax_bloom_retrieve_counts', array( $this, 'et_bloom_retrieve_counts' ) );

		add_action( 'wp_ajax_bloom_reset_accounts_table', array( $this, 'reset_accounts_table' ) );

		add_action( 'wp_ajax_bloom_generate_mailing_lists', array( $this, 'generate_mailing_lists' ) );

		add_action( 'wp_ajax_bloom_generate_new_account_fields', array( $this, 'generate_new_account_fields' ) );

		add_action( 'wp_ajax_bloom_generate_accounts_list', array( $this, 'generate_accounts_list' ) );

		add_action( 'wp_ajax_bloom_generate_current_lists', array( $this, 'generate_current_lists' ) );

		add_action( 'wp_ajax_bloom_generate_edit_account_page', array( $this, 'generate_edit_account_page' ) );

		add_action( 'wp_ajax_bloom_save_updates_tab', array( $this, 'save_updates_tab' ) );

		add_action( 'wp_ajax_bloom_save_google_tab', array( $this, 'save_google_tab' ) );

		add_action( 'wp_ajax_bloom_ab_test_actions', array( $this, 'ab_test_actions' ) );

		add_action( 'wp_ajax_bloom_get_stats_graph_ajax', array( $this, 'get_stats_graph_ajax' ) );

		add_action( 'wp_ajax_bloom_refresh_optins_stats_table', array( $this, 'refresh_optins_stats_table' ) );

		add_action( 'wp_ajax_bloom_reset_stats', array( $this, 'reset_stats' ) );

		add_action( 'wp_ajax_bloom_pick_winner_optin', array( $this, 'pick_winner_optin' ) );

		add_action( 'wp_ajax_bloom_clear_stats', array( $this, 'clear_stats' ) );

		add_action( 'wp_ajax_bloom_get_optin_stats', array( $this, 'get_optin_stats' ) );

		add_action( 'wp_ajax_bloom_get_premade_values', array( $this, 'get_premade_values' ) );
		add_action( 'wp_ajax_bloom_generate_premade_grid', array( $this, 'generate_premade_grid' ) );

		add_action( 'wp_ajax_bloom_display_preview', array( $this, 'display_preview' ) );

		add_action( 'wp_ajax_bloom_handle_stats_adding', array( $this, 'handle_stats_adding' ) );
		add_action( 'wp_ajax_nopriv_bloom_handle_stats_adding', array( $this, 'handle_stats_adding' ) );

		add_action( 'wp_ajax_bloom_subscribe', array( $this, 'subscribe' ) );
		add_action( 'wp_ajax_nopriv_bloom_subscribe', array( $this, 'subscribe' ) );

		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );

		add_shortcode( 'et_bloom_inline', array( $this, 'display_inline_shortcode' ) );
		add_shortcode( 'et_bloom_locked', array( $this, 'display_locked_shortcode' ) );

		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );

		add_action( 'bloom_lists_auto_refresh', array( $this, 'perform_auto_refresh' ) );
		add_action( 'bloom_stats_auto_refresh', array( $this, 'perform_stats_refresh' ) );

		add_action( 'admin_init', array( $this, 'maybe_set_db_version' ) );

		$this->frontend_register_locations();

		foreach ( array('post.php','post-new.php') as $hook ) {
			add_action( "admin_head-$hook", array( $this, 'tiny_mce_vars' ) );
			add_action( "admin_head-$hook", array( $this, 'add_mce_button_filters' ) );
		}

		$this->maybe_load_core();
		$this->maybe_update_options_schema();
		et_core_enable_automatic_updates( ET_BLOOM_PLUGIN_URI, $this->plugin_version );
	}

	function construct_dashboard() {
		$dashboard_args = array(
			'et_dashboard_options_pagename'  => $this->_options_pagename,
			'et_dashboard_plugin_name'       => 'bloom',
			'et_dashboard_save_button_text'  =>  esc_html__( 'Save & Exit', 'bloom' ),
			'et_dashboard_plugin_class_name' => 'et_bloom',
			'et_dashboard_options_path'      => ET_BLOOM_PLUGIN_DIR . '/dashboard/includes/options.php',
			'et_dashboard_options_page'      => 'toplevel_page',
		);

		parent::__construct( $dashboard_args );
	}

	public function maybe_load_core() {
		if ( ! defined( 'ET_CORE' ) ) {
			require_once ET_BLOOM_PLUGIN_DIR . 'core/init.php';
			et_core_setup();
		}

		$this->providers = new ET_Core_API_Email_Providers( 'bloom' );

		self::$_ = ET_Core_Data_Utils::instance();
	}

	static function activate_plugin() {
		// schedule lists auto update daily
		wp_schedule_event( time(), 'daily', 'bloom_lists_auto_refresh' );

		//install the db for stats
		self::db_install();

		update_option( 'bloom_is_just_activated', 'true' );
	}

	function deactivate_plugin() {
		// remove lists auto updates from wp cron if plugin deactivated
		wp_clear_scheduled_hook( 'bloom_lists_auto_refresh' );
		wp_clear_scheduled_hook( 'bloom_stats_auto_refresh' );
	}

	function get_all_optins_list() {
		$options_array = ET_Bloom::get_bloom_options();
		$optins_array = array();

		foreach( $options_array as $optin_id => $details ) {
			if ( false !== strpos( $optin_id, 'optin_' ) ) {
				if ( isset( $details['optin_status'] ) && 'active' === $details['optin_status'] ) {
					// add active optins to the beginning of array
					array_unshift( $optins_array, $optin_id );
				} else {
					// add inactive optins to the end of array
					$optins_array[] = $optin_id;
				}
			}
		}

		return $optins_array;
	}

	function define_page_name() {
		return $this->_options_pagename;
	}

	/**
	 * Returns an instance of the object
	 *
	 * @return object
	 */
	static function get_this() {
		return self::$_this;
	}

	function add_menu_link() {
		$menu_page = add_menu_page( esc_html__( 'Bloom', 'bloom' ), esc_html__( 'Bloom', 'bloom' ), 'manage_options', 'et_bloom_options', array( $this, 'options_page' ) );
		add_submenu_page( 'et_bloom_options', esc_html__( 'Optin Forms', 'bloom' ), esc_html__( 'Optin Forms', 'bloom' ), 'manage_options', 'et_bloom_options' );
		add_submenu_page( 'et_bloom_options', esc_html__( 'Email Accounts', 'bloom' ), esc_html__( 'Email Accounts', 'bloom' ), 'manage_options', 'admin.php?page=et_bloom_options#tab_et_dashboard_tab_content_header_accounts' );
		add_submenu_page( 'et_bloom_options', esc_html__( 'Statistics', 'bloom' ), esc_html__( 'Statistics', 'bloom' ), 'manage_options', 'admin.php?page=et_bloom_options#tab_et_dashboard_tab_content_header_stats' );
		add_submenu_page( 'et_bloom_options', esc_html__( 'Import & Export', 'bloom' ), esc_html__( 'Import & Export', 'bloom' ), 'manage_options', 'admin.php?page=et_bloom_options#tab_et_dashboard_tab_content_header_importexport' );
	}

	function add_body_class( $body_class ) {
		$body_class[] = 'et_bloom';

		return $body_class;
	}

	function save_btn_class() {
		return 'et_dashboard_custom_save';
	}

	/**
	 * Adds plugin localization
	 * Domain: bloom
	 *
	 * @return void
	 */
	function add_localization() {
		load_plugin_textdomain( 'bloom', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	// Add settings link on plugin page
	function add_settings_link( $links ) {
		$settings_link = sprintf( '<a href="admin.php?page=et_bloom_options">%1$s</a>', esc_html__( 'Settings', 'bloom' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}

	function options_page() {
		ET_Bloom::generate_options_page( $this->generate_optin_id() );
	}

	function import_settings() {
		return true;
	}

	function bloom_save_settings() {
		ET_Bloom::dashboard_save_settings();
	}

	function filter_export_settings( $options ) {
		$updated_array = array_merge( $options, array( 'accounts' ) );
		return $updated_array;
	}

	/**
	 * Perform the request to Bloom Stats table and return the results
	 * $sql - Query string ( required )
	 * $type - type of the query ( optional )
	 * $args - list of args ( optional )
	 *
	 * @return string
	 */
	function perform_stats_sql_request( $sql, $type = 'get_results', $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'et_bloom_stats';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			return false;
		}

		// replace the table name placeholder with actual table name
		$sql = str_replace( '__table_name__', $table_name, $sql );

		if ( ! empty( $args ) ) {
			$query_string = $wpdb->prepare( $sql, $args );
		} else {
			$query_string = $sql;
		}

		switch( $type ) {
			case 'get_results' :
				return $wpdb->get_results( $query_string, ARRAY_A );
				break;
			case 'query' :
				return $wpdb->query( $query_string );
				break;
		}
	}

	/**
	 * Retrieve the last record date from stats table
	 *
	 * @return string
	 */
	function get_last_record_date() {
		// get 1st record from the stats table ordered by record_date descending
		$last_record_date_sql = "SELECT record_date from __table_name__ ORDER BY record_date DESC LIMIT 1";

		$last_record_date_raw = $this->perform_stats_sql_request( $last_record_date_sql );

		// get the record date if table is not empty. Fallback to current_time otherwise
		$last_stats_record_date = ! empty( $last_record_date_raw ) && isset( $last_record_date_raw[0]['record_date'] ) ? $last_record_date_raw[0]['record_date'] : current_time( 'mysql' );

		return $last_stats_record_date;
	}

	/**
	 * Update the stats data for each optin in cache if needed
	 *
	 * @return void
	 */
	function refresh_all_optins_stats() {
		$all_optins = $this->get_all_optins_list();

		if ( empty( $all_optins ) ) {
			return;
		}

		$last_record_date = $this->get_last_record_date();

		foreach( $all_optins as $index => $optin_id ) {
			$this->get_stats_data( $optin_id, $last_record_date );
		}
	}

	/**
	 * Retrieve the stats data for specified optin for ajax request
	 *
	 * @return json string
	 */
	function get_optin_stats() {
		if ( ! wp_verify_nonce( $_POST['bloom_stats_nonce'] , 'bloom_stats' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$optin_id = ! empty( $_POST['bloom_stats_optin'] ) ? sanitize_text_field( $_POST['bloom_stats_optin'] ) : '';
		$last_record_date = ! empty( $_POST['bloom_stats_last_record'] ) ? sanitize_text_field( $_POST['bloom_stats_last_record'] ) : '';
		$stats_cache = get_option( 'et_bloom_stats_optin_cache', array() );
		$stats_data = array();

		$stats_data = $this->get_stats_data( $optin_id, $last_record_date );

		// calculate the conversion rate and add it to the stats data array
		$stats_data['rate'] = ! empty( $stats_data ) && isset( $stats_data['con'] ) && isset( $stats_data['imp'] ) ? $this->conversion_rate( '', $stats_data['con'], $stats_data['imp'] ) : 0;

		die( json_encode( $stats_data ) );
	}

	/**
	 * Retrieve the stats data for specified optin either from cache or from Database
	 *
	 * @return array
	 */
	function get_stats_data( $optin_id = '', $last_record_date = '' ) {
		if ( '' === $optin_id ) {
			return array();
		}

		$stats_cache = get_option( 'et_bloom_stats_optin_cache', array() );

		$last_record_date = '' === $last_record_date ? current_time( 'mysql' ) : $last_record_date;

		$last_cache_update = isset( $stats_cache['optins_cache'] ) && isset( $stats_cache['optins_cache'][$optin_id] ) ? $stats_cache['optins_cache'][ $optin_id ]['last_updated'] : false;

		// return data from cache if it contains actual data. Update cache otherwise
		if ( $last_cache_update && strtotime( $last_cache_update ) >= strtotime( $last_record_date ) ) {
			return $stats_cache['optins_cache'][$optin_id];
		}

		$update_time = current_time( 'mysql' );
		$sql = "SELECT id";
		$sql_args = array();

		// prepare sql request to retrieve the impressions/conversions for specified optin
		$sql .= ",sum(case when record_type ='imp' AND optin_id = %s then 1 else 0 end) %s,
				sum(case when record_type ='con' AND optin_id = %s then 1 else 0 end) %s";

		$sql_args[] = sanitize_text_field( $optin_id );
		$sql_args[] = sanitize_text_field( $optin_id ) . '_imp';
		$sql_args[] = sanitize_text_field( $optin_id );
		$sql_args[] = sanitize_text_field( $optin_id ) . '_con';

		$sql .= " FROM __table_name__";

		// limit the sql query by date to speed up the request to DB if cache exists for current optin
		if ( $last_cache_update ) {
			$sql .= " WHERE record_date > %s ORDER BY record_date";
			$sql_args[] = sanitize_text_field( $last_cache_update );
		}

		$stats_data = $this->perform_stats_sql_request( $sql, 'get_results', $sql_args );

		if ( ! empty( $stats_data ) ) {
			$stats_data_array = $stats_data[0];

			if ( ! isset( $stats_cache['optins_cache'] ) ) {
				$stats_cache['optins_cache'] = array();
			}

			// update the stats data array if correct data retrieved
			if ( isset( $stats_data_array[$optin_id . '_con'] ) && isset( $stats_data_array[$optin_id . '_imp'] ) ) {
				$retrieved_imp = (int) $stats_data_array[$optin_id . '_imp'];
				$retrieved_con = (int) $stats_data_array[$optin_id . '_con'];

				// Increment the numbers if cache exists. Save the retrieved numbers otherwise
				$stats_cache['optins_cache'][$optin_id] = array(
					'imp' => isset( $stats_cache['optins_cache'][$optin_id]['imp'] ) ? $stats_cache['optins_cache'][$optin_id]['imp'] + $retrieved_imp : $retrieved_imp,
					'con' => isset( $stats_cache['optins_cache'][$optin_id]['con'] ) ? $stats_cache['optins_cache'][$optin_id]['con'] + $retrieved_con : $retrieved_con,
				);
			}

			$stats_cache['optins_cache'][ $optin_id ]['last_updated'] = $update_time;

			// update the cache which stored as option
			update_option( 'et_bloom_stats_optin_cache', $stats_cache );

			return $stats_cache['optins_cache'][ $optin_id ];
		}

		return array();
	}

	/**
	 *
	 * Adds the "Next" button into the Bloom dashboard via ET_Dashboard action.
	 * @return prints the data on screen
	 *
	 */
	function add_next_button() {
		printf( '
			<div class="et_dashboard_row et_dashboard_next_design">
				<button class="et_dashboard_icon">%1$s</button>
			</div>',
			esc_html__( 'Next: Design Your Optin', 'bloom' )
		);

		printf( '
			<div class="et_dashboard_row et_dashboard_next_display">
				<button class="et_dashboard_icon">%1$s</button>
			</div>',
			esc_html__( 'Next: Display Settings', 'bloom' )
		);

		printf( '
			<div class="et_dashboard_row et_dashboard_next_customize">
				<button class="et_dashboard_icon" data-selected_layout="layout_1">%1$s</button>
			</div>',
			esc_html__( 'Next: Customize', 'bloom' )
		);

		printf( '
			<div class="et_dashboard_row et_dashboard_next_success_action">
				<button class="et_dashboard_icon">%1$s</button>
			</div>',
			esc_html__( 'Next: Success Action', 'bloom' )
		);

		printf( '
			<div class="et_dashboard_row et_dashboard_next_shortcode">
				<button class="et_dashboard_icon">%1$s</button>
			</div>',
			esc_html__( 'Generate Shortcode', 'bloom' )
		);
	}

	/**
	 * Retrieves the Bloom options from DB and makes it available outside the class
	 * @return array
	 */
	public static function get_bloom_options() {
		return get_option( 'et_bloom_options' ) ? get_option( 'et_bloom_options' ) : array();
	}

	/**
	 * Updates the Bloom options outside the class
	 * @return void
	 */
	public static function update_bloom_options( $update_array ) {
		$dashboard_options = ET_Bloom::get_bloom_options();

		$updated_options = array_merge( $dashboard_options, $update_array );
		update_option( 'et_bloom_options', $updated_options );
	}

	/**
	 * Filters the options_array before importing data. Function generates new IDs for imported options to avoid replacement of existing ones.
	 * Filter is used in ET_Dashboard class
	 * @return array
	 */
	function import_filter( $options_array ) {
		$updated_array = array();
		$new_id = $this->generate_optin_id( false );

		foreach ( $options_array as $key => $value ) {
			// Valid option is always array. Skip the wrong values
			if ( ! isset( $options_array[$key] ) || ! is_array( $options_array[$key] ) ) {
				continue;
			}

			$updated_array['optin_' . $new_id] = $options_array[$key];

			//reset accounts settings and make all new optins inactive
			$updated_array['optin_' . $new_id]['email_provider'] = 'empty';
			$updated_array['optin_' . $new_id]['account_name'] = 'empty';
			$updated_array['optin_' . $new_id]['email_list'] = 'empty';
			$updated_array['optin_' . $new_id]['optin_status'] = 'inactive';
			$new_id++;
		}

		return $updated_array;
	}

	function add_mce_button_filters() {
		add_filter( 'mce_external_plugins', array( $this, 'add_mce_button' ) );
		add_filter( 'mce_buttons', array( $this, 'register_mce_button' ) );
	}

	function add_mce_button( $plugin_array ) {
		global $typenow;

		wp_enqueue_style( 'bloom-shortcodes', ET_BLOOM_PLUGIN_URI . '/css/tinymcebutton.css', array(), $this->plugin_version );
		$plugin_array['bloom'] = ET_BLOOM_PLUGIN_URI . '/js/bloom-mce-buttons.js';


		return $plugin_array;
	}

	function register_mce_button( $buttons ) {
		global $typenow;

		array_push( $buttons, 'bloom_button' );

		return $buttons;
	}


	/**
	 * Pass locked_optins and inline_optins lists to tiny-MCE script
	 */
	function tiny_mce_vars() {
		$options_array = ET_Bloom::get_bloom_options();
		$locked_array = array();
		$inline_array = array();
		if ( ! empty( $options_array ) ) {
			foreach ( $options_array as $optin_id => $details ) {
				if ( 'accounts' !== $optin_id ) {
					if ( isset( $details['optin_status'] ) && 'active' === $details['optin_status'] && empty( $details['child_of'] ) ) {
						if ( 'inline' == $details['optin_type'] ) {
							$inline_array = array_merge( $inline_array, array( $optin_id => preg_replace( '/[^A-Za-z0-9 _-]/', '', $details['optin_name'] ) ) );
						}

						if ( 'locked' == $details['optin_type'] ) {
							$locked_array = array_merge( $locked_array, array( $optin_id => preg_replace( '/[^A-Za-z0-9 _-]/', '', $details['optin_name'] ) ) );
						}
					}
				}
			}
		}

		if ( empty( $locked_array ) ) {
			$locked_array = array(
				'empty' => esc_html__( 'No optins available', 'bloom' ),
			);
		}

		if ( empty( $inline_array ) ) {
			$inline_array = array(
				'empty' => esc_html__( 'No optins available', 'bloom' ),
			);
		}
	?>

	<!-- TinyMCE Shortcode Plugin -->
	<script type='text/javascript'>
		var bloom = {
			'locked_optins' : '<?php echo json_encode( $locked_array ); ?>',
			'inline_optins' : '<?php echo json_encode( $inline_array ); ?>',
			'bloom_tooltip' : '<?php echo json_encode( esc_html__( "insert bloom Opt-In", "bloom" ) ); ?>',
			'inline_text'   : '<?php echo json_encode( esc_html__( "Inline Opt-In", "bloom" ) ); ?>',
			'locked_text'   : '<?php echo json_encode( esc_html__( "Locked Content Opt-In", "bloom" ) ); ?>'
		}
	</script>
	<!-- TinyMCE Shortcode Plugin -->
<?php
	}

	static function db_install() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'et_bloom_stats';

		/*
		 * We'll set the default character set and collation for this table.
		 * If we don't do this, some characters could end up being converted
		 * to just ?'s when saved in our table.
		 */
		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = sprintf(
				'DEFAULT CHARACTER SET %1$s',
				sanitize_text_field( $wpdb->charset )
			);
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= sprintf(
				' COLLATE %1$s',
				sanitize_text_field( $wpdb->collate )
			);
		}

		$sql = "CREATE TABLE $table_name (
			id int NOT NULL AUTO_INCREMENT,
			record_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			record_type varchar(3) NOT NULL,
			optin_id varchar(20) NOT NULL,
			list_id varchar(100) NOT NULL,
			page_id varchar(20) NOT NULL,
			removed_flag boolean NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Save db_version to the database, when the plugin is activated
	 *
	 * @return void
	 */
	function maybe_set_db_version() {
		$options_array = ET_Bloom::get_bloom_options();
		$need_version_update = false;

		if ( isset( $options_array['db_version'] ) && version_compare( $options_array['db_version'], '1.1', '<' ) ) {
			// DB fields were updated in 1.1, so run db_install() for old versions of plugin.
			// type of "id" field was changed from 'mediumint' to 'int'
			// In 1.2, the ip_address column was removed to ensure GDPR compliance
			$this->db_install();
			$need_version_update = true;
		}

		if ( 'true' !== get_option( 'bloom_is_just_activated' ) && ! $need_version_update ) {
			return;
		}

		$db_version = array(
			'db_version' => $this->db_version,
		);

		ET_Bloom::update_option( $db_version );

		delete_option( 'bloom_is_just_activated' );
	}

	function register_image_sizes() {
		add_image_size( 'bloom_image', 610 );
	}

	/**
	 * Generates the Bloom's Home, Stats, Accounts tabs. Hooked to Dashboard class
	 */
	function generate_home_tab( $option, $dashboard_settings = array() ) {
		switch ( $option['type'] ) {
			case 'home' :
				printf( '
					<div class="et_dashboard_row et_dashboard_new_optin">
						<h1>%2$s</h1>
						<button class="et_dashboard_icon">%1$s</button>
						<input type="hidden" name="action" value="new_optin" />
					</div>' ,
					esc_html__( 'new optin', 'bloom' ),
					esc_html__( 'Active Optins', 'bloom' )
				);
				printf( '
					<div class="et_dashboard_row et_dashboard_optin_select">
						<h3>%1$s</h3>
						<span class="et_dashboard_icon et_dashboard_close_button"></span>
						<ul>
							<li class="et_dashboard_optin_type et_dashboard_optin_add et_dashboard_optin_type_popup" data-type="pop_up">
								<h6>%2$s</h6>
								<div class="optin_select_grey">
									<div class="optin_select_blue">
									</div>
								</div>
							</li>
							<li class="et_dashboard_optin_type et_dashboard_optin_add et_dashboard_optin_type_flyin" data-type="flyin">
								<h6>%3$s</h6>
								<div class="optin_select_grey"></div>
								<div class="optin_select_blue"></div>
							</li>
							<li class="et_dashboard_optin_type et_dashboard_optin_add et_dashboard_optin_type_below" data-type="below_post">
								<h6>%4$s</h6>
								<div class="optin_select_grey"></div>
								<div class="optin_select_blue"></div>
							</li>
							<li class="et_dashboard_optin_type et_dashboard_optin_add et_dashboard_optin_type_inline" data-type="inline">
								<h6>%5$s</h6>
								<div class="optin_select_grey"></div>
								<div class="optin_select_blue"></div>
								<div class="optin_select_grey"></div>
							</li>
							<li class="et_dashboard_optin_type et_dashboard_optin_add et_dashboard_optin_type_locked" data-type="locked">
								<h6>%6$s</h6>
								<div class="optin_select_grey"></div>
								<div class="optin_select_blue"></div>
								<div class="optin_select_grey"></div>
							</li>
							<li class="et_dashboard_optin_type et_dashboard_optin_add et_dashboard_optin_type_widget" data-type="widget">
								<h6>%7$s</h6>
								<div class="optin_select_grey"></div>
								<div class="optin_select_blue"></div>
								<div class="optin_select_grey_small"></div>
								<div class="optin_select_grey_small last"></div>
							</li>
						</ul>
					</div>',
					esc_html__( 'select optin type to begin', 'bloom' ),
					esc_html__( 'pop up', 'bloom' ),
					esc_html__( 'fly in', 'bloom' ),
					esc_html__( 'below post', 'bloom' ),
					esc_html__( 'inline', 'bloom' ),
					esc_html__( 'locked content', 'bloom' ),
					esc_html__( 'widget', 'bloom' )
				);

				$this->display_home_tab_tables();
			break;

			case 'account' :
				printf( '
					<div class="et_dashboard_row et_dashboard_new_account_row">
						<h1>%2$s</h1>
						<button class="et_dashboard_icon">%1$s</button>
						<input type="hidden" name="action" value="new_account" />
					</div>' ,
					esc_html__( 'new account', 'bloom' ),
					esc_html__( 'My Accounts', 'bloom' )
				);

				$this->display_accounts_table();
			break;

			case 'edit_account' :
				echo '<div id="et_dashboard_edit_account_tab"></div>';
			break;

			case 'stats' :
				printf( '
					<div class="et_dashboard_row et_dashboard_stats_row">
						<h1>%1$s</h1>
						<div class="et_bloom_stats_controls">
							<button class="et_dashboard_icon et_bloom_clear_stats">%2$s</button>
							<span class="et_dashboard_confirmation">%4$s</span>
							<button class="et_dashboard_icon et_bloom_refresh_stats">%3$s</button>
						</div>
					</div>
					<span class="et_bloom_stats_spinner"></span>
					<div class="et_dashboard_stats_contents"></div>',
					esc_html( $option['title'] ),
					esc_html__( 'Clear Stats', 'bloom' ),
					esc_html__( 'Refresh Stats', 'bloom' ),
					sprintf(
						'%1$s<span class="et_dashboard_confirm_stats">%2$s</span><span class="et_dashboard_cancel_delete">%3$s</span>',
						esc_html__( 'Remove all the stats data?', 'bloom' ),
						esc_html__( 'Yes', 'bloom' ),
						esc_html__( 'No', 'bloom' )
					)
				);
			break;

			case 'updates' :
				$et_updates_settings = get_option( 'et_automatic_updates_options' );
				printf( '
					<div class="et_dashboard_row et_dashboard_updates_settings_row">
						<h1>%1$s</h1>
						<p>%11$s</p>
						<div class="et_dashboard_form">
							<div class="et_dashboard_account_row">
								<label for="%2$s">%3$s</label>
								<input type="password" value="%4$s" id="%2$s">%5$s
							</div>
							<div class="et_dashboard_account_row">
								<label for="%6$s">%7$s</label>
								<input type="password" value="%8$s" id="%6$s">%9$s
							</div>
							<button class="et_dashboard_icon et_pb_save_updates_settings">%10$s</button>
							<span class="spinner"></span>
						</div>
					</div>' ,
					esc_html__( 'Enable Updates', 'bloom' ),
					esc_attr( 'et_bloom_updates_username' ),
					esc_html__( 'Username', 'bloom' ),
					isset( $et_updates_settings['username'] ) ? esc_attr( $et_updates_settings['username'] ) : '',
					ET_Bloom::generate_hint( esc_html__( 'Please enter your ElegantThemes.com username.', 'bloom' ), true ), // #5
					esc_attr( 'et_bloom_updates_api_key' ),
					esc_html__( 'API Key', 'bloom' ),
					isset( $et_updates_settings['api_key'] ) ? esc_attr( $et_updates_settings['api_key'] ) : '',
					ET_Bloom::generate_hint(
						sprintf( esc_html__( 'Enter your %1$s here.', 'bloom' ),
							sprintf( '<a href="%1$s" target="_blank">%2$s</a>',
								esc_attr( 'https://www.elegantthemes.com/members-area/api-key.php' ),
								esc_html__( 'Elegant Themes API Key', 'bloom' )
							)
						), false ),
					esc_html__( 'Save', 'bloom' ), // #10
					sprintf( esc_html__( 'Keeping your plugins updated is important. To %1$s for Bloom, you must first authenticate your Elegant Themes account by inputting your account Username and API Key below. Your username is the same username you use when logging into your Elegant Themes account, and your API Key can be found by logging into your account and navigating to the Account > API Key page.', 'bloom' ),
						sprintf( '<a href="%1$s" target="_blank">%2$s</a>',
							esc_attr( 'https://www.elegantthemes.com/members-area/documentation.html#update' ),
							esc_html__( 'enable updates', 'bloom' )
						)
					) // #11
				);
			break;

			case 'settings' :
				$google_api_settings = get_option( 'et_google_api_settings' );
				$google_fonts_disabled = isset( $google_api_settings['use_google_fonts'] ) && 'off' === $google_api_settings['use_google_fonts'];
				printf( '
					<div class="et_dashboard_row et_dashboard_updates_settings_row">
						<h1>%1$s</h1>
						<div class="et_dashboard_form">
							<div class="et_dashboard_account_row">
								<ul>
									<li class="et_dashboard_checkbox clearfix">
										<p>%2$s</p>
										<input type="checkbox" id="et_use_google_fonts" name="et_use_google_fonts" value="%3$s"%4$s/>
										<label for="et_use_google_fonts"></label>
									</li>
								</ul>
							</div>
							<button class="et_dashboard_icon et_pb_save_google_settings">%5$s</button>
							<span class="spinner"></span>
						</div>
					</div>' ,
					esc_html__( 'Bloom Settings', 'bloom' ),
					esc_html__( 'Use Google Fonts', 'bloom' ),
					!$google_fonts_disabled,
					$google_fonts_disabled ? '' : ' checked="checked"',
					esc_html__( 'Save', 'bloom' )
				);
			break;
		}
	}

	/**
	 * Generates tab for the premade layouts selection
	 */
	function generate_premade_templates( $option ) {
		switch ( $option['type'] ) {
			case 'premade_templates' :
				echo '<div class="et_bloom_premade_grid"><span class="spinner et_bloom_premade_spinner"></span></div>';
				break;
			case 'preview_optin' :
				printf( '
					<div class="et_dashboard_row et_dashboard_preview">
						<button class="et_dashboard_icon">%1$s</button>
					</div>',
					esc_html__( 'Preview', 'bloom' )
				);
				break;
		}
	}

	function generate_premade_grid() {
		if ( ! wp_verify_nonce( $_POST['bloom_premade_nonce'] , 'bloom_premade' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		require_once( ET_BLOOM_PLUGIN_DIR . 'includes/premade-layouts.php' );
		$output = '';

		if ( isset( $all_layouts ) ) {
			$i = 0;

			$output .= '<div class="et_bloom_premade_grid">';

			foreach( $all_layouts as $layout_id => $layout_options ) {
				$output .= sprintf( '
					<div class="et_bloom_premade_item%2$s et_bloom_premade_id_%1$s" data-layout="%1$s">
						<div class="et_bloom_premade_item_inner">
							<img src="%3$s" alt="" />
						</div>
					</div>',
					esc_attr( $layout_id ),
					0 == $i ? ' et_bloom_layout_selected' : '',
					esc_url( ET_BLOOM_PLUGIN_URI . '/images/thumb_' . $layout_id . '.svg' )
				);
				$i++;
			}

			$output .= '</div>';
		}

		die( $output );
	}

	/**
	 * Gets the layouts data, converts it to json string and passes back to js script to fill the form with predefined values
	 */
	function get_premade_values() {
		if ( ! wp_verify_nonce( $_POST['bloom_premade_nonce'] , 'bloom_premade' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$premade_data_json = str_replace( '\\', '' ,  $_POST['premade_data_array'] );
		$premade_data = json_decode( $premade_data_json, true );
		$layout_id = $premade_data['id'];

		require_once( ET_BLOOM_PLUGIN_DIR . 'includes/premade-layouts.php' );

		if ( isset( $all_layouts[$layout_id] ) ) {
			$options_set = json_encode( $all_layouts[$layout_id] );
		}

		die( $options_set );
	}

	/**
	 * Generates output for the Stats tab
	 */
	function generate_stats_tab() {
		et_core_security_check( 'manage_options' );

		$all_accounts = $this->_get_accounts();

		$output = sprintf( '
			<div class="et_dashboard_stats_contents et_dashboard_stats_ready">
				<div class="et_dashboard_all_time_stats">
					<h3>%1$s</h3>
					%2$s
				</div>
				<div class="et_dashboard_optins_stats et_dashboard_optins_all_table">
					<div class="et_dashboard_optins_list">
						%3$s
					</div>
				</div>
				<div class="et_dashboard_optins_stats et_dashboard_lists_stats_graph">
					<div class="et_bloom_graph_header">
						<h3>%6$s</h3>
						<div class="et_bloom_graph_controls">
							<a href="#" class="et_bloom_graph_button et_bloom_active_button" data-period="30">%7$s</a>
							<a href="#" class="et_bloom_graph_button" data-period="12">%8$s</a>
							<select class="et_bloom_graph_select_list">%9$s</select>
						</div>
					</div>
					%5$s
				</div>
				<div class="et_dashboard_optins_stats et_dashboard_lists_stats">
					%4$s
				</div>
				%10$s
			</div>',
			esc_html__( 'Overview', 'bloom' ),
			$this->generate_all_time_stats(),
			$this->generate_optins_stats_table( 'conversion_rate', true ),
			( ! empty( $all_accounts ) )
				? sprintf(
					'<div class="et_dashboard_optins_list">
						%1$s
					</div>',
					$this->generate_lists_stats_table( 'count', true )
				)
				: '',
			$this->generate_lists_stats_graph( 30, 'day', '' ), // #5
			esc_html__( 'New sign ups', 'bloom' ),
			esc_html__( 'Last 30 days', 'bloom' ),
			esc_html__( 'Last 12 month', 'bloom' ),
			$this->generate_all_lists_select(),
			$this->generate_pages_stats() // #10
		);

		return $output;
	}

	/**
	 * Generates the stats tab and passes it to jQuery
	 * @return string
	 */
	function reset_stats() {
		if ( ! wp_verify_nonce( $_POST['bloom_stats_nonce'] , 'bloom_stats' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$force_update = ! empty( $_POST['bloom_force_upd_stats'] ) ? sanitize_text_field( $_POST['bloom_force_upd_stats'] ) : '';

		if ( get_option( 'et_bloom_stats_cache' ) && 'true' !== $force_update ) {
			$output = get_option( 'et_bloom_stats_cache' );
		} else {
			$this->refresh_all_optins_stats();
			$output = $this->generate_stats_tab();
			update_option( 'et_bloom_stats_cache', $output );
		}

		if ( ! wp_get_schedule( 'bloom_stats_auto_refresh' ) ) {
			wp_schedule_event( time(), 'daily', 'bloom_stats_auto_refresh' );
		}

		die( $output );
	}

	/**
	 * Reset all opt-ins currently configured for provider account (make them inactive).
	 *
	 * @param string $provider
	 * @param string $account
	 */
	public function reset_optins_for_provider_account( $provider, $account ) {
		$options_array = self::get_bloom_options();

		foreach ( $options_array as $optin_id => $details ) {
			if ( 'accounts' === $optin_id || ! isset( $details['account_name'], $details['email_provider'] ) ) {
				continue;
			}

			if ( $account === $details['account_name'] && $provider === $details['email_provider'] ) {
				$options_array[ $optin_id ]['email_provider'] = 'empty';
				$options_array[ $optin_id ]['account_name'] = 'empty';
				$options_array[ $optin_id ]['email_list'] = 'empty';
				$options_array[ $optin_id ]['optin_status'] = 'inactive';
			}
		}

		ET_Bloom::update_option( $options_array );
	}

	/**
	 * Update Stats and save it into WP DB
	 * @return void
	 */
	function perform_stats_refresh() {

		// remove all cached values
		update_option( 'et_bloom_stats_optin_cache', array() );

		$fresh_stats = $output = $this->generate_stats_tab();
		update_option( 'et_bloom_stats_cache', $fresh_stats );
	}

	/**
	 * Removes all the stats data from DB
	 * @return void
	 */
	function clear_stats() {
		if ( ! wp_verify_nonce( $_POST['bloom_stats_nonce'] , 'bloom_stats' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		// remove everything from the stats table
		$sql = "TRUNCATE TABLE __table_name__";

		$this->perform_stats_sql_request( $sql, 'query' );

		//clear optins stats cache
		delete_option( 'et_bloom_stats_optin_cache' );
	}

	/**
	 * Generates the Lists menu for Lists stats graph
	 * @return string
	 */
	function generate_all_lists_select() {
		$all_accounts = $this->_get_accounts();
		$output = sprintf( '<option value="all">%1$s</option>', esc_html__( 'All lists', 'bloom' ) );

		foreach ( $all_accounts as $service => $accounts ) {
			foreach ( $accounts as $name => $details ) {
				if ( ! empty( $details['lists'] ) ) {
					foreach ( $details['lists'] as $id => $list_data ) {
						$output .= sprintf(
							'<option value="%2$s">%1$s</option>',
							esc_html( $service . ' - ' . $list_data['name'] ),
							esc_attr( $service . '_' . $id )
						);
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Generates the Overview part of stats page
	 * @return string
	 */
	function generate_all_time_stats( $empty_stats = false ) {

		$conversion_rate = $this->conversion_rate( 'all' );

		$all_subscribers = $this->calculate_subscribers( 'all' );

		$growth_rate = $this->calculate_growth_rate( 'all' );

		$ouptut = sprintf(
			'<div class="et_dashboard_stats_container">
				<div class="all_stats_column conversion_rate">
					<span class="value">%1$s</span>
					<span class="caption">%2$s</span>
				</div>
				<div class="all_stats_column subscribers">
					<span class="value">%3$s</span>
					<span class="caption">%4$s</span>
				</div>
				<div class="all_stats_column growth_rate">
					<span class="value">%5$s<span>/%7$s</span></span>
					<span class="caption">%6$s</span>
				</div>
				<div style="clear: both;"></div>
			</div>',
			esc_html( $conversion_rate . '%' ),
			esc_html__( 'Conversion Rate', 'bloom' ),
			esc_html( $all_subscribers ),
			esc_html__( 'Subscribers', 'bloom' ),
			esc_html( $growth_rate ),
			esc_html__( 'Subscriber Growth', 'bloom' ),
			esc_html__( 'week', 'bloom' )
		);

		return $ouptut;
	}

	/**
	 * Returns the data for a specific service provider account.
	 *
	 * @param string $service The service provider's slug.
	 * @param string $name    The account name.
	 *
	 * @return array
	 */
	private function _get_account( $service, $name ) {
		$accounts = $this->_get_accounts( $service );

		return isset( $accounts[ $name ] ) ? $accounts[ $name ] : array();
	}

	/**
	 * Returns the accounts for a service or all accounts if `$service` is empty.
	 *
	 * @param string $service The service for which to retrieve accounts. Optional.
	 *
	 * @return array
	 */
	private function _get_accounts( $service = '' ) {
		$accounts = $this->providers->accounts();

		if ( '' !== $service ) {
			$accounts = isset( $accounts[ $service ] ) ? $accounts[ $service ] : array();
		}

		return $accounts;
	}

	/**
	 * Returns an {@link \ET_Core_API_Email_Provider} instance for a service provider account.
	 *
	 * @param string $provider_slug The service provider slug.
	 * @param string $account_name  The account name.
	 *
	 * @return \ET_Core_API_Email_Provider|bool
	 */
	private function _get_provider( $provider_slug, $account_name ) {
		return $this->providers->get( $provider_slug, $account_name, 'bloom' );
	}

	public function maybe_update_options_schema() {
		$options         = self::get_bloom_options();
		$current_version = isset( $options['schema_version'] ) ? (int) $options['schema_version'] : 0;

		if ( $this->options_version === $current_version ) {
			return;
		}

		if ( 0 === $current_version &&  1 === $this->options_version ) {
			// Core API Wrappers Implemented. Copy accounts data to core.
			$core_options              = (array) get_option( 'et_core_api_email_options' );
			$core_options['accounts']  = isset( $core_options['accounts'] ) ? $core_options['accounts'] : array();
			$options['schema_version'] = $this->options_version;

			if ( isset( $options['accounts'] ) ) {
				$core_options['accounts']  = array_merge( $core_options['accounts'], $options['accounts'] );

				// Make sure the BB clears its template cache
				if ( function_exists( 'et_pb_force_regenerate_templates' ) ) {
					et_pb_force_regenerate_templates();
				}
			}

			ET_Bloom::update_bloom_options( $options );
			update_option( 'et_core_api_email_options', $core_options );
		}
	}

	/**
	 * Generates the stats table with optins
	 * @return string
	 */
	function generate_optins_stats_table( $orderby = 'conversion_rate', $include_header = false ) {
		et_core_security_check( 'manage_options' );

		$options_array = ET_Bloom::get_bloom_options();
		$optins_count = 0;
		$output = '';
		$total_impressions = 0;
		$total_conversions = 0;

		foreach ( $options_array as $optin_id => $value ) {
			if ( 'accounts' !== $optin_id && 'db_version' !== $optin_id ) {
				if ( 0 === $optins_count ) {
					if ( true == $include_header ) {
						$output .= sprintf(
							'<ul>
								<li data-table="optins">
									<div class="et_dashboard_table_name et_dashboard_table_column et_table_header">%1$s</div>
									<div class="et_dashboard_table_impressions et_dashboard_table_column et_dashboard_icon et_dashboard_sort_button" data-order_by="impressions">%2$s</div>
									<div class="et_dashboard_table_conversions et_dashboard_table_column et_dashboard_icon et_dashboard_sort_button" data-order_by="conversions">%3$s</div>
									<div class="et_dashboard_table_rate et_dashboard_table_column et_dashboard_icon et_dashboard_sort_button active_sorting" data-order_by="conversion_rate">%4$s</div>
									<div style="clear: both;"></div>
								</li>
							</ul>',
							esc_html__( 'My Optins', 'bloom' ),
							esc_html__( 'Impressions', 'bloom' ),
							esc_html__( 'Conversions', 'bloom' ),
							esc_html__( 'Conversion Rate', 'bloom' )
						);
					}

					$output .= '<ul class="et_dashboard_table_contents">';
				}

				$total_impressions += $impressions = $this->stats_count( $optin_id, 'imp' );
				$total_conversions += $conversions = $this->stats_count( $optin_id, 'con' );

				$unsorted_optins[$optin_id] = array(
					'name'            => $value['optin_name'],
					'impressions'     => $impressions,
					'conversions'     => $conversions,
					'conversion_rate' => $this->conversion_rate( $optin_id, $conversions, $impressions ),
					'type'            => $value['optin_type'],
					'status'          => $value['optin_status'],
					'child_of'        => $value['child_of'],
				);
				$optins_count++;

			}
		}

		if ( ! empty( $unsorted_optins ) ) {
			$sorted_optins = $this->sort_array( $unsorted_optins, $orderby );

			foreach ( $sorted_optins as $id => $details ) {
				if ( ! empty( $details['child_of'] ) ) {
					$status = $options_array[$details['child_of']]['optin_status'];
				} else {
					$status = $details['status'];
				}

				$output .= sprintf(
					'<li class="et_dashboard_optins_item et_dashboard_parent_item">
						<div class="et_dashboard_table_name et_dashboard_table_column et_dashboard_icon et_dashboard_type_%5$s et_dashboard_status_%6$s">%1$s</div>
						<div class="et_dashboard_table_impressions et_dashboard_table_column">%2$s</div>
						<div class="et_dashboard_table_conversions et_dashboard_table_column">%3$s</div>
						<div class="et_dashboard_table_rate et_dashboard_table_column">%4$s</div>
						<div style="clear: both;"></div>
					</li>',
					esc_html( $details['name'] ),
					esc_html( $details['impressions'] ),
					esc_html( $details['conversions'] ),
					esc_html( $details['conversion_rate'] ) . '%',
					esc_attr( $details['type'] ),
					esc_attr( $status )
				);
			}
		}

		if ( 0 < $optins_count ) {
			$output .= sprintf(
				'<li class="et_dashboard_optins_item_bottom_row">
					<div class="et_dashboard_table_name et_dashboard_table_column"></div>
					<div class="et_dashboard_table_impressions et_dashboard_table_column">%1$s</div>
					<div class="et_dashboard_table_conversions et_dashboard_table_column">%2$s</div>
					<div class="et_dashboard_table_rate et_dashboard_table_column">%3$s</div>
				</li>',
				esc_html( $this->get_compact_number( $total_impressions ) ),
				esc_html( $this->get_compact_number( $total_conversions ) ),
				( 0 !== $total_impressions )
					? esc_html( round( ( $total_conversions * 100 ) / $total_impressions, 1 ) . '%' )
					: '0%'
			);
			$output .= '</ul>';
		}

		return $output;
	}


	/**
	 * Changes the order of rows in array based on input parameters
	 * @return array
	 */
	function sort_array( $unsorted_array, $orderby, $order = SORT_DESC ) {
		$temp_array = array();
		foreach ( $unsorted_array as $ma ) {
			$temp_array[] = $ma[$orderby];
		}

		array_multisort( $temp_array, $order, $unsorted_array );

		return $unsorted_array;
	}

	/**
	 * Generates the highest converting pages table
	 * @return string
	 */
	function generate_pages_stats() {
		$pages_with_optins = $this->get_all_pages_with_optins();
		$output = '';

		if ( empty( $pages_with_optins ) ) {
			return;
		}

		$rate_by_pages = $this->get_pages_conversion_rate( $pages_with_optins );

		$i = 0;

		foreach ( $rate_by_pages as $page_id => $rate ) {
			$page_rate = 0;
			$rates_count = 0;
			$optins_data = array();
			$j = 0;

			foreach ( $rate as $current_optin ) {
				foreach ( $current_optin as $optin_id => $current_rate ) {
					$page_rate = $page_rate + $current_rate;
					$rates_count++;

					$optins_data[$j] = array(
						'optin_id' => $optin_id,
						'optin_rate' => $current_rate,
					);

				}
				$j++;
			}

			$average_rate = 0 != $rates_count ? round( $page_rate / $rates_count, 1 ) : 0;
			$rate_by_pages_unsorted[$i]['page_id'] = $page_id;
			$rate_by_pages_unsorted[$i]['page_rate'] = $average_rate;
			$rate_by_pages_unsorted[$i]['optins_data'] = $this->sort_array( $optins_data, 'optin_rate', $order = SORT_DESC );

			$i++;
		}

		$rate_by_pages_sorted = $this->sort_array( $rate_by_pages_unsorted, 'page_rate', $order = SORT_DESC );
		$output = '';

		if ( ! empty( $rate_by_pages_sorted ) ) {
			$options_array = ET_Bloom::get_bloom_options();
			$table_contents = '<ul>';

			for ( $i = 0; $i < 5; $i++ ) {
				if ( ! empty( $rate_by_pages_sorted[$i] ) ) {
					$table_contents .= sprintf(
						'<li class="et_table_page_row">
							<div class="et_dashboard_table_name et_dashboard_table_column et_table_page_row">%1$s</div>
							<div class="et_dashboard_table_pages_rate et_dashboard_table_column">%2$s</div>
							<div style="clear: both;"></div>
						</li>',
						-1 == $rate_by_pages_sorted[$i]['page_id']
							? esc_html__( 'Homepage', 'bloom' )
							: esc_html( get_the_title( $rate_by_pages_sorted[$i]['page_id'] ) ),
						esc_html( $rate_by_pages_sorted[$i]['page_rate'] ) . '%'
					);
					foreach ( $rate_by_pages_sorted[$i]['optins_data'] as $optin_details ) {
						if ( isset( $options_array[$optin_details['optin_id']]['child_of'] ) && '' !== $options_array[$optin_details['optin_id']]['child_of'] ) {
							$status = $options_array[$options_array[$optin_details['optin_id']]['child_of']]['optin_status'];
						} else {
							$status = isset( $options_array[$optin_details['optin_id']]['optin_status'] ) ? $options_array[$optin_details['optin_id']]['optin_status'] : 'inactive';
						}

						$table_contents .= sprintf(
							'<li class="et_table_optin_row et_dashboard_optins_item">
								<div class="et_dashboard_table_name et_dashboard_table_column et_dashboard_icon et_dashboard_type_%3$s et_dashboard_status_%4$s">%1$s</div>
								<div class="et_dashboard_table_pages_rate et_dashboard_table_column">%2$s</div>
								<div style="clear: both;"></div>
							</li>',
							( isset( $options_array[$optin_details['optin_id']]['optin_name'] ) )
								? esc_html( $options_array[$optin_details['optin_id']]['optin_name'] )
								: '',
							esc_html( $optin_details['optin_rate'] ) . '%',
							( isset( $options_array[$optin_details['optin_id']]['optin_type'] ) )
								? esc_attr( $options_array[$optin_details['optin_id']]['optin_type'] )
								: '',
							esc_attr( $status )
						);
					}
				}
			}

			$table_contents .= '</ul>';

			$output = sprintf(
				'<div class="et_dashboard_optins_stats et_dashboard_pages_stats">
					<div class="et_dashboard_optins_list">
						<ul>
							<li>
								<div class="et_dashboard_table_name et_dashboard_table_column et_table_header">%1$s</div>
								<div class="et_dashboard_table_pages_rate et_dashboard_table_column et_table_header">%2$s</div>
								<div style="clear: both;"></div>
							</li>
						</ul>
						%3$s
					</div>
				</div>',
				esc_html__( 'Highest converting pages', 'bloom' ),
				esc_html__( 'Conversion rate', 'bloom' ),
				$table_contents
			);
		}

		return $output;
	}

	/**
	 * Generates the stats table with lists
	 * @return string
	 */
	function generate_lists_stats_table( $orderby = 'count', $include_header = false ) {
		et_core_security_check( 'manage_options' );

		$all_accounts = $this->_get_accounts();
		$optins_count = 0;
		$output = '';
		$total_subscribers = 0;

		foreach ( $all_accounts as $service => $accounts ) {
			foreach ( $accounts as $name => $details ) {
				if ( ! empty( $details['lists'] ) ) {
					foreach ( $details['lists'] as $id => $list_data ) {
						if ( 0 === $optins_count ) {
							if ( true == $include_header ) {
								$output .= sprintf(
									'<ul>
										<li data-table="lists">
											<div class="et_dashboard_table_name et_dashboard_table_column et_table_header">%1$s</div>
											<div class="et_dashboard_table_impressions et_dashboard_table_column et_dashboard_icon et_dashboard_sort_button" data-order_by="service">%2$s</div>
											<div class="et_dashboard_table_rate et_dashboard_table_column et_dashboard_icon et_dashboard_sort_button active_sorting" data-order_by="count">%3$s</div>
											<div class="et_dashboard_table_conversions et_dashboard_table_column et_dashboard_icon et_dashboard_sort_button" data-order_by="growth">%4$s</div>
											<div style="clear: both;"></div>
										</li>
									</ul>',
									esc_html__( 'My Lists', 'bloom' ),
									esc_html__( 'Provider', 'bloom' ),
									esc_html__( 'Subscribers', 'bloom' ),
									esc_html__( 'Growth Rate', 'bloom' )
								);
							}

							$output .= '<ul class="et_dashboard_table_contents">';
						}

						$total_subscribers += $list_data['subscribers_count'];

						$unsorted_array[] = array(
							'name'    => $list_data['name'],
							'service' => $service,
							'count'   => $list_data['subscribers_count'],
							'growth'  => $list_data['growth_week'],
						);

						$optins_count++;
					}
				}
			}
		}

		if ( ! empty( $unsorted_array ) ) {
			$order = 'service' == $orderby ? SORT_ASC : SORT_DESC;

			$sorted_array = $this->sort_array( $unsorted_array, $orderby, $order );

			foreach ( $sorted_array as $single_list ) {
				$output .= sprintf(
					'<li class="et_dashboard_optins_item et_dashboard_parent_item">
						<div class="et_dashboard_table_name et_dashboard_table_column">%1$s</div>
						<div class="et_dashboard_table_conversions et_dashboard_table_column">%2$s</div>
						<div class="et_dashboard_table_rate et_dashboard_table_column">%3$s</div>
						<div class="et_dashboard_table_impressions et_dashboard_table_column">%4$s/%5$s</div>
						<div style="clear: both;"></div>
					</li>',
					esc_html( $single_list['name'] ),
					esc_html( $single_list['service'] ),
					esc_html( $single_list['count'] ),
					esc_html( $single_list['growth'] ),
					esc_html__( 'week', 'bloom' )
				);
			}
		}

		if ( $optins_count > 0 ) {
			$output .= sprintf(
				'<li class="et_dashboard_optins_item_bottom_row">
					<div class="et_dashboard_table_name et_dashboard_table_column"></div>
					<div class="et_dashboard_table_conversions et_dashboard_table_column"></div>
					<div class="et_dashboard_table_rate et_dashboard_table_column">%1$s</div>
					<div class="et_dashboard_table_impressions et_dashboard_table_column">%2$s/%3$s</div>
				</li>',
				esc_html( $total_subscribers ),
				esc_html( $this->calculate_growth_rate( 'all' ) ),
				esc_html__( 'week', 'bloom' )
			);
			$output .= '</ul>';
		}

		return $output;
	}

	/**
	 * Calculates the conversion rate for the optin
	 * @return int
	 */
	function conversion_rate( $optin_id, $con_data = '0', $imp_data = '0' ) {
		$conversion_rate = 0;

		$current_conversion = '0' === $con_data ? $this->stats_count( $optin_id, 'con' ) : $con_data;
		$current_impression = '0' === $imp_data ? $this->stats_count( $optin_id, 'imp' ) : $imp_data;

		if ( 0 < $current_impression ) {
			$conversion_rate = 	( $current_conversion * 100 )/$current_impression;
		}

		$conversion_rate_output = round( $conversion_rate, 1 );

		return $conversion_rate_output;
	}

	/**
	 * Calculates the conversion rates for pages
	 * @return array()
	 */
	function get_pages_conversion_rate( $pages_array ) {
		$sql = "SELECT id";
		$sql_args = array();

		foreach( $pages_array as $page_id => $optins_array ) {
			if ( empty( $optins_array ) ) {
				continue;
			}

			foreach( $optins_array as $optin_id ) {
				if ( '' === $optin_id ) {
					continue;
				}

				$sql .= ",sum(case when page_id=%s AND optin_id=%s AND record_type ='con' then 1 else 0 end) %s
						,sum(case when page_id=%s AND optin_id=%s AND record_type ='imp' then 1 else 0 end) %s";
				$sql_args[] = sanitize_text_field( $page_id );
				$sql_args[] = sanitize_text_field( $optin_id );
				$sql_args[] = sanitize_text_field( $page_id ) . '_' . sanitize_text_field( $optin_id ) . '_' . 'con';
				$sql_args[] = sanitize_text_field( $page_id );
				$sql_args[] = sanitize_text_field( $optin_id );
				$sql_args[] = sanitize_text_field( $page_id ) . '_' . sanitize_text_field( $optin_id ) . '_' . 'imp';
			}
		}

		$sql .= " FROM __table_name__";

		$stats_data = $this->perform_stats_sql_request( $sql, 'get_results', $sql_args );

		if ( empty( $stats_data ) ) {
			return array();
		}

		$rate_by_pages = array();

		foreach( $pages_array as $page_id => $optins_array ) {
			if ( empty( $optins_array ) ) {
				continue;
			}

			foreach( $optins_array as $optin_id ) {
				if ( '' === $optin_id ) {
					continue;
				}

				$index = $page_id . '_' . $optin_id;
				$conversions_count = isset( $stats_data[0][$index . '_con'] ) ? $stats_data[0][$index . '_con'] : 0;
				$impressions_count = isset( $stats_data[0][$index . '_imp'] ) ? $stats_data[0][$index . '_imp'] : 0;

				$rate_by_pages[$page_id][] = array(
					$optin_id => $this->conversion_rate( '', $conversions_count, $impressions_count ),
				);
			}
		}

		return $rate_by_pages;
	}

	/**
	 * Gets the conversions/impressions count for the optin from cache if exists
	 * @return int
	 */
	function stats_count( $optin_id, $type = 'imp' ) {
		$stats_cache = get_option( 'et_bloom_stats_optin_cache', array() );

		// retrieve data from cache if exists, otherwise return 0.
		if ( ! isset( $stats_cache['optins_cache'] ) || ! isset( $stats_cache['optins_cache'][ $optin_id ] ) || ! isset( $stats_cache['optins_cache'][ $optin_id ][ $type ] ) ) {
			return 0;
		}

		if ( 'all' === $optin_id ) {
			foreach( $stats_cache['optins_cache'] as $optin => $optin_stats ) {
				$count = 0;
				if ( 'last_updated' !== $optin ) {
					$count += $optin_stats[ $type ];
				}
			}

			return $count;
		} else {
			return $stats_cache['optins_cache'][ $optin_id ][ $type ];
		}
	}

	/**
	 * Get conversions by period
	 *
	 * @param int    $period       Optional. The numeric period of chosen unit of time.
	 * @param string $day_or_month Optional. The unit of time. Accepts 'day','month'.
	 * @param string $list_id      Optional. The list id to get conversions for.
	 *
	 * @return array Results array.
	 *               Empty array if stats data is empty.
	 */
	function get_conversions_by_period( $period = 28, $day_or_month = 'day', $list_id = '' ) {
		// whitelist the possible values, since this is directly included unescaped into the sql string
		$day_or_month = in_array( $day_or_month, array( 'day', 'month' ) ) ? $day_or_month : 'day';
		$sql = "SELECT id";
		$sql_args = array();

		for ( $i = $period; $i > 0; $i-- ) {
			// prepare sql request to retrieve the conversions count by period
			$sql .= ",sum(case when record_date BETWEEN date(now()-interval %d $day_or_month ) AND date(now()-interval %d $day_or_month) then 1 else 0 end) %s";

			$sql_args[] = $i;
			$sql_args[] = $i - 1;
			$sql_args[] = '_' . $i;
		}

		// limit the sql query by date to speed up the request to DB
		$sql .= " FROM __table_name__ WHERE record_type ='con' AND record_date >= date(now()-interval %d $day_or_month) ";
		$sql_args[] = absint( $period );

		if ( '' !== $list_id ) {
			$sql .= " AND list_id=%s";
			$sql_args[] = sanitize_text_field( $list_id );
		}

		$sql .= " ORDER BY record_date DESC";

		$stats_data = $this->perform_stats_sql_request( $sql, 'get_results', $sql_args );

		if ( empty( $stats_data ) ) {
			return array();
		}

		// remove unneeded data from array
		unset( $stats_data[0]['id'] );

		return $stats_data[0];
	}

	function get_all_pages_with_optins() {
		// construct sql query to get all the unique page IDs with unique optins from stats table
		$sql = "SELECT DISTINCT page_id, optin_id FROM __table_name__";

		$all_pages = $this->perform_stats_sql_request( $sql );

		if ( empty( $all_pages ) ) {
			return array();
		}

		$pages_array = array();

		// prepare the array of pages
		foreach( $all_pages as $index => $data ) {
			$pages_array[] = $data['page_id'];
		}

		// remove all duplicated records
		$pages_array = array_unique( $pages_array );

		$pages_with_optins_array = array();

		// prepare the final array with pages to optins relations
		foreach( $pages_array as $i => $single_page_id ) {
			foreach( $all_pages as $j => $pages_data ) {
				if ( $single_page_id === $pages_data['page_id'] ) {
					$pages_with_optins_array[ $single_page_id ][] = $pages_data['optin_id'];
				}
			}
		}

		return $pages_with_optins_array;
	}

	/**
	 * Calculates growth rate of the list. list_id should be provided in following format: <service>_<list_id>
	 * @return int
	 */
	function calculate_growth_rate( $list_id ) {
		$list_id = 'all' == $list_id ? '' : $list_id;

		$stats = $this->get_conversions_by_period( 28, 'day', $list_id );
		$total_subscribers = 0;
		$oldest_record = -1;

		foreach ( $stats as $day => $count ) {
			if ( 0 !== $count && -1 === $oldest_record ) {
				// get the clean number from $day value.
				// it stored like <underscore> followed by number ( ex. "_1" )
				$oldest_record = intval( substr( $day, 1 ) );
			}
			$total_subscribers += $count;
		}

		if ( -1 === $oldest_record ) {
			$growth_rate = 0;
		} else {
			$weeks_count = round( ( $oldest_record ) / 7, 0 );
			$weeks_count = 0 == $weeks_count ? 1 : $weeks_count;
			$growth_rate = round( $total_subscribers / $weeks_count, 0 );
		}

		return $growth_rate;
	}

	/**
	 * Calculates all the subscribers using data from accounts
	 * @return string
	 */
	function calculate_subscribers( $period, $service = '', $account_name = '', $list_id = '' ) {
		$all_accounts      = $this->_get_accounts();
		$subscribers_count = 0;

		if ( 'all' === $period ) {
			foreach ( $all_accounts as $service => $accounts ) {
				foreach ( $accounts as $name => $details ) {
					if ( ! empty( $details['lists'] ) ) {
						foreach( $details['lists'] as $id => $list_details ) {
							if ( ! empty( $list_details['subscribers_count'] ) ) {
								$subscribers_count += $list_details['subscribers_count'];
							}
						}
					}
				}
			}
		}

		return $this->get_compact_number( $subscribers_count );
	}

	/**
	 * Generates output for the lists stats graph.
	 */
	function generate_lists_stats_graph( $period, $day_or_month, $list_id = '' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$stats = $this->get_conversions_by_period( $period, $day_or_month, $list_id );

		$output = $this->generate_stats_graph_output( $period, $day_or_month, $stats );

		return $output;
	}

	/**
	 * Generated the output for lists graph. Period and data array are required
	 * @return string
	 */
	function generate_stats_graph_output( $period, $day_or_month, $data ) {
		$result = '<div class="et_dashboard_lists_stats_graph_container">';
		$result .= sprintf(
			'<ul class="et_bloom_graph_%1$s et_bloom_graph">',
			esc_attr( $period )
		);
		$bars_count = 0;
		$total_subscribers = 0;
		$data = array_reverse( $data );

		foreach( $data as $index => $count ) {
			$result .= sprintf( '<li%1$s>',
				intval( $period ) === intval( substr( $index, 1 ) ) ? ' class="et_bloom_graph_last"' : ''
			);

			if ( 0 < $count ) {
				$result .= sprintf( '<div value="%1$s" class="et_bloom_graph_bar">',
					esc_attr( $count )
				);

				$bars_count++;

				$total_subscribers += $count;

				$result .= '</div>';
			} else {
				$result .= '<div value="0"></div>';
			}

			$result .= '</li>';
		}

		$result .= '</ul>';

		if ( 0 < $bars_count ) {
			$per_day = round( $total_subscribers / $bars_count, 0 );
		} else {
			$per_day = 0;
		}

		$result .= sprintf(
			'<div class="et_bloom_overall">
				<span class="total_signups">%1$s | </span>
				<span class="signups_period">%2$s</span>
			</div>',
			sprintf(
				'%1$s %2$s',
				esc_html( $total_subscribers ),
				esc_html__( 'New Signups', 'bloom' )
			),
			sprintf(
				'%1$s %2$s %3$s',
				esc_html( $per_day ),
				esc_html__( 'Per', 'bloom' ),
				'day' == $day_or_month ? esc_html__( 'Day', 'bloom' ) : esc_html__( 'Month', 'bloom' )
			)
		);

		$result .= '</div>';

		return $result;
	}

	/**
	 * Generates the lists stats graph and passes it to jQuery
	 */
	function get_stats_graph_ajax() {
		if ( ! wp_verify_nonce( $_POST['bloom_stats_nonce'] , 'bloom_stats' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$list_id = ! empty( $_POST['bloom_list'] ) ? sanitize_text_field( $_POST['bloom_list'] ) : '';
		$period = ! empty( $_POST['bloom_period'] ) ? sanitize_text_field( $_POST['bloom_period'] ) : '';

		$day_or_month = '30' == $period ? 'day' : 'month';
		$list_id = 'all' == $list_id ? '' : $list_id;

		$output = $this->generate_lists_stats_graph( $period, $day_or_month, $list_id );

		die( $output );
	}

	/**
	 * Generates the optins stats table and passes it to jQuery
	 */
	function refresh_optins_stats_table() {
		if ( ! wp_verify_nonce( $_POST['bloom_stats_nonce'] , 'bloom_stats' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$orderby = ! empty( $_POST['bloom_orderby'] ) ? sanitize_text_field( $_POST['bloom_orderby'] ) : '';
		$table = ! empty( $_POST['bloom_stats_table'] ) ? sanitize_text_field( $_POST['bloom_stats_table'] ) : '';

		$output = '';
		if ( 'optins' === $table ) {
			$output = $this->generate_optins_stats_table( $orderby );
		} else if ( 'lists' === $table ) {
			$output = $this->generate_lists_stats_table( $orderby );
		}

		die( $output );
	}

	/**
	 * Converts number >1000 into compact numbers like 1k
	 */
	public static function get_compact_number( $full_number ) {
		if ( 1000000 <= $full_number ) {
			$full_number = floor( $full_number / 100000 ) / 10;
			$full_number .= 'Mil';
		} elseif ( 1000 < $full_number ) {
			$full_number = floor( $full_number / 100 ) / 10;
			$full_number .= 'k';
		}

		return $full_number;
	}

	/**
	 * Converts compact numbers like 1k into full numbers like 1000
	 */
	public static function get_full_number( $compact_number ) {
		if ( false !== strrpos( $compact_number, 'k' ) ) {
			$compact_number = floatval( str_replace( 'k', '', $compact_number ) ) * 1000;
		}
		if ( false !== strrpos( $compact_number, 'Mil' ) ) {
			$compact_number = floatval( str_replace( 'Mil', '', $compact_number ) ) * 1000000;
		}

		return $compact_number;
	}

	/**
	 * Generates the fields set for new account based on service and passes it to jQuery
	 */
	function generate_new_account_fields() {
		et_core_security_check( 'manage_options', 'accounts_tab' );

		$service = ! empty( $_POST['bloom_service'] ) ? sanitize_text_field( $_POST['bloom_service'] ) : '';

		if ( 'empty' == $service ) {
			echo '<ul class="et_dashboard_new_account_fields"><li></li></ul>';
		} else {
			$form_fields = $this->generate_new_account_form( $service );

			printf(
				'<ul class="et_dashboard_new_account_fields">
					<li class="select et_dashboard_select_account">
						%3$s
						<button class="et_dashboard_icon authorize_service new_account_tab" data-service="%2$s">%1$s</button>
						<span class="spinner"></span>
					</li>
				</ul>',
				esc_html__( 'Authorize', 'bloom' ),
				esc_attr( $service ),
				$form_fields
			);
		}

		die();
	}

	/**
	 * Generates the fields set for account editing form based on service and account name and passes it to jQuery
	 */
	function generate_edit_account_page(){
		et_core_security_check( 'manage_options', 'accounts_tab' );

		$edit_account = ! empty( $_POST['bloom_edit_account'] ) ? sanitize_text_field( $_POST['bloom_edit_account'] ) : '';
		$account_name = ! empty( $_POST['bloom_account_name'] ) ? sanitize_text_field( $_POST['bloom_account_name'] ) : '';
		$service = ! empty( $_POST['bloom_service'] ) ? sanitize_text_field( $_POST['bloom_service'] ) : '';

		echo '<div id="et_dashboard_edit_account_tab">';

		printf(
			'<div class="et_dashboard_row et_dashboard_new_account_row">
				<h1>%1$s</h1>
				<p>%2$s</p>
			</div>',
			( 'true' == $edit_account )
				? esc_html( $account_name )
				: esc_html__( 'New Account Setup', 'bloom' ),
			( 'true' == $edit_account )
				? esc_html__( 'You can view and re-authorize this account’s settings below', 'bloom' )
				: esc_html__( 'Setup a new email marketing service account below', 'bloom' )
		);

		if ( 'true' == $edit_account ) {
			$form_fields = $this->generate_new_account_form( $service, $account_name, false );

			printf(
				'<div class="et_dashboard_form et_dashboard_row">
					<h2>%1$s</h2>
					<div style="clear:both;"></div>
					<ul class="et_dashboard_new_account_fields et_dashboard_edit_account_fields">
						<li class="select et_dashboard_select_account">
							%2$s
							<button class="et_dashboard_icon authorize_service new_account_tab" data-service="%7$s" data-account_name="%4$s">%3$s</button>
							<span class="spinner"></span>
						</li>
					</ul>
					%5$s
					<button class="et_dashboard_icon save_account_tab" data-service="%7$s">%6$s</button>
				</div>',
				esc_html( $service ),
				$form_fields,
				esc_html__( 'Re-Authorize', 'bloom' ),
				esc_attr( $account_name ),
				$this->display_currrent_lists( $service, $account_name ),
				esc_html__( 'Go Back', 'bloom' ),
				esc_attr( $service )
			);

		} else {
			$provider_options = '';

			foreach ( (array) $this->provider_names as $provider_slug => $provider_name ) {
				if ( 'empty' === $provider_slug || 'custom_html' === $provider_slug ) {
					continue;
				}

				$provider_options .= sprintf(
					'<option value="%1$s">%2$s</option>',
					esc_attr( $provider_slug ),
					esc_html( $provider_name )
				);
			}

			printf(
				'<div class="et_dashboard_form et_dashboard_row">
					<h2>%1$s</h2>
					<div style="clear:both;"></div>
					<ul>
						<li class="select et_dashboard_select_provider_new">
							<p>%2$s</p>
							<select>
								<option value="empty" selected>%3$s</option>
								%4$s
							</select>
						</li>
					</ul>
					<ul class="et_dashboard_new_account_fields"><li></li></ul>
					<button class="et_dashboard_icon save_account_tab">%5$s</button>
				</div>',
				esc_html__( 'New account settings', 'bloom' ),
				esc_html__( 'Select Email Provider', 'bloom' ),
				esc_html__( 'Select One...', 'bloom' ),
				$provider_options,
				esc_html__( 'Go Back', 'bloom' ) // 5
			);
		}

		echo '</div>';

		die();
	}

	/**
	 * Generates the list of Lists for specific account and passes it to jQuery
	 */
	function generate_current_lists() {
		et_core_security_check( 'manage_options', 'accounts_tab' );

		$service = ! empty( $_POST['bloom_service'] ) ? sanitize_text_field( $_POST['bloom_service'] ) : '';
		$name = ! empty( $_POST['bloom_upd_name'] ) ? sanitize_text_field( $_POST['bloom_upd_name'] ) : '';

		echo $this->display_currrent_lists( $service, $name );

		die();
	}

	/**
	 * Generates the list of Lists for specific account
	 *
	 * @return string
	 */
	function display_currrent_lists( $service = '', $name = '' ) {
		et_core_security_check();

		$list_names = $this->get_subscriber_lists_for_account( $service, $name, 'name' );

		if ( false === $list_names ) {
			return '';
		}

		$output = sprintf(
			'<div class="et_dashboard_row et_dashboard_new_account_lists">
				<h2>%1$s</h2>
				<div style="clear:both;"></div>
				<p>%2$s</p>
			</div>',
			esc_html__( 'Account Lists', 'bloom' ),
			! empty( $list_names )
				? implode( ', ', array_map( 'esc_html', $list_names ) )
				: esc_html__( 'No lists available for this account', 'bloom' )
		);

		return $output;
	}


	/**
	 * Get subscriber lists for an account, optionally limit returned data to a specific set of data keys.
	 *
	 * @param string $service  The name of the provider.
	 * @param string $name     The name of the provider account.
	 * @param string $only_key Only return values under this key. Optional.
	 *
	 * {@internal To get just the names of the subscriber lists for an account:
	 *            `get_subscriber_lists_for_account( 'aweber', 'some-account', 'name' )`}
	 *
	 * @return array
	 */
	public function get_subscriber_lists_for_account( $service, $name, $only_key = '' ) {
		$name    = str_replace( array( '"', "'" ), '', stripslashes( $name ) );
		$account = $this->_get_account( $service, $name );
		$result  = array();
		$lists   = isset( $account['lists'] ) ? $account['lists'] : array();

		foreach ( $lists as $id => $list_info ) {
			if ( empty( $only_key ) ) {
				$result[ $id ] = $list_info;
				continue;
			}

			if ( isset( $list_info[ $only_key ] ) ) {
				$result[] = $list_info[ $only_key ];
			}
		}

		// Salesforce doesn't support lists on non-ssl websites.
		if ( 'salesforce' === $service && ! is_ssl() ) {
			return array();
		}

		return $result;
	}

	/**
	 * Generates and displays the table with all accounts for Accounts tab
	 */
	function display_accounts_table(){
		et_core_security_check();

		$services = $this->_get_accounts();

		echo '<div class="et_dashboard_accounts_content">';

		foreach ( (array) $services as $service => $accounts ) {
			if ( empty( $accounts ) ) {
				continue;
			}

			$optins_count = 0;
			$output = '';
			printf(
				'<div class="et_dashboard_row et_dashboard_accounts_title">
					<span class="et_dashboard_service_logo_%1$s"></span>
				</div>',
				esc_attr( $service )
			);
			foreach ( $accounts as $account_name => $account_info ) {
				if ( 0 === $optins_count ) {
					$output .= sprintf(
						'<div class="et_dashboard_optins_list">
							<ul>
								<li>
									<div class="et_dashboard_table_acc_name et_dashboard_table_column et_dashboard_table_header">%1$s</div>
									<div class="et_dashboard_table_subscribers et_dashboard_table_column et_dashboard_table_header">%2$s</div>
									<div class="et_dashboard_table_growth_rate et_dashboard_table_column et_dashboard_table_header">%3$s</div>
									<div class="et_dashboard_table_actions et_dashboard_table_column"></div>
									<div style="clear: both;"></div>
								</li>',
						esc_html__( 'Account name', 'bloom' ),
						esc_html__( 'Subscribers', 'bloom' ),
						esc_html__( 'Growth rate', 'bloom' )
					);
				}

				$output .= sprintf(
					'<li class="et_dashboard_optins_item" data-account_name="%1$s" data-service="%2$s">
						<div class="et_dashboard_table_acc_name et_dashboard_table_column">%3$s</div>
						<div class="et_dashboard_table_subscribers et_dashboard_table_column"></div>
						<div class="et_dashboard_table_growth_rate et_dashboard_table_column"></div>',
					esc_attr( $account_name ),
					esc_attr( $service ),
					esc_html( $account_name )
				);

				$output .= sprintf(	'
						<div class="et_dashboard_table_actions et_dashboard_table_column">
							<span class="et_dashboard_icon_edit_account et_optin_button et_dashboard_icon" title="%8$s" data-account_name="%1$s" data-service="%2$s"></span>
							<span class="et_dashboard_icon_delete et_optin_button et_dashboard_icon" title="%4$s"><span class="et_dashboard_confirmation">%5$s</span></span>
							%3$s
							<span class="et_dashboard_icon_indicator_%7$s et_optin_button et_dashboard_icon" title="%6$s"></span>
						</div>
						<div style="clear: both;"></div>
					</li>',
					esc_attr( $account_name ),
					esc_attr( $service ),
					$this->is_authorized( $account_info )
						? sprintf( '
							<span class="et_dashboard_icon_update_lists et_optin_button et_dashboard_icon" title="%1$s" data-account_name="%2$s" data-service="%3$s">
								<span class="spinner"></span>
							</span>',
							esc_attr__( 'Update Lists', 'bloom' ),
							esc_attr( $account_name ),
							esc_attr( $service )
						)
						: '',
					esc_attr__( 'Remove account', 'bloom' ),
					sprintf(
						'%1$s<span class="et_dashboard_confirm_delete" data-optin_id="%4$s" data-remove_account="true">%2$s</span><span class="et_dashboard_cancel_delete">%3$s</span>',
						esc_html__( 'Remove this account from list?', 'bloom' ),
						esc_html__( 'Yes', 'bloom' ),
						esc_html__( 'No', 'bloom' ),
						esc_attr( $account_name )
					), //#5
					$this->is_authorized( $account_info )
						? esc_html__( 'Authorized', 'bloom' )
						: esc_html__( 'Not Authorized', 'bloom' ),
					$this->is_authorized( $account_info )
						? 'check'
						: 'dot',
					esc_html__( 'Edit account', 'bloom' )
				);

				if ( isset( $account_info['lists'] ) && ! empty( $account_info['lists'] ) ) {
					foreach ( $account_info['lists'] as $id => $list ) {
						$output .= sprintf( '
							<li class="et_dashboard_lists_row">
								<div class="et_dashboard_table_acc_name et_dashboard_table_column">%1$s</div>
								<div class="et_dashboard_table_subscribers et_dashboard_table_column">%2$s</div>
								<div class="et_dashboard_table_growth_rate et_dashboard_table_column">%3$s / %4$s</div>
								<div class="et_dashboard_table_actions et_dashboard_table_column"></div>
							</li>',
							esc_html( $list['name'] ),
							esc_html( $list['subscribers_count'] ),
							isset( $list['growth_week'] ) ? esc_html( $list['growth_week'] ) : '0',
							esc_html__( 'week', 'bloom' )
						);
					}
				} else {
					$output .= sprintf(
						'<li class="et_dashboard_lists_row">
							<div class="et_dashboard_table_acc_name et_dashboard_table_column">%1$s</div>
							<div class="et_dashboard_table_subscribers et_dashboard_table_column"></div>
							<div class="et_dashboard_table_growth_rate et_dashboard_table_column"></div>
							<div class="et_dashboard_table_actions et_dashboard_table_column"></div>
						</li>',
						esc_html__( 'No lists available', 'bloom' )
					);
				}

				$optins_count++;
			}

			echo $output;
			echo '
				</ul>
			</div>';
		}
		echo '</div>';
	}

	/**
	 * Displays tables of Active and Inactive optins on homepage
	 */
	function display_home_tab_tables() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$options_array = ET_Bloom::get_bloom_options();

		echo '<div class="et_dashboard_home_tab_content">';

		$this->generate_optins_list( $options_array, 'active' );

		$this->generate_optins_list( $options_array, 'inactive' );

		echo '</div>';

	}

	/**
	 * Generates tables of Active and Inactive optins on homepage and passes it to jQuery
	 */
	function home_tab_tables() {
		if ( ! wp_verify_nonce( $_POST['home_tab_nonce'] , 'home_tab' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$this->display_home_tab_tables();
		die();
	}

	/**
	 * Generates accounts tables and passes it to jQuery
	 */
	function reset_accounts_table() {
		if ( ! wp_verify_nonce( $_POST['accounts_tab_nonce'] , 'accounts_tab' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$this->display_accounts_table();
		die();
	}

	/**
	 * Generates optins table for homepage. Can generate table for active or inactive optins
	 */
	function generate_optins_list( $options_array = array(), $status = 'active' ) {
		$optins_count = 0;
		$output = '';
		$total_impressions = 0;
		$total_conversions = 0;

		foreach ( $options_array as $optin_id => $value ) {
			if ( isset( $value['optin_status'] ) && $status === $value['optin_status'] && empty( $value['child_of'] ) ) {
				$child_row = '';

				if ( 0 === $optins_count ) {

					$output .= sprintf(
						'<div class="et_dashboard_optins_list">
							<ul>
								<li>
									<div class="et_dashboard_table_name et_dashboard_table_column">%1$s</div>
									<div class="et_dashboard_table_impressions et_dashboard_table_column">%2$s</div>
									<div class="et_dashboard_table_conversions et_dashboard_table_column">%3$s</div>
									<div class="et_dashboard_table_rate et_dashboard_table_column">%4$s</div>
									<div class="et_dashboard_table_actions et_dashboard_table_column"></div>
									<div style="clear: both;"></div>
								</li>',
						esc_html__( 'Optin Name', 'bloom' ),
						esc_html__( 'Impressions', 'bloom' ),
						esc_html__( 'Conversions', 'bloom' ),
						esc_html__( 'Conversion Rate', 'bloom' )
					);
				}

				if ( ! empty( $value['child_optins'] ) && 'active' == $status ) {
					$optins_data = array();

					foreach( $value['child_optins'] as $id ) {
						$total_impressions += $impressions = $this->stats_count( $id, 'imp' );
						$total_conversions += $conversions = $this->stats_count( $id, 'con' );

						$optins_data[] = array(
							'name'        => $options_array[$id]['optin_name'],
							'id'          => $id,
							'rate'        => $this->conversion_rate( $id, $conversions, $impressions ),
							'impressions' => $impressions,
							'conversions' => $conversions,
						);
					}

					$child_optins_data = $this->sort_array( $optins_data, 'rate', SORT_DESC );

					$child_row = '<ul class="et_dashboard_child_row">';

					foreach( $child_optins_data as $child_details ) {
						$child_row .= sprintf(
							'<li class="et_dashboard_optins_item et_dashboard_child_item" data-optin_id="%1$s">
								<div class="et_dashboard_table_name et_dashboard_table_column">%2$s</div>
								<div class="et_dashboard_table_impressions et_dashboard_table_column">%3$s</div>
								<div class="et_dashboard_table_conversions et_dashboard_table_column">%4$s</div>
								<div class="et_dashboard_table_rate et_dashboard_table_column">%5$s</div>
								<div class="et_dashboard_table_actions et_dashboard_table_column">
									<span class="et_dashboard_icon_edit et_optin_button et_dashboard_icon" title="%8$s" data-parent_id="%9$s"><span class="spinner"></span></span>
									<span class="et_dashboard_icon_delete et_optin_button et_dashboard_icon" title="%6$s"><span class="et_dashboard_confirmation">%7$s</span></span>
								</div>
								<div style="clear: both;"></div>
							</li>',
							esc_attr( $child_details['id'] ),
							esc_html( $child_details['name'] ),
							esc_html( $child_details['impressions'] ),
							esc_html( $child_details['conversions'] ),
							esc_html( $child_details['rate'] . '%' ), // #5
							esc_attr__( 'Delete Optin', 'bloom' ),
							sprintf(
								'%1$s<span class="et_dashboard_confirm_delete" data-optin_id="%4$s" data-parent_id="%5$s">%2$s</span>
								<span class="et_dashboard_cancel_delete">%3$s</span>',
								esc_html__( 'Delete this optin?', 'bloom' ),
								esc_html__( 'Yes', 'bloom' ),
								esc_html__( 'No', 'bloom' ),
								esc_attr( $child_details['id'] ),
								esc_attr( $optin_id )
							),
							esc_attr__( 'Edit Optin', 'bloom' ),
							esc_attr( $optin_id ) // #9
						);
					}

					$child_row .= sprintf(
						'<li class="et_dashboard_add_variant et_dashboard_optins_item">
							<a href="#" class="et_dashboard_add_var_button">%1$s</a>
							<div class="child_buttons_right">
								<a href="#" class="et_dashboard_start_test%5$s" data-parent_id="%4$s">%2$s</a>
								<a href="#" class="et_dashboard_end_test" data-parent_id="%4$s">%3$s</a>
							</div>
						</li>',
						esc_html__( 'Add variant', 'bloom' ),
						( isset( $value['test_status'] ) && 'active' == $value['test_status'] ) ? esc_html__( 'Pause test', 'bloom' ) : esc_html__( 'Start test', 'bloom' ),
						esc_html__( 'End & pick winner', 'bloom' ),
						esc_attr( $optin_id ),
						( isset( $value['test_status'] ) && 'active' == $value['test_status'] ) ? ' et_dashboard_pause_test' : ''
					);

					$child_row .= '</ul>';
				}

				$total_impressions += $impressions = $this->stats_count( $optin_id, 'imp' );
				$total_conversions += $conversions = $this->stats_count( $optin_id, 'con' );

				$output .= sprintf(
					'<li class="et_dashboard_optins_item et_dashboard_parent_item" data-optin_id="%1$s">
						<div class="et_dashboard_table_name et_dashboard_table_column et_dashboard_icon et_dashboard_type_%13$s">%2$s</div>
						<div class="et_dashboard_table_impressions et_dashboard_table_column">%3$s</div>
						<div class="et_dashboard_table_conversions et_dashboard_table_column">%4$s</div>
						<div class="et_dashboard_table_rate et_dashboard_table_column">%5$s</div>
						<div class="et_dashboard_table_actions et_dashboard_table_column">
							<span class="et_dashboard_icon_edit et_optin_button et_dashboard_icon" title="%10$s"><span class="spinner"></span></span>
							<span class="et_dashboard_icon_delete et_optin_button et_dashboard_icon" title="%9$s"><span class="et_dashboard_confirmation">%12$s</span></span>
							<span class="et_dashboard_icon_duplicate duplicate_id_%1$s et_optin_button et_dashboard_icon" title="%8$s"><span class="spinner"></span></span>
							<span class="et_dashboard_icon_%11$s et_dashboard_toggle_status et_optin_button et_dashboard_icon%16$s" data-toggle_to="%11$s" data-optin_id="%1$s" title="%7$s"><span class="spinner"></span></span>
							%14$s
							%6$s
						</div>
						<div style="clear: both;"></div>
						%15$s
					</li>',
					esc_attr( $optin_id ),
					esc_html( $value['optin_name'] ),
					esc_html( $impressions ),
					esc_html( $conversions ),
					esc_html( $this->conversion_rate( $optin_id, $conversions, $impressions ) . '%' ), // #5
					( 'locked' === $value['optin_type'] || 'inline' === $value['optin_type'] )
						? sprintf(
							'<span class="et_dashboard_icon_shortcode et_optin_button et_dashboard_icon" title="%1$s" data-type="%2$s"></span>',
							esc_attr__( 'Generate shortcode', 'bloom' ),
							esc_attr( $value['optin_type'] )
						)
						: '',
					'active' === $status ? esc_html__( 'Make Inactive', 'bloom' ) : esc_html__( 'Make Active', 'bloom' ),
					esc_attr__( 'Duplicate', 'bloom' ),
					esc_attr__( 'Delete Optin', 'bloom' ),
					esc_attr__( 'Edit Optin', 'bloom' ), //#10
					'active' === $status ? 'inactive' : 'active',
					sprintf(
						'%1$s<span class="et_dashboard_confirm_delete" data-optin_id="%4$s">%2$s</span>
						<span class="et_dashboard_cancel_delete">%3$s</span>',
						esc_html__( 'Delete this optin?', 'bloom' ),
						esc_html__( 'Yes', 'bloom' ),
						esc_html__( 'No', 'bloom' ),
						esc_attr( $optin_id )
					),
					esc_attr( $value['optin_type'] ),
					( 'active' === $status )
						? sprintf(
							'<span class="et_dashboard_icon_abtest et_optin_button et_dashboard_icon%2$s" title="%1$s"></span>',
							esc_attr__( 'A/B Testing', 'bloom' ),
							( '' != $child_row ) ? ' active_child_optins' : ''
						)
						: '',
					$child_row, //#15
					( 'empty' == $value['email_provider'] || ( 'custom_html' !== $value['email_provider'] && 'empty' == $value['email_list'] ) )
						? ' et_bloom_no_account'
						: '' //#16
				);
				$optins_count++;
			}
		}

		if ( 'active' === $status && 0 < $optins_count ) {
			$output .= sprintf(
				'<li class="et_dashboard_optins_item_bottom_row">
					<div class="et_dashboard_table_name et_dashboard_table_column"></div>
					<div class="et_dashboard_table_impressions et_dashboard_table_column">%1$s</div>
					<div class="et_dashboard_table_conversions et_dashboard_table_column">%2$s</div>
					<div class="et_dashboard_table_rate et_dashboard_table_column">%3$s</div>
					<div class="et_dashboard_table_actions et_dashboard_table_column"></div>
				</li>',
				esc_html( $this->get_compact_number( $total_impressions ) ),
				esc_html( $this->get_compact_number( $total_conversions ) ),
				( 0 !== $total_impressions )
					? esc_html( round( ( $total_conversions * 100 ) / $total_impressions, 1 ) . '%' )
					: '0%'
			);
		}

		if ( 0 < $optins_count ) {
			if ( 'inactive' === $status ) {
				printf( '
					<div class="et_dashboard_row">
						<h1>%1$s</h1>
					</div>',
					esc_html__( 'Inactive Optins', 'bloom' )
				);
			}

			echo $output . '</ul></div>';
		}
	}

	function add_admin_body_class( $classes ) {
		return "$classes et_bloom";
	}

	function register_scripts( $hook ) {

		wp_enqueue_style( 'et-bloom-menu-icon', ET_BLOOM_PLUGIN_URI . '/css/bloom-menu.css', array(), $this->plugin_version );

		if ( "toplevel_page_{$this->_options_pagename}" !== $hook ) {
			return;
		}

		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );

		et_core_load_main_fonts();

		if ( is_rtl() ) {
			wp_enqueue_style( 'et-bloom-rtl-css', ET_BLOOM_PLUGIN_URI . '/css/admin-rtl.css', array(), $this->plugin_version );
		}

		wp_enqueue_script( 'et_bloom-uniform-js', ET_BLOOM_PLUGIN_URI . '/js/jquery.uniform.min.js', array( 'jquery' ), $this->plugin_version, true );
		wp_enqueue_style( 'et-bloom-css', ET_BLOOM_PLUGIN_URI . '/css/admin.css', array(), $this->plugin_version );
		wp_enqueue_style( 'et_bloom-preview-css', ET_BLOOM_PLUGIN_URI . '/css/style.css', array(), $this->plugin_version );
		wp_enqueue_script( 'et-bloom-js', ET_BLOOM_PLUGIN_URI . '/js/admin.js', array( 'jquery' ), $this->plugin_version, true );
		wp_localize_script( 'et-bloom-js', 'bloom_settings', array(
			'bloom_nonce'          => wp_create_nonce( 'bloom_nonce' ),
			'ajaxurl'              => admin_url( 'admin-ajax.php', $this->protocol ),
			'reset_options'        => wp_create_nonce( 'reset_options' ),
			'remove_option'        => wp_create_nonce( 'remove_option' ),
			'duplicate_option'     => wp_create_nonce( 'duplicate_option' ),
			'home_tab'             => wp_create_nonce( 'home_tab' ),
			'toggle_status'        => wp_create_nonce( 'toggle_status' ),
			'optin_type_title'     => esc_html__( 'select optin type to begin', 'bloom' ),
			'shortcode_text'       => esc_html__( 'Shortcode for this optin:', 'bloom' ),
			'get_lists'            => wp_create_nonce( 'get_lists' ),
			'add_account'          => wp_create_nonce( 'add_account' ),
			'accounts_tab'         => wp_create_nonce( 'accounts_tab' ),
			'retrieve_lists'       => wp_create_nonce( 'retrieve_lists' ),
			'ab_test'              => wp_create_nonce( 'ab_test' ),
			'bloom_stats'          => wp_create_nonce( 'bloom_stats' ),
			'redirect_url'         => rawurlencode( admin_url( 'admin.php?page=' . $this->_options_pagename, $this->protocol ) ),
			'authorize_text'       => esc_html__( 'Authorize', 'bloom' ),
			'reauthorize_text'     => esc_html__( 'Re-Authorize', 'bloom' ),
			'no_account_name_text' => esc_html__( 'Account name is not defined', 'bloom' ),
			'ab_test_pause_text'   => esc_html__( 'Pause test', 'bloom' ),
			'ab_test_start_text'   => esc_html__( 'Start test', 'bloom' ),
			'bloom_premade_nonce'  => wp_create_nonce( 'bloom_premade' ),
			'preview_nonce'        => wp_create_nonce( 'bloom_preview' ),
			'no_account_text'      => esc_html__( 'You Have Not Added An Email List. Before your opt-in can be activated, you must first add an account and select an email list. You can save and exit, but the opt-in will remain inactive until an account is added.', 'bloom' ),
			'add_account_button'   => esc_html__( 'Add An Account', 'bloom' ),
			'save_inactive_button' => esc_html__( 'Save As Inactive', 'bloom' ),
			'cannot_activate_text' => esc_html__( 'You Have Not Added An Email List. Before your opt-in can be activated, you must first add an account and select an email list.', 'bloom' ),
			'save_settings'        => wp_create_nonce( 'save_settings' ),
			'updates_tab'          => wp_create_nonce( 'updates_tab' ),
			'google_tab'           => wp_create_nonce( 'google_tab' ),
			'all_optins_list'      => json_encode( $this->get_all_optins_list() ),
			'last_record_date'     => sanitize_text_field( $this->get_last_record_date() ),
		) );
	}

	/**
	 * Generates unique ID for new set of options
	 * @return string or int
	 */
	function generate_optin_id( $full_id = true ) {

		$options_array = ET_Bloom::get_bloom_options();
		$form_id = (int) 0;

		if( ! empty( $options_array ) ) {
			foreach ( $options_array as $key => $value) {
				$keys_array[] = (int) str_replace( 'optin_', '', $key );
			}

			$form_id = max( $keys_array ) + 1;
		}

		$result = true === $full_id ? (string) 'optin_' . $form_id : (int) $form_id;

		return $result;

	}

	/**
	 * Generates options page for specific optin ID
	 * @return string
	 */
	function reset_options_page() {
		if ( ! wp_verify_nonce( $_POST['reset_options_nonce'] , 'reset_options' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$optin_id = ! empty( $_POST['reset_optin_id'] )
			? sanitize_text_field( $_POST['reset_optin_id'] )
			: $this->generate_optin_id();
		$additional_options = '';

		ET_Bloom::generate_options_page( $optin_id );

		die();
	}

	/**
	 * Handles "Duplicate" button action
	 * @return string
	 */
	function duplicate_optin() {
		if ( ! wp_verify_nonce( $_POST['duplicate_option_nonce'] , 'duplicate_option' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$duplicate_optin_id = ! empty( $_POST['duplicate_optin_id'] ) ? sanitize_text_field( $_POST['duplicate_optin_id'] ) : '';
		$duplicate_optin_type = ! empty( $_POST['duplicate_optin_type'] ) ? sanitize_text_field( $_POST['duplicate_optin_type'] ) : '';

		$this->perform_option_duplicate( $duplicate_optin_id, $duplicate_optin_type, false );

		die();
	}

	/**
	 * Handles "Add Variant" button action
	 * @return string
	 */
	function add_variant() {
		if ( ! wp_verify_nonce( $_POST['duplicate_option_nonce'] , 'duplicate_option' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$duplicate_optin_id = ! empty( $_POST['duplicate_optin_id'] ) ? sanitize_text_field( $_POST['duplicate_optin_id'] ) : '';

		$variant_id = $this->perform_option_duplicate( $duplicate_optin_id, '', true );

		die( $variant_id );
	}

	/**
	 * Toggles testing status
	 * @return void
	 */
	function ab_test_actions() {
		if ( ! wp_verify_nonce( $_POST['ab_test_nonce'] , 'ab_test' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$parent_id = ! empty( $_POST['parent_id'] ) ? sanitize_text_field( $_POST['parent_id'] ) : '';
		$action = ! empty( $_POST['test_action'] ) ? sanitize_text_field( $_POST['test_action'] ) : '';
		$options_array = ET_Bloom::get_bloom_options();
		$update_test_status[$parent_id] = $options_array[$parent_id];

		switch( $action ) {
			case 'start' :
				$update_test_status[$parent_id]['test_status'] = 'active';
				$result = 'ok';
			break;
			case 'pause' :
				$update_test_status[$parent_id]['test_status'] = 'inactive';
				$result = 'ok';
			break;

			case 'end' :
				$result = $this->generate_end_test_modal( $parent_id );
			break;
		}

		ET_Bloom::update_option( $update_test_status );

		die( $result );
	}

	/**
	 * Generates modal window for the pick winner option
	 * @return string
	 */
	function generate_end_test_modal( $parent_id ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$options_array = ET_Bloom::get_bloom_options();
		$test_optins = $options_array[$parent_id]['child_optins'];
		$test_optins[] = $parent_id;
		$output = '';

		if ( ! empty( $test_optins ) ) {
			foreach( $test_optins as $id ) {
				$optins_data[] = array(
					'name' => $options_array[$id]['optin_name'],
					'id' => $id,
					'rate' => $this->conversion_rate( $id ),
				);
			}

			$optins_data = $this->sort_array( $optins_data, 'rate', SORT_DESC );

			$table = sprintf(
				'<div class="end_test_table">
					<ul data-optins_set="%3$s" data-parent_id="%4$s">
						<li class="et_test_table_header">
							<div class="et_dashboard_table_column">%1$s</div>
							<div class="et_dashboard_table_column et_test_conversion">%2$s</div>
						</li>',
				esc_html__( 'Optin name', 'bloom' ),
				esc_html__( 'Conversion rate', 'bloom' ),
				esc_attr( implode( '#', $test_optins ) ),
				esc_attr( $parent_id )
			);

			foreach( $optins_data as $single ) {
				$table .= sprintf(
					'<li class="et_dashboard_content_row" data-optin_id="%1$s">
						<div class="et_dashboard_table_column">%2$s</div>
						<div class="et_dashboard_table_column et_test_conversion">%3$s</div>
					</li>',
					esc_attr( $single['id'] ),
					esc_html( $single['name'] ),
					esc_html( $single['rate'] . '%' )
				);
			}

			$table .= '</ul></div>';

			$output = sprintf(
				'<div class="et_dashboard_networks_modal et_dashboard_end_test">
					<div class="et_dashboard_inner_container">
						<div class="et_dashboard_modal_header">
							<span class="modal_title">%1$s</span>
							<span class="et_dashboard_close"></span>
						</div>
						<div class="dashboard_icons_container">
							%3$s
						</div>
						<div class="et_dashboard_modal_footer">
							<a href="#" class="et_dashboard_ok et_dashboard_warning_button">%2$s</a>
						</div>
					</div>
				</div>',
				esc_html__( 'Choose an optin', 'bloom' ),
				esc_html__( 'cancel', 'bloom' ),
				$table
			);
		}

		return $output;
	}

	/**
	 * Handles "Pick winner" function. Replaces the content of parent optin with the content of "winning" optin.
	 * Updates options and stats accordingly.
	 * @return void
	 */
	function pick_winner_optin() {
		if ( ! wp_verify_nonce( $_POST['remove_option_nonce'] , 'remove_option' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$winner_id = ! empty( $_POST['winner_id'] ) ? sanitize_text_field( $_POST['winner_id'] ) : '';
		$optins_set = ! empty( $_POST['optins_set'] ) ? sanitize_text_field( $_POST['optins_set'] ) : '';
		$parent_id = ! empty( $_POST['parent_id'] ) ? sanitize_text_field( $_POST['parent_id'] ) : '';

		$options_array = ET_Bloom::get_bloom_options();
		$temp_array = $options_array[$winner_id];

		$temp_array['test_status'] = 'inactive';
		$temp_array['child_optins'] = array();
		$temp_array['child_of'] = '';
		$temp_array['next_optin'] = '-1';
		$temp_array['display_on'] = $options_array[$parent_id]['display_on'];
		$temp_array['post_types'] = $options_array[$parent_id]['post_types'];
		$temp_array['post_categories'] = $options_array[$parent_id]['post_categories'];
		$temp_array['pages_exclude'] = $options_array[$parent_id]['pages_exclude'];
		$temp_array['pages_include'] = $options_array[$parent_id]['pages_include'];
		$temp_array['posts_exclude'] = $options_array[$parent_id]['posts_exclude'];
		$temp_array['posts_include'] = $options_array[$parent_id]['posts_include'];
		$temp_array['email_provider'] = $options_array[$parent_id]['email_provider'];
		$temp_array['account_name'] = $options_array[$parent_id]['account_name'];
		$temp_array['email_list'] = $options_array[$parent_id]['email_list'];
		$temp_array['custom_html'] = $options_array[$parent_id]['custom_html'];

		$updated_array[$parent_id] = $temp_array;

		if ( $parent_id != $winner_id ){
			$this->update_stats_for_winner( $parent_id, $winner_id );
		}

		$optins_set = explode( '#', $optins_set );
		foreach ( $optins_set as $optin_id ) {
			if ( $parent_id != $optin_id ) {
				$this->remove_optin_or_account( $optin_id, false, '', '', false );
			}
		}

		ET_Bloom::update_option( $updated_array );
	}

	/**
	 * Updates stats table when A/B testing finished winner optin selected
	 * @return void
	 */
	function update_stats_for_winner( $optin_id, $winner_id ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$optin_id  = sanitize_text_field( $optin_id );
		$winner_id = sanitize_text_field( $winner_id );

		$this->remove_optin_from_db( $optin_id );

		$sql = "UPDATE __table_name__ SET optin_id = %s WHERE optin_id = %s AND removed_flag <> 1";

		$sql_args = array(
			$optin_id,
			$winner_id
		);

		$this->perform_stats_sql_request( $sql, 'query', $sql_args );
	}

	/**
	 * Performs duplicating of optin. Can duplicate parent optin as well as child optin based on $is_child parameter
	 * @return string
	 */
	function perform_option_duplicate( $duplicate_optin_id, $duplicate_optin_type = '', $is_child = false ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$new_optin_id = $this->generate_optin_id();
		$suffix = true == $is_child ? '_child' : '_copy';

		if ( '' !== $duplicate_optin_id ) {
			$options_array = ET_Bloom::get_bloom_options();
			$new_option[$new_optin_id] = $options_array[$duplicate_optin_id];
			$new_option[$new_optin_id]['optin_name'] = $new_option[$new_optin_id]['optin_name'] . $suffix;
			$new_option[$new_optin_id]['optin_status'] = 'active';

			if ( true == $is_child ) {
				$new_option[$new_optin_id]['child_of'] = $duplicate_optin_id;
				$updated_optin[$duplicate_optin_id] = $options_array[$duplicate_optin_id];
				unset( $new_option[$new_optin_id]['child_optins'] );
				$updated_optin[$duplicate_optin_id]['child_optins'] = isset( $options_array[$duplicate_optin_id]['child_optins'] ) ? array_merge( $options_array[$duplicate_optin_id]['child_optins'], array( $new_optin_id ) ) : array( $new_optin_id );
				ET_Bloom::update_option( $updated_optin );
			} else {
				$new_option[$new_optin_id]['optin_type'] = $duplicate_optin_type;
				unset( $new_option[$new_optin_id]['child_optins'] );
			}

			if ( 'breakout_edge' === $new_option[$new_optin_id]['edge_style'] && 'pop_up' !== $duplicate_optin_type ) {
				$new_option[$new_optin_id]['edge_style'] = 'basic_edge';
			}

			if ( ! ( 'flyin' === $duplicate_optin_type || 'pop_up' === $duplicate_optin_type ) ) {
				unset( $new_option[$new_optin_id]['display_on'] );
			}

			ET_Bloom::update_option( $new_option );

			return $new_optin_id;
		}
	}

	/**
	 * Handles optin/account removal function called via jQuery
	 */
	function remove_optin() {
		et_core_security_check( 'manage_options', 'remove_option' );

		$optin_id = ! empty( $_POST['remove_optin_id'] ) ? sanitize_text_field( $_POST['remove_optin_id'] ) : '';
		$is_account = ! empty( $_POST['is_account'] ) ? sanitize_text_field( $_POST['is_account'] ) : '';
		$service = ! empty( $_POST['service'] ) ? sanitize_text_field( $_POST['service'] ) : '';
		$parent_id = ! empty( $_POST['parent_id'] ) ? sanitize_text_field( $_POST['parent_id'] ) : '';

		$this->remove_optin_or_account( $optin_id, $is_account, $service, $parent_id );

		die();
	}

	/**
	 * Performs removal of optin or account. Can remove parent optin, child optin or account
	 *
	 * @param string $id_or_name   The optin id or account name.
	 * @param bool   $is_account   Whether or not an account removal is being requested.
	 * @param string $service      The name of the provider (if we're removing an account).
	 * @param string $parent_id    The parent optin id.
	 * @param bool   $remove_child Whether or not a child optin removal is being requested.
	 *
	 * @return void
	 */
	function remove_optin_or_account( $id_or_name, $is_account = false, $service = '', $parent_id = '', $remove_child = true ) {
		et_core_security_check();

		if ( '' === $id_or_name || ( 'true' === $is_account && '' === $service ) ) {
			return;
		}

		$options_array = ET_Bloom::get_bloom_options();

		if ( 'true' === $is_account && $this->providers->account_exists( $service, $id_or_name ) ) {
			$this->providers->remove_account( $service, $id_or_name );
			$this->reset_optins_for_provider_account( $service, $id_or_name );
		} else if ( 'true' !== $is_account ) {
			if ( '' !== $parent_id ) {
				$updated_array[ $parent_id ] = $options_array[ $parent_id ];
				$new_child_optins = array();

				foreach( $updated_array[ $parent_id ]['child_optins'] as $child ) {
					if ( $child !== $id_or_name ) {
						$new_child_optins[] = $child;
					}
				}

				$updated_array[ $parent_id ]['child_optins'] = $new_child_optins;

				// change test status to 'inactive' if there is no child options after removal.
				if ( empty( $new_child_optins ) ) {
					$updated_array[ $parent_id ]['test_status'] = 'inactive';
				}

				ET_Bloom::update_option( $updated_array );
			} else if ( true == $remove_child && ! empty( $options_array[ $id_or_name ]['child_optins'] ) ) {
				foreach( $options_array[ $id_or_name ]['child_optins'] as $single_optin ) {
					ET_Bloom::remove_option( $single_optin );
					$this->remove_optin_from_db( $single_optin );
				}
			}

			ET_Bloom::remove_option( $id_or_name );
			$this->remove_optin_from_db( $id_or_name );
		}
	}

	/**
	 * Remove the optin data from stats table.
	 */
	function remove_optin_from_db( $optin_id ) {
		if ( '' !== $optin_id ) {
			$optin_id = sanitize_text_field( $optin_id );

			// construct sql query to mark removed options as removed in stats DB
			$sql = "DELETE FROM __table_name__ WHERE optin_id = %s";

			$sql_args = array(
				$optin_id,
			);

			$this->perform_stats_sql_request( $sql, 'query', $sql_args );

			// remove the cache for this optin if exists
			$stats_cache = get_option( 'et_bloom_stats_optin_cache', array() );
			if ( ! empty( $stats_cache ) && isset( $stats_cache['optins_cache'] ) && isset( $stats_cache['optins_cache'][ $optin_id ] ) ) {
				unset( $stats_cache['optins_cache'][ $optin_id ] );
				update_option( 'et_bloom_stats_optin_cache', $stats_cache );
			}
		}
	}

	/**
	 * Toggles status of optin from active to inactive and vice versa
	 * @return void
	 */
	function toggle_optin_status() {
		if ( ! wp_verify_nonce( $_POST['toggle_status_nonce'] , 'toggle_status' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$optin_id = ! empty( $_POST['status_optin_id'] ) ? sanitize_text_field( $_POST['status_optin_id'] ) : '';
		$toggle_to = ! empty( $_POST['status_new'] ) ? sanitize_text_field( $_POST['status_new'] ) : '';

		if ( '' !== $optin_id ) {
			$options_array = ET_Bloom::get_bloom_options();
			$update_option[$optin_id] = $options_array[$optin_id];
			$update_option[$optin_id]['optin_status'] = 'active' === $toggle_to ? 'active' : 'inactive';

			ET_Bloom::update_option( $update_option );
		}

		die();
	}

	/**
	 * Updates the account details in DB.
	 * @return void
	 */
	function update_account( $service = '', $name = '', $data_array = array() ) {
		if ( '' === $service || '' === $name ) {
			return;
		}

		$name = str_replace( array( '"', "'" ), '', stripslashes( $name ) );

		$this->providers->update_account( $service, $name, $data_array );
	}

	/**
	 * Used to sync the accounts data. Executed by wp_cron daily.
	 * In case of errors adds record to WP log
	 */
	function perform_auto_refresh() {
		$all_accounts = $this->_get_accounts();

		foreach ( $all_accounts as $service => $account ) {
			foreach ( $account as $name => $details ) {
				$result = '';
				$error_message = '';

				if ( $this->is_authorized( $details ) ) {

					if ( $provider = $this->_get_provider( $service, $name ) ) {
						$error_message = $provider->fetch_subscriber_lists();

						if ( is_array( $error_message ) && isset( $error_message['need_counts_update'] ) ) {
							$provider->retrieve_subscribers_count();
						}
					}
				}

				if ( 'success' !== $error_message ) {
					$result = 'bloom_error: ' . $service . ' ' . $name . ' ' . esc_html__( 'Authorization failed: ', 'bloom' ) . $error_message;
				}

				// Log errors into WP log for troubleshooting
				if ( '' !== $result ) {
					ET_Core_Logger::error( $result );
				}
			}
		}
	}

	public function authorize_account() {
		et_core_security_check( 'manage_options', 'get_lists' );

		$service      = empty( $_POST['bloom_upd_service'] ) ? '' : sanitize_text_field( $_POST['bloom_upd_service'] );
		$account_name = empty( $_POST['bloom_upd_name'] ) ? '' : sanitize_text_field( $_POST['bloom_upd_name'] );

		if ( '' === $service || '' === $account_name ) {
			return;
		}

		$provider = $this->_get_provider( $service, $account_name );

		if ( ! $provider ) {
			// New wrapper class not yet implemented for this provider. Returning to legacy handler.
			return;
		}

		foreach ( $provider->get_account_fields() as $field_name => $field_info ) {
			$post_field_name  = "{$field_name}_{$service}";
			$post_field_value = ! empty( $_POST[ $post_field_name ] ) ? sanitize_text_field( $_POST[ $post_field_name ] ) : '';

			if ( '' === $post_field_value ) {
				$post_field_name  = "bloom_{$field_name}";
				$post_field_value = ! empty( $_POST[ $post_field_name ] ) ? sanitize_text_field( $_POST[ $post_field_name ] ) : '';
			}

			if ( '' !== $post_field_value && ( ! isset( $provider->data[ $field_name ] ) || $post_field_value !== $provider->data[ $field_name ] ) ) {
				$provider->data[ $field_name ] = $post_field_value;
			}
		}

		$error_message = $provider->fetch_subscriber_lists();

		if ( is_array( $error_message ) ) {
			// perform subscriber counts update after authorization if needed
			if ( isset( $error_message['need_counts_update'] ) ) {
				$result = $error_message;
				$result['next_action'] = 'bloom_retrieve_counts';
				$result['service'] = $service;
				$result['name'] = $account_name;

				die( json_encode( $result ) );
			}

			// Provider uses OAuth2, return the redirect url to the client.
			die( json_encode( $error_message ) );
		}

		if ( 'success' === $error_message ) {
			foreach ( (array) $provider->data['lists'] as $list_id => $list_details ) {
				$provider->data['lists'][ $list_id ]['growth_week'] = $this->calculate_growth_rate( "{$service}_{$list_id}" );
			}

			$provider->save_data();
		}

		$result = 'success' === $error_message
			? $error_message
			: esc_html__( 'Authorization failed: ', 'bloom' ) . esc_html( $error_message );

		die( json_encode( $result ) );
	}

	function et_bloom_retrieve_counts() {
		et_core_security_check( 'manage_options', 'get_lists' );

		$service      = empty( $_POST['bloom_service'] ) ? '' : sanitize_text_field( $_POST['bloom_service'] );
		$account_name = empty( $_POST['bloom_name'] ) ? '' : sanitize_text_field( $_POST['bloom_name'] );

		$provider = $this->_get_provider( $service, $account_name );

		if ( ! $provider ) {
			// New wrapper class not yet implemented for this provider. Returning to legacy handler.
			return;
		}

		$provider->retrieve_subscribers_count();
	}

	public function subscribe() {
		et_core_security_check( '', 'subscribe' );

		$subscribe_data_json  = str_replace( '\\', '', $_POST['subscribe_data_array'] );
		$subscribe_data_array = json_decode( $subscribe_data_json, true );

		$service      = sanitize_text_field( $subscribe_data_array['service'] );
		$account_name = sanitize_text_field( $subscribe_data_array['account_name'] );
		$list_id      = sanitize_text_field( $subscribe_data_array['list_id'] );
		$page_id      = sanitize_text_field( $subscribe_data_array['page_id'] );
		$optin_id     = sanitize_text_field( $subscribe_data_array['optin_id'] );
		$email        = sanitize_email( $subscribe_data_array['email'] );
		$name         = isset( $subscribe_data_array['name'] ) ? sanitize_text_field( $subscribe_data_array['name'] ) : '';
		$last_name    = isset( $subscribe_data_array['last_name'] ) ? sanitize_text_field( $subscribe_data_array['last_name'] ) : '';
		$error        = array( 'error' => esc_html__( 'Invalid input. Please try again.', 'bloom' ) );

		if ( empty( $service ) || empty( $account_name ) ) {
			die( json_encode( $error ) );
		}

		// check the email before further processing
		if ( ! is_email( $email ) ) {
			die( json_encode( array( 'error' => esc_html__( 'Invalid email', 'bloom' ) ) ) );
		}

		if ( ! $provider = $this->_get_provider( $service, $account_name ) ) {
			et_core_die( esc_html__( 'Configuration Error: Invalid data.', 'bloom' ) );
		}

		$custom_fields = self::$_->sanitize_text_fields( self::$_->array_get( $_POST, 'custom_fields', array() ) );

		$error_message = $provider->subscribe( array(
			'service'       => $service,
			'account_name'  => $account_name,
			'list_id'       => $list_id,
			'page_id'       => $page_id,
			'optin_id'      => $optin_id,
			'email'         => $email,
			'name'          => $name,
			'last_name'     => $last_name,
			'custom_fields' => $custom_fields,
		) );

		if ( 'success' === $error_message ) {
			ET_Bloom::add_stats_record( 'con', $optin_id, $page_id, "{$service}_{$list_id}" );
			$result = json_encode( array( 'success' => $error_message ) );
		} else {
			$result = json_encode( array( 'error' => esc_html( $error_message ) ) );
		}

		die( $result );
	}

	/**
	 * Generates output for the "Form Integration" options.
	 * @return string
	 */
	function generate_accounts_list() {
		et_core_security_check( 'manage_options', 'retrieve_lists' );

		$service     = isset( $_POST['bloom_service'] ) ? sanitize_text_field( $_POST['bloom_service'] ) : '';
		$optin_id    = isset( $_POST['bloom_optin_id'] ) ? sanitize_text_field( $_POST['bloom_optin_id'] ) : '';
		$new_account = isset( $_POST['bloom_add_account'] ) ? sanitize_text_field( $_POST['bloom_add_account'] ) : '';

		$options_array   = ET_Bloom::get_bloom_options();
		$current_account = isset( $options_array[ $optin_id ]['account_name'] ) ? $options_array[ $optin_id ]['account_name'] : 'empty';
		$accounts        = $this->_get_accounts( $service );

		$available_accounts = array_keys( $accounts );

		if ( ! empty( $available_accounts ) && '' === $new_account ) {
			printf(
				'<li class="select et_dashboard_select_account">
					<p>%1$s</p>
					<select name="et_dashboard[account_name]" data-service="%4$s">
						<option value="empty" %3$s>%2$s</option>
						<option value="add_new">%5$s</option>',
				esc_html__( 'Select Account', 'bloom' ),
				esc_html__( 'Select One...', 'bloom' ),
				selected( 'empty', $current_account, false ),
				esc_attr( $service ),
				esc_html__( 'Add Account', 'bloom' )
			);

			if ( ! empty( $available_accounts ) ) {
				foreach ( $available_accounts as $account ) {
					printf( '<option value="%1$s" %3$s>%2$s</option>',
						esc_attr( $account ),
						esc_html( $account ),
						selected( $account, $current_account, false )
					);
				}
			}

			printf( '
					</select>
				</li>' );
		} else {
			$form_fields = $this->generate_new_account_form( $service );

			printf(
				'<li class="select et_dashboard_select_account et_dashboard_new_account">
					%3$s
					<button class="et_dashboard_icon authorize_service" data-service="%2$s">%1$s</button>
					<span class="spinner"></span>
				</li>',
				esc_html__( 'Add Account', 'bloom' ),
				esc_attr( $service ),
				$form_fields
			);
		}

		die();
	}

	/**
	 * Generates fields for the account authorization form based on the service
	 * @return string
	 */
	function generate_new_account_form( $service, $account_name = '', $display_name = true ) {
		et_core_security_check();

		$field_values = '';

		if ( '' !== $account_name ) {
			$field_values = $this->_get_account( $service, $account_name );
		}

		$form_fields = sprintf(
			'<div class="account_settings_fields" data-service="%1$s">',
			esc_attr( $service )
		);

		if ( true === $display_name ) {
			$form_fields .= sprintf( '
				<div class="et_dashboard_account_row">
					<label for="%1$s">%2$s</label>
					<input type="text" value="%3$s" id="%1$s">%4$s
				</div>',
				esc_attr( 'name_' . $service ),
				esc_html__( 'Account Name', 'bloom' ),
				esc_attr( $account_name ),
				ET_Bloom::generate_hint( esc_html__( 'Enter the name for your account', 'bloom' ), true )
			);
		}

		foreach ( $this->providers->account_fields( $service ) as $field_name => $field ) {
			$form_fields .= sprintf(
				'<div class="et_dashboard_account_row">
					<label for="%1$s">%2$s</label>
					<input%6$s class="provider_field_%5$s%7$s" value="%3$s" id="%1$s">%4$s
				</div>',
				esc_attr( $field_name . '_' . $service ),
				esc_html( $field['label'] ),
				( ! empty( $field_values ) && isset( $field_values[ $field_name ] ) ? esc_attr( $field_values[ $field_name ] ) : '' ),
				ET_Bloom::generate_hint( sprintf(
					'<a href="http://www.elegantthemes.com/plugins/bloom/documentation/accounts/#%2$s" target="_blank">%1$s</a>',
					esc_html__( 'Click here for more information', 'bloom' ),
					esc_attr( $service )
				), false ),
				esc_attr( $service ),
				( isset( $field['apply_password_mask'] ) && ! $field['apply_password_mask'] ? '' : ' type="password"' ),
				( isset( $field['not_required'] ) && $field['not_required'] ? ' et_dashboard_not_required' : '' )
			);
		}

		$form_fields .= '</div>';

		return $form_fields;
	}

	/**
	 * Generates the list of "Lists" for selected account in the Dashboard. Returns the generated form to jQuery.
	 */
	function generate_mailing_lists( $service = '', $account_name = '' ) {
		et_core_security_check( 'manage_options', 'retrieve_lists' );

		$account_name = ! empty( $_POST['bloom_account_name'] ) ? sanitize_text_field( $_POST['bloom_account_name'] ) : $account_name;
		$service     = ! empty( $_POST['bloom_service'] ) ? sanitize_text_field( $_POST['bloom_service'] ) : $service;
		$optin_id = ! empty( $_POST['bloom_optin_id'] ) ? sanitize_text_field( $_POST['bloom_optin_id'] ) : '';

		$options_array = ET_Bloom::get_bloom_options();
		$current_email_list = isset( $options_array[$optin_id] ) ? $options_array[$optin_id]['email_list'] : 'empty';

		$available_lists = $this->get_subscriber_lists_for_account( $service, $account_name );

		if ( false === $available_lists ) {
			die();
		}

		printf( '
			<li class="select et_dashboard_select_list">
				<p>%1$s</p>
				<select name="et_dashboard[email_list]">
					<option value="empty" %3$s>%2$s</option>',
			esc_html__( 'Select Email List', 'bloom' ),
			esc_html__( 'Select One...', 'bloom' ),
			selected( 'empty', $current_email_list, false )
		);

			foreach ( $available_lists as $list_id => $list_details ) {
				printf( '<option value="%1$s" %3$s>%2$s</option>',
					esc_attr( $list_id ),
					esc_html( $list_details['name'] ),
					selected( $list_id, $current_email_list, false )
				);
			}

		printf( '
				</select>
			</li>' );

		die();
	}


/**-------------------------**/
/** 		Front end		**/
/**-------------------------**/
	function register_frontend_scripts() {
		wp_register_script( 'et_bloom-uniform-js', ET_BLOOM_PLUGIN_URI . '/js/jquery.uniform.min.js', array( 'jquery' ), $this->plugin_version, true );
		wp_register_script( 'et_bloom-custom-js', ET_BLOOM_PLUGIN_URI . '/js/custom.js', array( 'jquery' ), $this->plugin_version, true );
		wp_register_script( 'et_bloom-idle-timer-js', ET_BLOOM_PLUGIN_URI . '/js/idle-timer.min.js', array( 'jquery' ), $this->plugin_version, true );
		wp_register_style( 'et-gf-open-sans', esc_url_raw( "{$this->protocol}://fonts.googleapis.com/css?family=Open+Sans:400,700" ), array(), null );
		wp_register_style( 'et_bloom-css', ET_BLOOM_PLUGIN_URI . '/css/style.css', array(), $this->plugin_version );
	}

	public static function load_scripts_styles() {
		// do not proceed if scripts have been enqueued already
		if ( ET_Bloom::$scripts_enqueued ) {
			return;
		}

		wp_enqueue_script( 'et_bloom-uniform-js' );
		wp_enqueue_script( 'et_bloom-custom-js' );
		wp_enqueue_script( 'et_bloom-idle-timer-js' );
		wp_enqueue_style( 'et-gf-open-sans' );
		wp_enqueue_style( 'et_bloom-css' );
		wp_localize_script( 'et_bloom-custom-js', 'bloomSettings', array(
			'ajaxurl'         => admin_url( 'admin-ajax.php', is_ssl() ? 'https' : 'http' ),
			'pageurl'         => ( is_singular( get_post_types() ) ? get_permalink() : '' ),
			'stats_nonce'     => wp_create_nonce( 'update_stats' ),
			'subscribe_nonce' => wp_create_nonce( 'subscribe' ),
			'is_user_logged_in' => is_user_logged_in() ? 'logged' : 'not_logged',
		) );

		ET_Bloom::$scripts_enqueued = true;
	}

	/**
	 * Generates the array of all taxonomies supported by Bloom.
	 * Bloom fully supports only taxonomies from ET themes.
	 * @return array
	 */
	function get_supported_taxonomies( $post_types ) {
		$taxonomies = array();

		if ( ! empty( $post_types ) ) {
			foreach( $post_types as $single_type ) {
				if ( 'post' != $single_type ) {
					$taxonomies[] = $this->get_tax_slug( $single_type );
				}
			}
		}

		return $taxonomies;
	}

	/**
	 * Returns the slug for supported taxonomy based on post type.
	 * Returns empty string if taxonomy is not supported
	 * Bloom fully supports only taxonomies from ET themes.
	 * @return string
	 */
	function get_tax_slug( $post_type ) {
		$theme_name = wp_get_theme();
		$taxonomy = '';

		switch ( $post_type ) {
			case 'project' :
				$taxonomy = 'project_category';

			break;

			case 'product' :
				$taxonomy = 'product_cat';

				break;

			case 'listing' :
				if ( 'Explorable' == $theme_name ) {
					$taxonomy = 'listing_type';
				} else {
					$taxonomy = 'listing_category';
				}

				break;

			case 'event' :
				$taxonomy = 'event_category';

				break;

			case 'gallery' :
				$taxonomy = 'gallery_category';

				break;

			case 'post' :
				$taxonomy = 'category';

				break;
		}

		return $taxonomy;
	}

	/**
	 * Returns true if form should be displayed on particular page depending on user settings.
	 * @return bool
	 */
	function check_applicability( $optin_id ) {
		$options_array = ET_Bloom::get_bloom_options();

		$display_there = false;

		$optin_type = sanitize_text_field( $options_array[ $optin_id ]['optin_type'] );

		$current_optin_limits = array(
			'post_types'        => $options_array[ $optin_id ]['post_types'],
			'categories'        => $options_array[ $optin_id ]['post_categories'],
			'on_cat_select'     => isset( $options_array[ $optin_id ]['display_on'] ) && in_array( 'category', $options_array[ $optin_id ]['display_on'] ) ? true : false,
			'pages_exclude'     => is_array( $options_array[ $optin_id ]['pages_exclude'] ) ? $options_array[ $optin_id ]['pages_exclude'] : explode( ',', $options_array[ $optin_id ]['pages_exclude'] ),
			'pages_include'     => is_array( $options_array[ $optin_id ]['pages_include'] ) ? $options_array[ $optin_id ]['pages_include'] : explode( ',', $options_array[ $optin_id ]['pages_include'] ),
			'posts_exclude'     => is_array( $options_array[ $optin_id ]['posts_exclude'] ) ? $options_array[ $optin_id ]['posts_exclude'] : explode( ',', $options_array[ $optin_id ]['posts_exclude'] ),
			'posts_include'     => is_array( $options_array[ $optin_id ]['posts_include'] ) ? $options_array[ $optin_id ]['posts_include'] : explode( ',', $options_array[ $optin_id ]['posts_include'] ),
			'on_tag_select'     => isset( $options_array[ $optin_id ]['display_on'] ) && in_array( 'tags', $options_array[$optin_id]['display_on'] )
				? true
				: false,
			'on_archive_select' => isset( $options_array[ $optin_id ]['display_on'] ) && in_array( 'archive', $options_array[ $optin_id ]['display_on'] )
				? true
				: false,
			'homepage_select'   => isset( $options_array[ $optin_id ]['display_on'] ) && in_array( 'home', $options_array[ $optin_id ]['display_on'] )
				? true
				: false,
			'blog_select'   => isset( $options_array[ $optin_id ]['display_on'] ) && in_array( 'blog', $options_array[ $optin_id ]['display_on'] )
				? true
				: false,
			'everything_select' => isset( $options_array[ $optin_id ]['display_on'] ) && in_array( 'everything', $options_array[ $optin_id ]['display_on'] )
				? true
				: false,
			'auto_select'       => isset( $options_array[ $optin_id ]['post_categories']['auto_select'] )
				? $options_array[ $optin_id ]['post_categories']['auto_select']
				: false,
			'previously_saved'  => isset( $options_array[ $optin_id ]['post_categories']['previously_saved'] )
				? explode( ',', $options_array[ $optin_id ]['post_categories']['previously_saved'] )
				: false,
		);

		unset( $current_optin_limits['categories']['previously_saved'] );

		$tax_to_check = $this->get_supported_taxonomies( $current_optin_limits['post_types'] );

		if ( ( 'flyin' == $optin_type || 'pop_up' == $optin_type ) && true == $current_optin_limits['everything_select'] ) {
			if ( is_singular() ) {
				if ( ( is_singular( 'page' ) && ! in_array( get_the_ID(), $current_optin_limits['pages_exclude'] ) ) || ( ! is_singular( 'page' ) && ! in_array( get_the_ID(), $current_optin_limits['posts_exclude'] ) ) ) {
					$display_there = true;
				}
			} else {
				$display_there = true;
			}
		} else {
			if ( is_archive() && ! ET_Bloom::is_homepage() && ( 'flyin' == $optin_type || 'pop_up' == $optin_type ) ) {
				if ( true == $current_optin_limits['on_archive_select'] ) {
					$display_there = true;
				} else {
					if ( ( ( is_category( $current_optin_limits['categories'] ) || ( ! empty( $tax_to_check ) && is_tax( $tax_to_check, $current_optin_limits['categories'] ) ) ) && true == $current_optin_limits['on_cat_select'] ) || ( is_tag() && true == $current_optin_limits['on_tag_select'] ) ) {
						$display_there = true;
					}
				}
			} else {
				if ( ET_Bloom::is_blogpage() ) {
					if ( true === $current_optin_limits['blog_select'] ) {
						$display_there = true;
					}
				} else {
					$page_id = ( ET_Bloom::is_homepage() && !is_page() ) ? 'homepage' : get_the_ID();
					$current_post_type = 'homepage' === $page_id ? 'home' : get_post_type( $page_id );

					if ( is_singular() || ( ET_Bloom::is_homepage() && 'product' === $current_post_type ) || ( 'home' === $current_post_type && ( in_array( $optin_type, array( 'flyin', 'pop_up' ) ) ) ) ) {
						if ( in_array( $page_id, $current_optin_limits['pages_include'] ) || in_array( $page_id, $current_optin_limits['posts_include'] ) ) {
							$display_there = true;
						}

						if ( true == $current_optin_limits['homepage_select'] && ET_Bloom::is_homepage() ) {
							$display_there = true;
						}
					}
				}

				if ( ! empty( $current_optin_limits['post_types'] ) && is_singular( $current_optin_limits['post_types'] ) ) {

					switch ( $current_post_type ) {
						case 'page' :
						case 'home' :
							if ( ( 'home' == $current_post_type && ( 'flyin' == $optin_type || 'pop_up' == $optin_type ) ) || 'home' != $current_post_type ) {
								if ( ! ET_Bloom::is_homepage() && ! in_array( $page_id, $current_optin_limits['pages_exclude'] ) ) {
									$display_there = true;
								}
							}
							break;

						default :
							$taxonomy_slug = $this->get_tax_slug( $current_post_type );

							if ( ! in_array( $page_id, $current_optin_limits['posts_exclude'] ) ) {
								if ( '' != $taxonomy_slug ) {
									$categories = get_the_terms( $page_id, $taxonomy_slug );
									$post_cats = array();
									if ( $categories ) {
										foreach ( $categories as $category ) {
											$post_cats[] = $category->term_id;
										}
									}

									foreach ( $post_cats as $single_cat ) {
										if ( in_array( $single_cat, $current_optin_limits['categories'] ) ) {
											$display_there = true;
										}
									}

									if ( false === $display_there && 1 == $current_optin_limits['auto_select'] ) {
										foreach ( $post_cats as $single_cat ) {
											if ( ! in_array( $single_cat, $current_optin_limits['previously_saved'] ) ) {
												$display_there = true;
											}
										}
									}
								} else {
									$display_there = true;
								}
							}

							break;
					}
				}
			}
		}

		return $display_there;
	}

	/**
	 * Calculates and returns the ID of optin which should be displayed if A/B testing is enabled
	 * @return string
	 */
	public static function choose_form_ab_test( $optin_id, $optins_set, $update_option = true ) {
		$chosen_form = $optin_id;

		if( ! empty( $optins_set[$optin_id]['child_optins'] ) && 'active' == $optins_set[$optin_id]['test_status'] ) {
			$chosen_form = ( '-1' != $optins_set[$optin_id]['next_optin'] || empty( $optins_set[$optin_id]['next_optin'] ) )
				? $optins_set[$optin_id]['next_optin']
				: $optin_id;

			if ( '-1' == $optins_set[$optin_id]['next_optin'] ) {
				$next_optin = $optins_set[$optin_id]['child_optins'][0];
			} else {
				$child_forms_count = count( $optins_set[$optin_id]['child_optins'] );

				for ( $i = 0; $i < $child_forms_count; $i++ ) {
					if ( $optins_set[$optin_id]['next_optin'] == $optins_set[$optin_id]['child_optins'][$i] ) {
						$current_optin_number = $i;
					}
				}

				if ( ( $child_forms_count - 1 ) == $current_optin_number ) {
					$next_optin = '-1';
				} else {
					$next_optin = $optins_set[$optin_id]['child_optins'][$current_optin_number + 1];
				}

			}
			if ( true === $update_option ) {
				$update_test_optin[$optin_id] = $optins_set[$optin_id];
				$update_test_optin[$optin_id]['next_optin'] = $next_optin;
				ET_Bloom::update_bloom_options( $update_test_optin );
			}
		}

		return $chosen_form;
	}

	/**
	 * Handles the stats adding request via jQuery
	 * @return void
	 */
	function handle_stats_adding() {
		if ( ! wp_verify_nonce( $_POST['update_stats_nonce'] , 'update_stats' ) ) {
			die( -1 );
		}

		$stats_data_json = str_replace( '\\', '' ,  $_POST[ 'stats_data_array' ] );
		$stats_data_array = json_decode( $stats_data_json, true );

		ET_Bloom::add_stats_record( $stats_data_array['type'], $stats_data_array['optin_id'], $stats_data_array['page_id'], $stats_data_array['list_id'] );

		die();

	}

	/**
	 * Adds the record to stats table. Either conversion or impression for specific list on specific form on specific page.
	 */
	public static function add_stats_record( $type, $optin_id, $page_id, $list_id ) {
		// do not update stats if visitor logged in
		if ( is_user_logged_in() ) {
			return;
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'et_bloom_stats';
		$cookie     = "et_bloom_{$optin_id}_{$list_id}_{$type}";

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			return;
		}

		if ( isset( $_COOKIE[ $cookie ] ) ) {
			return;
		}

		$record_date = current_time( 'mysql' );

		$fields = array(
			'record_date'  => sanitize_text_field( $record_date ),
			'optin_id'     => sanitize_text_field( $optin_id ),
			'record_type'  => sanitize_text_field( $type ),
			'page_id'      => (int) $page_id,
			'list_id'      => sanitize_text_field( $list_id ),
			'removed_flag' => 0,
		);

		$format = array(
			'%s', // record_date
			'%s', // optin_id
			'%s', // record_type
			'%d', // page_id
			'%s', // list_id
			'%d', // removed_flag
		);

		$wpdb->insert( $table_name, $fields, $format );

		return;
	}

	/**
	 * Saves the Updates Settings
	 */
	function save_updates_tab() {
		if ( ! wp_verify_nonce( $_POST['updates_tab_nonce'] , 'updates_tab' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$username = ! empty( $_POST['et_bloom_updates_username'] ) ? sanitize_text_field( $_POST['et_bloom_updates_username'] ) : '';
		$api_key = ! empty( $_POST['et_bloom_updates_api_key'] ) ? sanitize_text_field( $_POST['et_bloom_updates_api_key'] ) : '';

		update_option( 'et_automatic_updates_options', array(
			'username' => $username,
			'api_key' => $api_key,
		) );

		die();
	}

	/**
	 * Saves the Google Settings
	 */
	function save_google_tab() {
		et_core_security_check( 'manage_options', 'google_tab' );

		$google_fonts_value = ! empty( $_POST['et_bloom_use_google_fonts'] ) ? sanitize_text_field( $_POST['et_bloom_use_google_fonts'] ) : '';

		if ( '' !== $google_fonts_value ) {
			$google_api_settings = get_option( 'et_google_api_settings' );
			$google_api_settings['use_google_fonts'] = $google_fonts_value;

			update_option( 'et_google_api_settings', $google_api_settings );
		}

		die();
	}

	// add marker at the bottom of the_content() for the "Trigger at bottom of post" option.
	function trigger_bottom_mark( $content ) {
		$content .= '<span class="et_bloom_bottom_trigger"></span>';
		return $content;
	}

	public static function get_field_restrictions($field) {
		$pattern         = '';
		$title           = '';
		$symbols_pattern = '.';
		$length_pattern  = '*';
		$allowed_symbols = isset( $field->allowed_symbols ) ? $field->allowed_symbols : 'any';
		$min_length      = isset( $field->min_length ) ? intval( $field->min_length ) : 0;
		$max_length      = isset( $field->max_length ) ? intval( $field->max_length ) : 0;
		$max_length_attr = '';

		if ( in_array( $allowed_symbols, array( 'letters', 'numbers', 'alphanumeric' ) ) ) {
			switch ( $allowed_symbols ) {
				case 'letters':
					$symbols_pattern = '[A-Z|a-z]';
					$title           = __( 'Only letters allowed.', 'bloom' );
					break;
				case 'numbers':
					$symbols_pattern = '[0-9]';
					$title           = __( 'Only numbers allowed.', 'bloom' );
					break;
				case 'alphanumeric':
					$symbols_pattern = '[A-Z|a-z|0-9]';
					$title           = __( 'Only letters and numbers allowed.', 'bloom' );
					break;
			}
		}

		if ( 0 !== $min_length && 0 !== $max_length ) {
			$max_length = max( $min_length, $max_length );
			$min_length = min( $min_length, $max_length );

			if ( $max_length > 0 ) {
				$max_length_attr = sprintf(
					' maxlength="%1$d"',
					$max_length
				);
			}
		}

		if ( 0 !== $min_length || 0 !== $max_length ) {
			$length_pattern = '{';

			if ( 0 !== $min_length ) {
				$length_pattern .= $min_length;
				$title   .= sprintf( __( 'Minimum length: %1$d characters. ', 'bloom' ), $min_length );
			}

			if ( 0 === $max_length ) {
				$length_pattern .= ',';
			}

			if ( 0 === $min_length ) {
				$length_pattern .= '0';
			}

			if ( 0 !== $max_length ) {
				$length_pattern .= ",{$max_length}";
				$title   .= sprintf( __( 'Maximum length: %1$d characters.', 'bloom' ), $max_length );
			}


			$length_pattern .= '}';
		}

		if ( '' !== $title ) {
			$title = sprintf(
				' title="%1$s"',
				esc_attr( $title )
			);
		}

		if ( '.' !== $symbols_pattern || '*' !== $length_pattern ) {
			$pattern = sprintf(
				' pattern="%1$s%2$s"%3$s%4$s',
				esc_attr( $symbols_pattern ),
				esc_attr( $length_pattern ),
				$title,
				$max_length_attr
			);
		}

		return $pattern;
	}

	public static function generate_custom_fields_html( $optin_id, $settings ) {
		if ( 'on' !== self::$_->array_get( $settings, 'use_custom_fields' ) ) {
			return '';
		}

		if ( ! $custom_fields = self::$_->array_get( $settings, 'custom_fields' ) ) {
			return '';
		}

		$custom_fields = json_decode( $custom_fields );
		$output        = '';

		foreach ( $custom_fields as $field ) {
			if ( ! isset( $field->field_type ) ) {
				continue;
			}

			$is_required_field = ! isset( $field->required_mark ) || 'off' !== $field->required_mark;
			$is_hidden_field = isset( $field->hidden ) && 'on' === $field->hidden;
			$fullwidth = isset( $field->fullwidth_field ) && 'on' === $field->fullwidth_field;
			$output   .= sprintf(
				'<p class="et_bloom_custom_field%1$s%2$s"%3$s>',
				$fullwidth ? ' et_bloom_fullwidth_field' : '',
				in_array( $field->field_type, array( 'input', 'email', 'textarea' ) ) ? ' et_bloom_popup_input' : '',
				$is_hidden_field ? ' style="display: none;"' : ''
			);

			switch ( $field->field_type ) {
				case 'checkbox':
					$options        = $field->checkbox_options;
					$options_output = '';

					foreach ( $options as $option_index => $option ) {
						$option_id     = isset( $option->id ) ? $option->id : $option_index;
						$link_html     = '';
						$has_link_url  = isset( $option->link_url ) && $option->link_url;
						$has_link_text = isset( $option->link_text ) && $option->link_text;

						if ( $has_link_url ) {
							$link_text = $has_link_text ? $option->link_text : '';
							$link_html = sprintf( ' <a href="%1$s">%2$s</a>', esc_url( $option->link_url ), esc_html( $link_text ) );
						}

						$options_output .= sprintf(
							'<span class="et_bloom_custom_field_checkbox">
								<input type="checkbox" id="%1$s" value="%2$s" data-id="%3$s"%4$s/>
								<label for="%1$s"><i></i>%2$s%5$s</label>
							</span>',
							esc_attr( "et_bloom_custom_field_{$optin_id}_{$field->field_id}_{$option_id}" ),
							esc_attr( $option->value ),
							esc_attr( $option_id ),
							checked( $option->checked, 1, false ),
							$link_html
						);
					}

					$output .= sprintf(
						'<input class="et_bloom_checkbox_handle" type="hidden" name="%1$s" data-field_type="%2$s" data-id="%3$s" data-required_mark="%6$s">
						<span class="et_bloom_custom_field_options_wrapper">
							<span class="et_bloom_custom_field_options_title">%4$s</span>
							<span class="et_bloom_custom_field_options_list">%5$s</span>
						</span>',
						esc_attr( "et_bloom_custom_field_{$optin_id}_{$field->field_id}" ),
						esc_attr( $field->field_type ),
						esc_attr( $field->field_id ),
						esc_html( $field->field_title ),
						$options_output,
						$is_required_field ? 'required' : 'not_required'
					);
					break;

				case 'radio':
					$options        = $field->radio_options;
					$options_output = '';

					foreach ( $options as $option_index => $option ) {
						$link_html     = '';
						$has_link_url  = isset( $option->link_url ) && $option->link_url;
						$has_link_text = isset( $option->link_text ) && $option->link_text;
						$option_id     = isset( $option->id ) ? $option->id : $option_index;

						if ( $has_link_url ) {
							$link_text = $has_link_text ? $option->link_text : '';
							$link_html = sprintf( ' <a href="%1$s">%2$s</a>', esc_url( $option->link_url ), esc_html( $link_text ) );
						}

						$options_output .= sprintf(
							'<span class="et_bloom_custom_field_radio">
								<input id="%1$s" type="radio" name="%6$s" value="%2$s" data-id="%3$s" data-required_mark="%7$s"%4$s/>
								<label for="%1$s"><i></i>%2$s%5$s</label>
							</span>',
							esc_attr( "et_bloom_custom_field_{$optin_id}_{$field->field_id}_{$option_id}" ),
							esc_attr( $option->value ),
							esc_attr( $option_id ),
							checked( $option->checked, 1, false ),
							$link_html,
							esc_attr( "et_bloom_custom_field_{$optin_id}_{$field->field_id}" ),
							$is_required_field ? 'required' : 'not_required'
						);
					}

					$output .= sprintf(
						'<span class="et_bloom_custom_field_options_wrapper" id="%1$s" data-field_type="%2$s" data-id="%3$s">
							<span class="et_bloom_custom_field_options_title">%4$s</span>
							<span class="et_bloom_custom_field_options_list">%5$s</span>
						</span>',
						esc_attr( "et_bloom_custom_field_{$optin_id}_{$field->field_id}" ),
						esc_attr( $field->field_type ),
						esc_attr( $field->field_id ),
						esc_html( $field->field_title ),
						$options_output
					);
					break;

				case 'input':
				case 'textarea':
				case 'email':
					$output .= sprintf(
						'<%1$s%5$s name="%2$s" data-id="%3$s" placeholder="%4$s" data-field_type="%8$s" data-required_mark="%6$s"%7$s %9$s',
						'email' === $field->field_type ? 'input' : esc_html( $field->field_type ),
						esc_attr( "et_bloom_custom_field_{$optin_id}_{$field->field_id}" ),
						esc_attr( $field->field_id ),
						esc_html( $field->field_title ),
						'textarea' !== $field->field_type ? ' type="text"' : '',
						$is_required_field ? 'required' : 'not_required',
						'input' === $field->field_type ? self::get_field_restrictions( $field ) : '',
						esc_attr( $field->field_type ),
						'textarea' === $field->field_type ? '></textarea>' : '/>'
					);
					break;

				case 'select':
					$options        = $field->select_options;
					$options_output = sprintf( '<option value="">%1$s</option>', $field->field_title );

					foreach ( $options as $option_index => $option ) {
						$option_id = isset( $option->id ) ? $option->id : $option_index;
						$options_output .= sprintf(
							'<option value="%1$s" data-id="%2$s"%3$s>%4$s</option>',
							esc_attr( $option->value ),
							esc_attr( "et_bloom_custom_field_{$optin_id}_{$option_id}" ),
							selected( $option->checked, 1, false ),
							esc_html( $option->value )
						);
					}

					$output .= sprintf(
						'<select class="et_bloom_checkbox_handle" name="%1$s" data-field_type="%2$s" data-id="%3$s" data-required_mark="%5$s">
							%4$s
						</select>',
						esc_attr( "et_bloom_custom_field_{$optin_id}_{$field->field_id}" ),
						esc_attr( $field->field_type ),
						esc_attr( $field->field_id ),
						$options_output,
						$is_required_field ? 'required' : 'not_required'
					);
					break;
			}

			$output .= '</p>';
		}

		return $output;
	}

	/**
	 * Generates the content for the optin.
	 * @return string
	 */
	public static function generate_form_content( $optin_id, $page_id, $details = array() ) {
		if ( empty( $details ) ) {
			$all_optins = ET_Bloom::get_bloom_options();
			$details = $all_optins[$optin_id];
		}

		$hide_img_mobile_class = isset( $details['hide_mobile'] ) && '1' == $details['hide_mobile'] ? 'et_bloom_hide_mobile' : '';
		$image_animation_class = isset( $details['image_animation'] )
			? esc_attr( ' et_bloom_image_' .  $details['image_animation'] )
			: 'et_bloom_image_no_animation';
		$image_class = $hide_img_mobile_class . $image_animation_class . ' et_bloom_image';

		// Translate all strings if WPML is enabled
		if ( function_exists ( 'icl_translate' ) ) {
			$optin_title      = icl_translate( 'bloom', 'optin_title_' . $optin_id, $details['optin_title'] );
			$optin_message    = icl_translate( 'bloom', 'optin_message_' . $optin_id, $details['optin_message'] );
			$email_text       = icl_translate( 'bloom', 'email_text_' . $optin_id, $details['email_text'] );
			$first_name_text  = icl_translate( 'bloom', 'name_text_' . $optin_id, $details['name_text'] );
			$single_name_text = icl_translate( 'bloom', 'single_name_text_' . $optin_id, $details['single_name_text'] );
			$last_name_text   = icl_translate( 'bloom', 'last_name_' . $optin_id, $details['last_name'] );
			$button_text      = icl_translate( 'bloom', 'button_text_' . $optin_id, $details['button_text'] );
			$success_text     = icl_translate( 'bloom', 'success_message_' . $optin_id, $details['success_message'] );
			$footer_text      = icl_translate( 'bloom', 'footer_text_' . $optin_id, $details['footer_text'] );
		} else {
			$optin_title      = $details['optin_title'];
			$optin_message    = $details['optin_message'];
			$email_text       = $details['email_text'];
			$first_name_text  = $details['name_text'];
			$single_name_text = $details['single_name_text'];
			$last_name_text   = $details['last_name'];
			$button_text      = $details['button_text'];
			$success_text     = $details['success_message'];
			$footer_text      = $details['footer_text'];
		}

		$use_custom_fields   = 'on' === self::$_->array_get( $details, 'use_custom_fields' );
		$inline_fields       = 'inline' === self::$_->array_get( $details, 'field_orientation' );
		$name_fullwidth      = $use_custom_fields && $inline_fields && 'on' === self::$_->array_get( $details, 'name_fullwidth' );
		$last_name_fullwidth = $use_custom_fields && $inline_fields && 'on' === self::$_->array_get( $details, 'last_name_fullwidth' );
		$email_fullwidth     = $use_custom_fields && $inline_fields && 'on' === self::$_->array_get( $details, 'email_fullwidth' );
		$ip_address          = self::$_->array_get( $details, 'ip_address' ) ? 'true' : 'false';

		$formatted_title = '&lt;h2&gt;&nbsp;&lt;/h2&gt;' != $details['optin_title']
			? str_replace( '&nbsp;', '', $optin_title )
			: '';
		$formatted_message = '' != $details['optin_message'] ? $optin_message : '';

		$formatted_footer = '' != $details['footer_text']
			? sprintf( '<div class="et_bloom_form_footer">%1$s</div>', html_entity_decode( $footer_text, ENT_QUOTES, 'UTF-8' ) )
			: '';

		$is_single_name = ( isset( $details['display_name'] ) && '1' == $details['display_name'] ) ? false : true;

		$custom_fields_html = self::generate_custom_fields_html( $optin_id, $details );

		$output = sprintf( '
			<div class="et_bloom_form_container_wrapper clearfix%14$s">
				<div class="et_bloom_header_outer">
					<div class="et_bloom_form_header%1$s%13$s">
						%2$s
						%3$s
						%4$s
					</div>
				</div>
				<div class="et_bloom_form_content%5$s%6$s%7$s%12$s"%11$s>
					%8$s
					<div class="et_bloom_success_container">
						<span class="et_bloom_success_checkmark"></span>
					</div>
					<h2 class="et_bloom_success_message">%9$s</h2>
					%10$s
				</div>
			</div>
			<span class="et_bloom_close_button"></span>',
			( 'right' == $details['image_orientation'] || 'left' == $details['image_orientation'] ) && 'widget' !== $details['optin_type']
				? sprintf( ' split%1$s', 'right' == $details['image_orientation']
					? ' image_right'
					: '' )
				: '',
			( ( 'above' == $details['image_orientation'] || 'right' == $details['image_orientation'] || 'left' == $details['image_orientation'] ) && 'widget' !== $details['optin_type'] ) || ( 'above' == $details['image_orientation_widget'] && 'widget' == $details['optin_type'] )
				? sprintf(
					'%1$s',
					empty( $details['image_url']['id'] )
						? sprintf(
							'<img src="%1$s" alt="%2$s" %3$s>',
							esc_url( $details['image_url']['url'] ),
							esc_attr( wp_strip_all_tags( html_entity_decode( $formatted_title ) ) ),
							'' !== $image_class
								? sprintf( 'class="%1$s"', esc_attr( $image_class ) )
								: ''
						)
						: wp_get_attachment_image( $details['image_url']['id'], 'bloom_image', false, array( 'class' => $image_class ) )
				)
				: '',
			( '' !== $formatted_title || '' !== $formatted_message )
				? sprintf(
					'<div class="et_bloom_form_text">
						%1$s%2$s
					</div>',
					stripslashes( html_entity_decode( $formatted_title, ENT_QUOTES, 'UTF-8' ) ),
					stripslashes( html_entity_decode( $formatted_message, ENT_QUOTES, 'UTF-8' ) )
				)
				: '',
			( 'below' == $details['image_orientation'] && 'widget' !== $details['optin_type'] ) || ( isset( $details['image_orientation_widget'] ) && 'below' == $details['image_orientation_widget'] && 'widget' == $details['optin_type'] )
				? sprintf(
					'%1$s',
					empty( $details['image_url']['id'] )
						? sprintf(
							'<img src="%1$s" alt="%2$s" %3$s>',
							esc_url( $details['image_url']['url'] ),
							esc_attr( wp_strip_all_tags( html_entity_decode( $formatted_title ) ) ),
							'' !== $image_class ? sprintf( 'class="%1$s"', esc_attr( $image_class ) ) : ''
						)
						: wp_get_attachment_image( $details['image_url']['id'], 'bloom_image', false, array( 'class' => $image_class ) )
					)
				: '', //#5
			( 'no_name' == $details['name_fields'] && ! ET_Bloom::is_only_name_support( $details['email_provider'] ) ) || ( ET_Bloom::is_only_name_support( $details['email_provider'] ) && $is_single_name )
				? ' et_bloom_1_field'
				: sprintf(
					' et_bloom_%1$s_fields',
					'first_last_name' == $details['name_fields'] && ! ET_Bloom::is_only_name_support( $details['email_provider'] )
						? '3'
						: '2'
				),
			'inline' == $details['field_orientation'] && 'bottom' == $details['form_orientation'] && 'widget' !== $details['optin_type']
				? ' et_bloom_bottom_inline'
				: '',
			( 'stacked' == $details['field_orientation'] && 'bottom' == $details['form_orientation'] ) || 'widget' == $details['optin_type']
				? ' et_bloom_bottom_stacked'
				: '',
			'custom_html' == $details['email_provider']
				? stripslashes( html_entity_decode( $details['custom_html'], ENT_QUOTES, 'UTF-8' ) )
				: sprintf( '
					%1$s
					<form method="post" class="clearfix">
						<div class="et_bloom_fields">
							%3$s
							<p class="et_bloom_popup_input et_bloom_subscribe_email%13$s">
								<input placeholder="%2$s">
							</p>
							%12$s
							<button data-optin_id="%4$s" data-service="%5$s" data-list_id="%6$s" data-page_id="%7$s" data-account="%8$s" data-ip_address="%14$s" class="et_bloom_submit_subscription%11$s">
								<span class="et_bloom_subscribe_loader"></span>
								<span class="et_bloom_button_text et_bloom_button_text_color_%10$s">%9$s</span>
							</button>
						</div>
					</form>',
					'basic_edge' == $details['edge_style'] || '' == $details['edge_style']
						? ''
						: ET_Bloom::get_the_edge_code( $details['edge_style'], 'widget' == $details['optin_type'] ? 'bottom' : $details['form_orientation'] ),
					'' != $email_text ? stripslashes( esc_attr( $email_text ) ) : esc_attr__( 'Email', 'bloom' ),
					( 'no_name' == $details['name_fields'] && ! ET_Bloom::is_only_name_support( $details['email_provider'] ) ) || ( ET_Bloom::is_only_name_support( $details['email_provider'] ) && $is_single_name )
						? ''
						: sprintf(
							'<p class="et_bloom_popup_input et_bloom_subscribe_name%4$s">
								<input placeholder="%1$s%2$s" maxlength="50">
							</p>%3$s',
							'first_last_name' == $details['name_fields']
								? sprintf(
									'%1$s',
									'' != $first_name_text
										? stripslashes( esc_attr( $first_name_text ) )
										: esc_attr__( 'First Name', 'bloom' )
								)
								: '',
							( 'first_last_name' != $details['name_fields'] )
								? sprintf( '%1$s', '' != $single_name_text
									? stripslashes( esc_attr( $single_name_text ) )
									: esc_attr__( 'Name', 'bloom' ) ) : '',
							'first_last_name' == $details['name_fields'] && ! ET_Bloom::is_only_name_support( $details['email_provider'] )
								? sprintf( '
									<p class="et_bloom_popup_input et_bloom_subscribe_last%2$s">
										<input placeholder="%1$s" maxlength="50">
									</p>',
									'' != $last_name_text ? stripslashes( esc_attr( $last_name_text ) ) : esc_attr__( 'Last Name', 'bloom' ),
									$last_name_fullwidth ? ' et_bloom_fullwidth_field' : ''
								)
								: '',
							$name_fullwidth ? ' et_bloom_fullwidth_field' : ''
						),
					esc_attr( $optin_id ),
					esc_attr( $details['email_provider'] ), //#5
					esc_attr( $details['email_list'] ),
					esc_attr( $page_id ),
					esc_attr( $details['account_name'] ),
					'' != $button_text ? stripslashes( esc_html( $button_text ) ) :  esc_html__( 'SUBSCRIBE!', 'bloom' ),
					isset( $details['button_text_color'] ) ? esc_attr( $details['button_text_color'] ) : '', // #10
					'locked' === $details['optin_type'] ? ' et_bloom_submit_subscription_locked' : '', // #11
					$custom_fields_html, // #12
					$email_fullwidth ? ' et_bloom_fullwidth_field' : '', // #13
					$ip_address // #14
				), //#9
			'' != $success_text
				? wp_kses( html_entity_decode( stripslashes( $success_text ) ), array(
					'a'      => array(
						'href' => array(),
						'title' => array(),
						'class' => array(),
					),
					'br'     => array(),
					'span'   => array(
						'class' => array(),
					),
					'strong' => array(),
				) )
				: esc_html__( 'You have Successfully Subscribed!', 'bloom' ), //#10
			$formatted_footer,
			'custom_html' == $details['email_provider']
				? sprintf(
					' data-optin_id="%1$s" data-service="%2$s" data-list_id="%3$s" data-page_id="%4$s" data-account="%5$s"',
					esc_attr( $optin_id ),
					'custom_form',
					'custom_form',
					esc_attr( $page_id ),
					'custom_form'
				)
				: '',
			'custom_html' == $details['email_provider'] ? ' et_bloom_custom_html_form' : '',
			isset( $details['header_text_color'] )
				? sprintf(
					' et_bloom_header_text_%1$s',
					esc_attr( $details['header_text_color'] )
				)
				: ' et_bloom_header_text_dark',
			$custom_fields_html ? ' et_bloom_with_custom_fields' : '' //#14
		);

		return $output;
	}

	/**
	 * Whether or not provider only supports a single name field.
	 *
	 * @return bool
	 */
	public static function is_only_name_support( $service ) {
		static $name_field_only = null;

		if ( null === $name_field_only ) {
			$providers       = ET_Core_API_Email_Providers::instance();
			$name_field_only = array_keys( $providers->names_by_slug( 'all', 'name_field_only' ) );
		}

		return in_array( $service, $name_field_only );
	}

	/**
	 * Generates the svg code for edges
	 * @return bool
	 */
	public static function get_the_edge_code( $style, $orientation ) {
		$output = '';
		switch ( $style ) {
			case 'wedge_edge' :
				$output = sprintf(
					'<svg class="triangle et_bloom_default_edge" xmlns="http://www.w3.org/2000/svg" version="1.1" width="%2$s" height="%3$s" viewBox="0 0 100 100" preserveAspectRatio="none">
						<path d="%1$s" fill=""></path>
					</svg>',
					'bottom' == $orientation ? 'M0 0 L50 100 L100 0 Z' : 'M0 0 L0 100 L100 50 Z',
					'bottom' == $orientation ? '100%' : '20',
					'bottom' == $orientation ? '20' : '100%'
				);

				//if right or left orientation selected we still need to generate bottom edge to support responsive design
				if ( 'bottom' !== $orientation ) {
					$output .= sprintf(
						'<svg class="triangle et_bloom_responsive_edge" xmlns="http://www.w3.org/2000/svg" version="1.1" width="%2$s" height="%3$s" viewBox="0 0 100 100" preserveAspectRatio="none">
							<path d="%1$s" fill=""></path>
						</svg>',
						'M0 0 L50 100 L100 0 Z',
						'100%',
						'20'
					);
				}

				break;
			case 'curve_edge' :
				$output = sprintf(
					'<svg class="curve et_bloom_default_edge" xmlns="http://www.w3.org/2000/svg" version="1.1" width="%2$s" height="%3$s" viewBox="0 0 100 100" preserveAspectRatio="none">
						<path d="%1$s"></path>
					</svg>',
					'bottom' == $orientation ? 'M0 0 C40 100 60 100 100 0 Z' : 'M0 0 C0 0 100 50 0 100 z',
					'bottom' == $orientation ? '100%' : '20',
					'bottom' == $orientation ? '20' : '100%'
				);

				//if right or left orientation selected we still need to generate bottom edge to support responsive design
				if ( 'bottom' !== $orientation ) {
					$output .= sprintf(
						'<svg class="curve et_bloom_responsive_edge" xmlns="http://www.w3.org/2000/svg" version="1.1" width="%2$s" height="%3$s" viewBox="0 0 100 100" preserveAspectRatio="none">
							<path d="%1$s"></path>
						</svg>',
						'M0 0 C40 100 60 100 100 0 Z',
						'100%',
						'20'
					);
				}

				break;
		}

		return $output;
	}

	/**
	 * Displays the Flyin content on front-end.
	 */
	function display_flyin() {
		$optins_set = $this->flyin_optins;

		if ( ! empty( $optins_set ) ) {
			foreach( $optins_set as $optin_id => $details ) {
				if ( $this->check_applicability( $optin_id ) ) {
					$success_action_enabled = '';
					$success_action_details = '';

					ET_Bloom::load_scripts_styles();

					$display_optin_id = ET_Bloom::choose_form_ab_test( $optin_id, $optins_set );

					if ( $display_optin_id != $optin_id ) {
						$all_optins = ET_Bloom::get_bloom_options();
						$optin_id = $display_optin_id;
						$details = $all_optins[$optin_id];
					}

					if ( is_singular() || ET_Bloom::is_homepage() ) {
						$page_id = ET_Bloom::is_homepage() ? -1 : get_the_ID();
					} else {
						$page_id = 0;
					}

					if ( isset( $details['success_action_type'] ) && 'default' !== $details['success_action_type'] ) {
						$success_action_enabled = ' et_bloom_success_action';
						$success_action_details = sprintf(
							' data-success_action_details="%1$s|%2$s"',
							esc_attr( $details['success_action_type'] ),
							esc_url( $details['success_action_info'] )
						);
					}

					$is_single_name = ( isset( $details['display_name'] ) && '1' == $details['display_name'] ) ? false : true;

					printf(
						'<div class="et_bloom_flyin et_bloom_optin et_bloom_resize et_bloom_flyin_%6$s et_bloom_%5$s%17$s%1$s%2$s%18$s%19$s%20$s%22$s%27$s%28$s"%3$s%4$s%16$s%21$s%26$s%30$s>
							<div class="et_bloom_form_container%7$s%8$s%9$s%10$s%12$s%13$s%14$s%15$s%23$s%24$s%25$s%29$s">
								%11$s
							</div>
						</div>',
						true == $details['post_bottom'] ? ' et_bloom_trigger_bottom' : '',
						isset( $details['trigger_idle'] ) && true == $details['trigger_idle'] ? ' et_bloom_trigger_idle' : '',
						isset( $details['trigger_auto'] ) && true == $details['trigger_auto']
							? sprintf( ' data-delay="%1$s"', esc_attr( $details['load_delay'] ) )
							: '',
						isset( $details['session'] ) && true == $details['session']
							? ' data-cookie_duration="' . esc_attr( $details['session_duration'] ) . '"'
							: '',
						esc_attr( $optin_id ), // #5
						esc_attr( $details['flyin_orientation'] ),
						'bottom' !== $details['form_orientation'] && 'custom_html' !== $details['email_provider']
							? sprintf(
								' et_bloom_form_%1$s',
								esc_attr( $details['form_orientation'] )
							)
							: ' et_bloom_form_bottom',
						'basic_edge' == $details['edge_style'] || '' == $details['edge_style']
							? ''
							: sprintf( ' with_edge %1$s', esc_attr( $details['edge_style'] ) ),
						( 'no_border' !== $details['border_orientation'] )
							? sprintf(
								' et_bloom_with_border et_bloom_border_%1$s%2$s',
								esc_attr( $details['border_style'] ),
								esc_attr( ' et_bloom_border_position_' . $details['border_orientation'] )
							)
							: '',
						( 'rounded' == $details['corner_style'] ) ? ' et_bloom_rounded_corners' : '', //#10
						ET_Bloom::generate_form_content( $optin_id, $page_id ),
						'bottom' == $details['form_orientation'] && ( 'no_image' == $details['image_orientation'] || 'above' == $details['image_orientation'] || 'below' == $details['image_orientation'] ) && 'stacked' == $details['field_orientation']
							? ' et_bloom_stacked_flyin'
							: '',
						( 'rounded' == $details['field_corner'] ) ? ' et_bloom_rounded' : '',
						'light' == $details['text_color'] ? ' et_bloom_form_text_light' : ' et_bloom_form_text_dark',
						isset( $details['load_animation'] )
							? sprintf(
								' et_bloom_animation_%1$s',
								esc_attr( $details['load_animation'] )
							)
							: ' et_bloom_animation_no_animation', //#15
						isset( $details['trigger_idle'] ) && true == $details['trigger_idle']
							? sprintf( ' data-idle_timeout="%1$s"', esc_attr( $details['idle_timeout'] ) )
							: '',
						isset( $details['trigger_auto'] ) && true == $details['trigger_auto']
							? ' et_bloom_auto_popup'
							: '',
						isset( $details['comment_trigger'] ) && true == $details['comment_trigger']
							? ' et_bloom_after_comment'
							: '',
						isset( $details['purchase_trigger'] ) && true == $details['purchase_trigger']
							? ' et_bloom_after_purchase'
							: '', //#20
						isset( $details['trigger_scroll'] ) && true == $details['trigger_scroll']
							? ' et_bloom_scroll'
							: '',
						isset( $details['trigger_scroll'] ) && true == $details['trigger_scroll']
							? sprintf( ' data-scroll_pos="%1$s"', esc_attr( $details['scroll_pos'] ) )
							: '',
						isset( $details['hide_mobile_optin'] ) && true == $details['hide_mobile_optin']
							? ' et_bloom_hide_mobile_optin'
							: '',
						( 'no_name' == $details['name_fields'] && ! ET_Bloom::is_only_name_support( $details['email_provider'] ) ) || ( ET_Bloom::is_only_name_support( $details['email_provider'] ) && $is_single_name )
							? ' et_flyin_1_field'
							: sprintf(
								' et_flyin_%1$s_fields',
								'first_last_name' == $details['name_fields'] && ! ET_Bloom::is_only_name_support( $details['email_provider'] )
									? '3'
									: '2'
							),
						'inline' == $details['field_orientation'] && 'bottom' == $details['form_orientation']
							? ' et_bloom_flyin_bottom_inline'
							: '',
						'stacked' == $details['field_orientation'] && 'bottom' == $details['form_orientation'] && ( 'right' == $details['image_orientation'] || 'left' == $details['image_orientation'] )
							? ' et_bloom_flyin_bottom_stacked'
							: '', //#25
						isset( $details['trigger_click'] ) && $details['trigger_click']
							? ' data-trigger_click="' . esc_attr( $details['trigger_click_selector'] ) . '"'
							: '',
						isset( $details['trigger_click'] ) && $details['trigger_click'] ? ' et_bloom_trigger_click' : '',
						isset( $details['auto_close'] ) && true === ( bool ) $details['auto_close'] ? ' et_bloom_auto_close' : '',
						$success_action_enabled,
						$success_action_details //#30
					);
				}
			}
		}
	}

	/**
	 * Displays the PopUp content on front-end.
	 */
	function display_popup() {
		$optins_set = $this->popup_optins;

		foreach ( (array) $optins_set as $optin_id => $details ) {
			if ( $this->check_applicability( $optin_id ) ) {
					$success_action_enabled = '';
					$success_action_details = '';

				ET_Bloom::load_scripts_styles();

				$display_optin_id = ET_Bloom::choose_form_ab_test( $optin_id, $optins_set );

				if ( $display_optin_id != $optin_id ) {
					$all_optins = ET_Bloom::get_bloom_options();
					$optin_id = $display_optin_id;
					$details = $all_optins[$optin_id];
				}

				if ( is_singular() || ET_Bloom::is_homepage() ) {
					$page_id = ET_Bloom::is_homepage() ? -1 : get_the_ID();
				} else {
					$page_id = 0;
				}

					if ( isset( $details['success_action_type'] ) && 'default' !== $details['success_action_type'] ) {
						$success_action_enabled = ' et_bloom_success_action';
						$success_action_details = sprintf(
							' data-success_action_details="%1$s|%2$s"',
							esc_attr( $details['success_action_type'] ),
							esc_url( $details['success_action_info'] )
						);
					}

					printf(
						'<div class="et_bloom_popup et_bloom_optin et_bloom_resize et_bloom_%5$s%15$s%1$s%2$s%16$s%17$s%18$s%20$s%22$s%23$s"%3$s%4$s%14$s%19$s%21$s%25$s>
							<div class="et_bloom_form_container et_bloom_popup_container%6$s%7$s%8$s%9$s%11$s%12$s%13$s%24$s">
								%10$s
							</div>
						</div>',
						true == $details['post_bottom'] ? ' et_bloom_trigger_bottom' : '',
						isset( $details['trigger_idle'] ) && true == $details['trigger_idle']
							? ' et_bloom_trigger_idle'
							: '',
						isset( $details['trigger_auto'] ) && true == $details['trigger_auto']
							? sprintf( ' data-delay="%1$s"', esc_attr( $details['load_delay'] ) )
							: '',
						isset( $details['session'] ) && true == $details['session']
							? ' data-cookie_duration="' . esc_attr( $details['session_duration'] ) . '"'
							: '',
						esc_attr( $optin_id ), // #5
						'bottom' !== $details['form_orientation'] && 'custom_html' !== $details['email_provider']
							? sprintf( ' et_bloom_form_%1$s',  esc_attr( $details['form_orientation'] ) )
							: ' et_bloom_form_bottom',
						'basic_edge' == $details['edge_style'] || '' == $details['edge_style']
							? ''
							: sprintf( ' with_edge %1$s', esc_attr( $details['edge_style'] ) ),
						( 'no_border' !== $details['border_orientation'] )
							? sprintf(
								' et_bloom_with_border et_bloom_border_%1$s%2$s',
								esc_attr( $details['border_style'] ),
								esc_attr( ' et_bloom_border_position_' . $details['border_orientation'] )
							)
							: '',
						( 'rounded' == $details['corner_style'] ) ? ' et_bloom_rounded_corners' : '',
						ET_Bloom::generate_form_content( $optin_id, $page_id ), //#10
						( 'rounded' == $details['field_corner'] ) ? ' et_bloom_rounded' : '',
						'light' == $details['text_color'] ? ' et_bloom_form_text_light' : ' et_bloom_form_text_dark',
						isset( $details['load_animation'] )
							? sprintf( ' et_bloom_animation_%1$s', esc_attr( $details['load_animation'] ) )
							: ' et_bloom_animation_no_animation',
						isset( $details['trigger_idle'] ) && true == $details['trigger_idle']
							? sprintf( ' data-idle_timeout="%1$s"', esc_attr( $details['idle_timeout'] ) )
							: '',
						isset( $details['trigger_auto'] ) && true == $details['trigger_auto'] ? ' et_bloom_auto_popup' : '', //#15
						isset( $details['comment_trigger'] ) && true == $details['comment_trigger'] ? ' et_bloom_after_comment' : '',
						isset( $details['purchase_trigger'] ) && true == $details['purchase_trigger'] ? ' et_bloom_after_purchase' : '',
						isset( $details['trigger_scroll'] ) && true == $details['trigger_scroll'] ? ' et_bloom_scroll' : '',
						isset( $details['trigger_scroll'] ) && true == $details['trigger_scroll']
							? sprintf( ' data-scroll_pos="%1$s"', esc_attr( $details['scroll_pos'] ) )
							: '',
						( isset( $details['hide_mobile_optin'] ) && true == $details['hide_mobile_optin'] )
							? ' et_bloom_hide_mobile_optin'
							: '',//#20
						isset( $details['trigger_click'] ) && $details['trigger_click']
							? ' data-trigger_click="' . esc_attr( $details['trigger_click_selector'] ) . '"'
							: '',
						isset( $details['trigger_click'] ) && true == $details['trigger_click'] ? ' et_bloom_trigger_click' : '',
						isset( $details['auto_close'] ) && true === ( bool ) $details['auto_close'] ? ' et_bloom_auto_close' : '' ,
						$success_action_enabled,
						$success_action_details //#25
					);

			}
		}
	}

	function display_preview() {
		if ( ! wp_verify_nonce( $_POST['bloom_preview_nonce'] , 'bloom_preview' ) ) {
			die( -1 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$options = $_POST['preview_options'];
		$processed_string = str_replace( array( '%5B', '%5D' ), array( '[', ']' ), $options );
		parse_str( $processed_string, $processed_array );
		$details = $processed_array['et_dashboard'];
		$fonts_array = array();

		if ( ! isset( $fonts_array[$details['header_font']] ) && isset( $details['header_font'] ) ) {
			$fonts_array[] = $details['header_font'];
		}
		if ( ! isset( $fonts_array[$details['body_font']] ) && isset( $details['body_font'] ) ) {
			$fonts_array[] = $details['body_font'];
		}

		$popup_array['popup_code'] = $this->generate_preview_popup( $details );
		$popup_array['popup_css'] = '<style id="et_bloom_preview_css">' . ET_Bloom::generate_custom_css( '.et_bloom .et_bloom_preview_popup', $details ) . '</style>';
		$popup_array['fonts'] = $fonts_array;

		die( json_encode( $popup_array ) );
	}

	/**
	 * Displays the PopUp preview in dashboard.
	 */
	function generate_preview_popup( $details ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$output = '';
		$output = sprintf(
			'<div class="et_bloom_popup et_bloom_animated et_bloom_preview_popup et_bloom_optin et_bloom_preview_%8$s">
				<div class="et_bloom_form_container et_bloom_animation_fadein et_bloom_popup_container%1$s%2$s%3$s%4$s%5$s%6$s">
					%7$s
				</div>
			</div>',
			'bottom' !== $details['form_orientation'] && 'custom_html' !== $details['email_provider'] && 'widget' !== $details['optin_type']
				? sprintf( ' et_bloom_form_%1$s', esc_attr( $details['form_orientation'] ) )
				: ' et_bloom_form_bottom',
			'basic_edge' == $details['edge_style'] || '' == $details['edge_style']
				? ''
				: sprintf( ' with_edge %1$s', esc_attr( $details['edge_style'] ) ),
			( 'no_border' !== $details['border_orientation'] )
				? sprintf(
					' et_bloom_with_border et_bloom_border_%1$s%2$s',
					esc_attr( $details['border_style'] ),
					esc_attr( ' et_bloom_border_position_' . $details['border_orientation'] )
				)
				: '',
			( 'rounded' == $details['corner_style'] ) ? ' et_bloom_rounded_corners' : '',
			( 'rounded' == $details['field_corner'] ) ? ' et_bloom_rounded' : '',
			'light' == $details['text_color'] ? ' et_bloom_form_text_light' : ' et_bloom_form_text_dark',
			ET_Bloom::generate_form_content( 0, 0, $details ),
			esc_attr( $details['optin_type'] ) // #8
		);

		return $output;
	}

	/**
	 * Modifies the_content to add the form below content.
	 */
	function display_below_post( $content ) {
		// do not output Below Content optin in Visual Builder
		if ( isset( $_GET['et_fb'] ) && '1' === $_GET['et_fb'] && is_user_logged_in() ) {
			return $content;
		}

		$optins_set = $this->below_post_optins;

		if ( ! empty( $optins_set ) && ! is_singular( 'product' ) ) {
			foreach( $optins_set as $optin_id => $details ) {
				if ( $this->check_applicability( $optin_id ) ) {
					$content .= '<div class="et_bloom_below_post">' . $this->generate_inline_form( $optin_id, $details ) . '</div>';
				}
			}
		}

		return $content;
	}

	/**
	 * Display the form on woocommerce product page.
	 */
	function display_on_wc_page() {
		$optins_set = $this->below_post_optins;

		if ( ! empty( $optins_set ) ) {
			foreach( $optins_set as $optin_id => $details ) {
				if ( $this->check_applicability( $optin_id ) ) {
					echo $this->generate_inline_form( $optin_id, $details );
				}
			}
		}
	}

	function is_authorized( $account ) {
		return isset( $account['is_authorized'] ) && in_array( $account['is_authorized'], array( true, 'true' ) );
	}

	/**
	 * Generates the content for inline form. Used to generate "Below content", "Inline" and "Locked content" forms.
	 */
	function generate_inline_form( $optin_id, $details, $update_stats = true ) {

		ET_Bloom::load_scripts_styles();

		$output = $success_action_enabled = $success_action_details = '';

		$page_id = get_the_ID();
		$list_id = $details['email_provider'] . '_' . $details['email_list'];
		$custom_css_output = '';

		$all_optins = ET_Bloom::get_bloom_options();
		$display_optin_id = ET_Bloom::choose_form_ab_test( $optin_id, $all_optins );

		if ( $display_optin_id != $optin_id ) {
			$optin_id = $display_optin_id;
			$details = $all_optins[$optin_id];
		}

		if ( 'below_post' !== $details['optin_type'] ) {
			$custom_css = ET_Bloom::generate_custom_css( '.et_bloom .et_bloom_' . $display_optin_id, $details );
			$custom_css_output = '' !== $custom_css ? sprintf( '<style type="text/css">%1$s</style>', $custom_css ) : '';
		}
		if ( isset( $details['success_action_type'] ) && 'default' !== $details['success_action_type'] ) {
			$success_action_enabled = ' et_bloom_success_action';
			$success_action_details = sprintf(
				' data-success_action_details="%1$s|%2$s"',
				esc_attr( $details['success_action_type'] ),
				esc_url( $details['success_action_info'] )
			);
		}

		$is_single_name = ( isset( $details['display_name'] ) && '1' == $details['display_name'] ) ? false : true;

		$output .= sprintf(
			'<div class="et_bloom_inline_form et_bloom_optin et_bloom_make_form_visible et_bloom_%1$s%9$s" style="display: none;"%13$s>
				%10$s
				<div class="et_bloom_form_container %3$s%4$s%5$s%6$s%7$s%8$s%11$s%12$s">
					%2$s
				</div>
			</div>',
			esc_attr( $optin_id ),
			ET_Bloom::generate_form_content( $optin_id, $page_id ),
			'basic_edge' == $details['edge_style'] || '' == $details['edge_style']
				? ''
				: sprintf( ' with_edge %1$s', esc_attr( $details['edge_style'] ) ),
			( 'no_border' !== $details['border_orientation'] )
				? sprintf(
					' et_bloom_border_%1$s%2$s',
					esc_attr( $details['border_style'] ),
					'full' !== $details['border_orientation']
						? esc_attr( ' et_bloom_border_position_' . $details['border_orientation'] )
						: ''
				)
				: '',
			( 'rounded' == $details['corner_style'] ) ? ' et_bloom_rounded_corners' : '', //#5
			( 'rounded' == $details['field_corner'] ) ? ' et_bloom_rounded' : '',
			'light' == $details['text_color'] ? ' et_bloom_form_text_light' : ' et_bloom_form_text_dark',
			'bottom' !== $details['form_orientation'] && 'custom_html' !== $details['email_provider']
				? sprintf(
					' et_bloom_form_%1$s',
					esc_html( $details['form_orientation'] )
				)
				: ' et_bloom_form_bottom',
			( isset( $details['hide_mobile_optin'] ) && true == $details['hide_mobile_optin'] )
				? ' et_bloom_hide_mobile_optin'
				: '',
			$custom_css_output, //#10
			( 'no_name' == $details['name_fields'] && ! ET_Bloom::is_only_name_support( $details['email_provider'] ) ) || ( ET_Bloom::is_only_name_support( $details['email_provider'] ) && $is_single_name )
				? ' et_bloom_inline_1_field'
				: sprintf(
					' et_bloom_inline_%1$s_fields',
					'first_last_name' == $details['name_fields'] && ! ET_Bloom::is_only_name_support( $details['email_provider'] )
						? '3'
						: '2'
				),
			$success_action_enabled,
			$success_action_details //#13
		);

		return $output;
	}

	/**
	 * Displays the Inline shortcode on front-end.
	 */
	function display_inline_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'optin_id' => '',
		), $atts );
		$optin_id = $atts['optin_id'];

		$optins_set = ET_Bloom::get_bloom_options();
		$selected_optin = isset( $optins_set[$optin_id] ) ? $optins_set[$optin_id] : '';
		$output = '';

		if ( '' !== $selected_optin && 'active' == $selected_optin['optin_status'] && 'inline' == $selected_optin['optin_type'] && empty( $selected_optin['child_of'] ) ) {
			$output = $this->generate_inline_form( $optin_id, $selected_optin );
		}

		return $output;
	}

	/**
	 * Displays the "locked content" shortcode on front-end.
	 */
	function display_locked_shortcode( $atts, $content=null ) {
		$atts = shortcode_atts( array(
			'optin_id' => '',
		), $atts );
		$optin_id = $atts['optin_id'];
		$optins_set = ET_Bloom::get_bloom_options();
		$display_optin_id = ET_Bloom::choose_form_ab_test( $optin_id, $optins_set );
		$selected_optin = isset( $optins_set[$optin_id] ) ? $optins_set[$optin_id] : '';
		if ( '' == $selected_optin ) {
			$output = $content;
		} else {
			$form = '';
			$page_id = get_the_ID();
			$list_id = 'custom_html' == $selected_optin['email_provider'] ? 'custom_html' : $selected_optin['email_provider'] . '_' . $selected_optin['email_list'];

			if ( '' !== $selected_optin && 'active' == $selected_optin['optin_status'] && 'locked' == $selected_optin['optin_type'] && empty( $selected_optin['child_of'] ) ) {
				$form = $this->generate_inline_form( $optin_id, $selected_optin, false );
			}

			$output = sprintf(
				'<div class="et_bloom_locked_container et_bloom_%4$s" data-page_id="%3$s" data-optin_id="%4$s" data-list_id="%5$s" data-current_optin_id="%6$s">
					<div class="et_bloom_locked_content" style="display: none;">
						%1$s
					</div>
					<div class="et_bloom_locked_form">
						%2$s
					</div>
				</div>',
				$content,
				$form,
				esc_attr( $page_id ),
				esc_attr( $optin_id ),
				esc_attr( $list_id ),
				esc_attr( $display_optin_id )
			);
		}

		return $output;
	}

	function register_widget() {
		require_once( ET_BLOOM_PLUGIN_DIR . 'includes/bloom-widget.php' );
		register_widget( 'BloomWidget' );
	}

	/**
	 * Displays the Widget content on front-end.
	 */
	public static function display_widget( $optin_id ) {
		$optins_set     = ET_Bloom::get_bloom_options();
		$selected_optin = isset( $optins_set[ $optin_id ] ) ? $optins_set[ $optin_id ] : '';
		$output         = '';

		if ( '' !== $selected_optin && 'active' == $optins_set[ $optin_id ]['optin_status'] && empty( $optins_set[ $optin_id ]['child_of'] ) ) {

			ET_Bloom::load_scripts_styles();

			$display_optin_id = ET_Bloom::choose_form_ab_test( $optin_id, $optins_set );

			if ( $display_optin_id != $optin_id ) {
				$optin_id       = $display_optin_id;
				$selected_optin = $optins_set[ $optin_id ];
			}

			if ( is_singular() || ET_Bloom::is_homepage() ) {
				$page_id = ET_Bloom::is_homepage() ? -1 : get_the_ID();
			} else {
				$page_id = 0;
			}

			$list_id      = $selected_optin['email_provider'] . '_' . $selected_optin['email_list'];
			$form_content = ET_Bloom::generate_form_content( $optin_id, $page_id );

			$custom_css        = ET_Bloom::generate_custom_css( '.et_bloom .et_bloom_' . $display_optin_id, $selected_optin );
			$custom_css_output = '' !== $custom_css ? sprintf( '<style type="text/css">%1$s</style>', $custom_css ) : '';

			$use_default_edge            = 'basic_edge' === $selected_optin['edge_style'] || '' === $selected_optin['edge_style'];
			$edge_style                  = $use_default_edge ? '' : sprintf( ' with_edge %1$s', esc_attr( $selected_optin['edge_style'] ) );
			$border_style                = '';
			$use_default_border_position = 'full' === $selected_optin['border_orientation'];

			if ( 'no_border' !== $selected_optin['border_orientation'] ) {
				$border_position = $use_default_border_position ? '' : " et_bloom_border_position_{$selected_optin['border_orientation']}";
				$border_style    = esc_attr( " et_bloom_border_{$selected_optin['border_style']}{$border_position}" );
			}

			$corner_style       = 'rounded' == $selected_optin['corner_style'] ? ' et_bloom_rounded_corners' : '';
			$field_corner_style = 'rounded' == $selected_optin['field_corner'] ? ' et_bloom_rounded' : '';
			$text_color_style   = 'light' == $selected_optin['text_color'] ? ' et_bloom_form_text_light' : ' et_bloom_form_text_dark';

			$success_action_class = $success_action_data = '';

			if ( isset( $selected_optin['success_action_type'] ) && 'default' !== $selected_optin['success_action_type'] ) {
				$success_action_class = ' et_bloom_success_action';
				$action_type = esc_attr( $selected_optin['success_action_type'] );
				$action_info = esc_url( $selected_optin['success_action_info'] );
				$success_action_data  = " data-success_action_details='{$action_type}|{$action_info}'";
			}

			$optin_id       = esc_attr( $optin_id );
			$widget_classes = 'et_bloom_widget_content et_bloom_make_form_visible et_bloom_optin';
			$form_classes   = $edge_style . $border_style . $corner_style . $field_corner_style . $text_color_style . $success_action_class;

			$output = "
				<div class='{$widget_classes} et_bloom_{$optin_id}' style='display: none;'{$success_action_data}>
					{$custom_css_output}
					<div class='et_bloom_form_container{$form_classes}'>
						{$form_content}
					</div>
				</div>";
		}

		return $output;
	}

	/**
	 * Returns list of widget optins to generate select option in widget settings
	 * @return array
	 */
	public static function widget_optins_list() {
		$optins_set = ET_Bloom::get_bloom_options();
		$output = array(
			'empty' => esc_html__( 'Select optin', 'bloom' ),
		);

		if ( ! empty( $optins_set ) ) {
			foreach( $optins_set as $optin_id => $details ) {
				if ( isset( $details['optin_status'] ) && 'active' === $details['optin_status'] && empty( $details['child_of'] ) ) {
					if ( 'widget' == $details['optin_type'] ) {
						$output = array_merge( $output, array( $optin_id => $details['optin_name'] ) );
					}
				}
			}
		} else {
			$output = array(
				'empty' => esc_html__( 'No Widget optins created yet', 'bloom' ),
			);
		}

		return $output;
	}

	function set_custom_css() {
		$options_array = ET_Bloom::get_bloom_options();
		$custom_css = '';
		$font_functions = ET_Bloom::load_fonts_class();
		$fonts_array = array();

		foreach( $options_array as $id => $single_optin ) {
			if ( 'accounts' != $id && 'db_version' != $id && isset( $single_optin['optin_type'] ) ) {
				if ( 'inactive' !== $single_optin['optin_status'] ) {
					$current_optin_id = ET_Bloom::choose_form_ab_test( $id, $options_array, false );
					$single_optin = $options_array[$current_optin_id];

					if ( ( ( 'flyin' == $single_optin['optin_type'] || 'pop_up' == $single_optin['optin_type'] || 'below_post' == $single_optin['optin_type'] ) && $this->check_applicability ( $id ) ) && ( isset( $single_optin['custom_css'] ) || isset( $single_optin['form_bg_color'] ) || isset( $single_optin['header_bg_color'] ) || isset( $single_optin['form_button_color'] ) || isset( $single_optin['border_color'] ) ) ) {
						$form_class = '.et_bloom .et_bloom_' . $current_optin_id;

						$custom_css .= ET_Bloom::generate_custom_css( $form_class, $single_optin );
					}

					if ( ! isset( $fonts_array[$single_optin['header_font']] ) && isset( $single_optin['header_font'] ) ) {
						$fonts_array[] = $single_optin['header_font'];
					}

					if ( ! isset( $fonts_array[$single_optin['body_font']] ) && isset( $single_optin['body_font'] ) ) {
						$fonts_array[] = $single_optin['body_font'];
					}
				}
			}
		}

		if ( ! empty( $fonts_array ) ) {
			$font_functions->et_gf_enqueue_fonts( $fonts_array );
		}

		if ( '' != $custom_css ) {
			printf(
				'<style type="text/css" id="et-bloom-custom-css">
					%1$s
				</style>',
				stripslashes( $custom_css )
			);
		}
	}

	/**
	 * Generated the output for custom css with specified class based on input option
	 * @return string
	 */
	public static function generate_custom_css( $form_class, $single_optin = array() ) {
		$font_functions = ET_Bloom::load_fonts_class();
		$custom_css = '';

		if ( isset( $single_optin['form_bg_color'] ) && '' !== $single_optin['form_bg_color'] ) {
			$custom_css .= esc_html( $form_class ) . ' .et_bloom_form_content { background-color: ' . esc_html( $single_optin['form_bg_color'] ) . ' !important; } ';

			if ( 'zigzag_edge' === $single_optin['edge_style'] ) {
				$custom_css .=
					esc_html( $form_class ) . ' .zigzag_edge .et_bloom_form_content:before { background: linear-gradient(45deg, transparent 33.33%, ' . esc_html( $single_optin['form_bg_color'] ) . ' 33.333%, ' . esc_html( $single_optin['form_bg_color'] ) . ' 66.66%, transparent 66.66%), linear-gradient(-45deg, transparent 33.33%, ' . esc_html( $single_optin['form_bg_color'] ) . ' 33.33%, ' . esc_html( $single_optin['form_bg_color'] ) . ' 66.66%, transparent 66.66%) !important; background-size: 20px 40px !important; } ' .
					esc_html( $form_class ) . ' .zigzag_edge.et_bloom_form_right .et_bloom_form_content:before, ' . esc_html( $form_class ) . ' .zigzag_edge.et_bloom_form_left .et_bloom_form_content:before { background-size: 40px 20px !important; }
					@media only screen and ( max-width: 767px ) {' .
						esc_html( $form_class ) . ' .zigzag_edge.et_bloom_form_right .et_bloom_form_content:before, ' . esc_html( $form_class ) . ' .zigzag_edge.et_bloom_form_left .et_bloom_form_content:before { background: linear-gradient(45deg, transparent 33.33%, ' . esc_html( $single_optin['form_bg_color'] ) . ' 33.333%, ' . esc_html( $single_optin['form_bg_color'] ) . ' 66.66%, transparent 66.66%), linear-gradient(-45deg, transparent 33.33%, ' . esc_html( $single_optin['form_bg_color'] ) . ' 33.33%, ' . esc_html( $single_optin['form_bg_color'] ) . ' 66.66%, transparent 66.66%) !important; background-size: 20px 40px !important; } ' .
					'}';
			}
		}

		if ( isset( $single_optin['header_bg_color'] ) && '' !== $single_optin['header_bg_color'] ) {
			$custom_css .= esc_html( $form_class ) .  ' .et_bloom_form_container .et_bloom_form_header { background-color: ' . esc_html( $single_optin['header_bg_color'] ) . ' !important; } ';

			switch ( $single_optin['edge_style'] ) {
				case 'curve_edge' :
					$custom_css .= esc_html( $form_class ) . ' .curve_edge .curve { fill: ' . esc_html( $single_optin['header_bg_color'] ) . '} ';
					break;

				case 'wedge_edge' :
					$custom_css .= esc_html( $form_class ) . ' .wedge_edge .triangle { fill: ' . esc_html( $single_optin['header_bg_color'] ) . '} ';
					break;

				case 'carrot_edge' :
					$custom_css .=
						esc_html( $form_class ) . ' .carrot_edge .et_bloom_form_content:before { border-top-color: ' . esc_html( $single_optin['header_bg_color'] ) . ' !important; } ' .
						esc_html( $form_class ) . ' .carrot_edge.et_bloom_form_right .et_bloom_form_content:before, ' . esc_html( $form_class ) . ' .carrot_edge.et_bloom_form_left .et_bloom_form_content:before { border-top-color: transparent !important; border-left-color: ' . esc_html( $single_optin['header_bg_color'] ) . ' !important; }
						@media only screen and ( max-width: 767px ) {' .
							esc_html( $form_class ) . ' .carrot_edge.et_bloom_form_right .et_bloom_form_content:before, ' . esc_html( $form_class ) . ' .carrot_edge.et_bloom_form_left .et_bloom_form_content:before { border-top-color: ' . esc_html( $single_optin['header_bg_color'] ) . ' !important; border-left-color: transparent !important; }
						}';
					break;
			}

			if ( 'dashed' === $single_optin['border_style'] ) {
				if ( 'breakout_edge' !== $single_optin['edge_style'] ) {
					$custom_css .= esc_html( $form_class ) . ' .et_bloom_form_container { background-color: ' . esc_html( $single_optin['header_bg_color'] ) . ' !important; } ';
				} else {
					$custom_css .= esc_html( $form_class ) . ' .et_bloom_header_outer { background-color: ' . esc_html( $single_optin['header_bg_color'] ) . ' !important; } ';
				}
			}
		}

		if ( isset( $single_optin['form_button_color'] ) && '' !== $single_optin['form_button_color'] ) {
			$custom_css .= esc_html( $form_class ) .  ' .et_bloom_form_content button { background-color: ' . esc_html( $single_optin['form_button_color'] ) . ' !important; } ';
			$custom_css .= esc_html( $form_class ) . ' .et_bloom_form_content .et_bloom_fields i { color: ' . esc_html( $single_optin['form_button_color'] ) . ' !important; } ';
			$custom_css .= esc_html( $form_class ) . ' .et_bloom_form_content .et_bloom_custom_field_radio i:before { background: ' . esc_html( $single_optin['form_button_color'] ) . ' !important; } ';
		}

		if ( isset( $single_optin['border_color'] ) && '' !== $single_optin['border_color'] && 'no_border' !== $single_optin['border_orientation'] ) {
			if ( 'breakout_edge' === $single_optin['edge_style'] ) {
				switch ( $single_optin['border_style'] ) {
					case 'letter' :
						$custom_css .= esc_html( $form_class ) .  ' .breakout_edge.et_bloom_border_letter .et_bloom_header_outer { background: repeating-linear-gradient( 135deg, ' . esc_html( $single_optin['border_color'] ) . ', ' . esc_html( $single_optin['border_color'] ) . ' 10px, #fff 10px, #fff 20px, #f84d3b 20px, #f84d3b 30px, #fff 30px, #fff 40px ) !important; } ';
						break;

					case 'double' :
						$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_double .et_bloom_form_header { -moz-box-shadow: inset 0 0 0 6px ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 0 0 8px ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 0 0 6px ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 0 0 8px ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 0 0 6px ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 0 0 8px ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';

						switch ( $single_optin['border_orientation'] ) {
							case 'top' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_double.et_bloom_border_position_top .et_bloom_form_header { -moz-box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
								break;

							case 'right' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_double.et_bloom_border_position_right .et_bloom_form_header { -moz-box-shadow: inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
								break;

							case 'bottom' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_double.et_bloom_border_position_bottom .et_bloom_form_header { -moz-box-shadow: inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
								break;

							case 'left' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_double.et_bloom_border_position_left .et_bloom_form_header { -moz-box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
								break;

							case 'top_bottom' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_double.et_bloom_border_position_top_bottom .et_bloom_form_header { -moz-box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
								break;

							case 'left_right' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_double.et_bloom_border_position_left_right .et_bloom_form_header { -moz-box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
						}
						break;

					case 'inset' :
						$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_inset .et_bloom_form_header { -moz-box-shadow: inset 0 0 0 3px ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 0 0 3px ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 0 0 3px ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';

						switch ( $single_optin['border_orientation'] ) {
							case 'top' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_inset.et_bloom_border_position_top .et_bloom_form_header { -moz-box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
								break;

							case 'right' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_inset.et_bloom_border_position_right .et_bloom_form_header { -moz-box-shadow: inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
								break;

							case 'bottom' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_inset.et_bloom_border_position_bottom .et_bloom_form_header { -moz-box-shadow: inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
								break;

							case 'left' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_inset.et_bloom_border_position_left .et_bloom_form_header { -moz-box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
								break;

							case 'top_bottom' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_inset.et_bloom_border_position_top_bottom .et_bloom_form_header { -moz-box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
								break;

							case 'left_right' :
								$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_inset.et_bloom_border_position_left_right .et_bloom_form_header { -moz-box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
						}
						break;

					case 'solid' :
						$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_solid .et_bloom_form_header { border-color: ' . esc_html( $single_optin['border_color'] ) . ' !important } ';
						break;

					case 'dashed' :
						$custom_css .= esc_html( $form_class ) . ' .breakout_edge.et_bloom_border_dashed .et_bloom_form_header { border-color: ' . esc_html( $single_optin['border_color'] ) . ' !important } ';
						break;
				}
			} else {
				switch ( $single_optin['border_style'] ) {
					case 'letter' :
						$custom_css .= esc_html( $form_class ) .  '.et_bloom_optin .et_bloom_border_letter { background: repeating-linear-gradient( 135deg, ' . esc_html( $single_optin['border_color'] ) . ', ' . esc_html( $single_optin['border_color'] ) . ' 10px, #fff 10px, #fff 20px, #f84d3b 20px, #f84d3b 30px, #fff 30px, #fff 40px ) !important; } ';
						break;

					case 'double' :
						$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_double { -moz-box-shadow: inset 0 0 0 6px ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 0 0 8px ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 0 0 6px ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 0 0 8px ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 0 0 6px ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 0 0 8px ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';

						switch ( $single_optin['border_orientation'] ) {
							case 'top' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_double.et_bloom_border_position_top { -moz-box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
								break;

							case 'right' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_double.et_bloom_border_position_right { -moz-box-shadow: inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
								break;

							case 'bottom' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_double.et_bloom_border_position_bottom { -moz-box-shadow: inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
								break;

							case 'left' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_double.et_bloom_border_position_left { -moz-box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
								break;

							case 'top_bottom' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_double.et_bloom_border_position_top_bottom { -moz-box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 8px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -6px 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 0 -8px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
								break;

							case 'left_right' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_double.et_bloom_border_position_left_right { -moz-box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset 8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -6px 0 0 0 ' . esc_html( $single_optin['header_bg_color'] ) . ', inset -8px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['border_color'] ) . '; } ';
						}
						break;

					case 'inset' :
						$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_inset { -moz-box-shadow: inset 0 0 0 3px ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 0 0 3px ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 0 0 3px ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';

						switch ( $single_optin['border_orientation'] ) {
							case 'top' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_inset.et_bloom_border_position_top { -moz-box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
								break;

							case 'right' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_inset.et_bloom_border_position_right { -moz-box-shadow: inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
								break;

							case 'bottom' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_inset.et_bloom_border_position_bottom { -moz-box-shadow: inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
								break;

							case 'left' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_inset.et_bloom_border_position_left { -moz-box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
								break;

							case 'top_bottom' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_inset.et_bloom_border_position_top_bottom { -moz-box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 0 3px 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset 0 -3px 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
								break;

							case 'left_right' :
								$custom_css .= esc_html( $form_class ) . '.et_bloom_optin .et_bloom_border_inset.et_bloom_border_position_left_right { -moz-box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; -webkit-box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; box-shadow: inset 3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . ', inset -3px 0 0 0 ' . esc_html( $single_optin['border_color'] ) . '; border-color: ' . esc_html( $single_optin['header_bg_color'] ) . '; } ';
						}
						break;

					case 'solid' :
						$custom_css .= esc_html( $form_class ) . ' .et_bloom_border_solid { border-color: ' . esc_html( $single_optin['border_color'] ) . ' !important } ';
						break;

					case 'dashed' :
						$custom_css .= esc_html( $form_class ) . ' .et_bloom_border_dashed .et_bloom_form_container_wrapper { border-color: ' . esc_html( $single_optin['border_color'] ) . ' !important } ';
						break;
				}
			}
		}
		$fonts_form_class = $form_class . ' .et_bloom_form_container';
		$custom_css .= isset( $single_optin['form_button_color'] ) && '' !== $single_optin['form_button_color'] ? esc_html( $form_class ) .  ' .et_bloom_form_content button { background-color: ' . esc_html( $single_optin['form_button_color'] ) . ' !important; } ' : '';
		$custom_css .= isset( $single_optin['header_font'] ) ? $font_functions->et_gf_attach_font( $single_optin['header_font'], $fonts_form_class . ' h2, ' . $fonts_form_class . ' h2 span, ' . $fonts_form_class . ' h2 strong' ) : '';
		$custom_css .= isset( $single_optin['body_font'] ) ? $font_functions->et_gf_attach_font( $single_optin['body_font'], $fonts_form_class . ' p, ' . $fonts_form_class . ' p span, ' . $fonts_form_class . ' p strong, ' . $fonts_form_class . ' form input, ' . $fonts_form_class . ' form button span' ) : '';

		$custom_css .= isset( $single_optin['custom_css'] ) ? ' ' . $single_optin['custom_css'] : '';

		return $custom_css;
	}

	/**
	 * Modifies the URL of post after commenting to trigger the popup after comment
	 * @return string
	 */
	function after_comment_trigger( $location ){
		$newurl = $location;
		$newurl = substr( $location, 0, strpos( $location, '#comment' ) );
		$delimeter = false === strpos( $location, '?' ) ? '?' : '&';
		$params = 'et_bloom_popup=true';

		$newurl .= $delimeter . $params;

		return $newurl;
	}

	/**
	 * Generated content for purchase trigger
	 * @return string
	 */
	function add_purchase_trigger() {
		echo '<div class="et_bloom_after_order"></div>';
	}

	/**
	 * Check the homepage
	 * @return bool
	 */
	public static function is_homepage() {
		return is_front_page() || is_home();
	}

	/**
	 * Check the Blog Page
	 * @return bool
	 */
	public static function is_blogpage() {
		if ( is_front_page() && is_home() ) {
			// Default homepage
			return false;
		} elseif ( is_front_page() ) {
			// static homepage
			return false;
		} elseif ( is_home() ) {
			// blog page
			return true;
		}

		//everything else
		return false;
	}


	/**
	 * Adds appropriate actions for Flyin, Popup, Below Content to wp_footer,
	 * Adds custom_css function to wp_head
	 * Adds trigger_bottom_mark to the_content filter for Flyin and Popup
	 * Creates arrays with optins for for Flyin, Popup, Below Content to improve the performance during forms displaying
	 */
	function frontend_register_locations() {
		$options_array = ET_Bloom::get_bloom_options();

		if ( ! is_admin() && ! empty( $options_array ) ) {
			add_action( 'wp_head', array( $this, 'set_custom_css' ) );

			$flyin_count = 0;
			$popup_count = 0;
			$below_count = 0;
			$after_comment = 0;
			$after_purchase = 0;

			foreach ( $options_array as $optin_id => $details ) {
				if ( 'accounts' !== $optin_id ) {
					if ( isset( $details['optin_status'] ) && 'active' === $details['optin_status'] && empty( $details['child_of'] ) ) {
						switch( $details['optin_type'] ) {
							case 'flyin' :
								if ( 0 === $flyin_count ) {
									add_action( 'wp_footer', array( $this, "display_flyin" ) );
									$flyin_count++;
								}

								if ( 0 === $after_comment && isset( $details['comment_trigger'] ) && true == $details['comment_trigger'] ) {
									add_filter( 'comment_post_redirect', array( $this, 'after_comment_trigger' ) );
									$after_comment++;
								}

								if ( 0 === $after_purchase && isset( $details['purchase_trigger'] ) && true == $details['purchase_trigger'] ) {
									add_action( 'woocommerce_thankyou', array( $this, 'add_purchase_trigger' ) );
									$after_purchase++;
								}

								$this->flyin_optins[$optin_id] = $details;
								break;

							case 'pop_up' :
								if ( 0 === $popup_count ) {
									add_action( 'wp_footer', array( $this, "display_popup" ) );
									$popup_count++;
								}

								if ( 0 === $after_comment && isset( $details['comment_trigger'] ) && true == $details['comment_trigger'] ) {
									add_filter( 'comment_post_redirect', array( $this, 'after_comment_trigger' ) );
									$after_comment++;
								}

								if ( 0 === $after_purchase && isset( $details['purchase_trigger'] ) && true == $details['purchase_trigger'] ) {
									add_action( 'woocommerce_thankyou', array( $this, 'add_purchase_trigger' ) );
									$after_purchase++;
								}

								$this->popup_optins[$optin_id] = $details;
								break;

							case 'below_post' :
								if ( 0 === $below_count ) {
									add_filter( 'the_content', array( $this, 'display_below_post' ), 9999 );
									add_action( 'woocommerce_after_single_product_summary', array( $this, 'display_on_wc_page' ) );
									$below_count++;
								}

								$this->below_post_optins[$optin_id] = $details;
								break;
						}
					}
				}
			}

			if ( 0 < $flyin_count || 0 < $popup_count ) {
				add_filter( 'the_content', array( $this, 'trigger_bottom_mark' ), 9999 );
			}
		}
	}
}


function et_bloom_init_plugin() {
	$et_bloom = new ET_Bloom();
	$GLOBALS['et_bloom'] = $et_bloom;
}
add_action( 'plugins_loaded', 'et_bloom_init_plugin' );

register_activation_hook( __FILE__, array( 'ET_Bloom', 'activate_plugin' ) );

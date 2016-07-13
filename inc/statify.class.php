<?php


/* Quit */
defined('ABSPATH') OR exit;


/**
* Statify
*
* @since 0.1.0
*/

class Statify
{


	/**
	* Plugin options
	*
	* @since   1.4.0
	*/

	public static $_options;


	/**
	* Class self initialize
	*
	* @since   0.1.0
	* @change  0.1.0
	*/

	public static function instance()
	{
		new self();
	}


	/**
	* Class constructor
	*
	* @since   0.1.0
	* @change  1.4.0
	*/

	public function __construct()
	{
		/* Skip me! */
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) OR (defined('DOING_AJAX') && DOING_AJAX) ) {
			return;
		}

		/* Table init */
		Statify_Table::init();

		/* Plugin options */
		self::$_options = wp_parse_args(
			get_option('statify'),
			array(
		        'days'      => 14,
		        'limit'     => 3,
		        'today'     => 0,
		        'snippet'   => 0,
		        'blacklist' => ''
		    )
		);

		/* XMLRPC */
		if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
			add_filter(
				'xmlrpc_methods',
				array(
					'Statify_XMLRPC',
					'xmlrpc_methods'
				)
			);

		/* Cron */
		} else if ( defined('DOING_CRON') && DOING_CRON ) {
			add_action(
				'statify_cleanup',
				array(
					'Statify_Cron',
					'cleanup_data'
				)
			);

		/* Backend */
		} else if ( is_admin() ) {
			add_action(
				'wpmu_new_blog',
				array(
					'Statify_Install',
					'init_site'
				)
			);
			add_action(
				'delete_blog',
				array(
					'Statify_Uninstall',
					'init_site'
				)
			);
			add_action(
				'wp_dashboard_setup',
				array(
					'Statify_Dashboard',
					'init'
				)
			);
			add_filter(
				'plugin_row_meta',
				array(
					'Statify_Backend',
					'add_meta_link'
				),
				10,
				2
			);
			add_filter(
				'plugin_action_links_' .STATIFY_BASE,
				array(
					'Statify_Backend',
					'add_action_link'
				)
			);

		/* Frontend */
		} else {
			add_action(
				'template_redirect',
				array(
					'Statify_Frontend',
					'track_visit'
				)
			);
			add_filter(
				'query_vars',
				array(
					'Statify_Frontend',
					'query_vars'
				)
			);
			add_action(
				'wp_footer',
				array(
					'Statify_Frontend',
					'wp_footer'
				)
			);
		}
	}
}
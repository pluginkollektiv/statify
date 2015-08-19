<?php
/*
Plugin Name: Statify
Description: Compact, easy-to-use and privacy-compliant stats plugin for WordPress.
Text Domain: statify
Domain Path: /lang
Author:      pluginkollektiv
Author URI:  https://pluginkollektiv.org
Plugin URI:  https://wordpress.org/plugins/statify
License:     GPLv3 or later
Version:     1.4.3
*/

/*
Copyright (C)  2011-2015 Sergej Müller

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/


/* Quit */
defined('ABSPATH') OR exit;


/*  Constants */
define('STATIFY_FILE', __FILE__);
define('STATIFY_DIR', dirname(__FILE__));
define('STATIFY_BASE', plugin_basename(__FILE__));


/* Hooks */
add_action(
	'plugins_loaded',
	array(
		'Statify',
		'instance'
	)
);
register_activation_hook(
	STATIFY_FILE,
	array(
		'Statify_Install',
		'init'
	)
);
register_deactivation_hook(
    STATIFY_FILE,
    array(
        'Statify_Deactivate',
        'init'
    )
);
register_uninstall_hook(
	STATIFY_FILE,
	array(
		'Statify_Uninstall',
		'init'
	)
);


/* Autoload */
spl_autoload_register('statify_autoload');

function statify_autoload($class) {
    $plugin_classes = array(
        'Statify',
        'Statify_Backend',
        'Statify_Frontend',
        'Statify_Dashboard',
        'Statify_Install',
        'Statify_Uninstall',
        'Statify_Deactivate',
        'Statify_Table',
        'Statify_XMLRPC',
        'Statify_Cron'
    );

    if ( in_array($class, $plugin_classes) ) {
        require_once(
            sprintf(
                '%s/inc/%s.class.php',
                STATIFY_DIR,
                strtolower($class)
            )
        );
    }
}

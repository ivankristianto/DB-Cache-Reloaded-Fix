<?php
/*
Plugin URI: http://www.ivankristianto.com/web-development/programming/db-cache-reloaded-fix-for-wordpress-3-1/1784/
Description: Database Caching Module provided by DB Cache Reloaded plugin With WordPress 3.1 compatibility.
Author: Ivan Kristianto
Version: 1.7 (bundled with DB Cache Reloaded 2.3)
Author URI: http://www.ivankristianto.com
Text Domain: db-cache-reloaded-fix
*/


/**
 * WordPress DB Class
 *
 * Original code from {@link http://www.poradnik-webmastera.com/ Daniel Fruzynski (daniel@poradnik-webmastera.com)}
 * Original code from {@link http://php.justinvincent.com Justin Vincent (justin@visunet.ie)}
 *
 * @package WordPress
 * @subpackage Database
 * @since 0.71
 */

/*  Modifications Copyright 2011  Ivan Kristianto  (email : ivan@ivankristianto.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * @since 0.71
 */
define( 'EZSQL_VERSION', 'WP1.25' );

/**
 * @since 0.71
 */
define( 'OBJECT', 'OBJECT', true );

/**
 * @since 2.5.0
 */
define( 'OBJECT_K', 'OBJECT_K' );

/**
 * @since 0.71
 */
define( 'ARRAY_A', 'ARRAY_A' );

/**
 * @since 0.71
 */
define( 'ARRAY_N', 'ARRAY_N' );


// --- DB Cache Start ---
// wp-settings.php defines this after loading this file, so have to add it here too
if ( !defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins' ); // full path, no trailing slash
}
// Path to plugin
if ( !defined( 'DBCR_PATH' ) ) {
	define( 'DBCR_PATH', WP_PLUGIN_DIR.'/db-cache-reloaded-fix' );
}
// Cache directory
if ( !defined( 'DBCR_CACHE_DIR' ) ) {
	define( 'DBCR_CACHE_DIR', DBCR_PATH.'/cache' );
}

// DB Module version (one or more digits for major, two digits for minor and revision numbers)
if ( !defined( 'DBCR_DB_MODULE_VER' ) ) {
	define( 'DBCR_DB_MODULE_VER', 10600 );
}

// Check if we have required functions
if ( !function_exists( 'is_multisite' ) ) { // Added in WP 3.0
	function is_multisite() {
		return false;
	}
}

// --- DB Cache End ---
    
require_once DBCR_PATH . '/db-module.php';

$GLOBALS['wpdb'] = new dbrc_wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );



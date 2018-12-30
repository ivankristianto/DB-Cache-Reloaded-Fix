<?php
/**
 * Plugin Name: DB Cache Reloaded Fix
 * Plugin URI: https://wordpress.org/plugins/db-cache-reloaded-fix/
 * Description: Database Caching Module provided by DB Cache Reloaded plugin.
 * Author: Ivan Kristianto
 * Version: 2.3
 * Author URI: https://www.ivankristianto.com
 * Text Domain: db-cache-reloaded-fix
 * License: GPL v2 or later
 * Requires at least: 4.3
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package dbcr
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
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' ); // full path, no trailing slash.
}
// Path to plugin.
if ( ! defined( 'DBCR_PATH' ) ) {
	define( 'DBCR_PATH', WP_PLUGIN_DIR . '/db-cache-reloaded-fix' );
}
// Cache directory.
if ( ! defined( 'DBCR_CACHE_DIR' ) ) {
	define( 'DBCR_CACHE_DIR', DBCR_PATH . '/cache' );
}

// DB Module version (one or more digits for major, two digits for minor and revision numbers).
if ( ! defined( 'DBCR_DB_MODULE_VER' ) ) {
	define( 'DBCR_DB_MODULE_VER', 10600 );
}

// --- DB Cache End ---
require_once DBCR_PATH . '/db-module.php';

$GLOBALS['wpdb'] = new dbrc_wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );



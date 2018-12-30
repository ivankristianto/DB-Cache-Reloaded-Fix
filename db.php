<?php

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

//$GLOBALS['wpdb'] = new dbrc_wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );

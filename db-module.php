<?php
/*
Plugin URI: http://www.ivankristianto.com/web-development/programming/db-cache-reloaded-fix-for-wordpress-3-1/1784/
Description: Database Caching Module provided by DB Cache Reloaded plugin With WordPress 3.1 compatibility.
Author: Ivan Kristianto
Version: 1.7 (bundled with DB Cache Reloaded Fix 2.3)
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
	define( 'DBCR_PATH', dirname( __FILE__ ) );
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


/**
 * WordPress Database Access Abstraction Object
 *
 * It is possible to replace this class with your own
 * by setting the $wpdb global variable in wp-content/db.php
 * file with your class. You can name it wpdb also, since
 * this file will not be included, if the other file is
 * available.
 *
 * @link http://codex.wordpress.org/Function_Reference/wpdb_Class
 *
 * @package WordPress
 * @subpackage Database
 * @since 0.71
 * @final
 */
if ( !class_exists( 'dbrc_wpdb' ) ) {
class dbrc_wpdb extends wpdb{

	// --- DB Cache Start ---
	/**
	 * Amount of all queries cached by DB Cache Reloaded made
	 *
	 * @var int
	 */
	var $num_cachequeries = 0;
	/**
	 * Amount of DML queries
	 *
	 * @var int
	 */
	var $dbcr_num_dml_queries = 0;
	/**
	 * True if caching is active, otherwise false
	 *
	 * @var bool
	 */
	var $dbcr_cacheable = true;
	/**
	 * Array with DB Cache Reloaded config
	 *
	 * @var array
	 */
	var $dbcr_config = null;
	/**
	 * DB Cache Reloaded helper
	 *
	 * @var object of pcache
	 */
	var $dbcr_cache = null;
	/**
	 * True if DB Cache Reloaded should show error in admin section
	 *
	 * @var bool
	 */
	var $dbcr_show_error = false;
	/**
	 * DB Cache Reloaded DB module version
	 *
	 * @var int
	 */
	var $dbcr_version = DBCR_DB_MODULE_VER;
	// --- DB Cache End ---
	
	/**
	 * Connects to the database server and selects a database
	 *
	 * PHP4 compatibility layer for calling the PHP5 constructor.
	 *
	 * @uses wpdb::__construct() Passes parameters and returns result
	 * @since 0.71
	 *
	 * @param string $dbuser MySQL database user
	 * @param string $dbpassword MySQL database password
	 * @param string $dbname MySQL database name
	 * @param string $dbhost MySQL database host
	 */
	function wpdb( $dbuser, $dbpassword, $dbname, $dbhost ) {
		if( defined( 'WP_USE_MULTIPLE_DB' ) && WP_USE_MULTIPLE_DB )
			$this->db_connect();
		return $this->__construct( $dbuser, $dbpassword, $dbname, $dbhost );
	}

	/**
	 * Connects to the database server and selects a database
	 *
	 * PHP5 style constructor for compatibility with PHP5. Does
	 * the actual setting up of the class properties and connection
	 * to the database.
	 *
	 * @link http://core.trac.wordpress.org/ticket/3354
	 * @since 2.0.8
	 *
	 * @param string $dbuser MySQL database user
	 * @param string $dbpassword MySQL database password
	 * @param string $dbname MySQL database name
	 * @param string $dbhost MySQL database host
	 */
	function __construct( $dbuser, $dbpassword, $dbname, $dbhost ) {
		register_shutdown_function( array( &$this, '__destruct' ) );

		if ( WP_DEBUG )
			$this->show_errors();

		$this->init_charset();

		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname = $dbname;
		$this->dbhost = $dbhost;

		$this->db_connect();
		
		// --- DB Cache Start ---
		// Caching
		// require_once would be better, but some people deletes plugin without deactivating it first
		if ( @include_once( DBCR_PATH.'/db-functions.php' ) ) {
			$this->dbcr_config = unserialize( @file_get_contents( WP_CONTENT_DIR.'/db-config.ini' ) );
			$this->dbcr_config['debug'] = false; //TODO: put this into option page
			$this->dbcr_cache =& new pcache();
			$this->dbcr_cache->lifetime = isset( $this->dbcr_config['timeout'] ) ? $this->dbcr_config['timeout'] : 1800;
			$this->dbcr_cache->lifetime *= 60; //convert to seconds
			
			// Clean unused
			// Move to cron for better performance
			/*$dbcheck = date('G')/4;
			if ( $dbcheck == intval( $dbcheck ) && ( !isset( $this->dbcr_config['lastclean'] ) 
				|| $this->dbcr_config['lastclean'] < time() - 3600 ) ) {
				$this->dbcr_cache->clean();
				$this->dbcr_config['lastclean'] = time();
				$file = fopen(WP_CONTENT_DIR.'/db-config.ini', 'w+');
				if ($file) {
					fwrite($file, serialize($this->dbcr_config));
					fclose($file);
				}
			}*/
			
			// cache only frontside
			if (
				( defined( 'WP_ADMIN' ) && WP_ADMIN ) ||
			 	( defined( 'DOING_CRON' ) && DOING_CRON ) || 
			 	( defined( 'DOING_AJAX' ) && DOING_AJAX ) || 
				strpos( $_SERVER['REQUEST_URI'], 'wp-admin' ) || 
				strpos( $_SERVER['REQUEST_URI'], 'wp-login' ) || 
				strpos( $_SERVER['REQUEST_URI'], 'wp-register' ) || 
				strpos( $_SERVER['REQUEST_URI'], 'wp-signup' )
			) {
				$this->dbcr_cacheable = false;
			}
		} else { // Cannot include db-functions.php
			$this->dbcr_cacheable = false;
			$this->dbcr_show_error = true;
		}
		// --- DB Cache End ---
	}

	/**
	 * PHP5 style destructor and will run when database object is destroyed.
	 *
	 * @see wpdb::__construct()
	 * @since 2.0.8
	 * @return bool true
	 */
	function __destruct() {
		return true;
	}

	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * More information can be found on the codex page.
	 *
	 * @since 0.71
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	function query( $query ) {
		return $this->dbcr_query( $query, true );
	}
	
	function dbcr_query( $query, $maybe_cache = true ) {
		if ( ! $this->ready )
			return false;

		// some queries are made before the plugins have been loaded, and thus cannot be filtered with this method
		if ( function_exists( 'apply_filters' ) )
			$query = apply_filters( 'query', $query );

		$return_val = 0;
		$this->flush();

		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";

		// Keep track of the last query for debug..
		$this->last_query = $query;

		// Perform the query via std mysql_query function..
		if ( ( defined('SAVEQUERIES') && SAVEQUERIES ) || ( defined('DBCR_SAVEQUERIES') && DBCR_SAVEQUERIES ) )
			$this->timer_start();

		// --- DB Cache Start ---
		$dbcr_db = 'local';
		// --- DB Cache End ---
		
		// use $this->dbh for read ops, and $this->dbhwrite for write ops
		// use $this->dbhglobal for gloal table ops
		unset( $dbh );
		if( defined( 'WP_USE_MULTIPLE_DB' ) && WP_USE_MULTIPLE_DB ) {
			if( $this->blogs != '' && preg_match("/(" . $this->blogs . "|" . $this->users . "|" . $this->usermeta . "|" . $this->site . "|" . $this->sitemeta . "|" . $this->sitecategories . ")/i",$query) ) {
				if( false == isset( $this->dbhglobal ) ) {
					$this->db_connect( $query );
				}
				$dbh =& $this->dbhglobal;
				$this->last_db_used = "global";
				// --- DB Cache Start ---
				$dbcr_db = 'global';
				// --- DB Cache End ---
			} elseif ( preg_match("/^\\s*(alter table|create|insert|delete|update|replace) /i",$query) ) {
				if( false == isset( $this->dbhwrite ) ) {
					$this->db_connect( $query );
				}
				$dbh =& $this->dbhwrite;
				$this->last_db_used = "write";
			} else {
				$dbh =& $this->dbh;
				$this->last_db_used = "read";
			}
		} else {
			$dbh =& $this->dbh;
			$this->last_db_used = "other/read";
			// DB Cache Start
			if( $this->blogs != '' && preg_match("/(" . $this->blogs . "|" . $this->users . "|" . $this->usermeta . "|" . $this->site . "|" . $this->sitemeta . "|" . $this->sitecategories . ")/i",$query) ) {
				$dbcr_db = 'global';
			}
			// DB Cache End
		}
		
		// Caching
		$dbcr_cacheable = false;
		// check if pcache object is in place
		if ( !is_null( $this->dbcr_cache ) ) {
			$dbcr_cacheable = $this->dbcr_cacheable && $maybe_cache;
			
			if ( $dbcr_cacheable ) {
				// do not cache non-select queries
				if ( preg_match( "/\\s*(insert|delete|update|replace|alter|SET NAMES|FOUND_ROWS|RAND)\\b/si", $query ) ) {
					$dbcr_cacheable = false;
				} elseif ( // For hard queries - skip them
					//preg_match(  "/\\s*(JOIN)/si", $query ) ||
					// User-defined cache filters
					( isset( $this->dbcr_config['filter'] ) && ( $this->dbcr_config['filter'] != '' ) &&
					preg_match( "/\\s*(".$this->dbcr_config['filter'].")/si", $query ) ) ) {
					$dbcr_cacheable = false;
				}
			}
			
			if ( $dbcr_cacheable ) {
				$dbcr_queryid = md5( $query );
				
				if ( strpos( $query, '_options' ) ) {
					$this->dbcr_cache->set_storage( $dbcr_db, 'options' );
				} elseif ( strpos( $query, '_links' ) ) {
					$this->dbcr_cache->set_storage( $dbcr_db, 'links' );
				} elseif ( strpos( $query, '_terms' ) ) {
					$this->dbcr_cache->set_storage( $dbcr_db, 'terms' );
				} elseif ( strpos( $query, '_user' ) ) {
					$this->dbcr_cache->set_storage( $dbcr_db, 'users' );
				} elseif ( strpos( $query, '_post' ) ) {
					$this->dbcr_cache->set_storage( $dbcr_db, 'posts' );
				} elseif ( strpos( $query, 'JOIN' ) ) {
					$this->dbcr_cache->set_storage( $dbcr_db, 'joins' );
				} else {
					$this->dbcr_cache->set_storage( $dbcr_db, '' );
				}
			}
			
			/* Debug part */
			if ( isset( $this->dbcr_config['debug'] ) && $this->dbcr_config['debug'] ) {
				if ( $dbcr_cacheable ) {
					echo "\n<!-- cache: $query -->\n\n";
				} else {
					echo "\n<!-- mysql: $query -->\n\n";
				}
			}
		} elseif ( $this->dbcr_show_error ) {
			$this->dbcr_show_error = false;
			add_action( 'admin_notices', array( &$this, '_dbcr_admin_notice' ) );
		}
		
		$dbcr_cached = false;
		if ( $dbcr_cacheable ) {
			// Try to load cached query
			$dbcr_cached = $this->dbcr_cache->load( $dbcr_queryid );
		}
		
		if ( $dbcr_cached !== false ) {
			// Extract cached query
			++$this->num_cachequeries;
			
			$dbcr_cached = unserialize( $dbcr_cached );
			$this->last_error = '';
			$this->last_query = $dbcr_cached['last_query'];
			$this->last_result = $dbcr_cached['last_result'];
			$this->col_info = $dbcr_cached['col_info'];
			$this->num_rows = $dbcr_cached['num_rows'];
			
			$return_val = $this->num_rows;
			
			if ( defined('DBCR_SAVEQUERIES') && DBCR_SAVEQUERIES ) {
				$this->queries[] = array( $query, $this->timer_stop(), $this->get_caller(), true );
			}
		} else {
			// Cache not found or query is not cacheable, perform query as usual
			// --- DB Cache End ---
			$this->result = @mysql_query( $query, $dbh );
			$this->num_queries++;
		
			if ( defined( 'DBCR_SAVEQUERIES' ) && DBCR_SAVEQUERIES )
				$this->queries[] = array( $query, $this->timer_stop(), $this->get_caller(), false );
			elseif ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
				$this->queries[] = array( $query, $this->timer_stop(), $this->get_caller() );
		}
		
		// If there is an error then take note of it..
		if ( $this->last_error = mysql_error( $dbh ) ) {
			$this->print_error();
			return false;
		}
		
		if ( preg_match( "/^\\s*(insert|delete|update|replace|alter) /i", $query ) ) {
			$this->rows_affected = mysql_affected_rows( $dbh );
			// Take note of the insert_id
			if ( preg_match( "/^\\s*(insert|replace) /i", $query ) ) {
				$this->insert_id = mysql_insert_id($dbh);
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
			
			// --- DB Cache Start ---
			++$this->dbcr_num_dml_queries;
			// --- DB Cache End ---
		} else {
			$i = 0;
			while ( $i < @mysql_num_fields( $this->result ) ) {
				$this->col_info[$i] = @mysql_fetch_field( $this->result );
				$i++;
			}
			$num_rows = 0;
			while ( $row = @mysql_fetch_object( $this->result ) ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}
	
			@mysql_free_result( $this->result );

			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;

			// --- DB Cache Start ---
			if ( $dbcr_cacheable && ( $dbcr_cached === false ) ) {
				$dbcr_cached = serialize( array(
					'last_query' => $this->last_query,
					'last_result' => $this->last_result,
					'col_info' => $this->col_info,
					'num_rows' => $this->num_rows,
				) );
				$this->dbcr_cache->save( $dbcr_cached, $dbcr_queryid );
			}
			// --- DB Cache End ---
		}

		return $return_val;
	}
}
}
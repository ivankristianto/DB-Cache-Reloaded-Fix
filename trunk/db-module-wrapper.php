<?php

// --- DB Cache Start ---
// Path to plugin
if ( !defined( 'DBCR_PATH' ) ) {
	define( 'DBCR_PATH', WP_PLUGIN_DIR.'/'.dirname( __FILE__ ) );
}
// Cache directory
if ( !defined( 'DBCR_CACHE_DIR' ) ) {
	define( 'DBCR_CACHE_DIR', WP_PLUGIN_DIR.'/cache' );
}

// DB Module version (one or more digits for major, two digits for minor and revision numbers)
if ( !defined( 'DBCR_DB_MODULE_VER' ) ) {
	define( 'DBCR_DB_MODULE_VER', 10600 );
}

// HACK: need to enable SAVEQUERY in order to save extended query data
if ( defined( 'DBCR_SAVEQUERIES' ) && DBCR_SAVEQUERIES && !defined ( 'SAVEQUERIES' ) ) {
	define( 'SAVEQUERIES', true );
}

// Check if we have required functions
if ( !function_exists( 'is_multisite' ) ) { // Added in WP 3.0
	function is_multisite() {
		return false;
	}
}

// --- DB Cache End ---


if ( !class_exists( 'DBCR_WPDB_Wrapper' ) ) {
class DBCR_WPDB_Wrapper {

	// --- DB Cache Start ---
	/**
	 * Aggregated WPDB object
	 *
	 * @var object of wpdb|null
	 */
	var $dbcr_wpdb = null;
	/**
	 * Amount of queries cached by DB Cache Reloaded made
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
	 * PHP5 style constructor for compatibility with PHP5. Does
	 * the actual setting up of the class properties and connection
	 * to the database.
	 *
	 * PHP4 incompatibility is intentional, because of using PHP5 object extensions.
	 */
	function __construct($wpdb) {
		$this->dbcr_wpdb = $wpdb;
		
		// --- DB Cache Start ---
		// Caching
		// require_once would be better, but some people deletes plugin without deactivating it first
		if ( @include_once( DBCR_PATH.'/db-functions.php' ) ) {
			$this->dbcr_config = unserialize( @file_get_contents( WP_CONTENT_DIR.'/db-config.ini' ) );
			
			$this->dbcr_cache =& new pcache();
			$this->dbcr_cache->lifetime = isset( $this->dbcr_config['timeout'] ) ? $this->dbcr_config['timeout'] : 5;
			
			// Clean unused
			$dbcheck = date('G')/4;
			if ( $dbcheck == intval( $dbcheck ) && ( !isset( $this->dbcr_config['lastclean'] ) 
				|| $this->dbcr_config['lastclean'] < time() - 3600 ) ) {
				$this->dbcr_cache->clean();
				$this->dbcr_config['lastclean'] = time();
				$file = fopen(WP_CONTENT_DIR.'/db-config.ini', 'w+');
				if ($file) {
					fwrite($file, serialize($this->dbcr_config));
					fclose($file);
				}
			}
			
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
	 * Perform a MySQL database query, using current database connection.
	 *
	 * More information can be found on the codex page.
	 *
	 * @since 0.71
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	function query($query) {
		return $this->dbcr_query( $query, true );
	}
	
	function dbcr_query( $query, $maybe_cache = true ) {
		if ( ! $this->dbcr_wpdb->ready )
			return false;

		// --- DB Cache Start ---
		if ( defined('DBCR_SAVEQUERIES') && DBCR_SAVEQUERIES )
			$this->dbcr_wpdb->timer_start();
		
		$dbcr_db = 'local';
		// --- DB Cache End ---
		
		if( defined( 'WP_USE_MULTIPLE_DB' ) && WP_USE_MULTIPLE_DB ) {
			if( $this->dbcr_wpdb->blogs != '' && preg_match("/(" . $this->dbcr_wpdb->blogs . "|" . $this->dbcr_wpdb->users . "|" . $this->dbcr_wpdb->usermeta . "|" . $this->dbcr_wpdb->site . "|" . $this->dbcr_wpdb->sitemeta . "|" . $this->dbcr_wpdb->sitecategories . ")/i",$query) ) {
				// --- DB Cache Start ---
				$dbcr_db = 'global';
				// --- DB Cache End ---
			}
		} else {
			// DB Cache Start
			if( $this->dbcr_wpdb->blogs != '' && preg_match("/(" . $this->dbcr_wpdb->blogs . "|" . $this->dbcr_wpdb->users . "|" . $this->dbcr_wpdb->usermeta . "|" . $this->dbcr_wpdb->site . "|" . $this->dbcr_wpdb->sitemeta . "|" . $this->dbcr_wpdb->sitecategories . ")/i",$query) ) {
				$dbcr_db = 'global';
			}
			// DB Cache End
		}
		
		// --- DB Cache Start ---
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
					!preg_match( "/\\s*(JOIN | \* |\*\,)/si", $query ) ||
					// User-defined cache filters
					( isset( $config['filter'] ) && ( $config['filter'] != '' ) &&
					preg_match( "/\\s*(".$config['filter'].")/si", $query ) ) ) {
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
				} else {
					$this->dbcr_cache->set_storage( $dbcr_db, '' );
				}
			}
			
			/* Debug part */
			if ( isset( $config['debug'] ) && $config['debug'] ) {
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
			$this->dbcr_wpdb->last_error = '';
			$this->dbcr_wpdb->last_query = $dbcr_cached['last_query'];
			$this->dbcr_wpdb->last_result = $dbcr_cached['last_result'];
			$this->dbcr_wpdb->col_info = $dbcr_cached['col_info'];
			$this->dbcr_wpdb->num_rows = $dbcr_cached['num_rows'];
			
			$return_val = $this->dbcr_wpdb->num_rows;
			
			if ( defined('DBCR_SAVEQUERIES') && DBCR_SAVEQUERIES ) {
				$this->dbcr_wpdb->queries[] = array( $query, $this->dbcr_wpdb->timer_stop(), $this->dbcr_wpdb->get_caller(), true );
			}
		} else {
			// Cache not found or query not cacheable, perform query as usual
			$return_val = $this->dbcr_wpdb->query( $query );
			if ( $return_val === false ) { // error executing sql query
				return false;
			}
			
			if ( defined('DBCR_SAVEQUERIES') && DBCR_SAVEQUERIES ) {
				$this->dbcr_wpdb->queries[count( $this->dbcr_wpdb->queries ) - 1][3] = false;
			}
		}
		
		if ( preg_match( "/^\\s*(insert|delete|update|replace|alter) /i", $query ) ) {
			// --- DB Cache Start ---
			++$this->dbcr_num_dml_queries;
			// --- DB Cache End ---
		} else {
			// --- DB Cache Start ---
			if ( $dbcr_cacheable && ( $dbcr_cached === false ) ) {
				$dbcr_cached = serialize( array(
					'last_query' => $this->dbcr_wpdb->last_query,
					'last_result' => $this->dbcr_wpdb->last_result,
					'col_info' => $this->dbcr_wpdb->col_info,
					'num_rows' => $this->dbcr_wpdb->num_rows,
				) );
				$this->dbcr_cache->save( $dbcr_cached, $dbcr_queryid );
			}
			// DB Cache End
		}
		
		return $return_val;
	}
	
	// Show error message when something is messed with DB Cache Reloaded plugin
	function _dbcr_admin_notice() {
		// Display error message
		echo '<div id="notice" class="error"><p>';
		printf( __('<b>DB Cache Reloaded Error:</b> cannot include <code>db-functions.php</code> file. Please either reinstall plugin or remove <code>%s</code> file.', 'db-cache-reloaded'), WP_CONTENT_DIR.'/db.php' );
		echo '</p></div>', "\n";
	}
	
	/**
	 * Retrieve one variable from the database.
	 *
	 * Executes a SQL query and returns the value from the SQL result.
	 * If the SQL result contains more than one column and/or more than one row, this function returns the value in the column and row specified.
	 * If $query is null, this function returns the value in the specified column and row from the previous SQL result.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query Optional. SQL query. Defaults to null, use the result from the previous query.
	 * @param int $x Optional. Column of value to return.  Indexed from 0.
	 * @param int $y Optional. Row of value to return.  Indexed from 0.
	 * @return string|null Database query result (as string), or null on failure
	 */
	function get_var( $query = null, $x = 0, $y = 0 ) {
		$this->dbcr_wpdb->func_call = "\$db->get_var(\"$query\", $x, $y)";
		if ( $query )
			$this->dbcr_query( $query );

		// Extract var out of cached results based x,y vals
		if ( !empty( $this->dbcr_wpdb->last_result[$y] ) ) {
			$values = array_values( get_object_vars( $this->dbcr_wpdb->last_result[$y] ) );
		}

		// If there is a value return it else return null
		return ( isset( $values[$x] ) && $values[$x] !== '' ) ? $values[$x] : null;
	}

	/**
	 * Retrieve one row from the database.
	 *
	 * Executes a SQL query and returns the row from the SQL result.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query SQL query.
	 * @param string $output Optional. one of ARRAY_A | ARRAY_N | OBJECT constants. Return an associative array (column => value, ...),
	 * 	a numerically indexed array (0 => value, ...) or an object ( ->column = value ), respectively.
	 * @param int $y Optional. Row to return. Indexed from 0.
	 * @return mixed Database query result in format specifed by $output or null on failure
	 */
	function get_row( $query = null, $output = OBJECT, $y = 0 ) {
		$this->dbcr_wpdb->func_call = "\$db->get_row(\"$query\",$output,$y)";
		if ( $query )
			$this->dbcr_query( $query );
		else
			return null;

		if ( !isset( $this->dbcr_wpdb->last_result[$y] ) )
			return null;

		if ( $output == OBJECT ) {
			return $this->dbcr_wpdb->last_result[$y] ? $this->dbcr_wpdb->last_result[$y] : null;
		} elseif ( $output == ARRAY_A ) {
			return $this->dbcr_wpdb->last_result[$y] ? get_object_vars( $this->dbcr_wpdb->last_result[$y] ) : null;
		} elseif ( $output == ARRAY_N ) {
			return $this->dbcr_wpdb->last_result[$y] ? array_values( get_object_vars( $this->dbcr_wpdb->last_result[$y] ) ) : null;
		} else {
			$this->dbcr_wpdb->print_error(/*WP_I18N_DB_GETROW_ERROR*/" \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N"/*/WP_I18N_DB_GETROW_ERROR*/);
		}
	}

	/**
	 * Retrieve one column from the database.
	 *
	 * Executes a SQL query and returns the column from the SQL result.
	 * If the SQL result contains more than one column, this function returns the column specified.
	 * If $query is null, this function returns the specified column from the previous SQL result.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query Optional. SQL query. Defaults to previous query.
	 * @param int $x Optional. Column to return. Indexed from 0.
	 * @return array Database query result.  Array indexed from 0 by SQL result row number.
	 */
	function get_col( $query = null , $x = 0 ) {
		if ( $query )
			$this->dbcr_query( $query );

		$new_array = array();
		// Extract the column values
		for ( $i = 0, $j = count( $this->dbcr_wpdb->last_result ); $i < $j; $i++ ) {
			$new_array[$i] = $this->get_var( null, $x, $i );
		}
		return $new_array;
	}

	/**
	 * Retrieve an entire SQL result set from the database (i.e., many rows)
	 *
	 * Executes a SQL query and returns the entire SQL result.
	 *
	 * @since 0.71
	 *
	 * @param string $query SQL query.
	 * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants. With one of the first three, return an array of rows indexed from 0 by SQL result row number.
	 * 	Each row is an associative array (column => value, ...), a numerically indexed array (0 => value, ...), or an object. ( ->column = value ), respectively.
	 * 	With OBJECT_K, return an associative array of row objects keyed by the value of each row's first column's value.  Duplicate keys are discarded.
	 * @return mixed Database query results
	 */
	function get_results( $query = null, $output = OBJECT ) {
		$this->dbcr_wpdb->func_call = "\$db->get_results(\"$query\", $output)";

		if ( $query )
			$this->dbcr_query( $query );
		else
			return null;

		$new_array = array();
		if ( $output == OBJECT ) {
			// Return an integer-keyed array of row objects
			return $this->dbcr_wpdb->last_result;
		} elseif ( $output == OBJECT_K ) {
			// Return an array of row objects with keys from column 1
			// (Duplicates are discarded)
			foreach ( $this->dbcr_wpdb->last_result as $row ) {
				$key = array_shift( get_object_vars( $row ) );
				if ( ! isset( $new_array[ $key ] ) )
					$new_array[ $key ] = $row;
			}
			return $new_array;
		} elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
			// Return an integer-keyed array of...
			if ( $this->dbcr_wpdb->last_result ) {
				foreach( (array) $this->dbcr_wpdb->last_result as $row ) {
					if ( $output == ARRAY_N ) {
						// ...integer-keyed row arrays
						$new_array[] = array_values( get_object_vars( $row ) );
					} else {
						// ...column name-keyed row arrays
						$new_array[] = get_object_vars( $row );
					}
				}
			}
			return $new_array;
		}
		return null;
	}
	
	// Wrappers for members of aggregated class
	function __get( $name ) {
		return $this->dbcr_wpdb->$name;
	}
	
	function __set( $name, $value ) {
		$this->dbcr_wpdb->$name = $value;
	}
	
	function __isset( $name ) {
		return isset( $this->dbcr_wpdb->$name );
	}
	
	function __unset( $name ) {
		unset( $this->dbcr_wpdb->$name );
	}
	
	function __call( $name, $args ) {
		return call_user_func_array( array( $this->dbcr_wpdb, $name ), $args );
	}
}

$wpdb = new DBCR_WPDB_Wrapper( $wpdb );
$dbcr_wpdb = $wpdb;

}

?>
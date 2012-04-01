<?php
/**
 * Cache framework
 * Author: Daniel Frużyński
 * Based on DB Cache by Dmitry Svarytsevych
 * http://design.lviv.ua
 */

/*  Original code Copyright Dmitry Svarytsevych
    Modifications Copyright 2009  Daniel Frużyński  (email : daniel [A-T] poradnik-webmastera.com)

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

// Path to plugin
if ( !defined( 'DBCR_PATH' ) ) {
	define( 'DBCR_PATH', dirname( __FILE__ ) );
}

// Cache directory
if ( !defined( 'DBCR_CACHE_DIR' ) ) {
	define( 'DBCR_CACHE_DIR', DBCR_PATH.'/cache' );
}

class pcache {
	// Cache lifetime - by default 86400 sec = 24 hours
	var $lifetime = 86400;
	
	// Base storage path for global data
	var $base_storage_global = null;
	
	// Base storage patch for local data
	var $base_storage = null;
	
	// Path to current storage dir
	var $storage = null;
	
	// All subdirs
	var $folders = array( 'options', 'links', 'terms', 'users', 'posts', 'joins', '' );
	
	// Constructor
	function pcache() {
		$this->set_storage();
	}
	
	// Set storage dir for next operation(s)
	function set_storage( $db = 'local', $context = '' ) {
		/*if ( is_multisite() ) { // WP 3.0+ multisite install
			if ( $db == 'global' ) {
				if ( is_null( $this->base_storage ) ) {
					$this->base_storage_global = DBCR_CACHE_DIR . '/@global';
				}
			} else { // local
				if ( is_null( $this->base_storage_local ) ) {
					global $current_site;
					$this->base_storage = DBCR_CACHE_DIR . '/' . $current_site->domain . $current_site->path;
				}
				$this->storage = $this->base_storage;
			}
		} elseif ( defined( 'DBCR_MULTISITE' ) && DBCR_MULTISITE ) { // HTTP_HOST-based multisite
			if ( is_null( $this->base_storage ) ) {
				$host = strtolower( $_SERVER['HTTP_HOST'] );
				$this->base_storage = DBCR_CACHE_DIR . '/' . $host;
			}
			$this->storage = $this->base_storage;
		} else { // Single WP install
			$this->storage = DBCR_CACHE_DIR;
		}*/
		
		$this->base_storage = DBCR_CACHE_DIR;
		$this->storage = DBCR_CACHE_DIR;
		
		// Set per-context path
		if ( $context != '' ) {
			$this->storage .= '/' . $context;
		}
	}
	
	// Load data from cache for given tag
	function load( $tag ) {
		if ( $tag == '' ) {
			return false;
		}

		$file = $this->storage.'/'.$tag;
		$result = false;
		
		// If file exists
		if ( $filemtime = @filemtime( $file ) ) {
			$f = @fopen( $file, 'r' );
			if ( $f ) {
				@flock( $f, LOCK_SH );
				// for PHP5
				if ( function_exists( 'stream_get_contents' ) ) {
					$result = unserialize( stream_get_contents( $f ) );
				} else { // for PHP4
					$result = '';
					while ( !feof( $f ) ) {
		  				$result .= fgets( $f, 4096 );
					}
					$result = unserialize( $result );
				}
				@flock( $f, LOCK_UN );
				@fclose( $f );

				// Remove if expired
				if ( ( $filemtime + $this->lifetime - time() ) < 0 ) {
					$this->remove( $tag );
				}
			}
		}

		return $result;
	}
	
	// Save data to cache for given tag
	function save( $value, $tag ) {
		if ( $tag == '' || $value == '' ) {
			return false;
		}
		
		$file = $this->storage.'/'.$tag;
		
		$f = @fopen( $file, 'w' );
		if ( !$f ) {
			return false;
		}
		
		@flock( $f, LOCK_EX );
		@fwrite( $f, serialize( $value ) );
		@flock( $f, LOCK_UN );
		@fclose( $f );
		@chmod( $file, 0755 );

		return true;
	}
	
	// Remove data from cache for given tag
	function remove( $tag = '', $dir = false, $remove_expired_only = false ) {
		if ( $tag == '' ) {
			return false;
		}
		
		if ( $dir === false ) {
			$dir = $this->storage;
		}
		
		$file = $dir.'/'.$tag;

		if ( is_file( $file ) ) {
			if ( $remove_expired_only && ( @filemtime( $file ) + $this->lifetime - time() ) > 0 ) {
				return true;
			}
			if ( @unlink( $file ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	function clean( $remove_expired_only = true, $db = 'local' ) {
		$this->set_storage( $db );
		
		foreach( $this->folders as $folder ) {
			$path = $this->base_storage . '/';
			if ( $folder != '' ) {
				$path .= $folder . '/';
			}
			if ( $dir = @opendir( $path ) ) {
				while ( $tag = readdir( $dir ) ) {
					if ( ( $tag != '.' ) && ( $tag != '..' ) && ( $tag != '.htaccess' ) ) {
						$this->remove( $tag, $path, $remove_expired_only );
					}
				}
				closedir( $dir );
			}
		}
	}
	
	// Setup cache dirs
	// Return true on success, false otherwise
	function install( $global = false ) {
		$this->set_storage( 'local' );
		
		foreach( $this->folders as $folder ) {
			$path = $this->base_storage . '/';
			if ( $folder != '' ) {
				$path .= $folder . '/';
			}
			
			if ( $folder != '' ) { // Skip base folder - it is already created
				if ( !@mkdir( $path, 0755, true ) ) {
					return false;
				}
			}
			if ( !@copy( DBCR_PATH.'/htaccess', $path.'/.htaccess' ) ) {
				return false;
			}
		}
		
		/*if ( is_multisite() ) {
			$this->set_storage( 'global' );
			foreach( $this->folders as $folder ) {
				$path = $this->base_storage . '/';
				if ( $folder != '' ) {
					$path .= $folder . '/';
				}
				if ( !@mkdir( $path, 0755, true ) ) {
					return false;
				}
				if ( !@copy( DBCR_PATH.'/htaccess', $path.'/.htaccess' ) ) {
					return false;
				}
			}
		}*/
		
		return true;
	}
	
	// Remove cache dirs
	// Return true on success, false otherwise
	function uninstall( $global = false ) {
		$this->set_storage( 'local' );
		
		$this->clean( false );
		
		foreach( $this->folders as $folder ) {
			$path = $this->base_storage . '/';
			if ( $folder != '' ) {
				$path .= $folder . '/';
			}
			
			@unlink( $path.'.htaccess' );
			@rmdir( $path );
		}
	}
}

?>
<?php
/**
 * Plugin Name: DB Cache Reloaded Fix
 * Short Name: db_cache_reloaded
 * Description: The fastest cache engine for WordPress, that produces cache of database queries with easy configuration. (Disable and enable caching after update). Now compatible with WordPress 3.1. 
 * Author: Ivan Kristianto
 * Version: 2.3
 * Requires at least: 2.7
 * Tested up to: 3.4
 * Tags: db cache, db cache reloaded, db cache reloaded fix
 * Contributors: Ivan Kristianto
 * WordPress URI: http://wordpress.org/extend/plugins/db-cache-reloaded-fix/
 * Author URI: http://www.ivankristianto.com/
 * Donate URI: http://www.ivankristianto.com/portfolio/
 * Plugin URI: http://www.ivankristianto.com/web-development/programming/db-cache-reloaded-fix-for-wordpress-3-1/1784/
 * Text Domain: db-cache-reloaded-fix

	Copyright 2011 Ivan Kristianto (ivan@ivankristianto.com)

	Based On DB Cache Reloaded by Daniel Frużyński  (email : daniel [A-T] poradnik-webmastera.com)
    Based on DB Cache by Dmitry Svarytsevych

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

define( 'DBCR_DEBUG', false );

// Path to plugin
if ( !defined( 'DBCR_PATH' ) ) {
	define( 'DBCR_PATH', dirname( __FILE__ ) );
}
// Cache directory
if ( !defined( 'DBCR_CACHE_DIR' ) ) {
	define( 'DBCR_CACHE_DIR', DBCR_PATH.'/cache' );
}

// Check if we have required functions
if ( !function_exists( 'is_multisite' ) ) { // Added in WP 3.0
	function is_multisite() {
		return false;
	}
}

// DB Module version (one or more digits for major, two digits for minor and revision numbers)
define( 'DBCR_CURRENT_DB_MODULE_VER', 10600 );

// Load pcache class if needed
if ( !class_exists('pcache') ) {
	include DBCR_PATH.'/db-functions.php';
}

if ( !class_exists( 'DBCacheReloaded' ) ) {

class DBCacheReloaded {
	var $config = null;
	var $folders = null;
	var $settings_page = false;
	var $dbcr_cache = null;
	
	// Constructor
	function DBCacheReloaded() {
		$this->config = unserialize( @file_get_contents( WP_CONTENT_DIR.'/db-config.ini' ) );
		
		// Load DB Module Wrapper if needed (1st check)
		global $wpdb;
		if ( isset( $this->config['enabled'] ) && $this->config['enabled']
			&& isset( $this->config['wrapper'] ) && $this->config['wrapper'] ) {
			global $dbcr_wpdb;
			include DBCR_PATH.'/db-module-wrapper.php';
			
			// 3rd check to make sure DB Module Wrapper is loaded
			add_filter( 'query_vars', array( &$this, 'query_vars' ) );
		}
		
		// Set our copy of pcache object
		if ( isset ( $wpdb->dbcr_cache ) ) {
			$this->dbcr_cache =& $wpdb->dbcr_cache;
		} else {
			$this->dbcr_cache =& new pcache();
		}
		
		// Install/Upgrade
		//add_action( 'activate_'.plugin_basename( __FILE__ ), array( &$this, 'dbcr_install' ) );
		register_activation_hook(__FILE__,  array( &$this, 'dbcr_install'));
		// Uninstall
		//add_action( 'deactivate_'.plugin_basename( __FILE__ ), array( &$this, 'dbcr_uninstall' ) );
		register_deactivation_hook(__FILE__, array( &$this, 'dbcr_uninstall' ));
		
		// Initialise plugin
		add_action( 'init', array( &$this, 'init' ) );
		
		// Add cleaning on publish and new comment
		// Posts
		add_action( 'publish_post', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'edit_post', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'delete_post', array( &$this, 'dbcr_clear' ), 0 );
		// Comments
		add_action( 'trackback_post', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'pingback_post', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'comment_post', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'edit_comment', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'wp_set_comment_status', array( &$this, 'dbcr_clear' ), 0 );
		// Other
		add_action( 'delete_comment', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'switch_theme', array( &$this, 'dbcr_clear' ), 0 );
		
		add_action('clean_cache_event', array(&$this, 'hourly_clean'));
		
		// Display stats in footer
		add_action( 'wp_footer', 'loadstats', 999999 );
		
		if ( is_admin() ) {
			// Show warning message to admin
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
			
			// Catch options page
			add_action( 'load-settings_page_'.substr( plugin_basename( __FILE__ ), 0, -4 ), array( &$this, 'load_settings_page' ) );
			
			// Create options menu
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			
			// Clear cache when option is changed
			global $wp_version;
			if ( version_compare( $wp_version, '2.9', '>=' ) ) {
				add_action( 'added_option', array( &$this, 'dbcr_clear' ), 0 );
				add_action( 'updated_option', array( &$this, 'dbcr_clear' ), 0 );
				add_action( 'deleted_option', array( &$this, 'dbcr_clear' ), 0 );
			} else {
				// Hook for all actions
				add_action( 'all', array( &$this, 'all_actions' ) );
			}
			
			// Provide icon for Ozh' Admin Drop Down Menu plugin
			add_action( 'ozh_adminmenu_icon_'.plugin_basename( __FILE__ ), array( &$this, 'ozh_adminmenu_icon' ) );
		}
	}
	
	// Initialise plugin
	function init() {
		load_plugin_textdomain( 'db-cache-reloaded', false, dirname( plugin_basename( __FILE__ ) ).'/lang' );
		
		if ( !wp_next_scheduled('wp_update_plugins') && !defined('WP_INSTALLING') )
			wp_schedule_event(time(), 'hourly', 'clean_cache_event');
		// 2nd check
		global $wpdb;
		if ( isset( $this->config['enabled'] ) && $this->config['enabled']
			&& isset( $this->config['wrapper'] ) && $this->config['wrapper']
			&& !isset ( $wpdb->dbcr_version ) ) {
			// Looks that other plugin replaced our object in the meantime - need to fix this
			global $dbcr_wpdb;
			$dbcr_wpdb->dbcr_wpdb = $wpdb;
			$wpdb = $dbcr_wpdb;
		}
	}
	
	// Create options menu
	function admin_menu() {
		add_submenu_page( 'options-general.php', 'DB Cache Reloaded', 'DB Cache Reloaded', 
			'manage_options', __FILE__, array( &$this, 'options_page' ) );
	}

	// 3rd check to make sure DB Module Wrapper is loaded
	function query_vars( $vars ) {
		// 3rd check
		global $wpdb;
		if ( isset( $this->config['enabled'] ) && $this->config['enabled']
			&& isset( $this->config['wrapper'] ) && $this->config['wrapper']
			&& !isset ( $wpdb->dbcr_version ) ) {
			// Looks that other plugin replaced our object in the meantime - need to fix this
			global $dbcr_wpdb;
			$dbcr_wpdb->dbcr_wpdb = $wpdb;
			$wpdb = $dbcr_wpdb;
		}
		
		return $vars;
	}
	
	function admin_notices() {
		global $wpdb;
		if ( defined( 'DBCR_WPDB_EXISTED' ) ) {
			// Display error message
			echo '<div id="notice" class="error"><p>';
			_e('<b>DB Cache Reloaded Error:</b> <code>wpdb</code> class is redefined, plugin cannot work!', 'db-cache-reloaded');
			if ( DBCR_WPDB_EXISTED !== true ) {
				echo '<br />';
				printf( __('Previous definition is at %s.', 'db-cache-reloaded'), DBCR_WPDB_EXISTED );
			}
			echo '</p></div>', "\n";
		}
		
		if ( !$this->settings_page ) {
			if ( ( !isset( $this->config['enabled'] ) || !$this->config['enabled'] ) ) {
				// Caching is disabled - display info message
				echo '<div id="notice" class="updated fade"><p>';
				printf( __('<b>DB Cache Reloaded Info:</b> caching is not enabled. Please go to the <a href="%s">Options Page</a> to enable it.', 'db-cache-reloaded'), admin_url( 'options-general.php?page='.plugin_basename( __FILE__ ) ) );
				echo '</p></div>', "\n";
			} elseif ( !isset( $wpdb->num_cachequeries ) ) {
				echo '<div id="notice" class="error"><p>';
				printf( __('<b>DB Cache Reloaded Error:</b> DB Module (<code>wpdb</code> class) is not loaded. Please open the <a href="%1$s">Options Page</a>, disable caching (remember to save options) and enable it again. If this will not help, please check <a href="%2$s">FAQ</a> how to do manual upgrade.', 'db-cache-reloaded'),
					admin_url( 'options-general.php?page='.plugin_basename( __FILE__ ) ), 
					'http://wordpress.org/extend/plugins/db-cache-reloaded/faq/' );
				echo '</p></div>', "\n";
			} else {
				if ( isset ( $wpdb->dbcr_version ) ) {
					$dbcr_db_version = $wpdb->dbcr_version;
				} else {
					$dbcr_db_version = 0;
				}
				
				if ( $dbcr_db_version != DBCR_CURRENT_DB_MODULE_VER ) {
					echo '<div id="notice" class="error"><p>';
					printf( __('<b>DB Cache Reloaded Error:</b> DB Module is not up to date (detected version %1$s instead of %2$s). In order to fix this, please open the <a href="%3$s">Options Page</a>, disable caching (remember to save options) and enable it again.', 'db-cache-reloaded'), 
						$this->format_ver_num( $dbcr_db_version ), 
						$this->format_ver_num( DBCR_CURRENT_DB_MODULE_VER ), 
						admin_url( 'options-general.php?page='.plugin_basename( __FILE__ ) ) );
					echo '</p></div>', "\n";
				}
			}
		}
	}
	
	// Hook for all actions
	// Note: Called in Admin section only
	function all_actions( $hook ) {
		// Clear cache when option is updated or added
		if ( preg_match( '/^(update_option_|add_option_)/', $hook ) ) {
			$this->dbcr_clear();
		}
	}
	
	// Provide icon for Ozh' Admin Drop Down Menu plugin
	function ozh_adminmenu_icon() {
		return plugins_url( 'icon.png', __FILE__ );
	}
	
	function load_settings_page() {
		$this->settings_page = true;
	}

	// Enable cache
	function dbcr_enable( $echo = true ) {
		$status = true;
		
		// Copy DB Module (if needed)
		if ( !isset( $this->config['wrapper'] ) || !$this->config['wrapper'] ) {
			if ( !@copy( DBCR_PATH.'/db.php', WP_CONTENT_DIR.'/db.php' ) ) {
				$status = false;
			}
		}
		
		// Create cache dirs and copy .htaccess
		if ( $status ) {
			$status = $this->dbcr_cache->install();
		}
		
		if ( $echo ) {
			if ( $status ) {
				echo '<div id="message" class="updated fade"><p>';
				_e('Caching activated.', 'db-cache-reloaded');
				echo '</p></div>';
			} else {
				echo '<div id="message" class="error"><p>';
				_e('Caching can\'t be activated. Please <a href="http://codex.wordpress.org/Changing_File_Permissions" target="blank">chmod 755</a> <u>wp-content/plugins/db-cache-reloaded-fix/cache</u> folder', 'db-cache-reloaded');
				echo '</p></div>';
			}
		}
		
		if ( !$status ) {
			$this->dbcr_disable( $echo );
		}
		
		return $status;
	}

	// Disable cache
	function dbcr_disable( $echo = true ) {
		$this->dbcr_uninstall( false );
		if ( $echo ) {
			echo '<div id="message" class="updated fade"><p>';
			_e('Caching deactivated. Cache files deleted.', 'db-cache-reloaded');
			echo '</p></div>';
		}
		
		return true;
	}
	
	// Install plugin
	function dbcr_install() {
		if ( isset( $this->config['enabled'] ) && $this->config['enabled'] ) { // This should be a plugin upgrade
			$this->dbcr_uninstall( false );
			$this->dbcr_enable( false ); // No echo - ob_start()/ob_ob_end_clean() is used in installer
		}
	}

	// Uninstall plugin
	function dbcr_uninstall( $remove_all = true ) {
		$this->dbcr_clear();
		@unlink( WP_CONTENT_DIR.'/db.php' );
		if ( $remove_all ) {
			@unlink( WP_CONTENT_DIR.'/db-config.ini' );
		}
		@unlink( DBCR_CACHE_DIR.'/.htaccess' );
		
		$this->dbcr_cache->uninstall();
		
		@rmdir( DBCR_CACHE_DIR );
		wp_clear_scheduled_hook('clean_cache_event');
	}
	
	// This event will run hourly, and clean the expired cache
	function hourly_clean(){
		$this->dbcr_cache->clean();
	}

	// Clears the cache folder
	function dbcr_clear() {
		$this->dbcr_cache->clean( false );
	}
	
	// Format version number
	function format_ver_num( $version ) {
		if ( $version % 100 == 0 ) {
			return sprintf( '%d.%d', (int)($version / 10000), (int)($version / 100) % 100 );
		} else {
			return sprintf( '%d.%d.%d', (int)($version / 10000), (int)($version / 100) % 100, $version % 100 );
		}
	}
	
	// Settings page
	function options_page() {
		if ( !isset( $this->config['timeout'] ) || intval( $this->config['timeout'] ) == 0) {
			$this->config['timeout'] = 5;
		} else {
			$this->config['timeout'] = intval( $this->config['timeout']/60 );
		}
		if ( !isset( $this->config['enabled'] ) ) {
			$this->config['enabled'] = false;
			$cache_enabled = false;
		} else {
			$cache_enabled = true;
		}
		if ( !isset( $this->config['loadstat'] ) ) {
			$this->config['loadstat'] = __('<!-- Generated in {timer} seconds. Made {queries} queries to database and {cached} cached queries. Memory used - {memory} -->', 'db-cache-reloaded');
		}
		if ( !isset( $this->config['filter'] ) ) {
			$this->config['filter'] = '_posts|_postmeta';
		}
		if ( !isset( $this->config['wrapper'] ) ) {
			$this->config['wrapper'] = false;
		}
		if ( defined( 'DBCR_DEBUG' ) && DBCR_DEBUG ) {
			$this->config['debug'] = 1;
		}
		
		if ( isset( $_POST['clear'] ) ) {
			check_admin_referer( 'db-cache-reloaded-update-options' );
			$this->dbcr_cache->clean( false );
			echo '<div id="message" class="updated fade"><p>';
			_e('Cache files deleted.', 'db-cache-reloaded');
			echo '</p></div>';
		} elseif ( isset( $_POST['clearold'] ) ) {
			check_admin_referer( 'db-cache-reloaded-update-options' );
			$this->dbcr_cache->clean();
			echo '<div id="message" class="updated fade"><p>';
			_e('Expired cache files deleted.', 'db-cache-reloaded');
			echo '</p></div>';
		} elseif ( isset( $_POST['save'] ) ) {
			check_admin_referer( 'db-cache-reloaded-update-options' );
			$saveconfig = $this->config = $this->dbcr_request( 'options' );
		
			if ( defined( 'DBCR_DEBUG' ) && DBCR_DEBUG ) {
				$saveconfig['debug'] = 1;
			}
			if ( $saveconfig['timeout'] == '' || !is_numeric( $saveconfig['timeout'] ) ) {
				$this->config['timeout'] = 5;
			}
		
			// Convert to seconds for save
			$saveconfig['timeout'] = intval( $this->config['timeout']*60 );
		
			if ( !isset( $saveconfig['filter'] ) ) {
				$saveconfig['filter'] = '';
			} else {
				$this->config['filter'] = $saveconfig['filter'] = trim( $saveconfig['filter'] );
			}
			
			// Activate/deactivate caching
			if ( !isset( $this->config['enabled'] ) && $cache_enabled ) {
				$this->dbcr_disable();
			} elseif ( isset( $this->config['enabled'] ) && $this->config['enabled'] == 1 && !$cache_enabled ) {
				if ( !$this->dbcr_enable() ) {
					unset( $this->config['enabled'] );
					unset( $saveconfig['enabled'] );
				} else {
					$this->config['lastclean'] = time();
				}
			}
		
			$file = @fopen( WP_CONTENT_DIR."/db-config.ini", 'w+' );
			if ( $file ) {
				fwrite( $file, serialize( $saveconfig ) );
				fclose( $file );
				echo '<div id="message" class="updated fade"><p>';
				_e('Settings saved.', 'db-cache-reloaded');
				echo '</p></div>';
			} else {
				echo '<div id="message" class="error"><p>';
				_e('Settings can\'t be saved. Please <a href="http://codex.wordpress.org/Changing_File_Permissions" target="blank">chmod 755</a> file <u>db-config.ini</u>', 'db-cache-reloaded');
				echo '</p></div>';
			}
		}
?>
<div class="wrap">
<a href="http://www.ivankristianto.com/">
	<div id="db-cache-reloaded-icon" style="background: url(<?php echo plugin_dir_url(__FILE__) ?>dbcache-icon.png) no-repeat;" class="icon32"><br /></div>
</a>
<form method="post">
<?php wp_nonce_field('db-cache-reloaded-update-options'); ?>
<h2><?php _e('DB Cache Reloaded Fix By Ivan - Options', 'db-cache-reloaded'); ?></h2>
        
<h3><?php _e('Configuration', 'db-cache-reloaded'); ?></h3>
<table class="form-table">
	<tr valign="top">
		<?php $this->dbcr_field_checkbox( 'enabled', __('Enable', 'db-cache-reloaded') ); ?>
	</tr>
	<tr valign="top">
		<?php $this->dbcr_field_text( 'timeout', __('Expire a cached query after', 'db-cache-reloaded'),
			__('minutes. <em>(Expired files are deleted automatically)</em>', 'db-cache-reloaded'), 'size="5"' ); ?>
	</tr>
</table>

<h3><?php _e('Additional options', 'db-cache-reloaded'); ?></h3>
<table class="form-table">
	<tr valign="top">
		<?php $this->dbcr_field_text( 'filter', __('Cache filter', 'db-cache-reloaded'), 
			'<br/>'.__('Do not cache queries that contains this input contents. Divide different filters with \'|\' (vertical line, e.g. \'_posts|_postmeta\'). You can put \'JOIN\' as well if you want to exclude complex query', 'db-cache-reloaded'), 'size="100"' ); ?>
	</tr>
	<tr valign="top">
		<?php $this->dbcr_field_text( 'loadstat', __('Load stats template', 'db-cache-reloaded'), 
			'<br/>'.__('It shows resources usage statistics in your template footer. To disable view just leave this field empty.<br/>{timer} - generation time, {queries} - count of queries to DB, {cached} - cached queries, {memory} - memory', 'db-cache-reloaded'), 'size="100"' ); ?>
	</tr>
</table>

<h3><?php _e('Advanced', 'db-cache-reloaded'); ?></h3>
<table class="form-table">
	<tr valign="top">
	<?php 
		$wrapper_msg = __('Wrapper Mode uses different method to load DB Module. It is less efficient (at least one query is not cached; some plugins may increase this number) and a bit slower. It allows to use DB Cache Reloaded along with incompatible plugins, which tries to load its own DB Module. You can try it if your cached query count is zero or -1.', 'db-cache-reloaded');
		if ( version_compare( PHP_VERSION, '5',  '<' ) ) {
		echo '<td colspan="2">';
		printf( __( 'Wrapper Mode requires at least PHP 5, and you are using PHP %s now. Please read the <a href="http://codex.wordpress.org/Switching_to_PHP5">Switching to PHP5</a> article for information how to switch to PHP 5.', 'db-cache-reloaded' ), PHP_VERSION );
		echo '<br/ ><br />', $wrapper_msg, '</td>';
	} elseif ( isset( $this->config['enabled'] ) && $this->config['enabled'] ) {
		echo '<td colspan="2">';
		echo '<input type="hidden" name="options[wrapper]" value="', isset( $this->config['wrapper'] ) && $this->config['wrapper'] ? 1 : 0, '" />';
		if ( isset( $this->config['wrapper'] ) && $this->config['wrapper'] ) {
			_e( 'Wrapper Mode is <strong>Enabled</strong>. In order to disable it, please disable cache first.', 'db-cache-reloaded' );
		} else {
			_e( 'Wrapper Mode is <strong>Disabled</strong>. In order to enable it, please disable cache first.', 'db-cache-reloaded' );
		}
		echo '<br />', $wrapper_msg, '</td>';
	} else { ?>
		<?php $this->dbcr_field_checkbox( 'wrapper', __('Enable Wrapper Mode', 'db-cache-reloaded'), '<br />'.$wrapper_msg ); ?>
	<?php } ?>
	</tr>
</table>

<p class="submit">
	<input class="button" type="submit" name="save" value="<?php _e('Save', 'db-cache-reloaded'); ?>">  
	<input class="button" type="submit" name="clear" value="<?php _e('Clear the cache', 'db-cache-reloaded'); ?>">
	<input class="button" type="submit" name="clearold" value="<?php _e('Clear the expired cache', 'db-cache-reloaded'); ?>">
</p>      
</form>
<p>This plugin has cost me hours of work, if you use it, please donate a token of your appreciation!</p><br/><form style="margin-left:50px;" action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="G463UW5KA8EZ6">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</div>
<?php
	}
	
	// Other functions used on options page
	function dbcr_request( $name, $default=null ) {
		if ( !isset( $_POST[$name]) ) {
			return $default;
		}
		
		return $_POST[$name];
	}
	
	function dbcr_field_checkbox( $name, $label='', $tips='', $attrs='' ) {
		echo '<th scope="row">';
		echo '<label for="options[' . $name . ']">' . $label . '</label></th>';
		echo '<td><input type="checkbox" ' . $attrs . ' name="options[' . $name . ']" value="1" ';
		checked( isset( $this->config[$name] ) && $this->config[$name], true );
		echo '/> ' . $tips . '</td>';
	}
	
	function dbcr_field_text($name, $label='', $tips='', $attrs='') {
		if ( strpos($attrs, 'size') === false ) {
			$attrs .= 'size="30"';
		}
		echo '<th scope="row">';
		echo '<label for="options[' . $name . ']">' . $label . '</label></th>';
		echo '<td><input type="text" ' . $attrs . ' name="options[' . $name . ']" value="' . 
			htmlspecialchars($this->config[$name]) . '"/>';
		echo ' ' . $tips;
		echo '</td>';
	}
	
	function dbcr_field_textarea( $name, $label='', $tips='', $attrs='' ) {
		if ( strpos( $attrs, 'cols' ) === false ) {
			$attrs .= 'cols="70"';
		}
		if ( strpos( $attrs, 'rows' ) === false ) {
			$attrs .= 'rows="5"';
		}
		
		echo '<th scope="row">';
		echo '<label for="options[' . $name . ']">' . $label . '</label></th>';
		echo '<td><textarea wrap="off" ' . $attrs . ' name="options[' . $name . ']">' .
			htmlspecialchars($this->config[$name]) . '</textarea>';
		echo '<br />' . $tips;
		echo '</td>';
	}
}

$wp_db_cache_reloaded = new DBCacheReloaded();

function get_num_cachequeries() {
	global $wpdb, $wp_db_cache_reloaded;
	if ( isset( $wpdb->num_cachequeries ) ) {
		// DB Module loaded
		return $wpdb->num_cachequeries;
	} elseif ( !isset( $wp_db_cache_reloaded->config['enabled'] ) || !$wp_db_cache_reloaded->config['enabled'] ) {
		// Cache disabled
		return 0;
	} else {
		// Probably conflict with another plugin or configuration issue :)
		return -1;
	}
}

function get_num_dml_queries() {
	global $wpdb, $wp_db_cache_reloaded;
	if ( isset( $wpdb->dbcr_num_dml_queries ) ) {
		// DB Module loaded
		return $wpdb->dbcr_num_dml_queries;
	} elseif ( !isset( $wp_db_cache_reloaded->config['enabled'] ) || !$wp_db_cache_reloaded->config['enabled'] ) {
		// Cache disabled
		return 0;
	} else {
		// Probably conflict with another plugin or configuration issue :)
		return -1;
	}
}

/* 
Function to display load statistics
Put in your template <? loadstats(); ?>
*/
function loadstats() {
	global $wp_db_cache_reloaded;

	if ( strlen( $wp_db_cache_reloaded->config['loadstat'] ) > 7 ) {
		$stats['timer'] = timer_stop();
		$replace['timer'] = "{timer}";
		
		$stats['normal'] = get_num_queries();
		$replace['normal'] = "{queries}";
		
		$stats['dml'] = get_num_dml_queries();
		$replace['dml'] = "{dml_queries}";
		
		$stats['cached'] = get_num_cachequeries();
		$replace['cached'] = "{cached}";
		
		if ( function_exists( 'memory_get_usage' ) ) {
			$stats['memory'] = round( memory_get_usage()/1024/1024, 2 ) . 'MB';
		} else {
			$stats['memory'] = 'N/A';
		}
		$replace['memory'] = "{memory}";
		
		$result = str_replace( $replace, $stats, $wp_db_cache_reloaded->config['loadstat'] );
		
		echo $result;
	}
	
	echo "\n<!-- Cached by DB Cache Reloaded Fix -->\n";
}

} // END

?>
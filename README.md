=== DB Cache Reloaded Fix ===
**Contributors:**      ivankristianto  
**Donate link:**       https://www.ivankristianto.com/  
**Tags:**              performance, caching, wp-cache, db-cache, cache  
**Requires at least:** 4.3  
**Tested up to:**      5.0  
**Stable tag:**        2.3  
**License:**           GPLv2 or later  
**License URI:**       http://www.gnu.org/licenses/gpl-2.0.html  
Requires PHP:      5.6

The fastest cache engine for WordPress, that produces cache of database queries with easy configuration. Compatible with WordPress 3.1


## Description 

This plugin caches every database query with given lifetime. It is much faster than other html caching plugins and uses less disk space for caching. Now compatible with WordPress 3.1.

This plugin is based on [DB Cache Reloaded](http://wordpress.org/extend/plugins/db-cache-reloaded/) by sirzooro. I patch it so it have WordPress 3.1 compatibility.

I think you've heard of [WP-Cache](http://wordpress.org/extend/plugins/wp-cache/) or [WP Super Cache](http://wordpress.org/extend/plugins/wp-super-cache/), they are both top plugins for WordPress, which make your site faster and responsive. Forget about them - with DB Cache Reloaded your site will work much faster and will use less disk space for cached files. Your visitors will always get actual information in sidebars and server CPU loads will be as low as possible.

This plugin is a fork of a [DB Cache](http://wordpress.org/extend/plugins/db-cache/) plugin. Because his author did not updated its plugin to WordPress 2.8, I finally (after almost three months since release of WP 2.8) took his plugin and updated it so now it works with newest WordPress version. Additionally I fixed few bugs, cleaned up the code and make it more secure.

This plugin was tested with WordPress 2.8 and never. It may work with earlier versions too - I have not tested. If you perform such tests, let me know of the results.

If you are using WordPress 2.9, please use DB Cache Reloaded version 2.0 or never - versions 1.x are not compatible with WordPress 2.9.

If you are using WordPress 3.0, please use DB Cache Reloaded version 2.1 or never - earlier versions are not compatible with WordPress 3.0.

For Web Developer: 

If you are a web developer fork me on [Github] (https://github.com/ivankristianto/DB-Cache-Reloaded-Fix)

Available translations:

* English
* French (fr_FR) - thanks [InMotion Hosting](http://www.inmotionhosting.com/)
* Polish (pl_PL)
* Italian (it_IT) - thanks [Iacopo](http://www.iacchi.org/)
* Portuguese Brazilian (pt_BR) - thanks Calebe Aires
* Belorussian (be_BY) - thanks FatCow
* Spanish (es_ES) - thanks Dasumo
* Dutch (nl_NL) - thanks Rene
* Turkish (tr_TR) - thanks wolkanca
* Japanese (jp) - thanks wokamoto
* German (de_DE) - thanks [Carsten Tauber](http://greatsolution.de/)
* Chinese (zh_CN and zh_TW) - thanks [企鹅君](http://neverweep.com)


## Installation 

1. Upload `db-cache-reloaded-fix` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure and enjoy :)


## Frequently Asked Questions 


### How do I know my blog is being cached? 

Check your cache directory wp-content/tmp/ for cache files. Check the load statistics in footer.
Also you can set `DBCR_DEBUG` to true in db-cache-reloaded.php file to display as hidden comments on your html page, what queries were loaded from cache and what from mysql.

You can also define `DBCR_SAVEQUERIES` in wp-config.php file - it works similarly as defining `SAVEQUERIES`, but DB Cache Reloaded adds one extra field to the `$wpdb->queries` array - boolean value indicating if query was executed directly (`false`) or loaded from cache (`true`). Of course you can also use some extra code (e.g. some plugin) do display this data.


### What does this plugin do? 

This plugin decreases count of queries to DB, which means that CPU load of your web-server decreases and your blog can serve much more visitors in one moment.


### What is page generation time? 

It is time from request to server (start of generation) and the generated page sent (end of generation). This time depends on server parameters: CPU speed, RAM size and the server load (how much requests it operates at the moment, popularity of sites hosted on the server) and of course it depends on how much program code it needs to operate for page generation.

Let set the fourth parameter as constant (we can't change the program code). So we have only 3: CPU, RAM and popularity.

If you have a powerful server (costs more) it means that will be as low as possible and it can serve for example 100 visitors in one moment without slowing down. And another server (low cost) with less CPU speed and RAM size, which can operate for example 10 visitors in one moment. So if the popularity of your site grows it is needed more time to generate the page. That's why you need to use any caching plugins to decrease the generation time.


### How can I ensure of reducing server usage? 

You can show usage statistics with your custom template in your footer.

Checking count of queries, ensure that other cache plugins are disabled, because you can see cached number.

View the source of your site page, there maybe some code like this at the foot:

`<!-- 00 queries. 00 seconds. -->`

If not, please put these codes in your footer template:

`<!-- <?php echo get_num_queries(); ?> queries. <?php timer_stop(1); ?> seconds. -->`

After using the DB Cache Reloaded, I think you'll find the number of queries reducing a lot.


### Why is DB Cache Reloaded better than WP Super Cache? 

This plugin is based on a fundamentally different principle of caching queries to database instead of full pages, which optimises WordPress from the very beginning and uses less disk space for cache files because it saves only useful information.
It saves information separately and also caches hidden requests to database.

Dmitry Svarytsevych analysed server load graphs of his sites and he can say that the peaks of server load are caused of search engines bots indexing your site (they load much pages practically in one moment). He has tried WP Super Cache to decrease the server loads but it was no help from it. Simply saying WP Super Cache saves any loaded page and much of these pages that are opened only once by bots. His original plugin (DB Cache) roughly saves parts of web-page (configuration, widgets, comments, content) separately, which means that once configuration is cached it will be loaded on every page.

Here is the Google translation of [Dmitry Svarytsevych's article](http://translate.google.com/translate?prev=&hl=uk&u=http%3A%2F%2Fwordpress.net.ua%2Fmaster%2Foptimizaciya-wordpress.html&sl=uk&tl=en) on it.


### Troubleshooting 

Make sure wp-content is writeable by the web server. If not you'll need to [chmod](http://codex.wordpress.org/Changing_File_Permissions) wp-content folder for writing.


### How do I uninstall DB Cache Reloaded? 

1. Disable it at Settings->DB Cache Reloaded page. The plugin will automatically delete all cache files. If something went wrong - delete /wp-content/db.php, /wp-content/db-config.ini and /wp-content/tmp folder manually. When wp-content/db.php file exists, WordPress will use our optimised DB class instead of its own.
1. Deactivate it at plugins page.


### Why plugin shows -1 as number of cached queries? 

By default DB Cache Reloaded shows number of cached queries in hidden HTML comment in page footer. When you see -1 as a cached queries count, this means that caching is not active. Please make sure that you have enabled caching on settings page (DB Cache Reloaded also shows message in admin backend when caching is not enabled). If caching is enabled and you still see -1, this is a result of conflict with other plugin, which wants to replace default `wpdb` class with its own too. You have to disable plugins one by one until you find one which causes this conflict. If you have added custom code to your wp-config.php (or other file) in order to install plugin, please remove (or comment out) it too. When you find conflicting plugin, please notify its author about this problem.

You can also try to enable Wrapper Mode - it may help.


### Why plugin shows 0 as number of cached queries? 

Please check if you have enabled caching. If yes, this may indicate some problems with your plugins - check previous question (Why plugin shows -1 as number of cached queries?) for more details.


### What is the Wrapper Mode? 

When DB Cache Reloaded works in Wrapper Mode, it uses different method to load DB Module. By default plugin assumes that WordPress will be able to load wp-content/db.php. However sometimes other plugin may load wp-includes/wp-db.php file directly, or replace value of `$wpdb` variable. This usually does not allow DB Cache Reloaded to work. When you enable Wrapper Mode, DB Cache Reloaded will load a bit different DB Module, which adds caching and works as a proxy for DB Module loaded by other plugin. Depending on your plugin, everything may work smoothly, or there may be some issues.

Wrapper is also a bit slower then normal method, and does not cache all queries (usually one, but some plugins may increase this number). It also requires at least PHP 5 to work.


### I am a plugin developer. How can I make my plugin compatible with DB Cache Reloaded? 

DB Cache Reloaded uses default WordPress mechanism to load custom version of `wpdb` class - it creates custom wp-content/db.php file. WordPress checks if this file exists, and loads it instead of wp-includes/wp-db.php.

When your plugin includes this class using custom code added to wp-config.php (or any other file), please use `require_wp_db()` to do this, or use similar code to this function body.

When you need to modify `wpdb` class (e.g. by adding or replacing methods), consider deriving your class from the default one (using the `extends` keyword). Another option is to use aggregation - save value of `$wpdb` variable, create object of your class and assign to `$wpdb`. Your class should call methods and access member variables of this saved object, in order to keeps its functionality. Your class should also implement magic methods `__get`, `__set`, `__isset`, `__unset` and `__call`.

Note: when you use derivation, make sure you create object of your class very early, before queries are done. Otherwise number of queries shown in stats will be incorrect.


### How to move default cache directory elsewhere? 

By default DB Cache Reloaded saves cached queries in `wp-content/db-cache-reloaded-fix/cache`. If you want to change this location, please define `DBCR_CACHE_DIR` constant in your `wp-config.php` file - it should point to existing directory. DB Cache Reloaded will use it instead of default location.


## Screenshots 

### 1. No Screenshot available
![No Screenshot available](https://ps.w.org/#-description/assets/screenshot-1.png)



## Changelog 


### 2.3 
* Compatible to WordPress 3.4


### 2.2.4 
* Add French (fr_FR) translation
* Move cache folder from wp-contents/tmp to db-cache-reloaded-fix/cache to fix permission issue
* No cache the complex queries including join query
* Use WP Scheduler to schedule clean the expired cache


### 2.2.3 
* Fix missing lang folder


### 2.2.2 
* Add zh_CN and zh_TW lang


### 2.2.1 
* Fix an error in db.php wrong path in DBCR_PATH thanks to Christian for remind me.


### 2.2 
* Now compatible with WordPress 3.1


### 2.1 
* Make plugin compatible with WordPress 3.0 (single site mode; multisite mode requires additional work);
* Added Dutch translation (thanks Rene);
* Added Turkish translation (thanks wolkanca);
* Added Japanese translation (thanks wokamoto);
* Added German translation (thanks Carsten Tauber);
* Do not cache queries which use `RAND()`;
* Fixed table filter not working all times (thanks poer for pointing this);
* Code cleanup


### 2.0.2 
* Merged last WP 2.9 changes: bump required MySQL version to 4.1.2 for WP 2.9;
* Added Spanish translation (thanks Dasumo)


### 2.0.1 
* Fix: WordPress plugin repository does not add hidden (.name) files to release archives - added workaround


### 2.0 
* Merged changes introduced in WordPress 2.9 (make sure you upgrade DB Cache Reloaded to version 2.0 before upgrading WordPress to version 2.9 - earlier plugin versions are not compatible with WP 2.9!);
* Marked plugin as compatible with WP 2.9;
* Added Wrapper Mode - with it DB Cache Reloaded can work with some incompatible plugins, like WP Tuner;
* Added Belorussian translation (thanks FatCow);
* Fix: added missing .htaccess file;
* Fix: remove created files if plugin cannot be activated successfully


### 1.4.2 
* Marked as compatible with WP 2.8.5


### 1.4.1 
* Added Portuguese Brazilian translation (thanks Calebe Aires)


### 1.4 
* Show -1 as cached queries count when plugin's DB Module is not in use (e.g. because of conflict with other plugin);
* Added check if DB Module version is in sync with plugin version (may not be if someone will upgrade plugin manually without deactivating it or disabling cache);
* Allow to use `DBCR_CACHE_DIR` define to change default cache directory;
* Fix: uninstall function was not executed


### 1.3 
* Further performance improvement;
* Fix: changes done in admin section were not visible in frontend;
* Added icon to use by Ozh' Admin Drop Down Menu plugin


### 1.2 
* Fix: queries were not cached starting from first not cached query;
* Fix: all queries were cached in the same directory instead of dedicated subdirectories;
* Fix: total row count calculation did not work for complex queries (usually observed as no pagination problem);
* Added support for `DBCR_SAVEQUERIES` define to enable extended query logging (as compared to default `SAVEQUERIES` support)


### 1.1 
* Added Polish translation;
* Added Italian translation (thanks Iacopo);
* Show error in admin section when wpdb class already exists (this should not happen, but I got few reports about this);
* Fix: do not cause fatal error when plugin is deleted manually without deactivating it first (in this case wp-content/db.php is left). Instead display error in admin section;
* Added support for custom plugin directory;
* Some performance improvements;
* Show message in admin section when plugin is activated but caching is not enabled


### 1.0.1 
* Fix: statistics are not working


### 1.0 
* Took [DB Cache 0.6](http://wordpress.org/extend/plugins/db-cache/) as a baseline;
* Merged changes done in WordPress 2.9 for the wpdb class (this fixes annoying tags bug);
* Cleaned up code, moved almost everything from global scope to class;
* Secured settings page with nonces;
* Switched to po/mo files for internationalisation


## Upgrade Notice 

Upgrade to this plugin to make your DB cache reloaded working with WordPress 3.1
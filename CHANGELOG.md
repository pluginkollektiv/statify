# Changelog
All notable changes to this project will be documented in this file. This project adheres to [Semantic Versioning](http://semver.org/).

## 1.8.4
* Use same date retrieval for tracking and analysis (#227) (#232)
* Replace input filtering for PHP 8.1 compatibility (#237)
* Minor markup corrections in dashboard widget (#235)
* Tested up to WordPress 6.1

## 1.8.3
* Update documentation links (#204)
* Minor markup fix on settings page (#206)
* Dashboard widget is closeable again (#208) (#209)
* Fix static initialization on multisite with PHP 8 (#210, props @walterebert)
* Tested up to WordPress 5.8

## 1.8.2
* Minor adjustments for the dashboard widget (#197) (#199)
* Tested up to WordPress 5.7

## 1.8.1
* Fix AMP compatibility for Standard and Transitional mode (#181) (#182)
* JavaScript is no longer embedded if request is served by AMP (#181) (#182)
* Always register the action for the cleanup (#184)
* Exclude sitemap calls (WP 5.5+) from tracking (#185) (#186)
* Tested up to WordPress 5.6

## 1.8.0
* Fix date offset in dashboard widget in WP 5.3+ environments with mixed timezones (#167)
* Allow to deactivate the nonce check during JavaScript tracking (#168)
* Add support for "disallowed_keys" option instead of "blacklist_keys" in WordPress 5.5 (#174)
* Add refresh button in the dashboard, increase caching time (#157)

## 1.7.2
* Prevent JavaScript tracking from raising 400 for logged-in users, if tracking is disabled (#159)
* Use `wp_die()` instead of header and exit for AJAX requests (#160)
* Fix 1 day offset between display range and number of days evaluated in top lists (#162)

## 1.7.1
* Fix refresh of the dashboard widget when settings have been changed through the settings page (#147)
* Fix _Cachify_ cache not being flushed after changing JavaScript settings (#152)
* Fix date inconsistency for number of total visits (#150)
* Extend user agent filter for bot detection (#149) (#151)
* Update tooltip library (containing a bugfix in IE 11) (#156)

## 1.7.0
* Fix JavaScript embedding when bots visit before caching (#84) (#86) 
* Fix offset in visitor reporting due to different timezones between PHP and database (#117, props @sophiehuiberts)
* Fix untranslatable support link (#122) (#126, props @arkonisus)
* Add separate settings page and reduced widget backview to widget settings only (#111)
* Add options to track logged in users (#103) (#111)
* Add option to show total visits (#134, props @yurihs)
* Refactored JavaScript tracking to use WP AJAX (#109) (#142)
* Introduced new option to separate display from storage range (#72)
* Automatically add AMP analytics trigger if official AMP PlugIn is installed (#110) (#116, props @tthemann)
* Dashboard widget is now scrollable with dynamic point radius to keep long-term statistics readable (#71) (#101, props @manumeter)
* Improved bot detection (#112) (#125, props @mahype)
* Updated Chartist JS library for dashboard widget (#132)
* Skip tracking for favicon.ico redirects (since WP 5.4) (#144)
* Tested up to WordPress 5.4

## 1.6.3
* Fix compatibility issue with some PHP implementations not populating `INPUT_SERVER`
* Fix failing blacklist check for empty referrers
* JS snippet call properly breaks page generation when tracking is skipped

## 1.6.2
* Fix compatibility issues with JavaScript optimization plugins
* Fix tracking issue if JavaScript tracking is disabled

## 1.6.1
* Scaled datapoint size to number of records in dashboard widget to improve legibility
* Fix display of larger numbers in the y-axis
* Added JS source maps to avoid warnings with developer tools
* Move JS snippet to separate file
* Add JS snippet to output even if tracking is skipped to avoid caching problems
* Improve code style
* Enable nonce-verification in dashboard widget to prevent CSRF

## 1.6.0
* Added hook statify__visit_saved which is fired after a visit was stored in the database.
* Migrated dashboard chart to Chartist.
* Fixed JavaScript tracking not working in some environment which have X-Content-Type: nosniff environment enabled.

## 1.5.3 / 2017-11-28
* Replace javascript library to fixed several problems. #52

## 1.5.2 / 2017-08-15
* Switched to minimal WordPress version 4.7, removed fallback code not needed anymore

## 1.5.1 / 2017-05-04
* Bugfix: Consider filter for skipping tracking correctly if JavaScript tracking is disabled.
* Bugfix: PHP Notice for empty blacklist value.

## 1.5.0 / 2017-03-23
* Switched to minimal PHP version 5.3
* Added more flexible settings for period of data saving and the number of entries shown in top lists
* Added validation of form data before saving settings
* Moved all documentation to [wordpress.org](https://wordpress.org/plugins/statify/).
* Added optional referrer spam protection (can be activated via the Statify settings).
* Improved conformance to the WordPress coding guidelines
* Bugfix for multi-site installations: Don't track network admin url.
* Changed hook name `statify_skip_tracking` to `statify__skip_tracking`.

## 1.4.3 / 15.08.2016
* Corrected tracking and display in Multisite
* Minor CSS fixes in the dashboard widget
* Removed deprecated links and updated URLs for donate and wiki links
* Administrative updates to plugin header and README
* Updated [plugin authors](https://gist.github.com/glueckpress/f058c0ab973d45a72720)

## 1.4.2 / 01.05.2015
* Replace `filter_has_var(INPUT_SERVER)` calls with `isset($_SERVER[])` ([why](https://github.com/wp-stream/stream/issues/254))

## 1.4.1 / 29.04.2015
* Renew the tracking mechanism

## 1.4.0 / 16.04.2015
* WordPress 4.2 support
* Plugin-wide code refactoring
* Translations for English and Russian
* [GitHub Repository](https://github.com/pluginkollektiv/statify)

## 1.3.0 / 28.04.2014
* Sourcecode optimization for plugin-finalization

## 1.2.8 / 19.04.2014
* JavaScript-Snippet: Relative Path for HTTP(S)-calls

## 1.2.7 / 09.04.2014
* Support for WordPress 3.9
* Correction of dashboard links (if WordPress is in subfolder)

## 1.2.6 / 12.12.2013
* Optimization for WordPress 3.8
* Control of tracking via `statify_skip_tracking`

## 1.2.5 / 22.08.2013
* Migration of chart-software

## 1.2.4 / 06.08.2013
* Compatibility to WordPress 3.6

## 1.2.3 / 06.06.2013
* Additional protection of PHP classes against direct access
* Replacement for Deprecated [User Levels](http://codex.wordpress.org/Roles_and_Capabilities#User_Levels)

## 1.2.2 / 14.03.2013
* No-Cache and No-Content Header for optional Count JavaScript

## 1.2.1 / 18.12.2012
* Additional periods (up to one year) for statistics
* WordPress 3.4 as requirement

## 1.2 / 29.11.2012
* Specially for Chrome Browser developed Statify App (discontinued)
* Fix for introduced XML-RPC-interface

## 1.1 / 23.11.2012
* WordPress 3.5 Support
* Interface via XML-RPC
* Refactoring of Code Base
* Revision of Online Documentation
* Optional Tracking via JavaScript for Caching-Plugins

## 1.0 / 12.06.2012
* WordPress 3.4 Support
* [Official Plugin Website](http://statify.de "Statify WordPress Stats")
* Uncompressed Version of Source Codes

## 0.9 / 23.12.2011
* Xmas Edition

## 0.8 / 14.12.2011
* Support für WordPress 3.3
* Display of Dashboard Widgets also for authors
* Direct Link to settings on dashboard
* Filtering der Targets/Referrer on the current date

## 0.7 / 05.07.2011
* Replacement of Statistic Days
* Recolouring of Statistics Mark
* Ignoring XML-RPC-Requests

## 0.6 / 22.06.2011
* Support for WordPress 3.2
* Support for WordPress Multisite
* Adjustment of redundant URL parameters of target pages
* Interaktive Statistics with further informations

## 0.5 / 15.05.2011
* Fix: query for missing referrer in dashboard widget

## 0.4 / 16.03.2011
* Statify goes online

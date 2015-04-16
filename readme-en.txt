=== Statify ===
Contributors: sergej.mueller
Tags: stats, analytics, privacy, dashboard
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZAQUT9RLPW8QN
Requires at least: 3.9
Tested up to: 4.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html



Visitor statistics for WordPress with focus on data protection, transparancy and clarity. Perfect as a widget in your WordPress Dashboard.

== Description ==

The free and add-free plugin Statify pursues a simple objective: to provide a straightforward and compact access to the number of site views. No frills. No Cookies. No third party. No storage of personal data. No endless data privacy statements.

= Dashboard-Widget =
Less is more: An interactive chart is followed by lists of the most common reference sources and target pages. The period of statistics and length of lists can be set directly in the dashboard widget.


= Datenschutz =
I direct comparison to statistics services such as Google Analytics, WordPress.com Stats and Piwik Statify doesn't process and store personal data as e.g. IP addresses – Statify counts site views, not visitors.

Absolute privacy compliance coupled with transparent procedures: A locally in WordPress created database table consists of only 4 fields (ID, date, source, target) and can be viewed at any time, cleaned up and cleared by the administrator.


= Filter =
*Statify* logs every page view in the front end of the WordPress installation. Excluded are preview, feed, resource views as well as access by registered users. More details about the options and functions in the [Online Manual](http://playground.ebiene.de/statify-wordpress-statistik/).

= Caching-Plugins =
For compatibility with caching plugins like Cachify Statify offers an optional switchable tracking via JavaScript. This function allows reliable count of cached blog pages.

= Support =
I will be glad to answer you question about the plugin by mail. Please follow the [guidelines](https://plus.google.com/+SergejMüller/posts/Ex2vYGN8G2L).


= Requirements =
* PHP 5.2.4
* WordPress from 3.9


= Support =
* Via [Flattr](https://flattr.com/thing/148966/)
* Via [PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZAQUT9RLPW8QN)


= German Manual =
* [Statify: Statistik für WordPress](http://playground.ebiene.de/statify-wordpress-statistik/)


= Website =
* [statify.de](http://statify.de)


= Author =
* [Twitter](https://twitter.com/wpSEO "Sergej Müller auf Twitter")
* [Google+](https://plus.google.com/110569673423509816572 "Sergej Müller auf Google+")
* [Plugins](http://wpcoder.de "WordPress Plugins")



== Changelog ==

= 1.3.0 =
* Sourcecode optimization for plugin-finalization

= 1.2.8 =
* JavaScript-Snippet: Relative Path for HTTP(S)-calls

= 1.2.7 =
* Support for WordPress 3.9
* Correction of dashboard links (if WordPress is in subfolder)

= 1.2.6 =
* Optimization for WordPress 3.8
* Control of tracking via `statify_skip_tracking`

= 1.2.5 =
* Migration of chart-software 

= 1.2.4 =
* Compatibility to WordPress 3.6

= 1.2.3 =
* Additional protection of PHP classes against direct access
* Replacement for Deprecated [User Levels](http://codex.wordpress.org/Roles_and_Capabilities#User_Levels)

= 1.2.2 =
* No-Cache and No-Content Header for optional Count JavaScript

= 1.2.1 =
* Additional periods (up to one year) for statistics
* WordPress 3.4 as requirement

= 1.2 =
* Spezially for Chrome Browser developped [Statify App](http://playground.ebiene.de/statify-wordpress-statistik/#chrome_app)
* Fix for introduced XML-RPC-interface

= 1.1 =
* WordPress 3.5 Support
* Interface via XML-RPC
* Refactoring of Code Base
* Revision of Online Documentation
* Optional Tracking via JavaScript for Caching-Plugins

= 1.0 =
* WordPress 3.4 Support
* [Official Plugin Website](http://statify.de "Statify WordPress Stats")
* Uncompressed Version of Source Codes

= 0.9 =
* Xmas Edition

= 0.8 =
* Support für WordPress 3.3
* Display of Dashboard Widgets also for authors
* Direct Link to settings on dashboard
* Filtering der Targets/Referrer on the current date

= 0.7 =
* Replacement of Statistic Days
* Recolouring of Statistics Mark
* Ignoring XML-RPC-Requests

= 0.6 =
* Support for WordPress 3.2
* Support for WordPress Multisite
* Adjustment of redundant URL parameters of target pages
* Interaktive Statistics with further informations

= 0.5 =
* Fix: query for missing referrer in dashboard widget

= 0.4 =
* Statify goes online



== Screenshots ==

1. Statify Dashboard Widget
2. Statify Dashboard Widget Options
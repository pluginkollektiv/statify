# Changelog
All notable changes to this project will be documented in this file. This project adheres to [Semantic Versioning](http://semver.org/).

## 1.5.0 / ToDo
* Add referrer spam protection.
* Change of code style, now it is WP Codex fit.
* Multisite fix to don't track network admin url.
* Change hook name `statify_skip_tracking` to `statify__skip_tracking`.

## 1.4.3 / 2016-08-15
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

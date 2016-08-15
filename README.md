# Statify #
* Contributors:      pluginkollektiv
* Donate link:       https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LG5VC9KXMAYXJ
* Tags:              stats, analytics, privacy, dashboard
* Requires at least: 3.9
* Tested up to:      4.6
* Stable tag:        trunk
* License:           GPLv3 or later
* License URI:       https://www.gnu.org/licenses/gpl-3.0.html

Visitor statistics for WordPress with focus on _data protection_, _transparancy_ and _clarity_. Perfect as a widget in your WordPress Dashboard.

## Description ##
The free and add-free plugin [Statify](http://statify.de) pursues a simple objective: to provide a straightforward and compact access to the number of site views.

No frills. No Cookies. No third party. No storage of personal data. No endless data privacy statements.

An interactive chart is followed by lists of the most common reference sources and target pages. The period of statistics and length of lists can be set directly in the dashboard widget.

### Data Privacy ###
In direct comparison to statistics services such as *Google Analytics*, *WordPress.com Stats* and *Piwik* *Statify* doesn't process and store personal data as e.g. IP addresses – *Statify* counts site views, not visitors.
Absolute privacy compliance coupled with transparent procedures: A locally in WordPress created database table consists of only 4 fields (ID, date, source, target) and can be viewed at any time, cleaned up and cleared by the administrator.

> ### Deutsch ###
> Datenschutzkonformes, anonymes und kompaktes Statistik-Plugin für WordPress.
Statify kommt ohne jegliche Cookies und versteckte Zähl-Pixel aus. Die Dashboard-Statistik greift auf momentane Daten der Datenbanktabelle zu (4 Minuten Zwischenspeicherung) und liefert somit den Live-Zustand der Seitenzugriffe aus. Einsatzbereit auch in WordPress-Multisite.
> For German users: [Plugin-Wiki in Deutsch](https://github.com/pluginkollektiv/statify)

### Compatibility ###
For compatibility with caching plugins like [Cachify](http://cachify.de) *Statify*  offers an optional switchable tracking via JavaScript. This function allows reliable count of cached blog pages.

### Memory Usage ###
* Backend: ~ 0.2 MB
* Frontend: ~ 0.1 MB

### Credits ###
* Author: [Sergej Müller](https://sergejmueller.github.io/)
* Maintainers: [pluginkollektiv](http://pluginkollektiv.org/)
* Contributor: [Bego Mario Garde](https://garde-medienberatung.de)

## Installation ##
* If you don’t know how to install a plugin for WordPress, [here’s how](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

### Requirements ###
* PHP 5.2.4
* WordPress 3.9

## Changelog ##

### 1.4.3 / 2016-08-15 ###
* Corrected tracking and display in Multisite
* Minor CSS fixes in the dashboard widget
* Removed deprecated links and updated URLs for donate and wiki links
* Administrative updates to plugin header and README
* Updated [plugin authors](https://gist.github.com/glueckpress/f058c0ab973d45a72720)

### 1.4.2 / 01.05.2015 ###
* Replace `filter_has_var(INPUT_SERVER)` calls with `isset($_SERVER[])` ([why](https://github.com/wp-stream/stream/issues/254))

### 1.4.1 / 29.04.2015 ###
* Renew the tracking mechanism

### 1.4.0 / 16.04.2015 ###
* WordPress 4.2 support
* Plugin-wide code refactoring
* Translations for English and Russian
* [GitHub Repository](https://github.com/pluginkollektiv/statify)

### 1.3.0 / 28.04.2014 ###
* Sourcecode optimization for plugin-finalization

For the complete changelog, check out our [GitHub repository](https://github.com/pluginkollektiv/statify).

## Upgrade Notice ##

### 1.4.3 ###

This is mainly a maintenance release ensuring compatibility with the latest version of WordPress. Works well with Multisite too!

## Screenshots ##
1. Statify dashboard widget
2. Statify dashboard widget options

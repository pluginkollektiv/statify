=== Statify ===
Contributors: sergej.mueller
Tags: stats, analytics, privacy, dashboard
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZAQUT9RLPW8QN
Requires at least: 3.9
Tested up to: 4.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html



Visitor statistics for WordPress with focus on _data protection_, _transparancy_ and _clarity_. Perfect as a widget in your WordPress Dashboard.



== Description ==
The free and add-free plugin [Statify](http://statify.de) pursues a simple objective: to provide a straightforward and compact access to the number of site views.

No frills. No Cookies. No third party. No storage of personal data. No endless data privacy statements.

An interactive chart is followed by lists of the most common reference sources and target pages. The period of statistics and length of lists can be set directly in the dashboard widget.

> For German users: [Plugin-Beschreibung in Deutsch](https://github.com/sergejmueller/statify)


= Data Privacy =
I direct comparison to statistics services such as *Google Analytics*, *WordPress.com Stats* and *Piwik* *Statify* doesn't process and store personal data as e.g. IP addresses – *Statify* counts site views, not visitors.

Absolute privacy compliance coupled with transparent procedures: A locally in WordPress created database table consists of only 4 fields (ID, date, source, target) and can be viewed at any time, cleaned up and cleared by the administrator.


= Compatibility =
For compatibility with caching plugins like [Cachify](http://cachify.de) *Statify*  offers an optional switchable tracking via JavaScript. This function allows reliable count of cached blog pages.


= Requirements =
* PHP 5.2.4
* WordPress ab 3.9


= Memory Usage =
* Backend: ~ 0,2 MB
* Frontend: ~ 0,1 MB


= Languages =
* German
* English
* Русский


= Contributors =
* [Caspar Hübinger](http://glueckpress.com)
* [Bego Mario Garde](https://garde-medienberatung.de)


= Author =
* [Twitter](https://twitter.com/wpSEO)
* [Google+](https://plus.google.com/110569673423509816572)
* [Plugins](http://wpcoder.de "WordPress Plugins")



== Changelog ==

= 1.3.0 =
* Sourcecode-Optimierung für die Plugin-Finalisierung

= 1.2.8 =
* JavaScript-Snippet: Relativer Pfad für HTTP(S)-Aufrufe

= 1.2.7 =
* Unterstützung zu WordPress 3.9
* Korrektur der Dashboard-Links (wenn WordPress im Unterordner)

= 1.2.6 =
* Optimierung für WordPress 3.8
* Steuerung des Trackings via `statify_skip_tracking`

= 1.2.5 =
* Umstellung der Diagramm-Software

= 1.2.4 =
* Kompatibilität zu WordPress 3.6

= 1.2.3 =
* Zusätzliche Absicherung der PHP-Klassen vor direkten Aufrufen
* Ersatz für Deprecated [User Levels](http://codex.wordpress.org/Roles_and_Capabilities#User_Levels)

= 1.2.2 =
* No-Cache und No-Content Header für das optionale Zähl-JavaScript

= 1.2.1 =
* Zusätzliche Zeiträume (bis zu einem Jahr) für Statistik
* WordPress 3.4 als Systemanforderung

= 1.2 =
* Speziell für Chrome-Browser entwickelte [Statify App](http://playground.ebiene.de/statify-wordpress-statistik/#chrome_app)
* Fix für eingeführte XML-RPC-Schnittstelle

= 1.1 =
* WordPress 3.5 Support
* Schnittstelle via XML-RPC
* Refactoring der Code-Basis
* Überarbeitung der Online-Dokumentation
* Optionales Tracking via JavaScript für Caching-Plugins

= 1.0 =
* WordPress 3.4 Support
* [Offizielle Plugin-Website](http://statify.de "Statify WordPress Stats")
* Unkomprimierte Version des Source Codes

= 0.9 =
* Xmas Edition

= 0.8 =
* Unterstützung für WordPress 3.3
* Anzeige des Dashboard-Widgets auch für Autoren
* Direkter Link zu den Einstellungen auf dem Dashboard
* Filterung der Ziele/Referrer auf den aktuellen Tag

= 0.7 =
* Umsortierung der Statistiktage
* Umfärbung der Statistikmarkierung
* Ignorierung der XMLRPC-Requests

= 0.6 =
* WordPress 3.2 Unterstützung
* Support für WordPress Multisite
* Bereinigung überflüssiger URL-Parameter bei Zielseiten
* Interaktive Statistik mit weiterführenden Informationen

= 0.5 =
* Fix: Abfrage für fehlende Referrer im Dashboard Widget

= 0.4 =
* Statify geht online



== Screenshots ==

1. Statify dashboard widget
2. Statify dashboard widget options
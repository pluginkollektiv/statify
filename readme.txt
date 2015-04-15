=== Statify ===
Contributors: sergej.mueller
Tags: stats, analytics, privacy, dashboard
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZAQUT9RLPW8QN
Requires at least: 3.9
Tested up to: 4.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html



Besucherstatistik in WordPress mit Schwerpunkten Datenschutz, Transparenz und Übersichtlichkeit. Ideal für Dashboard.



== Description ==

Das kostenlose Plugin *Statify* verfolgt ein simples Ziel: Zugriffszahlen der WordPress-Seiten blitzschnell und kompakt zugänglich machen. Ohne Schnickschnack. Ohne meterlange Datenschutzerklärungen.


= Dashboard-Widget =
Weniger ist mehr: Den aktuellen Verlauf der Seitenaufrufe präsentiert das Statistik-Plugin in Form eines interaktiven [Diagramms](https://wordpress.org/plugins/statify/screenshots/). Der Zeitskala folgen Listen mit häufigsten Verweisquellen (Referrer) und Zielseiten (Targets). Praktisch: Der Statistikzeitraum sowie die Listenlänge lassen sich direkt im Dashboard-Widget konfigurieren.


= Datenschutz =
In unmittelbarem Vergleich zu Statistik-Diensten wie *Google Analytics*, *WordPress.com Stats* und *Piwik* verarbeitet und speichert *Statify* keinerlei personenbezogene Daten wie z.B. IP-Adressen – *Statify* zählt Seitenaufrufe, keine Besucher. Absolute Datenschutzkonformität gepaart mit transparenter Arbeitsweise: Eine lokal in WordPress angelegte Datenbanktabelle besteht aus nur 4 Feldern (ID, Datum, Quelle, Ziel) und kann vom Administrator jederzeit eingesehen, bereinigt und geleert werden.


= Filter =
*Statify* protokolliert jeden Seitenaufruf im Frontend der WordPress-Installation. Ausgeschlossen sind Preview-, Feed-, Ressourcen-Ansichten sowie Zugriffe durch angemeldete Nutzer. Mehr Einzelheiten zu Optionen und Funktionen im [Online-Handbuch](http://playground.ebiene.de/statify-wordpress-statistik/).


= Caching-Plugins =
Für die Kompatibilität mit Caching-Plugins wie [Cachify](http://wordpress.org/extend/plugins/cachify/) verfügt *Statify* über ein optional zuschaltbares Tracking via JavaScript-Snippet. Diese Methode erlaubt eine zuverlässige Zählung bei gecachten Blogseiten.


= Support =
Fragen rund um das Plugin werden gern per E-Mail beantwortet. Beachtet auch die [Guidelines](https://plus.google.com/+SergejMüller/posts/Ex2vYGN8G2L).


= Systemanforderungen =
* PHP 5.2.4
* WordPress ab 3.9


= Unterstützung =
* Per [Flattr](https://flattr.com/thing/148966/)
* Per [PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZAQUT9RLPW8QN)


= Handbuch =
* [Statify: Statistik für WordPress](http://playground.ebiene.de/statify-wordpress-statistik/)


= Website =
* [statify.de](http://statify.de)


= Autor =
* [Twitter](https://twitter.com/wpSEO "Sergej Müller auf Twitter")
* [Google+](https://plus.google.com/110569673423509816572 "Sergej Müller auf Google+")
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

1. Statify Dashboard Widget
2. Statify Dashboard Widget Optionen
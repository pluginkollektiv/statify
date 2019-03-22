# Statify #
* Contributors:      pluginkollektiv
* Donate link:       https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW
* Tags:              analytics, dashboard, pageviews, privacy, statistics, stats, visits, web stats, widget
* Requires at least: 4.7
* Tested up to:      5.1
* Requires PHP:      5.2
* Stable tag:        1.6.3
* License:           GPLv3 or later
* License URI:       https://www.gnu.org/licenses/gpl-3.0.html

Visitor statistics for WordPress with focus on data protection, transparency and clarity. Perfect as a widget in your WordPress Dashboard.


## Description ##
Statify provides a straightforward and compact access to the number of site views. It is privacy-friendly as it uses neither cookies nor a third party.

An interactive chart is followed by lists of the most common reference sources and target pages. The period of statistics and length of lists can be set directly in the dashboard widget.

### Data Privacy ###
In direct comparison to statistics services such as *Google Analytics*, *WordPress.com Stats* and *Matomo (Piwik)* *Statify* doesn't process and store personal data as e.g. IP addresses – *Statify* counts site views, not visitors.

Absolute privacy compliance coupled with transparent procedures: A locally in WordPress created database table consists of only four fields (ID, date, source, target) and can be viewed at any time, cleaned up and cleared by the administrator.

Due to this tracking approach, Statify is 100% compliant with GDPR and serves as an lightweight alternative to other tracking services.

### Display of the widget ###
The plugin configuration can be changed directly in the *Statify* Widget on the dashboard by clicking the *Configure* link.

The amount of links shown in the *Statify* Widget can be set as well as the option to only count views from today. Of course, older entries are not deleted when changing this setting.

The statistics for the dashboard widget are cached for four minutes.

### Period of data saving ###
*Statify* stores the data only for a limited period (default: two weeks), longer intervals can be selected as option in the widget. Data which is older than the selected period is deleted by a daily cron job.

An increase in the database volume can be expected because all statistic values are collected and managed in the local WordPress database (expecially if you increase the period of data saving).

### JavaScript tracking for caching compatibility ###
For compatibility with caching plugins like [Cachify](http://cachify.de) *Statify* offers an optional switchable tracking via JavaScript. This function allows reliable count of cached blog pages.

For this to work correctly, the active theme has to call `wp_footer()`, typically in a file named `footer.php`.

### Skip tracking for spam referrers ###
The comment blacklist can be enabled to skip tracking for views with a referrer URL listed in comment blacklist, i. e. which considered as spam.

### Support ###
If you've problems or think you’ve found a bug (e.g. you’re experiencing unexpected behavior), please post at the [support forums](https://wordpress.org/support/plugin/statify).

### Contribute ###
* Active development of this plugin is handled [on GitHub](https://github.com/pluginkollektiv/statify).
* Pull requests for documented bugs are highly appreciated.
* If you want to help us translate this plugin you can do so [on WordPress Translate](https://translate.wordpress.org/projects/wp-plugins/statify).


## Frequently Asked Questions ##

### Which areas are excluded from counting? ###
*Statify* does not count the following views:

* feeds
* trackbacks
* searches
* previews
* views by logged in users
* error pages

This behavior can be modified with the `statify__skip_tracking` hook.

### Can further visitor data be recorded? ###
Some plugin users want to capture additional visitor data, e.g. name of the device and resolution.
*Statify* counts exclusively page views and no visitors, the desired data acquisition is not a question.

### How to change who can see the Dashboard widget? ###
Per default only administrators can see the widget. This can be changed with the `statify__user_can_see_stats` hook.

Example:
`
add_filter(
    'statify__user_can_see_stats',
    '__return_true'
);
`

has to be added to the theme's `functions.php` and adapted to your needs. This example would allow all users to see the widget.

Editing the configuration is still limited to users with `edit_dashboard` capability.

### How to skip tracking for defined users or pages ###
The conditions for tracking views can be customized according to page type and user capabilities by using the hook `statify__skip_tracking`.

Example:
`
add_filter(
    'statify__skip_tracking',
    function() {
        if ( condition ) {
            return true;
        }

        return false;
    }
);
`

has to be added to the theme's `functions.php`. The condition has modified such that the method returns true if and only if the view should be ignored.

### How to extend this plugin? ###

* [Statify - Extended Evaluation](https://wordpress.org/plugins/extended-evaluation-for-statify/) for a more detailed evaluation and export function
* [Statify Widget](https://wordpress.org/plugins/statify-widget/) to display most popular content
* [Statify Blacklist](https://wordpress.org/plugins/statify-blacklist/) to define a customized blacklist for referrer spam


## Changelog ##
You can find the full changelog in [our GitHub repository](https://github.com/pluginkollektiv/statify/blob/master/CHANGELOG.md).

### 1.6.3
* Fix compatibility issue with some PHP implementations not populating `INPUT_SERVER`
* Fix failing blacklist check for empty referrers
* JS snippet call properly breaks page generation when tracking is skipped

### 1.6.2
* Fix compatibility issues with JavaScript optimization plugins
* Fix tracking issue if JavaScript tracking is disabled

### 1.6.1
* Scaled datapoint size to number of records in dashboard widget to improve legibility
* Fix display of larger numbers in the y-axis
* Added JS source maps to avoid warnings with developer tools
* Move JS snippet to separate file
* Add JS snippet to output even if tracking is skipped to avoid caching problems
* Improve code style
* Enable nonce-verification in dashboard widget to prevent CSRF

### 1.6.0
* Added hook statify__visit_saved which is fired after a visit was stored in the database.
* Migrated dashboard chart to Chartist.
* Fixed JavaScript tracking not working in some environment which have X-Content-Type: nosniff environment enabled.

For the complete changelog, check out our [GitHub repository](https://github.com/pluginkollektiv/statify).


## Upgrade Notice ##

### 1.6.3 ###
This bugfix release is recommended for all users.
It fixes completely broken tracking with some PHP implementations. Sorry for that, we might owe you a couple of visitors.

### 1.6.2 ###
This bugfix release is recommended for all users.

### 1.6.1 ###
This bugfix release is recommended for all users.


## Screenshots ##
1. Statify dashboard widget
2. Statify dashboard widget options

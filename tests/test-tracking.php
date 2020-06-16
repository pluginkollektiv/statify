<?php
/**
 * Statify tracking tests.
 *
 * @package Statify
 */

/**
 * Class Test_Tracking.
 * Tests for non-JavaScript tracking and tracking-related mechanisms.
 */
class Test_Tracking extends WP_UnitTestCase {
	use Statify_Test_Support;

	/**
	 * Set up the test case.
	 */
	public function setUp() {
		parent::setUp();

		// "Install" Statify, i.e. create tables and options.
		Statify_Install::init();
	}

	/**
	 * Test case for non-JS tracking.
	 */
	public function test_default_tracking() {
		global $_SERVER;

		// Initialize Statify with default configuration: no JS tracking, no logged-in users.
		$this->init_statify_tracking();

		// Check if actions are registered.
		$this->assertNotFalse(
			has_action(
				'template_redirect',
				array( 'Statify_Frontend', 'track_visit' )
			),
			'Statify tracking action not registered'
		);

		// Track a valid request.
		$_SERVER['REQUEST_URI']     = '/';
		$_SERVER['HTTP_REFERER']    = 'https://statify.pluginkollektiv.org/';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36';

		Statify_Frontend::track_visit();
		$stats = $this->get_stats();

		$this->assertNotNull( $stats, 'Stats should be filled after tracking' );

		$this->assertEquals( 1, count( $stats['visits'] ), 'Unexpected number of days with visits' );
		$this->assertEquals( ( new DateTime() )->format( 'Y-m-d' ), $stats['visits'][0]['date'], 'Unexpected date of tracking' );
		$this->assertEquals( 1, $stats['visits'][0]['count'], 'Unexpected visit count' );

		$this->assertEquals( 1, count( $stats['target'] ), 'Unexpected number of targets' );
		$this->assertEquals( '', $stats['target'][0]['url'], 'Unexpected target URL' );
		$this->assertEquals( 1, $stats['target'][0]['count'], 'Unexpected target count' );
		$this->assertEquals( 1, $stats['visits'][0]['count'], 'Unexpected visit count' );

		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 'https://statify.pluginkollektiv.org/', $stats['referrer'][0]['url'], 'Unexpected referrer URL' );
		$this->assertEquals( 'statify.pluginkollektiv.org', $stats['referrer'][0]['host'], 'Unexpected referrer hostname' );
		$this->assertEquals( 1, $stats['referrer'][0]['count'], 'Unexpected referrer count' );

		// And a second try...
		$_SERVER['REQUEST_URI']     = '/';
		$_SERVER['HTTP_REFERER']    = 'https://statify.pluginkollektiv.org/documentation/';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:77.0) Gecko/20100101 Firefox/77.0';
		Statify_Frontend::track_visit();

		$stats = $this->get_stats();
		$this->assertNotNull( $stats, 'Stats should be filled after tracking' );

		$this->assertEquals( 1, count( $stats['visits'] ), 'Unexpected number of days with visits' );
		$this->assertEquals( ( new DateTime() )->format( 'Y-m-d' ), $stats['visits'][0]['date'], 'Unexpected date of tracking' );
		$this->assertEquals( 2, $stats['visits'][0]['count'], 'Unexpected visit count' );

		$this->assertEquals( 1, count( $stats['target'] ), 'Unexpected number of targets' );
		$this->assertEquals( '', $stats['target'][0]['url'], 'Unexpected target URL' );
		$this->assertEquals( 2, $stats['target'][0]['count'], 'Unexpected target count' );

		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 'https://statify.pluginkollektiv.org/', $stats['referrer'][0]['url'], 'Unexpected referrer URL' );
		$this->assertEquals( 'statify.pluginkollektiv.org', $stats['referrer'][0]['host'], 'Unexpected referrer hostname' );
		$this->assertEquals( 2, $stats['referrer'][0]['count'], 'Unexpected referrer count' );

		// Request to invalid target should not be tracked.
		$_SERVER['REQUEST_URI'] = '';
		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertEquals( 2, $stats['visits'][0]['count'], 'Unexpected visit count' );

		// Internal referrer should be cleared + check permalink with structure.
		$this->set_permalink_structure( '/%postname%/' );
		$_SERVER['REQUEST_URI']  = '/?foo=bar';
		$_SERVER['HTTP_REFERER'] = home_url();

		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertEquals( 3, $stats['visits'][0]['count'], 'Unexpected visit count' );
		$this->assertEquals( 2, count( $stats['target'] ), 'Unexpected number of targets' );
		$this->assertEquals( '/', $stats['target'][1]['url'], 'Unexpected target URL' );
		$this->assertEquals( 1, $stats['target'][1]['count'], 'Unexpected target count' );
		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 2, $stats['referrer'][0]['count'], 'Unexpected referrer count' );
		$this->set_permalink_structure( '' );

		// If JavaScript tracking is enabled, the regular request should not be tracked.
		$_SERVER['REQUEST_URI'] = '/';
		$this->init_statify_tracking( true, false );
		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertEquals( 3, $stats['visits'][0]['count'], 'Unexpected visit count' );
	}

	/**
	 * Test case for non-js tracking with built-in skip conditions (except bots and configurable features).
	 */
	public function test_skip_tracking() {
		global $_SERVER;
		global $wp_query;

		// Initialize Statify with default configuration: no JS tracking, no logged-in users.
		$this->init_statify_tracking();

		// Basically a valid request.
		$_SERVER['REQUEST_URI']     = '/';
		$_SERVER['HTTP_REFERER']    = 'https://statify.pluginkollektiv.org/';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36';

		$wp_query->is_robots = true;
		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertNull( $stats, 'Robots should not be tracked' );

		$wp_query->is_robots    = false;
		$wp_query->is_trackback = true;
		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertNull( $stats, 'Trackbacks should not be tracked.' );

		$wp_query->is_trackback = false;
		$wp_query->is_preview   = true;
		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertNull( $stats, 'Previews should not be tracked.' );

		$wp_query->is_preview = false;
		$wp_query->is_404     = true;
		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertNull( $stats, '404 should not be tracked.' );

		$wp_query->is_404  = false;
		$wp_query->is_feed = true;
		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertNull( $stats, 'Feeds should not be tracked.' );

		// Favicon is available for WP 5.4 and above only.
		$wp_query->is_feed = false;
		if ( function_exists( 'is_favicon' ) ) {
			$wp_query->is_favicon = true;
			Statify_Frontend::track_visit();
			$stats = $this->get_stats();
			$this->assertNull( $stats, 'Favicons should not be tracked.' );
			$wp_query->is_favicon = false;
		}
	}

	/**
	 * Test tracking exclusions for bots.
	 */
	public function test_bot_tracking() {
		global $_SERVER;

		// Initialize Statify with default configuration: no JS tracking, no logged-in users.
		$this->init_statify_tracking();

		$bot_uas = array(
			// Google Bots.
			'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
			'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
			'AdsBot-Google (+http://www.google.com/adsbot.html)',
			'AdsBot-Google-Mobile-Apps',
			// Bing Bots.
			'Mozilla/5.0 (compatible; Bingbot/2.0; +http://www.bing.com/bingbot.htm)',
			'msnbot/2.0b (+http://search.msn.com/msnbot.htm)',
			'Mozilla/5.0 (Windows Phone 8.1; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; NOKIA; Lumia 530) like Gecko (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
			// Yahoo Slurp.
			'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)',
			// DUckDuckGo Bot.
			'DuckDuckBot/1.0; (+http://duckduckgo.com/duckduckbot.html)',
			// Baidu Spider.
			'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
			'Baiduspider+(+http://www.baidu.com/search/spider.htm)',
			// Yandex Bots.
			'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
			'Mozilla/5.0 (compatible; YandexBlogs/0.99; robot; +http://yandex.com/bots)',
			'Mozilla/5.0 (iPhone; CPU iPhone OS 8_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B411 Safari/600.1.4 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
			// Sogou Spider.
			'Sogou web spider/4.0(+http://www.sogou.com/docs/help/webmasters.htm#07)',
			// Exabot.
			'Mozilla/5.0 (compatible; Konqueror/3.5; Linux) KHTML/3.5.5 (like Gecko) (Exabot-Thumbnails)',
			'Mozilla/5.0 (compatible; Exabot/3.0; +http://www.exabot.com/go/robot)',
			// Facebook.
			'facebot',
			'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
			// Alexa crawler.
			'ia_archiver (+http://www.alexa.com/site/help/webmasters; crawler@alexa.com)',
			// Script clients.
			'curl/7.69.1',
			'python-requests/2.22.0',
			'Python-urllib/3.8',
			'Wget/1.20.3 (linux-gnu)',
			// Monitoring tools.
			'check_http/v2.2 (monitoring-plugins 2.2)',
			'Mozilla/5.0 (compatible; PRTG Network Monitor (www.paessler.com); Windows)',
		);

		// Basically a valid request.
		$_SERVER['REQUEST_URI']  = '/';
		$_SERVER['HTTP_REFERER'] = 'https://statify.pluginkollektiv.org/';

		foreach ( $bot_uas as $bot_ua ) {
			$_SERVER['HTTP_USER_AGENT'] = $bot_ua;

			Statify_Frontend::track_visit();
			$stats = $this->get_stats();
			$this->assertNull( $stats, 'Bot exclusion failed for user agent: ' . $bot_ua );
		}
	}

	/**
	 * Test tracking exclusions for blacklisted referrers.
	 */
	public function test_referer_blacklist() {
		global $_SERVER;

		// Define a blacklist.
		update_option(
			'blacklist_keys',
			"example.com\nstatify.pluginkollektiv.org\nexample.net"
		);

		$this->init_statify_tracking( false, false, true );

		// Basically a valid request.
		$_SERVER['REQUEST_URI']     = '/';
		$_SERVER['HTTP_REFERER']    = 'https://statify.pluginkollektiv.org/';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36';

		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertNull( $stats, 'Tracking for blacklisted referrer succeeded' );

		$this->init_statify_tracking();
		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertNotNull( $stats, 'Blacklist evaluated when not enabled' );
	}

	/**
	 * Test evaluation of the statify__skip_tracking hook.
	 */
	public function test_skip_tracking_hook() {
		global $_SERVER;
		global $wp_query;

		$this->init_statify_tracking();

		// A valid request that should be tracked.
		$_SERVER['REQUEST_URI']     = '/';
		$_SERVER['HTTP_REFERER']    = 'https://statify.pluginkollektiv.org/';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36';

		$capture       = null;
		$filter_result = null;

		add_filter(
			'statify__skip_tracking',
			function ( $previous_result ) use ( &$capture, &$filter_result ) {
				$capture = $previous_result;

				return $filter_result;
			}
		);

		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertEquals( 1, $stats['visits'][0]['count'], 'Filter result NULL should not affect counting' );
		$this->assertNull( $capture, 'Initial filter should receive NULL value as previous result' );

		// Explicitly blacklist request.
		$filter_result = true;

		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertEquals( 1, $stats['visits'][0]['count'], 'Filter result FALSE should prevent request from being tracked' );

		// The following request sould be skipped by internal filters, let's say the request raises a 404.
		$filter_result    = null;
		$wp_query->is_404 = true;

		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertEquals( 1, $stats['visits'][0]['count'], 'Filter result NULL should not affect built-in filters' );

		// We now explicitly NOT skip the request.
		$filter_result = false;

		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertEquals( 2, $stats['visits'][0]['count'], 'Filter result TRUE should force counting' );
	}

	/**
	 * Test evaluation of the statify__visit_saved hook.
	 */
	public function test_visit_saved_hook() {
		global $_SERVER;

		$this->init_statify_tracking();

		// A valid request that should be tracked.
		$_SERVER['REQUEST_URI']     = '/page/';
		$_SERVER['HTTP_REFERER']    = 'https://statify.pluginkollektiv.org/';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36';

		$capture = array();

		add_filter(
			'statify__visit_saved',
			function ( $data, $id ) use ( &$capture ) {
				$capture['data'] = $data;
				$capture['id']   = $id;
			},
			10,
			2
		);

		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertNotNull( $stats['visits'][0]['count'], 'Request not tracked' );
		$this->assertNotEmpty( $capture, 'Hook stativy__visit_saved has not fired' );
		$this->assertTrue( is_numeric( $capture['id'] ) && $capture['id'] > 0, 'unexpected entry ID' );
		$this->assertCount( 3, $capture['data'], 'unexpected number of data fields' );
		$this->assertEquals( ( new DateTime() )->format( 'Y-m-d' ), $capture['data']['created'], 'unexpected creation date' );
		$this->assertEquals( 'https://statify.pluginkollektiv.org/', $capture['data']['referrer'], 'unexpected referrer' );
		$this->assertEquals( '/page', $capture['data']['target'], 'unexpected target' );
	}

	/**
	 * Test tracking for logged-in users.
	 */
	public function test_track_users() {
		global $_SERVER;
		global $wp_query;

		// Assume we are logged in.
		wp_set_current_user( 1 );

		// Initialize Statify with default configuration: no JS tracking, no logged-in users.
		$this->init_statify_tracking();

		// Basically a valid request.
		$_SERVER['REQUEST_URI']     = '/private-page/';
		$_SERVER['HTTP_REFERER']    = 'https://statify.pluginkollektiv.org/';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36';

		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertNull( $stats, 'Logged-in user should not be tracked' );

		// Re-initialize Statify, enabling logged-in user tracking.
		$this->init_statify_tracking( false, true );

		Statify_Frontend::track_visit();
		$stats = $this->get_stats();
		$this->assertNotNull( $stats, 'Logged-in user should be tracked' );
	}
}

=== AI Feed Digest ===
Contributors: developer
Tags: rss, feed, digest, newsletter, ai, gemini
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Aggregate RSS feeds and receive AI-generated newsletter summaries via email.

== Description ==

AI Feed Digest is a WordPress plugin that aggregates RSS/Atom feeds, stores their content, and uses the Google Gemini API to generate periodic newsletter summaries that are emailed to the site administrator.

= Features =

* **RSS/Atom Feed Aggregation** - Subscribe to any number of feeds using WordPress's built-in Link Manager
* **AI-Powered Summaries** - Generate engaging newsletter digests using Google Gemini AI
* **Flexible Scheduling** - Choose weekly or monthly digest frequency per feed
* **Full Content Extraction** - Optionally fetch full article content from source URLs
* **Customizable Prompts** - Tailor the AI prompt template to your needs
* **Email Notifications** - Receive beautifully formatted HTML email digests
* **Digest History** - View and resend past digests

= Requirements =

* WordPress 6.0 or higher
* PHP 8.0 or higher
* A Google Gemini API key

= Getting Started =

1. Install and activate the plugin
2. Go to AI Feed Digest > Settings and enter your Gemini API key
3. Add feeds using the Link Manager (Links > Add New)
4. Configure digest settings for each feed
5. Wait for the scheduled digest, or trigger one manually

== Installation ==

1. Upload the `ai-feed-digest` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Gemini API key in AI Feed Digest > Settings
4. Start adding feeds via the Link Manager

== Frequently Asked Questions ==

= Where do I get a Gemini API key? =

You can obtain a free Gemini API key from [Google AI Studio](https://aistudio.google.com/apikey).

= How often are feeds fetched? =

By default, feeds are fetched once daily via WP-Cron. For more reliable scheduling, consider setting up a real cron job on your server.

= Can I customize the AI prompt? =

Yes! Go to AI Feed Digest > Prompt Template to customize how the AI generates your digests. You can use various template variables to include feed and article information.

= How do I trigger a digest manually? =

In the Link Manager, hover over a feed and click "Generate Digest" to create and send a digest immediately.

= What email template is used? =

The plugin includes a mobile-responsive HTML email template. Plain text fallback is also included for email clients that don't support HTML.

== Screenshots ==

1. Settings page - Configure API key and email settings
2. Prompt editor - Customize the AI prompt template
3. Link Manager - Manage your RSS feed subscriptions
4. Digest preview - View and resend past digests

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of AI Feed Digest.

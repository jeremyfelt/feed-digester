# AI Feed Digest

A WordPress plugin that aggregates RSS/Atom feeds, stores their content, and uses the Google Gemini API to generate periodic newsletter summaries that are emailed to the site administrator.

## Features

- **RSS/Atom Feed Aggregation** - Subscribe to any number of feeds using WordPress's built-in Link Manager
- **AI-Powered Summaries** - Generate engaging newsletter digests using Google Gemini AI
- **Flexible Scheduling** - Choose weekly or monthly digest frequency per feed
- **Full Content Extraction** - Optionally fetch full article content from source URLs
- **Customizable Prompts** - Tailor the AI prompt template to your needs
- **Email Notifications** - Receive beautifully formatted HTML email digests
- **Digest History** - View and resend past digests

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- A Google Gemini API key

## Installation

1. Clone or download this repository to your WordPress plugins directory:

```bash
cd wp-content/plugins
git clone https://github.com/jeremyfelt/feed-digester.git ai-feed-digest
```

2. Install PHP dependencies:

```bash
cd ai-feed-digest
composer install
```

3. Activate the plugin through the WordPress admin

4. Configure your Gemini API key in **AI Feed Digest > Settings**

## Getting Started

1. Go to **AI Feed Digest > Settings** and enter your Gemini API key
2. Add feeds using the Link Manager (**Links > Add New**)
3. Configure digest settings for each feed in the meta box
4. Wait for the scheduled digest, or trigger one manually using the row actions

## Development

### Setup

```bash
# Install dependencies
composer install

# Run coding standards check
composer phpcs

# Fix auto-fixable issues
composer phpcbf

# Run tests
composer test
```

### Directory Structure

```
ai-feed-digest/
├── .github/workflows/    # GitHub Actions workflows
├── src/
│   ├── Admin/           # Admin pages and settings
│   ├── Core/            # Plugin bootstrap and core functionality
│   ├── Feed/            # Feed fetching and storage
│   ├── AI/              # Gemini API integration
│   └── Email/           # Newsletter composition and sending
├── templates/
│   ├── admin/           # Admin page templates
│   └── email/           # Email templates
├── assets/
│   ├── css/             # Admin stylesheets
│   └── js/              # Admin JavaScript
├── languages/           # Translations
├── tests/               # PHPUnit tests
└── ...
```

## WP-Cron Note

WP-Cron relies on site traffic. For more reliable scheduling, consider setting up a real cron job:

```bash
# Add to your crontab
*/15 * * * * curl -s https://yoursite.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

Or disable WP-Cron and use system cron:

```php
// In wp-config.php
define('DISABLE_WP_CRON', true);
```

## Obtaining a Gemini API Key

1. Visit [Google AI Studio](https://aistudio.google.com/apikey)
2. Sign in with your Google account
3. Create a new API key
4. Copy the key and paste it in the plugin settings

## Contributing

Contributions are welcome! Please ensure your code:

1. Follows WordPress coding standards (run `composer phpcs`)
2. Includes appropriate tests
3. Updates documentation as needed

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Built with [Google Gemini AI](https://ai.google.dev/)
- Uses WordPress's built-in SimplePie for feed parsing

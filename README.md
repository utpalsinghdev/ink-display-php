# GitHub Profile Display

A beautiful PHP website to showcase your GitHub profile and contribution graph.

## Features

- 🎨 Modern, responsive design with dark theme
- 📊 GitHub contribution graph display
- 👤 Complete profile information (avatar, bio, stats)
- 📦 Recent repositories showcase
- ⚡ Caching system to reduce API calls
- 🔒 Optional GitHub token support for higher rate limits

## Setup Instructions

### 1. Configure Your GitHub Username

Edit `config.php` and set your GitHub username:

```php
define('GITHUB_USERNAME', 'your-username-here');
```

Replace `your-username-here` with your actual GitHub username.

### 2. (Optional) Add GitHub Personal Access Token

**Important:** Adding a token enables two key features:
- **Private Contributions**: Shows contributions from private repositories in your contribution graph
- **Higher Rate Limits**: Increases API rate limits from 60 to 5,000 requests per hour

To set up a token:

1. Go to [GitHub Settings > Developer settings > Personal access tokens](https://github.com/settings/tokens)
2. Click "Generate new token (classic)"
3. Give it a descriptive name (e.g., "GitHub Profile Display")
4. Select the `repo` scope (required for private contributions)
5. Click "Generate token"
6. **Copy the token immediately** (you won't see it again!)
7. Add it to `config.php`:

```php
define('GITHUB_TOKEN', 'your-token-here');
```

**Security Note:** Never commit your token to version control. The `.gitignore` file is configured to help prevent this.

### 3. Set Up Web Server

#### Using PHP Built-in Server (Development)

```bash
php -S localhost:8000
```

Then open `http://localhost:8000` in your browser.

#### Using Apache/Nginx (Production)

1. Place the files in your web server's document root
2. Ensure PHP is installed and enabled
3. Make sure the `cache/` directory is writable:

```bash
mkdir cache
chmod 755 cache
```

### 4. File Permissions

Make sure the cache directory is writable:

```bash
chmod 755 cache
```

## Requirements

- PHP 7.4 or higher
- cURL extension enabled
- Internet connection (for GitHub API)

## File Structure

```
ink-display-php/
├── index.php          # Main page
├── config.php         # Configuration file
├── github_api.php     # GitHub API helper functions
├── style.css          # Styling
├── cache/             # Cache directory (auto-created)
└── README.md          # This file
```

## Customization

### Styling

Edit `style.css` to customize colors, fonts, and layout. The CSS uses CSS variables for easy theming:

```css
:root {
    --primary-color: #238636;
    --secondary-color: #1f6feb;
    --bg-color: #0d1117;
    /* ... */
}
```

### Cache Duration

You can adjust the cache duration in `config.php`:

```php
define('CACHE_DURATION', 300); // 5 minutes (in seconds)
```

## Troubleshooting

### Profile Not Loading

- Check that your GitHub username is correct in `config.php`
- Ensure you have an internet connection
- Check if GitHub API is accessible from your server

### Contribution Graph Not Showing

- The contribution graph is loaded directly from GitHub
- Make sure your GitHub profile is public
- Check browser console for any CORS or loading errors

### Rate Limit Issues

- Add a GitHub Personal Access Token to `config.php` for higher rate limits
- The caching system helps reduce API calls

## License

This project is open source and available for personal use.


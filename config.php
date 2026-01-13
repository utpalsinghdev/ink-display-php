<?php
/**
 * GitHub Profile Display - Configuration
 * 
 * Set your GitHub username here.
 * For authenticated requests (higher rate limits), you can optionally set a GitHub Personal Access Token.
 */

// Your GitHub username
define('GITHUB_USERNAME', 'utpalsinghdev');

// Optional: GitHub Personal Access Token (for higher rate limits)
// Get one at: https://github.com/settings/tokens
// Leave empty if you don't want to use authentication
define('GITHUB_TOKEN', '');

// GitHub API base URL
define('GITHUB_API_URL', 'https://api.github.com');

// Cache duration in seconds (to reduce API calls)
define('CACHE_DURATION', 300); // 5 minutes

// Timezone
date_default_timezone_set('UTC');

// OpenWeather API Configuration
// Get your free API key at: https://openweathermap.org/api
define('OPENWEATHER_API_KEY', '99f0dd7d13f8c1f70c048426daa53e70');
define('WEATHER_CITY', 'New Delhi'); // Default city, or use coordinates below
define('WEATHER_LAT', null); // Optional: Latitude
define('WEATHER_LON', null); // Optional: Longitude
?>


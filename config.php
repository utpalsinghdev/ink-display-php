<?php
/**
 * GitHub Profile Display - Configuration
 * 
 * Loads configuration from .env file
 */

// Application Version
define('APP_VERSION', '1.0.0');

// Load .env file
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            if (!empty($key)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Helper function to get env variable with default
function get_env($key, $default = '') {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// Your GitHub username
define('GITHUB_USERNAME', get_env('GITHUB_USERNAME', 'utpalsinghdev'));

// Optional: GitHub Personal Access Token (for higher rate limits)
// Get one at: https://github.com/settings/tokens
define('GITHUB_TOKEN', get_env('GITHUB_TOKEN', ''));

// GitHub API base URL
define('GITHUB_API_URL', 'https://api.github.com');

// Cache duration in seconds (to reduce API calls)
define('CACHE_DURATION', 300); // 5 minutes

// Timezone (UTC+5:30 - Indian Standard Time)
date_default_timezone_set('Asia/Kolkata');

// OpenWeather API Configuration
// Get your free API key at: https://openweathermap.org/api
define('OPENWEATHER_API_KEY', get_env('OPENWEATHER_API_KEY', ''));
define('WEATHER_CITY', get_env('WEATHER_CITY', 'New Delhi')); // Default city, or use coordinates below
define('WEATHER_LAT', get_env('WEATHER_LAT', null)); // Optional: Latitude
define('WEATHER_LON', get_env('WEATHER_LON', null)); // Optional: Longitude
?>


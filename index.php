<?php
require_once 'config.php';
require_once 'github_api.php';
require_once 'weather_api.php';

// Get GitHub username from config
$username = GITHUB_USERNAME;

// Validate username
if (empty($username) || $username === 'your-username-here') {
    die('Please set your GitHub username in config.php');
}

// Fetch profile data
$profile = get_github_profile($username);
$repositories = get_github_repositories($username);
$commit_stats = get_commit_statistics($username);
$readme_content = get_profile_readme($username);

// Fetch weather data
$weather = null;
$forecast = null;
if (!empty(OPENWEATHER_API_KEY) && OPENWEATHER_API_KEY !== 'your-openweather-api-key-here') {
    if (WEATHER_LAT && WEATHER_LON) {
        $weather = get_weather_data(null, WEATHER_LAT, WEATHER_LON);
        $forecast = get_weather_forecast(null, WEATHER_LAT, WEATHER_LON);
    } elseif (WEATHER_CITY) {
        $weather = get_weather_data(WEATHER_CITY);
        $forecast = get_weather_forecast(WEATHER_CITY);
    }
}

// Handle API errors
if (!$profile) {
    die('Error: Could not fetch GitHub profile. Please check your username and internet connection.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?php echo htmlspecialchars($profile['name'] ?? $profile['login']); ?> - GitHub Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <header class="header">
            <div class="profile-card">
                <div class="profile-avatar-section">
                <div class="profile-avatar">
                    <img src="<?php echo htmlspecialchars($profile['avatar_url']); ?>" 
                         alt="<?php echo htmlspecialchars($profile['login']); ?>"
                         onerror="this.src='https://via.placeholder.com/200'">
                </div>
                    <div class="profile-name-section">
                    <h1 class="profile-name"><?php echo htmlspecialchars($profile['name'] ?? $profile['login']); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($profile['login']); ?></p>
                    </div>
                </div>

                <!-- Contribution Graph -->
                <div class="contribution-container">
                <?php 
                // Try to fetch GitHub-style contribution calendar SVG
                $contribution_svg = get_github_contribution_calendar_svg($username);
                if ($contribution_svg): 
                        // Only adjust background to match paper theme, keep GitHub colors
                        $contribution_svg = str_ireplace('fill="transparent"', 'fill="#f5f5f0"', $contribution_svg);
                        $contribution_svg = str_ireplace('fill: transparent;', 'fill: #f5f5f0;', $contribution_svg);
                        $contribution_svg = str_ireplace('fill="#ffffff"', 'fill="#f5f5f0"', $contribution_svg);
                        $contribution_svg = str_ireplace('fill: #ffffff;', 'fill: #f5f5f0;', $contribution_svg);
                        
                    // Make SVG responsive by removing fixed width/height and adding viewBox
                        // GitHub's SVG is typically 828x128, but we need to ensure legend is included
                        // Check if viewBox exists and adjust if needed
                        if (preg_match('/viewBox="([^"]*)"/', $contribution_svg, $matches)) {
                            // ViewBox already exists, just ensure it's correct
                            $viewBox = $matches[1];
                            $parts = explode(' ', $viewBox);
                            if (count($parts) >= 4) {
                                $height = floatval($parts[3]);
                                // If height is less than 150, increase it to include legend
                                if ($height < 150) {
                                    $newViewBox = $parts[0] . ' ' . $parts[1] . ' ' . $parts[2] . ' 150';
                                    $contribution_svg = str_replace('viewBox="' . $viewBox . '"', 'viewBox="' . $newViewBox . '"', $contribution_svg);
                                }
                            }
                        } else {
                            // No viewBox, add one that includes space for legend
                            $contribution_svg = str_replace('<svg', '<svg viewBox="0 0 828 150" preserveAspectRatio="xMidYMid meet"', $contribution_svg);
                        }
                        
                        // Remove fixed width/height to make it responsive
                    $contribution_svg = preg_replace('/width="[^"]*"/', '', $contribution_svg);
                    $contribution_svg = preg_replace('/height="[^"]*"/', '', $contribution_svg);
                        
                        // Ensure preserveAspectRatio is set
                        if (strpos($contribution_svg, 'preserveAspectRatio') === false) {
                            $contribution_svg = preg_replace('/<svg([^>]*)>/', '<svg$1 preserveAspectRatio="xMidYMid meet">', $contribution_svg);
                    }
                ?>
                    <div class="contribution-calendar-wrapper">
                        <?php echo $contribution_svg; ?>
                    </div>
                <?php else: ?>
                        <div class="contribution-error">
                            <p>Unable to load contribution calendar. Please try again later.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Weather Section -->
        <?php if ($weather): ?>
        <section class="weather-section">
            <div class="weather-container">
                <!-- Top Section: Current Weather -->
                <div class="weather-top">
                    <div class="weather-icon-large">
                        <?php 
                        $icon_code = $weather['weather'][0]['icon'] ?? '01d';
                        echo get_weather_icon($icon_code);
                        ?>
                    </div>
                    <div class="weather-main">
                        <div class="weather-temp">
                            <?php echo round($weather['main']['temp']); ?>°
                        </div>
                        <div class="weather-time">
                            Temperature (<?php echo date('g:i A'); ?>)
                        </div>
                    </div>
                    <div class="weather-current">
                        <div class="weather-item">
                            <span class="weather-item-icon">🌡️</span>
                            <span class="weather-item-text"><?php echo round($weather['main']['feels_like']); ?>° Feels Like</span>
                        </div>
                        <div class="weather-item">
                            <span class="weather-item-icon">💧</span>
                            <span class="weather-item-text"><?php echo $weather['main']['humidity']; ?>% Humidity</span>
                        </div>
                        <div class="weather-item">
                            <span class="weather-item-icon"><?php echo get_weather_icon($icon_code); ?></span>
                            <span class="weather-item-text"><?php echo ucfirst($weather['weather'][0]['description']); ?> Right Now</span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Section: Forecast -->
                <div class="weather-bottom">
                    <div class="weather-column">
                        <div class="weather-forecast-item">
                            <span class="weather-forecast-icon"><?php echo get_weather_icon($icon_code); ?></span>
                            <span class="weather-forecast-text"><?php echo ucfirst($weather['weather'][0]['description']); ?> Today</span>
                        </div>
                        <?php if ($forecast && isset($forecast['list'][0])): 
                            $tomorrow_icon = $forecast['list'][0]['weather'][0]['icon'] ?? '01d';
                            $tomorrow_desc = $forecast['list'][0]['weather'][0]['description'] ?? 'Clear';
                        ?>
                        <div class="weather-forecast-item">
                            <span class="weather-forecast-icon"><?php echo get_weather_icon($tomorrow_icon); ?></span>
                            <span class="weather-forecast-text"><?php echo ucfirst($tomorrow_desc); ?> Tomorrow</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="weather-column">
                        <?php 
                        $uv_today = calculate_uv_index($weather);
                        $uv_tomorrow = $forecast && isset($forecast['list'][0]) ? calculate_uv_index(['weather' => [$forecast['list'][0]['weather'][0]]]) : $uv_today * 0.9;
                        ?>
                        <div class="weather-forecast-item">
                            <span class="weather-forecast-icon">☀️</span>
                            <span class="weather-forecast-text"><?php echo get_uv_description($uv_today); ?> (<?php echo $uv_today; ?>) UV</span>
                        </div>
                        <div class="weather-forecast-item">
                            <span class="weather-forecast-icon">☀️</span>
                            <span class="weather-forecast-text"><?php echo get_uv_description($uv_tomorrow); ?> (<?php echo $uv_tomorrow; ?>) UV</span>
                        </div>
                    </div>
                    <div class="weather-column">
                        <div class="weather-forecast-item">
                            <span class="weather-forecast-icon">🌡️</span>
                            <span class="weather-forecast-text"><?php echo round($weather['main']['temp_min']); ?>° Low</span>
                            <span class="weather-forecast-text"><?php echo round($weather['main']['temp_max']); ?>° High</span>
                        </div>
                        <?php if ($forecast && isset($forecast['list'][0])): ?>
                        <div class="weather-forecast-item">
                            <span class="weather-forecast-icon">🌡️</span>
                            <span class="weather-forecast-text"><?php echo round($forecast['list'][0]['main']['temp_min']); ?>° Low</span>
                            <span class="weather-forecast-text"><?php echo round($forecast['list'][0]['main']['temp_max']); ?>° High</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php else: ?>
        <!-- Weather Error (for debugging - remove in production) -->
        <?php if (!empty(OPENWEATHER_API_KEY) && OPENWEATHER_API_KEY !== 'your-openweather-api-key-here'): 
            global $weather_api_error;
        ?>
        <section class="weather-section">
            <div class="weather-container">
                <div class="weather-error">
                    <p><strong>Unable to load weather data</strong></p>
                    <?php if (isset($weather_api_error)): ?>
                        <p style="color: var(--text-secondary); margin: 10px 0;">
                            <strong>API Error:</strong> <?php echo htmlspecialchars($weather_api_error); ?>
                        </p>
                <?php endif; ?>
                    <p style="margin-top: 15px;">Please check:</p>
                    <ul>
                        <li>Your OpenWeather API key is valid and activated (new keys may take a few minutes to activate)</li>
                        <li>Your city name is correct (currently: <strong><?php echo htmlspecialchars(WEATHER_CITY); ?></strong>)</li>
                        <li>You have an active internet connection</li>
                    </ul>
                    <p style="margin-top: 15px; font-size: 12px; color: var(--text-secondary);">
                        Get a free API key at: <a href="https://openweathermap.org/api" target="_blank" style="color: var(--text-primary); text-decoration: underline;">openweathermap.org/api</a>
                    </p>
                </div>
            </div>
        </section>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>


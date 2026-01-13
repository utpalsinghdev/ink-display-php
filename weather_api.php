<?php
/**
 * OpenWeather API Helper Functions
 */

require_once 'config.php';

/**
 * Get weather data from OpenWeather API
 */
function get_weather_data($city = null, $lat = null, $lon = null) {
    // Use city name or coordinates
    if ($city) {
        $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city) . '&appid=' . OPENWEATHER_API_KEY . '&units=metric';
    } elseif ($lat && $lon) {
        $url = 'https://api.openweathermap.org/data/2.5/weather?lat=' . $lat . '&lon=' . $lon . '&appid=' . OPENWEATHER_API_KEY . '&units=metric';
    } else {
        return null;
    }
    
    $cache_file = __DIR__ . '/cache/weather_' . md5($url) . '.json';
    
    // Check cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 600) { // 10 minutes cache
        return json_decode(file_get_contents($cache_file), true);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Weather Display');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code !== 200) {
        // Store error in a global variable for display
        $error_data = json_decode($response, true);
        if ($error_data && isset($error_data['message'])) {
            global $weather_api_error;
            $weather_api_error = $error_data['message'];
        }
        return null;
    }
    
    if ($curl_error) {
        global $weather_api_error;
        $weather_api_error = "Connection error: " . $curl_error;
        return null;
    }
    
    $data = json_decode($response, true);
    
    if ($data) {
        // Create cache directory if it doesn't exist
        $cache_dir = __DIR__ . '/cache';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        // Save to cache
        file_put_contents($cache_file, json_encode($data));
    }
    
    return $data;
}

/**
 * Get weather forecast from OpenWeather API
 */
function get_weather_forecast($city = null, $lat = null, $lon = null) {
    // Use city name or coordinates
    if ($city) {
        $url = 'https://api.openweathermap.org/data/2.5/forecast?q=' . urlencode($city) . '&appid=' . OPENWEATHER_API_KEY . '&units=metric&cnt=2';
    } elseif ($lat && $lon) {
        $url = 'https://api.openweathermap.org/data/2.5/forecast?lat=' . $lat . '&lon=' . $lon . '&appid=' . OPENWEATHER_API_KEY . '&units=metric&cnt=2';
    } else {
        return null;
    }
    
    $cache_file = __DIR__ . '/cache/weather_forecast_' . md5($url) . '.json';
    
    // Check cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 600) { // 10 minutes cache
        return json_decode(file_get_contents($cache_file), true);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Weather Display');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if ($data) {
        // Create cache directory if it doesn't exist
        $cache_dir = __DIR__ . '/cache';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        // Save to cache
        file_put_contents($cache_file, json_encode($data));
    }
    
    return $data;
}

/**
 * Get weather icon SVG based on condition
 */
function get_weather_icon($condition_code, $size = 24) {
    $icons = [
        '01d' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>', // clear sky day
        '01n' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>', // clear sky night
        '02d' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>', // few clouds day
        '02n' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>', // few clouds night
        '03d' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>', // scattered clouds
        '03n' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
        '04d' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>', // broken clouds
        '04n' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
        '09d' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16" y1="13" x2="16" y2="21"/><line x1="8" y1="13" x2="8" y2="21"/><line x1="12" y1="15" x2="12" y2="23"/><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/></svg>', // shower rain
        '09n' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16" y1="13" x2="16" y2="21"/><line x1="8" y1="13" x2="8" y2="21"/><line x1="12" y1="15" x2="12" y2="23"/><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/></svg>',
        '10d' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16" y1="13" x2="16" y2="21"/><line x1="8" y1="13" x2="8" y2="21"/><line x1="12" y1="15" x2="12" y2="23"/><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/></svg>', // rain day
        '10n' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16" y1="13" x2="16" y2="21"/><line x1="8" y1="13" x2="8" y2="21"/><line x1="12" y1="15" x2="12" y2="23"/><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/></svg>', // rain night
        '11d' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>', // thunderstorm
        '11n' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        '13d' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 17.58A5 5 0 0 0 18 8h-1.26A8 8 0 1 0 4 16.25"/><line x1="8" y1="16" x2="8.01" y2="16"/><line x1="8" y1="20" x2="8.01" y2="20"/><line x1="12" y1="18" x2="12.01" y2="18"/><line x1="12" y1="22" x2="12.01" y2="22"/><line x1="16" y1="16" x2="16.01" y2="16"/><line x1="16" y1="20" x2="16.01" y2="20"/></svg>', // snow
        '13n' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 17.58A5 5 0 0 0 18 8h-1.26A8 8 0 1 0 4 16.25"/><line x1="8" y1="16" x2="8.01" y2="16"/><line x1="8" y1="20" x2="8.01" y2="20"/><line x1="12" y1="18" x2="12.01" y2="18"/><line x1="12" y1="22" x2="12.01" y2="22"/><line x1="16" y1="16" x2="16.01" y2="16"/><line x1="16" y1="20" x2="16.01" y2="20"/></svg>',
        '50d' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6m0 4h.01"/></svg>', // mist
        '50n' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6m0 4h.01"/></svg>',
    ];
    
    return $icons[$condition_code] ?? '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>';
}

/**
 * Get SVG icon for thermometer
 */
function get_thermometer_icon($size = 18) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/></svg>';
}

/**
 * Get SVG icon for droplet/humidity
 */
function get_droplet_icon($size = 18) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>';
}

/**
 * Get SVG icon for sun/UV
 */
function get_sun_icon($size = 20) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
}

/**
 * Get Air Quality Index (AQI) from OpenWeather Air Pollution API
 */
function get_air_quality($lat, $lon) {
    if (empty(OPENWEATHER_API_KEY) || OPENWEATHER_API_KEY === 'your-openweather-api-key-here') {
        return null;
    }
    
    $url = 'http://api.openweathermap.org/data/2.5/air_pollution?lat=' . $lat . '&lon=' . $lon . '&appid=' . OPENWEATHER_API_KEY;
    
    $cache_file = __DIR__ . '/cache/aqi_' . md5($url) . '.json';
    
    // Check cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 600) { // 10 minutes cache
        return json_decode(file_get_contents($cache_file), true);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Weather Display');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['list'][0])) {
        // Create cache directory if it doesn't exist
        $cache_dir = __DIR__ . '/cache';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        // Save to cache
        file_put_contents($cache_file, json_encode($data));
        
        return $data['list'][0];
    }
    
    return null;
}

/**
 * Get AQI description
 */
function get_aqi_description($aqi) {
    $descriptions = [
        1 => 'Good',
        2 => 'Fair',
        3 => 'Moderate',
        4 => 'Poor',
        5 => 'Very Poor'
    ];
    
    return $descriptions[$aqi] ?? 'Unknown';
}

/**
 * Get SVG icon for air quality
 */
function get_air_quality_icon($size = 18) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6m0 4h.01"/></svg>';
}

/**
 * Format UV index description
 */
function get_uv_description($uv) {
    $uv = floatval($uv);
    if ($uv <= 2) return 'Low';
    if ($uv <= 5) return 'Moderate';
    if ($uv <= 7) return 'High';
    if ($uv <= 10) return 'Very High';
    return 'Extreme';
}

/**
 * Get UV index (placeholder - OpenWeather free tier doesn't include UV)
 * This is a simple calculation based on time of day and weather conditions
 */
function calculate_uv_index($weather_data) {
    // Simple UV estimation based on time and conditions
    $hour = (int)date('G');
    $condition = $weather_data['weather'][0]['main'] ?? 'Clear';
    
    // Peak UV around noon (12 PM)
    $base_uv = 0;
    if ($hour >= 10 && $hour <= 14) {
        $base_uv = 5 - abs(12 - $hour);
    } else {
        $base_uv = max(0, 3 - abs(12 - $hour) * 0.5);
    }
    
    // Adjust based on conditions
    if ($condition === 'Clear') {
        $base_uv *= 1.2;
    } elseif ($condition === 'Clouds') {
        $base_uv *= 0.6;
    } elseif (in_array($condition, ['Rain', 'Drizzle', 'Thunderstorm'])) {
        $base_uv *= 0.3;
    }
    
    return round(max(0.1, min(10, $base_uv)), 1);
}

?>


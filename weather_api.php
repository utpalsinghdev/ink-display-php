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
        $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city) . '&appid=' . OPENWEATHER_API_KEY . '&units=imperial';
    } elseif ($lat && $lon) {
        $url = 'https://api.openweathermap.org/data/2.5/weather?lat=' . $lat . '&lon=' . $lon . '&appid=' . OPENWEATHER_API_KEY . '&units=imperial';
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
        $url = 'https://api.openweathermap.org/data/2.5/forecast?q=' . urlencode($city) . '&appid=' . OPENWEATHER_API_KEY . '&units=imperial&cnt=2';
    } elseif ($lat && $lon) {
        $url = 'https://api.openweathermap.org/data/2.5/forecast?lat=' . $lat . '&lon=' . $lon . '&appid=' . OPENWEATHER_API_KEY . '&units=imperial&cnt=2';
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
 * Get weather icon based on condition
 */
function get_weather_icon($condition_code) {
    $icons = [
        '01d' => '☀️', // clear sky day
        '01n' => '🌙', // clear sky night
        '02d' => '⛅', // few clouds day
        '02n' => '☁️', // few clouds night
        '03d' => '☁️', // scattered clouds
        '03n' => '☁️',
        '04d' => '☁️', // broken clouds
        '04n' => '☁️',
        '09d' => '🌧️', // shower rain
        '09n' => '🌧️',
        '10d' => '🌦️', // rain day
        '10n' => '🌧️', // rain night
        '11d' => '⛈️', // thunderstorm
        '11n' => '⛈️',
        '13d' => '❄️', // snow
        '13n' => '❄️',
        '50d' => '🌫️', // mist
        '50n' => '🌫️',
    ];
    
    return $icons[$condition_code] ?? '☁️';
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


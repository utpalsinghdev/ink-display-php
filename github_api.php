<?php
/**
 * GitHub API Helper Functions
 */

require_once 'config.php';

/**
 * Make a request to GitHub API
 */
function github_api_request($endpoint) {
    $url = GITHUB_API_URL . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'GitHub Profile Display');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.github.v3+json'
    ]);
    
    // Add authentication if token is provided
    if (!empty(GITHUB_TOKEN)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.github.v3+json',
            'Authorization: token ' . GITHUB_TOKEN
        ]);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code !== 200) {
        error_log("GitHub API Error: HTTP $http_code for $endpoint" . ($curl_error ? " - $curl_error" : ""));
        return null;
    }
    
    if ($curl_error) {
        error_log("GitHub API CURL Error: $curl_error");
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Get GitHub user profile
 */
function get_github_profile($username) {
    $cache_file = __DIR__ . '/cache/profile_' . $username . '.json';
    
    // Check cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_DURATION) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    $profile = github_api_request('/users/' . $username);
    
    if ($profile) {
        // Create cache directory if it doesn't exist
        $cache_dir = __DIR__ . '/cache';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        // Save to cache
        file_put_contents($cache_file, json_encode($profile));
    }
    
    return $profile;
}

/**
 * Get GitHub user repositories
 */
function get_github_repositories($username) {
    $cache_file = __DIR__ . '/cache/repos_' . $username . '.json';
    
    // Check cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_DURATION) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    $repos = github_api_request('/users/' . $username . '/repos?sort=updated&per_page=6');
    
    if ($repos) {
        // Create cache directory if it doesn't exist
        $cache_dir = __DIR__ . '/cache';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        // Save to cache
        file_put_contents($cache_file, json_encode($repos));
    }
    
    return $repos ?: [];
}

/**
 * Format number with K/M suffix
 */
function format_number($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return $number;
}

/**
 * Get README.md content from profile repository
 */
function get_profile_readme($username) {
    $cache_file = __DIR__ . '/cache/readme_' . $username . '.md';
    
    // Check cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_DURATION) {
        return file_get_contents($cache_file);
    }
    
    // Try to get README from profile repository (username/username)
    $readme_url = 'https://raw.githubusercontent.com/' . $username . '/' . $username . '/main/README.md';
    
    // Also try master branch
    $readme_content = false;
    $branches = ['main', 'master'];
    
    foreach ($branches as $branch) {
        $url = 'https://raw.githubusercontent.com/' . $username . '/' . $username . '/' . $branch . '/README.md';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GitHub Profile Display');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && $content && strlen($content) > 10) {
            $readme_content = $content;
            break;
        }
    }
    
    if ($readme_content) {
        // Create cache directory if it doesn't exist
        $cache_dir = __DIR__ . '/cache';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        // Save to cache
        file_put_contents($cache_file, $readme_content);
        
        return $readme_content;
    }
    
    return false;
}

/**
 * Convert markdown to HTML (simple implementation)
 * Handles both markdown and HTML content
 */
function markdown_to_html($markdown) {
    // If content already contains HTML tags, assume it's HTML and just clean it up
    if (preg_match('/<[a-z][\s\S]*>/i', $markdown)) {
        // Already HTML, just return it (GitHub READMEs often have HTML)
        return $markdown;
    }
    
    // Otherwise, convert markdown to HTML
    $html = $markdown;
    
    // Headers
    $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
    
    // Bold
    $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
    $html = preg_replace('/__(.*?)__/', '<strong>$1</strong>', $html);
    
    // Italic
    $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
    $html = preg_replace('/_(.*?)_/', '<em>$1</em>', $html);
    
    // Code blocks
    $html = preg_replace('/```(\w+)?\n(.*?)```/s', '<pre><code>$2</code></pre>', $html);
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
    
    // Links
    $html = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $html);
    
    // Images
    $html = preg_replace('/!\[([^\]]*)\]\(([^\)]+)\)/', '<img src="$2" alt="$1">', $html);
    
    // Lists
    $html = preg_replace('/^\* (.*$)/m', '<li>$1</li>', $html);
    $html = preg_replace('/^- (.*$)/m', '<li>$1</li>', $html);
    $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
    
    // Line breaks
    $html = nl2br($html);
    
    // Paragraphs
    $html = preg_replace('/\n\n/', '</p><p>', $html);
    $html = '<p>' . $html . '</p>';
    
    return $html;
}

/**
 * Get commit statistics and streaks from contribution calendar
 */
function get_commit_statistics($username) {
    $cache_file = __DIR__ . '/cache/commit_stats_' . $username . '.json';
    $include_private = !empty(GITHUB_TOKEN);
    
    // Check cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_DURATION) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    // Calculate date range (January to January, excluding current month)
    // Set end date to the last day of the previous month
    $end_date = date('Y-m-d', strtotime('last day of last month'));
    // Set start date to January 1st of the year that contains the end date
    $end_year = date('Y', strtotime($end_date));
    $start_date = ($end_year - 1) . '-01-01';
    
    $calendar_data = null;
    
    // Try to get data with token first (includes private)
    if ($include_private) {
        $query = '{
            viewer {
                login
                contributionsCollection(from: "' . $start_date . 'T00:00:00Z", to: "' . $end_date . 'T23:59:59Z") {
                    contributionCalendar {
                        totalContributions
                        weeks {
                            contributionDays {
                                date
                                contributionCount
                            }
                        }
                    }
                }
            }
        }';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/graphql');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: bearer ' . GITHUB_TOKEN,
            'Content-Type: application/json',
            'User-Agent: GitHub Profile Display'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            if (isset($data['data']['viewer']['contributionsCollection']['contributionCalendar'])) {
                $viewer_login = $data['data']['viewer']['login'];
                if (strtolower($viewer_login) === strtolower($username)) {
                    $calendar_data = $data['data']['viewer']['contributionsCollection']['contributionCalendar'];
                }
            }
        }
    }
    
    // Fallback to public API if no token or token failed
    if (!$calendar_data) {
        $query = '{
            user(login: "' . $username . '") {
                contributionsCollection(from: "' . $start_date . 'T00:00:00Z", to: "' . $end_date . 'T23:59:59Z") {
                    contributionCalendar {
                        totalContributions
                        weeks {
                            contributionDays {
                                date
                                contributionCount
                            }
                        }
                    }
                }
            }
        }';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/graphql');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
        $headers = ['Content-Type: application/json', 'User-Agent: GitHub Profile Display'];
        if (!empty(GITHUB_TOKEN)) {
            $headers[] = 'Authorization: bearer ' . GITHUB_TOKEN;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            if (isset($data['data']['user']['contributionsCollection']['contributionCalendar'])) {
                $calendar_data = $data['data']['user']['contributionsCollection']['contributionCalendar'];
            }
        }
    }
    
    if (!$calendar_data) {
        return null;
    }
    
    // Calculate statistics
    $total_contributions = $calendar_data['totalContributions'];
    $weeks = $calendar_data['weeks'];
    
    // Flatten all days
    $all_days = [];
    foreach ($weeks as $week) {
        foreach ($week['contributionDays'] as $day) {
            $all_days[] = [
                'date' => $day['date'],
                'count' => $day['contributionCount']
            ];
        }
    }
    
    // Calculate current streak (consecutive days with contributions ending today or yesterday)
    $current_streak = 0;
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Create a map for quick lookup
    $day_map = [];
    foreach ($all_days as $day) {
        $day_map[$day['date']] = $day['count'];
    }
    
    // Start from today or yesterday (if today has no contributions)
    $start_date = isset($day_map[$today]) && $day_map[$today] > 0 ? $today : $yesterday;
    $current_date = new DateTime($start_date);
    
    // Count backwards
    while (true) {
        $date_str = $current_date->format('Y-m-d');
        $has_contributions = isset($day_map[$date_str]) && $day_map[$date_str] > 0;
        
        if ($has_contributions) {
            $current_streak++;
            $current_date->modify('-1 day');
        } else {
            break;
        }
    }
    
    // Calculate longest streak
    $longest_streak = 0;
    $temp_streak = 0;
    
    foreach ($all_days as $day) {
        if ($day['count'] > 0) {
            $temp_streak++;
            $longest_streak = max($longest_streak, $temp_streak);
        } else {
            $temp_streak = 0;
        }
    }
    
    $stats = [
        'total_contributions' => $total_contributions,
        'current_streak' => $current_streak,
        'longest_streak' => $longest_streak
    ];
    
    // Cache the results
    $cache_dir = __DIR__ . '/cache';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    file_put_contents($cache_file, json_encode($stats));
    
    return $stats;
}

/**
 * Get contribution graph image URL (SVG format)
 */
function get_contribution_graph_url($username) {
    return 'https://github.com/users/' . $username . '/contributions.svg';
}

/**
 * Get contribution graph iframe URL (fallback)
 */
function get_contribution_graph_iframe_url($username) {
    return 'https://github.com/users/' . $username . '/contributions';
}

/**
 * Get contribution graph iframe URL (GitHub's native contribution calendar)
 */
function get_contribution_iframe_url($username) {
    return 'https://github.com/users/' . $username . '/contributions';
}

/**
 * Get GitHub contribution calendar SVG
 * If token is provided, uses GitHub GraphQL API to include private contributions
 * Otherwise falls back to public contributions API
 */
function get_github_contribution_calendar_svg($username) {
    $cache_file = __DIR__ . '/cache/contributions_calendar_' . $username . '.svg';
    $include_private = !empty(GITHUB_TOKEN);
    
    // Check cache (use different cache for private vs public)
    if ($include_private) {
        $cache_file = __DIR__ . '/cache/contributions_calendar_private_' . $username . '.svg';
    }
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_DURATION) {
        return file_get_contents($cache_file);
    }
    
    // If token is provided, try to get contributions with private repos using GraphQL
    if ($include_private) {
        $svg_content = get_github_contributions_with_token($username);
        if ($svg_content) {
            // Create cache directory if it doesn't exist
            $cache_dir = __DIR__ . '/cache';
            if (!is_dir($cache_dir)) {
                mkdir($cache_dir, 0755, true);
            }
            
            // Save to cache
            file_put_contents($cache_file, $svg_content);
            
            return $svg_content;
        }
    }
    
    // Fallback to public contributions API service
    $url = 'https://github-contributions-api.deno.dev/' . $username . '.svg';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; GitHub Profile Display)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $svg_content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $svg_content && strlen($svg_content) > 100) {
        // Create cache directory if it doesn't exist
        $cache_dir = __DIR__ . '/cache';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        // Save to cache
        file_put_contents($cache_file, $svg_content);
        
        return $svg_content;
    }
    
    return false;
}

/**
 * Get GitHub contributions using GraphQL API with token (includes private contributions)
 * Note: When authenticated with a token that has 'repo' scope, private contributions are automatically included
 */
function get_github_contributions_with_token($username) {
    if (empty(GITHUB_TOKEN)) {
        return false;
    }
    
    // Calculate date range (January to January, excluding current month)
    // Set end date to the last day of the previous month
    $end_date = date('Y-m-d', strtotime('last day of last month'));
    // Set start date to January 1st of the year that contains the end date
    $end_year = date('Y', strtotime($end_date));
    $start_date = ($end_year - 1) . '-01-01';
    
    // GraphQL query to get contribution data
    // Use 'viewer' to get authenticated user's contributions (includes private)
    // Then verify it matches the requested username
    $query = '{
        viewer {
            login
            contributionsCollection(from: "' . $start_date . 'T00:00:00Z", to: "' . $end_date . 'T23:59:59Z") {
                contributionCalendar {
                    totalContributions
                    weeks {
                        contributionDays {
                            date
                            contributionCount
                            color
                        }
                    }
                }
            }
        }
    }';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/graphql');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: bearer ' . GITHUB_TOKEN,
        'Content-Type: application/json',
        'User-Agent: GitHub Profile Display'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        
        // Check for GraphQL errors
        if (isset($data['errors'])) {
            error_log('GitHub GraphQL Error: ' . json_encode($data['errors']));
            return false;
        }
        
        // Check if we got viewer data and it matches the requested username
        if (isset($data['data']['viewer'])) {
            $viewer_login = $data['data']['viewer']['login'];
            
            // Verify the authenticated user matches the requested username
            if (strtolower($viewer_login) !== strtolower($username)) {
                error_log('GitHub GraphQL: Authenticated user (' . $viewer_login . ') does not match requested user (' . $username . ')');
                return false;
            }
            
            if (isset($data['data']['viewer']['contributionsCollection']['contributionCalendar'])) {
                return generate_contribution_svg($data['data']['viewer']['contributionsCollection']['contributionCalendar'], $username);
            }
        }
    } else {
        // Log error for debugging
        error_log('GitHub GraphQL API Error - HTTP Code: ' . $http_code . ', Response: ' . substr($response, 0, 500));
        if ($curl_error) {
            error_log('cURL Error: ' . $curl_error);
        }
    }
    
    return false;
}

/**
 * Generate SVG from contribution calendar data
 */
function generate_contribution_svg($calendar_data, $username) {
    $weeks = $calendar_data['weeks'];
    $total = $calendar_data['totalContributions'];
    
    // GitHub contribution colors (original green theme)
    $colors = [
        'NONE' => '#ebedf0',
        'FIRST_QUARTILE' => '#c6e48b',
        'SECOND_QUARTILE' => '#7bc96f',
        'THIRD_QUARTILE' => '#239a3b',
        'FOURTH_QUARTILE' => '#196127'
    ];
    
    // Determine color based on contribution count
    $get_color_class = function($count) {
        if ($count === 0) return 'NONE';
        if ($count <= 3) return 'FIRST_QUARTILE';
        if ($count <= 6) return 'SECOND_QUARTILE';
        if ($count <= 9) return 'THIRD_QUARTILE';
        return 'FOURTH_QUARTILE';
    };
    
    // Calculate dimensions - GitHub-style sizing
    $pixel_size = 11;
    $pixel_spacing = 3;
    $cell_size = $pixel_size + $pixel_spacing;
    
    $svg_width = 828;
    $svg_height = 150; // Increased height to include legend
    $chart_x = 27; // Left margin for day labels
    $chart_y = 25; // Top margin for month labels
    $chart_width = $svg_width - $chart_x - 20; // Full width for chart
    $chart_height = 91; // 7 days * 13px spacing
    
    $svg = '<svg width="' . $svg_width . '" height="' . $svg_height . '" xmlns="http://www.w3.org/2000/svg" id="github-contributions-graph">';
    $svg .= '<style>#github-contributions-graph .pixel { width: ' . $pixel_size . 'px; height: ' . $pixel_size . 'px; rx: 2px; ry: 2px; stroke: rgba(27,31,35,0.06); stroke-width: 1px; }';
    $svg .= '#github-contributions-graph text { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 12px; fill: #57606a; }';
    $svg .= '#github-contributions-graph .axis-label { font-size: 12px; fill: #57606a; }';
    $svg .= '#github-contributions-graph .NONE { fill: #ebedf0; }';
    $svg .= '#github-contributions-graph .FIRST_QUARTILE { fill: #c6e48b; }';
    $svg .= '#github-contributions-graph .SECOND_QUARTILE { fill: #7bc96f; }';
    $svg .= '#github-contributions-graph .THIRD_QUARTILE { fill: #239a3b; }';
    $svg .= '#github-contributions-graph .FOURTH_QUARTILE { fill: #196127; }</style>';
    $svg .= '<rect width="' . $svg_width . '" height="' . $svg_height . '" fill="#f5f5f0"></rect>';
    
    // Contribution text
    $svg .= '<g><text x="0" y="15" fill="#57606a" font-size="14px" font-weight="400">' . number_format($total) . ' contributions in the last year</text></g>';
    
    // Day of week labels (Y-axis) - only show Sun, Mon, Wed, Fri (like GitHub)
    $day_labels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    $day_label_positions = [0, 1, 3, 5]; // Sun, Mon, Wed, Fri
    foreach ($day_label_positions as $day_idx) {
        $y_pos = $chart_y + ($day_idx * $cell_size) + ($pixel_size / 2) + 2;
        $svg .= '<text x="' . ($chart_x - 5) . '" y="' . $y_pos . '" class="axis-label" text-anchor="end" dominant-baseline="middle">' . $day_labels[$day_idx] . '</text>';
    }
    
    // Contribution calendar grid
    $svg .= '<g transform="translate(' . $chart_x . ', ' . $chart_y . ')">';
    
    $x = 0;
    $month_labels = [];
    $last_month = '';
    $last_month_x = -1;
    
    foreach ($weeks as $week_index => $week) {
        // Get the first day of the week to determine the month
        if (!empty($week['contributionDays'])) {
            $first_day = $week['contributionDays'][0];
            $date_obj = new DateTime($first_day['date']);
            $month = $date_obj->format('M'); // Jan, Feb, etc.
            
            // Only add month label if it's different from the last one, or if it's the first week
            if ($month !== $last_month || $week_index === 0) {
                // Only show month label if there's enough space from the last one
                if ($week_index === 0 || ($x - $last_month_x) > 30) {
                    $month_labels[] = [
                        'month' => $month,
                        'x' => $x
                    ];
                    $last_month_x = $x;
                }
                $last_month = $month;
            }
        }
        
        $y = 0;
        foreach ($week['contributionDays'] as $day) {
            $count = $day['contributionCount'];
            $date = $day['date'];
            $color_class = $get_color_class($count);
            
            $svg .= '<rect class="pixel ' . $color_class . '" x="' . $x . '" y="' . $y . '" data-date="' . $date . '" data-count="' . $count . '">';
            $svg .= '<title>' . $date . ': ' . $count . ' contribution' . ($count !== 1 ? 's' : '') . '</title></rect>';
            
            $y += $cell_size;
        }
        $x += $cell_size;
    }
    
    $svg .= '</g>';
    
    // Month labels on X-axis (top)
    foreach ($month_labels as $label) {
        $svg .= '<text x="' . ($chart_x + $label['x']) . '" y="' . ($chart_y - 6) . '" class="axis-label" text-anchor="start" dominant-baseline="auto">' . $label['month'] . '</text>';
    }
    
    // Legend at the bottom
    $legend_y = $chart_y + $chart_height + 20;
    $legend_x = $svg_width - 200; // Position legend on the right side
    
    // Legend text "Less"
    $svg .= '<text x="' . $legend_x . '" y="' . ($legend_y + 8) . '" class="axis-label" text-anchor="start">Less</text>';
    
    // Legend squares
    $legend_square_size = 10;
    $legend_square_spacing = 3;
    $legend_start_x = $legend_x + 35;
    
    $legend_colors = ['NONE', 'FIRST_QUARTILE', 'SECOND_QUARTILE', 'THIRD_QUARTILE', 'FOURTH_QUARTILE'];
    foreach ($legend_colors as $idx => $color_class) {
        $x_pos = $legend_start_x + ($idx * ($legend_square_size + $legend_square_spacing));
        $svg .= '<rect class="pixel ' . $color_class . '" x="' . $x_pos . '" y="' . ($legend_y - 5) . '" width="' . $legend_square_size . '" height="' . $legend_square_size . '"></rect>';
    }
    
    // Legend text "More"
    $svg .= '<text x="' . ($legend_start_x + (count($legend_colors) * ($legend_square_size + $legend_square_spacing)) + 5) . '" y="' . ($legend_y + 8) . '" class="axis-label" text-anchor="start">More</text>';
    
    $svg .= '</svg>';
    
    return $svg;
}
?>


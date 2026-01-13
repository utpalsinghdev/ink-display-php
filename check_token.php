<?php
/**
 * Token Verification Script
 * Run this to check if your GitHub token has the correct permissions
 */

require_once 'config.php';

if (empty(GITHUB_TOKEN)) {
    die("Error: No token set in config.php\n");
}

echo "Checking GitHub token permissions...\n\n";

// Check token validity and get user info
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: bearer ' . GITHUB_TOKEN,
    'User-Agent: GitHub Profile Display',
    'Accept: application/vnd.github.v3+json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    echo "✓ Token is valid\n";
    echo "✓ Authenticated as: " . $data['login'] . "\n\n";
    
    // Try to access private repos to verify repo scope
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, 'https://api.github.com/user/repos?visibility=private&per_page=1');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Authorization: bearer ' . GITHUB_TOKEN,
        'User-Agent: GitHub Profile Display',
        'Accept: application/vnd.github.v3+json'
    ]);
    
    $response2 = curl_exec($ch2);
    $http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    if ($http_code2 === 200) {
        $repos = json_decode($response2, true);
        echo "✓ Token has 'repo' scope - can access private repositories\n";
        echo "  Private repos accessible: " . (is_array($repos) ? count($repos) : 0) . " (showing first page)\n\n";
        echo "✓ Your contribution graph SHOULD include private contributions\n";
    } else {
        echo "✗ Token does NOT have 'repo' scope\n";
        echo "  HTTP Code: $http_code2\n";
        echo "  Response: " . substr($response2, 0, 200) . "\n\n";
        echo "⚠️  To see private contributions:\n";
        echo "  1. Go to: https://github.com/settings/tokens\n";
        echo "  2. Generate a new token (classic)\n";
        echo "  3. Select the 'repo' scope\n";
        echo "  4. Update GITHUB_TOKEN in config.php\n\n";
    }
    
    // Test GraphQL API
    echo "Testing GraphQL API for contributions...\n";
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-1 year'));
    
    $query = '{
        user(login: "' . GITHUB_USERNAME . '") {
            contributionsCollection(from: "' . $start_date . 'T00:00:00Z", to: "' . $end_date . 'T23:59:59Z") {
                contributionCalendar {
                    totalContributions
                }
            }
        }
    }';
    
    $ch3 = curl_init();
    curl_setopt($ch3, CURLOPT_URL, 'https://api.github.com/graphql');
    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch3, CURLOPT_POST, true);
    curl_setopt($ch3, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    curl_setopt($ch3, CURLOPT_HTTPHEADER, [
        'Authorization: bearer ' . GITHUB_TOKEN,
        'Content-Type: application/json',
        'User-Agent: GitHub Profile Display'
    ]);
    
    $response3 = curl_exec($ch3);
    $http_code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
    curl_close($ch3);
    
    if ($http_code3 === 200) {
        $data3 = json_decode($response3, true);
        if (isset($data3['data']['user']['contributionsCollection']['contributionCalendar'])) {
            $total = $data3['data']['user']['contributionsCollection']['contributionCalendar']['totalContributions'];
            echo "✓ GraphQL API working\n";
            echo "  Total contributions (last year): $total\n";
            echo "  (This includes private contributions if token has 'repo' scope)\n";
        } else if (isset($data3['errors'])) {
            echo "✗ GraphQL Error: " . json_encode($data3['errors']) . "\n";
        }
    } else {
        echo "✗ GraphQL API failed. HTTP Code: $http_code3\n";
    }
    
} else {
    echo "✗ Token validation failed\n";
    echo "  HTTP Code: $http_code\n";
    echo "  Response: " . substr($response, 0, 200) . "\n";
    echo "\nPlease check your token in config.php\n";
}

?>


<?php
/**
 * Show Repository Count Script
 */

require_once 'config.php';
require_once 'github_api.php';

$username = GITHUB_USERNAME;

echo "Fetching repository information for: $username\n\n";

// Get total count using GitHub API
$ch = curl_init();
$url = GITHUB_API_URL . '/users/' . $username;

if (!empty(GITHUB_TOKEN)) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.github.v3+json',
        'Authorization: token ' . GITHUB_TOKEN,
        'User-Agent: GitHub Profile Display'
    ]);
} else {
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.github.v3+json',
        'User-Agent: GitHub Profile Display'
    ]);
}

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $profile = json_decode($response, true);
    echo "Total Public Repositories: " . $profile['public_repos'] . "\n";
    
    if (!empty(GITHUB_TOKEN)) {
        // Get authenticated user's repo count (includes private)
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, GITHUB_API_URL . '/user');
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.github.v3+json',
            'Authorization: token ' . GITHUB_TOKEN,
            'User-Agent: GitHub Profile Display'
        ]);
        
        $response2 = curl_exec($ch2);
        $http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        
        if ($http_code2 === 200) {
            $user_data = json_decode($response2, true);
            
            // Count all repos with pagination
            $all_repos = [];
            $page = 1;
            $per_page = 100;
            
            do {
                $repos_page = github_api_request('/user/repos?per_page=' . $per_page . '&page=' . $page . '&sort=updated');
                if ($repos_page && count($repos_page) > 0) {
                    $all_repos = array_merge($all_repos, $repos_page);
                    $page++;
                } else {
                    break;
                }
            } while (count($repos_page) === $per_page);
            
            if (!empty($all_repos)) {
                $public_count = 0;
                $private_count = 0;
                foreach ($all_repos as $repo) {
                    if ($repo['private']) {
                        $private_count++;
                    } else {
                        $public_count++;
                    }
                }
                echo "Total Private Repositories: " . $private_count . "\n";
                echo "Total Repositories (Public + Private): " . count($all_repos) . "\n";
            }
        }
    } else {
        echo "\n(Add a GitHub token in config.php to see private repository count)\n";
    }
    
    // Show recent repositories
    echo "\n---\n";
    echo "Recent Repositories (showing 6 most recent):\n";
    $repos = get_github_repositories($username);
    if (!empty($repos)) {
        foreach ($repos as $repo) {
            echo "  - " . $repo['name'];
            if ($repo['private']) {
                echo " (private)";
            }
            echo "\n";
        }
    }
} else {
    echo "Error fetching repository information. HTTP Code: $http_code\n";
}

?>


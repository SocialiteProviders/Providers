<?php

use Illuminate\Http\Client\Factory;

require_once __DIR__.'/../vendor/autoload.php';

$http = new Factory;

/**
 * Create a new repo with preset information.
 */
$repoName = $argv[1] ?? null;

if (empty($repoName)) {
    echo 'No name provided' . PHP_EOL;
    exit(1);
}

$res = $http->withHeaders([
    'Accept'        => 'application/vnd.github.v3+json',
    'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
])->post('https://api.github.com/orgs/SocialiteProviders/repos', [
    'name'        => $repoName,
    'description' => sprintf('[READ ONLY] Subtree split of the SocialiteProviders/%s Provider (see SocialiteProviders/Providers)', $repoName),
    'homepage'    => sprintf('https://socialiteproviders.com/%s/', $repoName),
    'has_issues'  => false,
]);

$responseData = $res->json();
echo sprintf("Created Repo: %s, response: %s\n", $res->failed() ? $res->body() : ($responseData['full_name'] ?? 'Unknown'), $res->status());

if ($res->failed()) {
    exit(1);
}

$repoUrl = $responseData['url'] ?? null;
if ($repoUrl) {
    $res = $http->withHeaders([
        'Accept'        => 'application/vnd.github.mercy-preview+json',
        'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
    ])->put($repoUrl.'/topics', [
        'names' => ['laravel', 'oauth', 'socialite', 'oauth1', 'oauth2', 'socialite-providers', 'social-media'],
    ]);
} else {
    echo "Warning: Could not get repository URL for topics update\n";
    exit(1);
}

echo sprintf("Updated Repo Topics: %s, response code: %s\n", $repoName, $res->status());

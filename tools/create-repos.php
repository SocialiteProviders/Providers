<?php

/**
 * Create split repos for new providers added in a merged PR.
 *
 * Detects new src/* directories added in the commit, creates the corresponding
 * GitHub repos under the SocialiteProviders org with standard settings.
 *
 * Usage: GITHUB_TOKEN=xxx php tools/create-repos.php <commit_sha> <repository>
 *
 * Environment overrides (for testing):
 *   CREATE_PACKAGES - Comma-separated list of packages to create repos for (skip detection)
 *   CREATE_REPO_MAP - JSON object mapping package names to repo names (merged with split-overrides.json)
 */

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Http\Client\Factory;

$commitSha = $argv[1] ?? null;
$repository = $argv[2] ?? null;

if (empty($commitSha) || empty($repository)) {
    echo "Usage: create-repos.php <commit_sha> <repository>\n";
    exit(1);
}

$token = getenv('GITHUB_TOKEN');
if (! $token) {
    echo "Error: GITHUB_TOKEN is required\n";
    exit(1);
}

$http = (new Factory)->withToken($token)->baseUrl('https://api.github.com');
$overrides = json_decode(file_get_contents(__DIR__.'/../split-overrides.json'), true);
$org = 'SocialiteProviders';
$topics = ['laravel', 'oauth', 'socialite', 'oauth1', 'oauth2', 'socialite-providers', 'social-media'];

if ($repoMap = getenv('CREATE_REPO_MAP')) {
    $overrides = array_merge($overrides, json_decode($repoMap, true));
}

// Determine which packages need new repos
$createPackages = getenv('CREATE_PACKAGES') ?: null;

if ($createPackages) {
    $packages = array_map('trim', explode(',', $createPackages));
} else {
    // Check for new-provider label on the PR
    $response = $http->get(sprintf('/repos/%s/commits/%s/pulls', $repository, $commitSha));

    if ($response->failed() || empty($response->json())) {
        echo "No PR found for commit $commitSha, skipping\n";
        exit(0);
    }

    $pr = $response->json()[0];
    $hasLabel = collect($pr['labels'] ?? [])
        ->pluck('name')
        ->contains('new-provider');

    if (! $hasLabel) {
        echo "No new-provider label found on PR, skipping\n";
        exit(0);
    }

    // Find new src/* directories added in this commit
    $output = shell_exec(sprintf('git diff-tree --no-commit-id --name-only --diff-filter=A -r %s', escapeshellarg($commitSha)));
    $addedFiles = array_filter(explode("\n", trim($output ?? '')));

    $packages = [];
    foreach ($addedFiles as $file) {
        if (str_starts_with($file, 'src/')) {
            $parts = explode('/', $file);
            $packages[$parts[1]] = true;
        }
    }
    $packages = array_keys($packages);
}

if (empty($packages)) {
    echo "No new provider packages found\n";
    exit(0);
}

echo sprintf("Creating repos for %d packages: %s\n\n", count($packages), implode(', ', $packages));

foreach ($packages as $package) {
    $repo = $overrides[$package] ?? $package;

    echo sprintf("[create] %s (%s/%s)\n", $package, $org, $repo);

    // Check if repo already exists
    $response = $http->get(sprintf('/repos/%s/%s', $org, $repo));
    if ($response->successful()) {
        echo sprintf("[create] %s already exists, skipping\n\n", $repo);

        continue;
    }

    $response = $http->post(sprintf('/orgs/%s/repos', $org), [
        'name' => $repo,
        'description' => sprintf('[READ ONLY] Subtree split of the SocialiteProviders/%s Provider (see SocialiteProviders/Providers)', $package),
        'homepage' => sprintf('https://socialiteproviders.com/%s/', $package),
        'has_issues' => false,
    ]);

    if ($response->failed()) {
        echo sprintf("[error] Failed to create %s: %s\n", $repo, $response->body());

        continue;
    }

    echo sprintf("[create] %s created\n", $repo);

    // Set topics
    $http->put(sprintf('/repos/%s/%s/topics', $org, $repo), [
        'names' => $topics,
    ]);

    echo sprintf("[create] %s topics updated\n\n", $repo);
}

echo "All repos created.\n";

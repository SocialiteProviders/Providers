<?php

/**
 * Release new versions for providers that changed in a merged PR.
 *
 * Finds the merged PR for the given commit, checks for release:* labels,
 * detects changed src/* directories, fetches the latest tag from each split repo,
 * bumps the version, and creates a GitHub release.
 *
 * Usage: GITHUB_TOKEN=xxx php tools/release.php <commit_sha> <repository>
 *
 * Environment overrides (for testing):
 *   RELEASE_BUMP       - Skip PR label detection, use this bump type (major/minor/patch)
 *   RELEASE_PACKAGES   - Comma-separated list of packages to release (skip change detection)
 *   RELEASE_REPO_MAP   - JSON object mapping package names to repo names (merged with split-overrides.json)
 */

require_once __DIR__.'/../vendor/autoload.php';

use Composer\Semver\VersionParser;
use Illuminate\Http\Client\Factory;

$commitSha = $argv[1] ?? null;
$repository = $argv[2] ?? null;

if (empty($commitSha) || empty($repository)) {
    echo "Usage: release.php <commit_sha> <repository>\n";
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
$parser = new VersionParser;

// Allow overriding repo mapping for testing
if ($repoMap = getenv('RELEASE_REPO_MAP')) {
    $overrides = array_merge($overrides, json_decode($repoMap, true));
}

// Determine bump type and PR info
$bump = getenv('RELEASE_BUMP') ?: null;
$pr = null;

if (! $bump) {
    $response = $http->get(sprintf('/repos/%s/commits/%s/pulls', $repository, $commitSha));

    if ($response->failed() || empty($response->json())) {
        echo "No PR found for commit $commitSha, skipping\n";
        exit(0);
    }

    $pr = $response->json()[0];
    $labels = collect($pr['labels'] ?? [])->pluck('name');

    $bump = $labels
        ->filter(fn (string $label) => str_starts_with($label, 'release:'))
        ->map(fn (string $label) => str_replace('release:', '', $label))
        ->first();

    // new-provider label triggers an initial release
    if (! $bump && $labels->contains('new-provider')) {
        $bump = 'new-provider';
    }
}

if (! $bump || ! in_array($bump, ['major', 'minor', 'patch', 'new-provider'])) {
    echo "No release label found on PR, skipping\n";
    exit(0);
}

// Determine changed packages
$releasePackages = getenv('RELEASE_PACKAGES') ?: null;

if ($releasePackages) {
    $changedPackages = array_map('trim', explode(',', $releasePackages));
} else {
    $output = shell_exec(sprintf('git diff-tree --no-commit-id --name-only -r %s', escapeshellarg($commitSha)));
    $changedFiles = array_filter(explode("\n", trim($output ?? '')));

    $changedPackages = [];
    foreach ($changedFiles as $file) {
        if (str_starts_with($file, 'src/')) {
            $parts = explode('/', $file);
            $changedPackages[$parts[1]] = true;
        }
    }
    $changedPackages = array_keys($changedPackages);
}

if (empty($changedPackages)) {
    echo "No provider packages changed in commit $commitSha\n";
    exit(0);
}

echo sprintf("Found %d changed packages: %s\n", count($changedPackages), implode(', ', $changedPackages));
echo sprintf("Bump type: %s\n\n", $bump);

foreach ($changedPackages as $package) {
    $repo = $overrides[$package] ?? $package;

    echo sprintf("[release] %s (%s/%s)\n", $package, $org, $repo);

    $response = $http->get(sprintf('/repos/%s/%s/releases/latest', $org, $repo));
    $latest = $response->successful() ? $response->json('tag_name') : null;

    if ($bump === 'new-provider' || ! $latest) {
        $newVersion = '1.0.0';
        echo sprintf("[release] Initial release for %s at %s\n", $repo, $newVersion);
    } else {
        $newVersion = bumpVersion(ltrim($latest, 'v'), $bump, $parser);
    }

    echo sprintf("[release] %s: %s -> %s\n", $repo, $latest ?? 'none', $newVersion);

    $body = generateReleaseBody($newVersion, $latest, $pr, $repository);

    $response = $http->post(sprintf('/repos/%s/%s/releases', $org, $repo), [
        'tag_name' => $newVersion,
        'target_commitish' => 'master',
        'name' => $newVersion,
        'body' => $body,
    ]);

    if ($response->failed()) {
        echo sprintf("[error] Failed to release %s: %s\n", $repo, $response->body());

        continue;
    }

    echo sprintf("[release] %s: released %s\n\n", $repo, $newVersion);
}

echo "All releases complete.\n";

function generateReleaseBody(string $newVersion, ?string $previousVersion, ?array $pr, string $repository): string
{
    $lines = ["## What's Changed"];

    if ($pr) {
        $lines[] = sprintf(
            '* %s by @%s in https://github.com/%s/pull/%d',
            $pr['title'],
            $pr['user']['login'],
            $repository,
            $pr['number']
        );
    }

    if ($previousVersion) {
        $lines[] = '';
        $lines[] = sprintf('**Full Changelog**: https://github.com/%s/compare/%s...%s', $repository, $previousVersion, $newVersion);
    }

    return implode("\n", $lines);
}

function bumpVersion(string $version, string $type, VersionParser $parser): string
{
    $normalized = $parser->normalize($version);
    $parts = explode('.', $normalized);

    $major = (int) $parts[0];
    $minor = (int) $parts[1];
    $patch = (int) $parts[2];

    return match ($type) {
        'major' => sprintf('%d.0.0', $major + 1),
        'minor' => sprintf('%d.%d.0', $major, $minor + 1),
        'patch' => sprintf('%d.%d.%d', $major, $minor, $patch + 1),
    };
}

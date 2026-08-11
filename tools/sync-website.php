<?php

/**
 * Add newly added providers to the website's provider list.
 *
 * Detects new src/* directories added in the commit, reads the `category` and
 * optional `name` keys from each provider's README frontmatter, and opens a PR
 * against SocialiteProviders/website adding them to providers.js.
 *
 * Usage: GITHUB_TOKEN=xxx php tools/sync-website.php <commit_sha> <repository>
 *
 * Environment overrides (for testing):
 *   SYNC_PACKAGES   - Comma-separated list of packages to sync (skip detection)
 *   SYNC_REPO_MAP   - JSON object mapping package names to repo names (merged with split-overrides.json)
 *   SYNC_DRY_RUN    - Set to 1 to print the resulting providers.js diff without pushing
 */

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Http\Client\Factory;

const WEBSITE_REPO = 'SocialiteProviders/website';
const DEFAULT_BRANCH = 'master';

$commitSha = $argv[1] ?? null;
$repository = $argv[2] ?? null;

if (empty($commitSha) || empty($repository)) {
    echo "Usage: sync-website.php <commit_sha> <repository>\n";
    exit(1);
}

$token = getenv('GITHUB_TOKEN');
if (! $token) {
    echo "Error: GITHUB_TOKEN is required\n";
    exit(1);
}

$dryRun = (bool) getenv('SYNC_DRY_RUN');
$http = (new Factory)->withToken($token)->baseUrl('https://api.github.com');
$overrides = json_decode(file_get_contents(__DIR__.'/../split-overrides.json'), true);

if ($repoMap = getenv('SYNC_REPO_MAP')) {
    $overrides = array_merge($overrides, json_decode($repoMap, true));
}

// Determine which packages to sync
$syncPackages = getenv('SYNC_PACKAGES') ?: null;

if ($syncPackages) {
    $packages = array_map('trim', explode(',', $syncPackages));
} else {
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

echo sprintf("Syncing %d packages to the website: %s\n\n", count($packages), implode(', ', $packages));

// Collect an entry per package from its README frontmatter
$entries = [];
$failed = false;

foreach ($packages as $package) {
    // Package names come from directory names in the PR, and are used to build
    // a path and to write JS, so keep them to a known-safe shape.
    if (! preg_match('/\A[A-Za-z0-9._-]+\z/', $package)) {
        echo sprintf("[error] %s: invalid package name\n", $package);
        $failed = true;

        continue;
    }

    $readme = __DIR__.'/../src/'.$package.'/README.md';

    if (! is_file($readme)) {
        echo sprintf("[error] %s: no README.md found\n", $package);
        $failed = true;

        continue;
    }

    $frontmatter = parseFrontmatter(file_get_contents($readme));
    $category = $frontmatter['category'] ?? null;

    if (! $category) {
        echo sprintf("[error] %s: README.md is missing a `category` frontmatter key\n", $package);
        $failed = true;

        continue;
    }

    $name = $frontmatter['name'] ?? $package;

    // The name is written into a single-quoted JS string literal.
    if (str_contains($name, "'") || str_contains($name, '\\')) {
        echo sprintf("[error] %s: `name` may not contain quotes or backslashes\n", $package);
        $failed = true;

        continue;
    }

    $entries[] = [
        'slug'     => $overrides[$package] ?? $package,
        'name'     => $name,
        'category' => $category,
    ];
}

if ($failed) {
    echo "\nOne or more providers could not be synced.\n";
    exit(1);
}

// Fetch the website's current provider list
$response = $http->get(sprintf('/repos/%s/contents/providers.js', WEBSITE_REPO), ['ref' => DEFAULT_BRANCH]);

if ($response->failed()) {
    echo sprintf("[error] Failed to fetch providers.js: %s\n", $response->body());
    exit(1);
}

$fileSha = $response->json()['sha'];
$providersJs = base64_decode($response->json()['content']);

$categories = availableCategories($providersJs);
$added = [];

foreach ($entries as $entry) {
    if (providerExists($providersJs, $entry['slug'])) {
        echo sprintf("[skip] %s already listed on the website\n", $entry['slug']);

        continue;
    }

    if (! in_array($entry['category'], $categories, true)) {
        echo sprintf(
            "[error] %s: unknown category \"%s\". Valid categories: %s\n",
            $entry['slug'],
            $entry['category'],
            implode(', ', $categories)
        );
        exit(1);
    }

    $providersJs = insertProvider($providersJs, $entry);

    if ($providersJs === null) {
        echo sprintf("[error] %s: failed to insert into category \"%s\"\n", $entry['slug'], $entry['category']);
        exit(1);
    }

    echo sprintf("[add] %s (%s) -> %s\n", $entry['name'], $entry['slug'], $entry['category']);
    $added[] = $entry;
}

if (empty($added)) {
    echo "\nNothing to add, website is already up to date.\n";
    exit(0);
}

if ($dryRun) {
    echo "\n[dry-run] Resulting providers.js:\n";
    echo $providersJs;
    exit(0);
}

// Commit the change to a branch on the website repo and open a PR
$branch = 'add-provider/'.substr($commitSha, 0, 7);

$response = $http->get(sprintf('/repos/%s/git/ref/heads/%s', WEBSITE_REPO, DEFAULT_BRANCH));

if ($response->failed()) {
    echo sprintf("[error] Failed to resolve %s: %s\n", DEFAULT_BRANCH, $response->body());
    exit(1);
}

$baseSha = $response->json()['object']['sha'];

$response = $http->post(sprintf('/repos/%s/git/refs', WEBSITE_REPO), [
    'ref' => 'refs/heads/'.$branch,
    'sha' => $baseSha,
]);

// 422 means the branch already exists, which is fine on a re-run
if ($response->failed() && $response->status() !== 422) {
    echo sprintf("[error] Failed to create branch %s: %s\n", $branch, $response->body());
    exit(1);
}

$names = implode(', ', array_column($added, 'name'));
$message = sprintf('feat: add %s to the provider list', $names);

$response = $http->put(sprintf('/repos/%s/contents/providers.js', WEBSITE_REPO), [
    'message' => $message,
    'content' => base64_encode($providersJs),
    'sha'     => $fileSha,
    'branch'  => $branch,
]);

if ($response->failed()) {
    echo sprintf("[error] Failed to commit providers.js: %s\n", $response->body());
    exit(1);
}

echo sprintf("\n[commit] providers.js updated on %s\n", $branch);

$response = $http->post(sprintf('/repos/%s/pulls', WEBSITE_REPO), [
    'title' => $message,
    'head'  => $branch,
    'base'  => DEFAULT_BRANCH,
    'body'  => sprintf(
        "Added automatically from https://github.com/%s/commit/%s.\n\n%s",
        $repository,
        $commitSha,
        implode("\n", array_map(
            fn (array $e) => sprintf('* `%s` — %s (%s)', $e['slug'], $e['name'], $e['category']),
            $added
        ))
    ),
]);

if ($response->failed()) {
    // A PR for this branch may already exist from an earlier run
    echo sprintf("[warn] Could not open a PR: %s\n", $response->body());
    exit(0);
}

echo sprintf("[pr] %s\n", $response->json()['html_url']);

/**
 * Parse a leading YAML frontmatter block into a key => value array.
 *
 * Only flat `key: value` pairs are supported, which is all the website needs.
 */
function parseFrontmatter(string $markdown): array
{
    if (! preg_match('/\A---\R(.*?)\R---\R/s', $markdown, $matches)) {
        return [];
    }

    $values = [];

    foreach (preg_split('/\R/', $matches[1]) as $line) {
        if (! str_contains($line, ':')) {
            continue;
        }

        [$key, $value] = explode(':', $line, 2);
        $values[trim($key)] = trim(trim($value), "\"'");
    }

    return $values;
}

/**
 * The category names a new provider may be filed under.
 *
 * "Deprecated" is a real section in providers.js but is never a valid choice
 * for a provider being added.
 *
 * @return list<string>
 */
function availableCategories(string $providersJs): array
{
    preg_match_all("/^  \{\R    name: '([^']+)'/m", $providersJs, $matches);

    return array_values(array_filter($matches[1], fn (string $c) => $c !== 'Deprecated'));
}

function providerExists(string $providersJs, string $slug): bool
{
    return (bool) preg_match("/slug: '".preg_quote($slug, '/')."',/", $providersJs);
}

/**
 * Insert a provider entry at the end of its category block.
 *
 * The last entry in a category is not guaranteed to have a trailing comma, so
 * one is added when missing to keep the result valid JavaScript.
 */
function insertProvider(string $providersJs, array $entry): ?string
{
    $pattern = sprintf(
        "/(^  \{\R    name: '%s'.*?\R)(    \],\R  \},\R)/ms",
        preg_quote($entry['category'], '/')
    );

    $entryJs = sprintf(
        "      {\n        slug: '%s', name: '%s',\n        maintainers: [],\n      },\n",
        $entry['slug'],
        $entry['name']
    );

    $result = preg_replace_callback($pattern, function (array $m) use ($entryJs) {
        $body = $m[1];

        // Ensure the previous entry ends with `},` before appending a new one.
        $body = preg_replace('/\}(\R)\z/', '},$1', $body, 1);

        return $body.$entryJs.$m[2];
    }, $providersJs, 1, $count);

    return $count === 1 ? $result : null;
}

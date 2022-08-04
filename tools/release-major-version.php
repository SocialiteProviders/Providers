<?php

require_once __DIR__.'/../vendor/autoload.php';

use Composer\Semver\Comparator;
use Zttp\Zttp;

/**
 * Release a new major version across all providers.
 */
$excludedRepos = [
    'Providers',
    'Manager',
    'website',
    'Generators',
    'socialiteproviders.github.io',
];

define('NEW_VERSION', '4.0.0');

$repos = collect(range(1, 5))
    ->map(fn (int $page) => Zttp::withHeaders(['Accept' => 'application/vnd.github.v3+json'])->get('https://api.github.com/orgs/SocialiteProviders/repos?per_page=100&page='.$page)->json())
    ->flatten(1)
    ->filter(fn (array $repo) => $repo['description'] && str_contains($repo['description'], '[READ ONLY] Subtree split'))
    ->sortBy('name')
    ->each(function (array $repo) {
        $res = Zttp::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
        ])->get($repo['url'].'/releases');

        $higherThanNew = collect($res->json())->filter(fn (array $rel) => Comparator::greaterThanOrEqualTo($rel['tag_name'], NEW_VERSION));

        if ($higherThanNew->isNotEmpty()) {
            echo sprintf("Found Higher Release %s for provider %s, skipping\n", $higherThanNew->first()['tag_name'], $repo['name']);

            return;
        }

        $res = Zttp::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
        ])->post($repo['url'].'/releases', [
            'tag_name' => NEW_VERSION,
            'target_commitish' => 'master',
            'name' => 'Release V4',
            'body' => "- Drop PHP < 7.2\n- Drop Laravel < 6",
        ]);

        echo sprintf("Released Version for Repo: %s, response code: %s\n", $repo['name'], $res->status());
    });

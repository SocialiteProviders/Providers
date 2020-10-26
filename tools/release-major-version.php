<?php

/**
 * Release a new major version across all providers.
 */
require_once __DIR__.'/../vendor/autoload.php';

$excludedRepos = [
    'Providers',
    'Manager',
    'website',
    'Generators',
    'socialiteproviders.github.io',
];

define('NEW_VERSION', '4.0.0');

$repos = collect(range(1, 5))
    ->map(function (int $page) {
        return \Zttp\Zttp::withHeaders(['Accept' => 'application/vnd.github.v3+json'])->get('https://api.github.com/orgs/SocialiteProviders/repos?per_page=100&page='.$page)->json();
    })
    ->flatten(1)
    ->filter(function (array $repo) {
        return $repo['description'] && str_contains($repo['description'], '[READ ONLY] Subtree split');
    })
    ->sortBy('name')
    ->each(function (array $repo) {
        $res = \Zttp\Zttp::withHeaders([
            'Accept'        => 'application/vnd.github.v3+json',
            'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
        ])->get($repo['url'].'/releases');

        $higherThanNew = collect($res->json())->filter(function (array $rel) {
            return \Composer\Semver\Comparator::greaterThanOrEqualTo($rel['tag_name'], NEW_VERSION);
        });

        if ($higherThanNew->isNotEmpty()) {
            echo sprintf("Found Higher Release %s for provider %s, skipping\n", $higherThanNew->first()['tag_name'], $repo['name']);

            return;
        }

        $res = \Zttp\Zttp::withHeaders([
            'Accept'        => 'application/vnd.github.v3+json',
            'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
        ])->post($repo['url'].'/releases', [
            'tag_name'         => NEW_VERSION,
            'target_commitish' => 'master',
            'name'             => 'Release V4',
            'body'             => "- Drop PHP < 7.2\n- Drop Laravel < 6",
        ]);

        echo sprintf("Released Version for Repo: %s, response code: %s\n", $repo['name'], $res->status());
    });

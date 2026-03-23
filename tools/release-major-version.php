<?php

require_once __DIR__.'/../vendor/autoload.php';

use Composer\Semver\Comparator;
use Illuminate\Http\Client\Factory;

$http = new Factory;
$token = getenv('GITHUB_TOKEN');

/**
 * Release a new major version across all providers.
 */
$excludedRepos = [
    '.github',
    'Providers',
    'Manager',
    'website',
    'Generators',
    'socialiteproviders.github.io',
];

define('NEW_VERSION', '4.0.0');

$repos = collect(range(1, 5))
    ->flatMap(fn (int $page) => $http
        ->get('https://api.github.com/orgs/SocialiteProviders/repos?per_page=100&page='.$page)
        ->json()
    )
    ->filter(fn (array $repo) => $repo['description'] && str_contains($repo['description'], '[READ ONLY] Subtree split'))
    ->sortBy('name')
    ->each(function (array $repo) use ($http, $token) {
        $res = $http->withToken($token)
            ->get($repo['url'].'/releases');

        $higherThanNew = collect($res->json())->filter(fn (array $rel) => Comparator::greaterThanOrEqualTo($rel['tag_name'], NEW_VERSION));

        if ($higherThanNew->isNotEmpty()) {
            echo sprintf("Found Higher Release %s for provider %s, skipping\n", $higherThanNew->first()['tag_name'], $repo['name']);

            return;
        }

        $res = $http->withToken($token)
            ->post($repo['url'].'/releases', [
                'tag_name'         => NEW_VERSION,
                'target_commitish' => 'master',
                'name'             => 'Release V4',
                'body'             => "- Drop PHP < 7.2\n- Drop Laravel < 6",
            ]);

        echo sprintf("Released Version for Repo: %s, response code: %s\n", $repo['name'], $res->status());
    });

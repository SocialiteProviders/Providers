<?php

use Illuminate\Http\Client\Factory;

require_once __DIR__.'/../vendor/autoload.php';

$http = new Factory;
$token = getenv('GITHUB_TOKEN');

/**
 * Automatically update all of the repos to have a consistent description/URL and point people to the correct
 * documentation.
 */
$excludedRepos = [
    '.github',
    'Providers',
    'Manager',
    'website',
    'Generators',
    'socialiteproviders.github.io',
];

$repos = collect(range(1, 5))
    ->flatMap(fn (int $page) => $http
        ->get('https://api.github.com/orgs/SocialiteProviders/repos?per_page=100&page='.$page)
        ->json()
    )
    ->filter(fn (array $repo) => ! $repo['archived'] && ! in_array($repo['name'], $excludedRepos, true))
    ->sortBy('name')
    ->each(function (array $repo) use ($http, $token) {
        $res = $http->withToken($token)
            ->patch($repo['url'], [
                'description' => sprintf('[READ ONLY] Subtree split of the SocialiteProviders/%s Provider (see SocialiteProviders/Providers)', $repo['name']),
                'homepage'    => sprintf('https://socialiteproviders.com/%s/', $repo['name']),
                'has_issues'  => false,
            ]);

        echo sprintf("Updated Repo: %s, response code: %s\n", $repo['name'], $res->status());

        $res = $http->withToken($token)
            ->put($repo['url'].'/topics', [
                'names' => ['laravel', 'oauth', 'socialite', 'oauth1', 'oauth2', 'socialite-providers', 'social-media'],
            ]);

        echo sprintf("Updated Repo Topics: %s, response code: %s\n", $repo['name'], $res->status());
    });

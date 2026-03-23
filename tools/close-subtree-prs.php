<?php

use Illuminate\Http\Client\Factory;

require_once __DIR__.'/../vendor/autoload.php';

$http = new Factory;
$token = getenv('GITHUB_TOKEN');

/**
 * Automatically update all the repos to have a consistent description/URL and point people to the correct
 * documentation.
 */
$repos = collect(range(1, 5))
    ->flatMap(fn (int $page) => $http
        ->get('https://api.github.com/orgs/SocialiteProviders/repos?per_page=100&page='.$page)
        ->json()
    )
    ->sortBy('name')
    ->filter(fn (array $repo) => $repo['has_issues'] === false)
    ->each(function (array $repo) use ($http, $token) {
        $prs = collect(
            $http->withToken($token)
                ->get(sprintf('https://api.github.com/repos/SocialiteProviders/%s/pulls?state=open', $repo['name']))
                ->json()
        );

        echo sprintf("Found Repo: %s, %d open PRs\n", $repo['name'], $prs->count());

        if ($prs->isEmpty()) {
            return;
        }

        $prs->map(function (array $pr) use ($http, $token) {
            $http->withToken($token)
                ->patch($pr['url'], [
                    'state' => 'closed',
                ]);

            $http->withToken($token)
                ->post($pr['comments_url'], [
                    'body' => "This repository is a **READ ONLY** subtree split from [SocialiteProviders/Providers](https://github.com/SocialiteProviders/Providers).\n\nPlease open a PR against [SocialiteProviders/Providers](https://github.com/SocialiteProviders/Providers). ",
                ]);

            echo sprintf("Closed PR %d in %s\n", $pr['number'], $pr['url']);
        });
    });

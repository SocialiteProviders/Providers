<?php

/**
 * Automatically close PRs opened against read-only subtree split repos
 * and comment directing contributors to the main Providers repo.
 */

use Illuminate\Http\Client\Factory;

require_once __DIR__.'/../vendor/autoload.php';

$token = getenv('GITHUB_TOKEN');
if (! $token) {
    echo "Error: GITHUB_TOKEN is required\n";
    exit(1);
}

$http = (new Factory)->withToken($token)->baseUrl('https://api.github.com');

collect(range(1, 5))
    ->flatMap(fn (int $page) => $http
        ->get('/orgs/SocialiteProviders/repos', ['per_page' => 100, 'page' => $page])
        ->json()
    )
    ->sortBy('name')
    ->filter(fn (array $repo) => $repo['has_issues'] === false)
    ->each(function (array $repo) use ($http) {
        $prs = collect(
            $http->get(sprintf('/repos/SocialiteProviders/%s/pulls', $repo['name']), ['state' => 'open'])
                ->json()
        );

        echo sprintf("Found Repo: %s, %d open PRs\n", $repo['name'], $prs->count());

        if ($prs->isEmpty()) {
            return;
        }

        $prs->each(function (array $pr) use ($http) {
            $http->patch($pr['url'], ['state' => 'closed']);

            $http->post($pr['comments_url'], [
                'body' => "This repository is a **READ ONLY** subtree split from [SocialiteProviders/Providers](https://github.com/SocialiteProviders/Providers).\n\nPlease open a PR against [SocialiteProviders/Providers](https://github.com/SocialiteProviders/Providers). ",
            ]);

            echo sprintf("Closed PR %d in %s\n", $pr['number'], $pr['url']);
        });
    });

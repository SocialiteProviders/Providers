<?php

/**
 * Automatically update all of the repos to have a consistent description/URL and point people to the correct
 * documentation.
 */
require_once __DIR__.'/../vendor/autoload.php';

$repos = collect(range(1, 5))
    ->map(function (int $page) {
        return \Zttp\Zttp::withHeaders(['Accept' => 'application/vnd.github.v3+json'])->get('https://api.github.com/orgs/SocialiteProviders/repos?per_page=100&page='.$page)->json();
    })
    ->flatten(1)
    ->sortBy('name')
    ->filter(function (array $repo) {
        return $repo['has_issues'] === false;
    })
    ->each(function (array $repo) {
        $res = \Zttp\Zttp::withHeaders([
            'Accept'        => 'application/vnd.github.v3+json',
            'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
        ])->get(sprintf('https://api.github.com/repos/SocialiteProviders/%s/pulls?state=open', $repo['name']));

        $prs = collect($res->json());

        echo sprintf("Found Repo: %s, %d open PRs\n", $repo['name'], $prs->count());

        if ($prs->isEmpty()) {
            return;
        }

        $prs->map(function (array $pr) {
            $res = \Zttp\Zttp::withHeaders([
                'Accept'        => 'application/vnd.github.v3+json',
                'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
            ])->patch($pr['url'], [
                'state' => 'closed',
            ]);

            $res = \Zttp\Zttp::withHeaders([
                'Accept'        => 'application/vnd.github.v3+json',
                'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
            ])->post($pr['comments_url'], [
                'body' => "This repository is a **READ ONLY** subtree split from [SocialiteProviders/Providers](https://github.com/SocialiteProviders/Providers).\n\nPlease open a PR against [SocialiteProviders/Providers](https://github.com/SocialiteProviders/Providers). ",
            ]);

            echo sprintf("Closed PR %d in %s\n", $pr['number'], $pr['url']);
        });
    });

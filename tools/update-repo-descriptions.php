<?php

/**
 * Automatically update all of the repos to have a consistent description/URL and point people to the correct
 * documentation.
 */
require_once __DIR__.'/../vendor/autoload.php';

$excludedRepos = [
    'Providers',
    'Manager',
    'website',
    'Generators',
    'socialiteproviders.github.io',
];

$repos = collect(range(1, 5))
    ->map(function (int $page) {
        return \Zttp\Zttp::withHeaders(['Accept' => 'application/vnd.github.v3+json'])->get('https://api.github.com/orgs/SocialiteProviders/repos?per_page=100&page='.$page)->json();
    })
    ->flatten(1)
    ->filter(function (array $repo) use ($excludedRepos) {
        return !$repo['archived'] && !in_array($repo['name'], $excludedRepos, true);
    })
    ->sortBy('name')
    ->each(function (array $repo) {
        $res = \Zttp\Zttp::withHeaders([
            'Accept'        => 'application/vnd.github.v3+json',
            'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
        ])
            ->patch($repo['url'], [
                'description' => sprintf('[READ ONLY] Subtree split of the SocialiteProviders/%s Provider (see SocialiteProviders/Providers)', $repo['name']),
                'homepage'    => sprintf('https://socialiteproviders.com/%s/', $repo['name']),
                'has_issues'  => false,
            ]);

        echo sprintf("Updated Repo: %s, response code: %s\n", $repo['name'], $res->status());

        $res = \Zttp\Zttp::withHeaders([
            'Accept'        => 'application/vnd.github.mercy-preview+json',
            'Authorization' => 'token '.getenv('GITHUB_TOKEN'),
        ])
            ->put($repo['url'].'/topics', [
                'names' => ['laravel', 'oauth', 'socialite', 'oauth1', 'oauth2', 'socialite-providers', 'social-media'],
            ]);

        echo sprintf("Updated Repo Topics: %s, response code: %s\n", $repo['name'], $res->status());
    });

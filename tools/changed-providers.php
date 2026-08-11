<?php

/**
 * Detect which providers need their test suite run for a diff range.
 *
 * Mirrors the change detection in release.php, but works across a range (a PR is
 * many commits) and only emits providers that actually have a suite under tests/.
 * Outputs a JSON array for use as a GitHub Actions matrix.
 *
 * Usage: php tools/changed-providers.php <base_ref> [head_ref]
 *
 * Environment overrides (for testing):
 *   TEST_PACKAGES - Comma-separated list of packages (skip change detection)
 */
$base = $argv[1] ?? null;
$head = $argv[2] ?? 'HEAD';

if (empty($base)) {
    echo "Usage: changed-providers.php <base_ref> [head_ref]\n";
    exit(1);
}

$root = dirname(__DIR__);

/**
 * Providers are only testable if someone has written tests for them.
 */
$hasTests = static fn (string $package): bool => is_dir($root.'/tests/'.$package);

$testable = static function (array $packages) use ($root, $hasTests): array {
    return array_values(array_filter($packages, static function (string $package) use ($root, $hasTests): bool {
        // Skip providers deleted in this range - there is nothing left to test.
        return is_dir($root.'/src/'.$package) && $hasTests($package);
    }));
};

$allProviders = static function () use ($root): array {
    return array_map('basename', glob($root.'/tests/*', GLOB_ONLYDIR));
};

/**
 * GitHub caps a matrix at 256 jobs per run. Once that many providers have tests,
 * a full run has to collapse into a single job rather than fanning out.
 *
 * @see https://docs.github.com/en/actions/reference/limits
 */
$emit = static function (array $packages): void {
    if (count($packages) > 256) {
        echo json_encode(['all'])."\n";

        return;
    }

    echo json_encode($packages)."\n";
};

if ($packages = getenv('TEST_PACKAGES')) {
    $emit($testable(array_map('trim', explode(',', $packages))));
    exit(0);
}

// Accept either "base head" or a single "base...head" range.
$range = str_contains($base, '..')
    ? escapeshellarg($base)
    : escapeshellarg($base).' '.escapeshellarg($head);

$output = shell_exec(sprintf('git -C %s diff --name-only %s', escapeshellarg($root), $range));

$changedFiles = array_filter(explode("\n", trim($output ?? '')));

// Changes to shared files can affect every provider.
$global = ['composer.json', 'composer.lock', 'phpunit.xml.dist', 'tests/TestCase.php', '.github/workflows/test.yml'];

foreach ($changedFiles as $file) {
    if (in_array($file, $global, true)) {
        $emit($testable($allProviders()));
        exit(0);
    }
}

$changedPackages = [];

foreach ($changedFiles as $file) {
    // A change to either the provider or its tests should run that suite.
    if (str_starts_with($file, 'src/') || str_starts_with($file, 'tests/')) {
        $parts = explode('/', $file);

        if (isset($parts[1])) {
            $changedPackages[$parts[1]] = true;
        }
    }
}

$emit($testable(array_keys($changedPackages)));

<?php

$overrides = json_decode(file_get_contents(__DIR__.'/../split-overrides.json'), true);
$packages = [];

foreach (glob(__DIR__.'/../src/*', GLOB_ONLYDIR) as $dir) {
    $name = basename($dir);
    $entry = ['package' => $name];

    if (isset($overrides[$name])) {
        $entry['split_repository'] = $overrides[$name];
    }

    $packages[] = $entry;
}

echo json_encode(['include' => $packages]);

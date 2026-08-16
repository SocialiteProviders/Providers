<?php

/**
 * Scaffolds a new provider under src/<Provider>/, and optionally a test suite
 * under tests/<Provider>/.
 *
 * Interactive by default. Any answer can be pre-supplied as a flag, which skips
 * that question; supply them all and it never prompts.
 *
 *   php tools/make-provider.php
 *   php tools/make-provider.php --name=Frappe --oauth=2
 *   php tools/make-provider.php --name=Frappe --oauth=2 --category=Misc --no-tests
 *
 * Flags:
 *   --name=<StudlyName>   Provider name, becomes src/<Name> and the namespace
 *   --alias=<slug>        Driver alias used by Socialite::driver() and the config key
 *   --oauth=<1|2>         OAuth version
 *   --category=<name>     Website category, must be one from the list
 *   --title=<name>        Display name, when it differs from --name (e.g. "Frappe / ERPNext")
 *   --config=<a,b>        Extra config keys for additionalConfigKeys()
 *   --pkce                OAuth2 only, sets $usesPKCE = true
 *   --tests / --no-tests  Generate tests/<Name>/ (default: yes)
 *   --force               Overwrite an existing src/<Name>
 *
 * The generated files come from tools/stubs/*.stub, rendered by replacing
 * {{ placeholder }} tokens. Edit the stubs rather than this file to change output.
 */
$root = dirname(__DIR__);

/**
 * Categories a provider may be filed under, mirroring the website's providers.js.
 *
 * sync-website.php validates the README frontmatter against the live file, so
 * this list only needs to stay roughly in step; a stale entry fails there, not here.
 */
const CATEGORIES = [
    'Social / Platform',
    'Gaming',
    'Education / Career',
    'Productivity / Business',
    'Government / University',
    'Payments',
    'Music',
    'Misc',
];

$options = parseArgs($argv);
$interactive = stream_isatty(STDIN);

// Name drives the directory, the namespace and the ExtendSocialite class, so it
// is the one answer with no usable default.
$name = trim((string) ($options['name'] ?? ask('Provider name (StudlyCase)', null, $interactive)));

if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) {
    fail("Provider name must be StudlyCase and alphanumeric, got \"{$name}\".");
}

$providerDir = $root.'/src/'.$name;

if (is_dir($providerDir) && ! isset($options['force'])) {
    fail("src/{$name} already exists. Pass --force to overwrite.");
}

$alias = trim((string) ($options['alias'] ?? ask('Driver alias', strtolower($name), $interactive)));

if (! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $alias)) {
    fail("Driver alias must be lowercase kebab-case, got \"{$alias}\".");
}

$oauth = (string) ($options['oauth'] ?? ask('OAuth version (1 or 2)', '2', $interactive));

if (! in_array($oauth, ['1', '2'], true)) {
    fail("OAuth version must be 1 or 2, got \"{$oauth}\".");
}

$title = trim((string) ($options['title'] ?? ask('Display name', $name, $interactive)));
$category = resolveCategory($options['category'] ?? null, $interactive);

// OAuth1 has no scopes and no PKCE, so both questions only apply to OAuth2.
$usesPkce = false;
$configKeys = [];

if ($oauth === '2') {
    $usesPkce = isset($options['pkce']) ? true : confirm('Use PKCE?', false, $interactive);

    $rawConfig = $options['config'] ?? ask(
        'Extra config keys, comma separated (e.g. base_url), blank for none',
        '',
        $interactive
    );

    $configKeys = array_values(array_filter(array_map('trim', explode(',', (string) $rawConfig))));

    foreach ($configKeys as $key) {
        if (! preg_match('/^[a-z0-9_]+$/', $key)) {
            fail("Config keys must be lowercase snake_case, got \"{$key}\".");
        }
    }
}

$withTests = isset($options['no-tests'])
    ? false
    : (isset($options['tests']) ? true : confirm('Generate tests?', true, $interactive));

$written = [];

@mkdir($providerDir, 0755, true);

$written[] = write($providerDir.'/Provider.php', $oauth === '1'
    ? render('provider.oauth1', [
        'name'       => $name,
        'identifier' => strtoupper($name),
    ])
    : render('provider.oauth2', [
        'name'       => $name,
        'identifier' => strtoupper($name),
        'pkce'       => $usesPkce ? "\n    protected \$usesPKCE = true;\n" : '',
        'config'     => additionalConfigKeys($configKeys),
    ]));

$written[] = write($providerDir.'/'.$name.'ExtendSocialite.php', render('extend-socialite', [
    'name'  => $name,
    'alias' => $alias,
]));

$written[] = write($providerDir.'/composer.json', composerJson($name, $alias, $title));

$written[] = write($providerDir.'/README.md', render('readme', [
    'name'        => $name,
    'alias'       => $alias,
    'title'       => $title,
    'category'    => $category,
    // The name frontmatter only earns its place when it differs from the directory.
    'nameLine'    => $title === $name ? '' : "\nname: {$title}",
    'configLines' => serviceConfigLines($alias, $configKeys),
]));

if ($oauth === '1') {
    $written[] = write($providerDir.'/Server.php', render('server.oauth1', ['name' => $name]));
}

if ($withTests) {
    $testDir = $root.'/tests/'.$name;
    @mkdir($testDir.'/fixtures', 0755, true);

    $written[] = write($testDir.'/'.$name.'ProviderTest.php', $oauth === '1'
        ? render('test.oauth1', ['name' => $name, 'identifier' => strtoupper($name)])
        : testOAuth2($name, $configKeys, $usesPkce));

    $written[] = write($testDir.'/fixtures/user.json', userFixture());
}

foreach ($written as $path) {
    echo 'Created ', substr($path, strlen($root) + 1), "\n";
}

echo "\nNext:\n";
echo "  1. Fill in the TODOs in src/{$name}/Provider.php against the provider's OAuth docs.\n";
echo "  2. Link those docs in the README, a reviewer will ask for them.\n";
echo "  3. Add yourself to \"authors\" in src/{$name}/composer.json.\n";

if ($withTests) {
    echo "  4. Replace tests/{$name}/fixtures/user.json with a real response body.\n";
    echo "     vendor/bin/phpunit tests/{$name}\n";
}

echo "  vendor/bin/pint src/{$name}\n";

/**
 * Render a stub from tools/stubs, replacing {{ token }} placeholders.
 *
 * Unreplaced tokens are a bug in the caller rather than valid output, so they
 * fail loudly instead of shipping "{{ foo }}" into a generated file.
 *
 * @param  array<string, string>  $replacements
 */
function render(string $stub, array $replacements): string
{
    $path = __DIR__.'/stubs/'.$stub.'.stub';

    if (! is_file($path)) {
        fail("Missing stub: {$path}");
    }

    $contents = (string) file_get_contents($path);

    foreach ($replacements as $key => $value) {
        $contents = str_replace('{{ '.$key.' }}', $value, $contents);
    }

    if (preg_match('/\{\{ (\w+) \}\}/', $contents, $matches)) {
        fail("Stub {$stub} has an unreplaced placeholder: {{ {$matches[1]} }}");
    }

    return $contents;
}

/**
 * The additionalConfigKeys() method, or nothing when the provider has no extra keys.
 *
 * @param  list<string>  $configKeys
 */
function additionalConfigKeys(array $configKeys): string
{
    if ($configKeys === []) {
        return '';
    }

    return sprintf(
        "\n    public static function additionalConfigKeys(): array\n    {\n        return ['%s'];\n    }\n",
        implode("', '", $configKeys)
    );
}

/**
 * The services.php config block for the README.
 *
 * @param  list<string>  $configKeys
 */
function serviceConfigLines(string $alias, array $configKeys): string
{
    $upper = strtoupper(str_replace('-', '_', $alias));

    $lines = [
        "  'client_id' => env('{$upper}_CLIENT_ID'),",
        "  'client_secret' => env('{$upper}_CLIENT_SECRET'),",
        "  'redirect' => env('{$upper}_REDIRECT_URI'),",
    ];

    foreach ($configKeys as $key) {
        $lines[] = sprintf("  '%s' => env('%s_%s'),", $key, $upper, strtoupper($key));
    }

    return implode("\n", $lines);
}

/**
 * The OAuth2 test, which grows a configured() helper when the provider takes
 * extra config keys (it can't be constructed without them).
 *
 * @param  list<string>  $configKeys
 * @param  bool  $usesPkce  Switches the request factory, since PKCE needs a session.
 */
function testOAuth2(string $name, array $configKeys, bool $usesPkce): string
{
    $hasConfig = $configKeys !== [];

    // Pint's ordered_imports fixer sorts these, so sort here rather than
    // leaving the generated file failing its own lint.
    $useStatements = [
        'GuzzleHttp\\Psr7\\Response',
        'SocialiteProviders\\'.$name.'\\Provider',
        'SocialiteProviders\\Tests\\TestCase',
    ];

    if ($hasConfig) {
        $useStatements[] = 'Illuminate\\Http\\Request';
        $useStatements[] = 'SocialiteProviders\\Manager\\Config';
    }

    sort($useStatements);

    $configured = '';

    if ($hasConfig) {
        // Pint's binary_operator_spaces fixer aligns the => across the block.
        $width = max(array_map('strlen', $configKeys));

        $sets = implode("\n", array_map(
            fn (string $key) => sprintf(
                "                %s => 'TODO',",
                str_pad("'".$key."'", $width + 2)
            ),
            $configKeys
        ));

        $configured = render('test-configured', ['configSets' => $sets]);
    }

    // usesPKCE() reads the session even under stateless(), so a PKCE provider
    // needs a session-backed request or redirect()/user() both throw.
    $request = fn (string $query) => $usesPkce
        ? sprintf('$this->makeRequestWithSession(%s)', $query)
        : sprintf('$this->makeRequest(%s)', $query);

    return render('test.oauth2', [
        'name'            => $name,
        'imports'         => implode("\n", array_map(fn (string $u) => 'use '.$u.';', $useStatements)),
        'configured'      => $configured,
        'redirectRequest' => $request(''),
        'redirectSubject' => $hasConfig ? '$this->configured($request)' : '$this->makeProvider($request)',
        'userRequest'     => $request("['code' => 'code', 'state' => 'state']"),
        'userSubject'     => $hasConfig
            ? '$this->configured($request, $responses)'
            : '$this->makeProvider($request, $responses)',
    ]);
}

function composerJson(string $name, string $alias, string $title): string
{
    // Encoded rather than rendered from a stub so the PSR-4 namespace escaping
    // and key ordering are handled by json_encode.
    $json = [
        'name'        => 'socialiteproviders/'.$alias,
        'description' => $title.' OAuth Provider for Laravel Socialite',
        'license'     => 'MIT',
        'keywords'    => [$alias, 'laravel', 'oauth', 'provider', 'socialite'],
        // "authors" is intentionally omitted: a placeholder email fails
        // `composer validate --strict`. Add yourself before opening the PR.
        'support' => [
            'issues' => 'https://github.com/socialiteproviders/providers/issues',
            'source' => 'https://github.com/socialiteproviders/providers',
            'docs'   => 'https://socialiteproviders.com/'.$alias,
        ],
        'require' => [
            'php'                        => '^8.3',
            'socialiteproviders/manager' => '^4.4',
        ],
        'autoload' => [
            'psr-4' => ['SocialiteProviders\\'.$name.'\\' => ''],
        ],
    ];

    return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function userFixture(): string
{
    // TODO markers would end up in JSON, so the placeholder values carry the hint.
    return json_encode([
        'id'       => 'replace-with-a-real-response-body',
        'nickname' => 'jdoe',
        'name'     => 'J. Doe',
        'email'    => 'j@doe.com',
        'avatar'   => 'https://example.com/avatar.png',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Parse --flag and --flag=value into a map. Bare flags become true.
 *
 * @param  list<string>  $argv
 * @return array<string, string|true>
 */
function parseArgs(array $argv): array
{
    $options = [];

    foreach (array_slice($argv, 1) as $arg) {
        if (! str_starts_with($arg, '--')) {
            continue;
        }

        $arg = substr($arg, 2);

        if (str_contains($arg, '=')) {
            [$key, $value] = explode('=', $arg, 2);
            $options[$key] = $value;

            continue;
        }

        $options[$arg] = true;
    }

    return $options;
}

/**
 * Prompt for a value, falling back to $default on an empty answer.
 *
 * With no tty (CI, a pipe) there is nothing to prompt, so a missing answer with
 * no default is a hard error rather than a hang.
 */
function ask(string $question, ?string $default, bool $interactive): string
{
    if (! $interactive) {
        if ($default === null) {
            fail("Missing required answer for \"{$question}\" and stdin is not interactive.");
        }

        return $default;
    }

    $suffix = ($default !== null && $default !== '') ? " [{$default}]" : '';

    // A required answer is re-asked, but only while there is input left to read.
    // On EOF fgets() returns false forever, so bail rather than loop.
    while (true) {
        echo $question, $suffix, ': ';

        $line = fgets(STDIN);

        if ($line === false) {
            if ($default === null) {
                fail("No answer given for \"{$question}\".");
            }

            echo "\n";

            return $default;
        }

        $answer = trim($line);

        if ($answer !== '') {
            return $answer;
        }

        if ($default !== null) {
            return $default;
        }
    }
}

function confirm(string $question, bool $default, bool $interactive): bool
{
    if (! $interactive) {
        return $default;
    }

    // The [Y/n] hint stands in for the default here, so ask() is given an empty
    // one and an empty answer falls through to $default.
    $answer = strtolower(ask($question.($default ? ' [Y/n]' : ' [y/N]'), '', $interactive));

    return $answer === '' ? $default : str_starts_with($answer, 'y');
}

/**
 * Resolve the website category, either from the flag or a numbered menu.
 */
function resolveCategory(string|bool|null $flag, bool $interactive): string
{
    if (is_string($flag)) {
        foreach (CATEGORIES as $category) {
            if (strcasecmp($category, $flag) === 0) {
                return $category;
            }
        }

        fail(sprintf('Unknown category "%s". Valid: %s', $flag, implode(', ', CATEGORIES)));
    }

    if (! $interactive) {
        return 'Misc';
    }

    echo "Category:\n";

    foreach (CATEGORIES as $i => $category) {
        printf("  %d) %s\n", $i + 1, $category);
    }

    $default = (string) (array_search('Misc', CATEGORIES, true) + 1);
    $choice = (int) ask('Choice', $default, $interactive);

    return CATEGORIES[$choice - 1] ?? 'Misc';
}

function write(string $path, string $contents): string
{
    file_put_contents($path, rtrim($contents, "\n")."\n");

    return $path;
}

function fail(string $message): never
{
    fwrite(STDERR, "[error] {$message}\n");
    exit(1);
}

<?php

$stub = <<<DOC
### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled \$event) {
    \$event->extendSocialite('%PROVIDER_ALIAS%', \SocialiteProviders\%PROVIDER%\Provider::class);
});
```
<details>
<summary>
Laravel 10 or below
</summary>
Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected \$listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\%PROVIDER%\%PROVIDER%ExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage
DOC;

$directories = array_map('basename', glob('../src'.'/*', GLOB_ONLYDIR));

foreach ($directories as $provider) {
    $path = sprintf('%s/../src/%s/README.md', __DIR__, $provider);
    if (! file_exists($path)) {
        continue;
    }

    $existingReadmeContent = file_get_contents($path);

    if (str_contains($existingReadmeContent, '#### Laravel 11+')) {
        continue;
    }

    $newContent = preg_replace('/### Add provider event listener\n(.*)\n### Usage/sm', $stub, $existingReadmeContent);

    preg_match(
        "/extendSocialite\('(.*?)',/",
        file_get_contents(sprintf('%s/../src/%s/%sExtendSocialite.php', __DIR__, $provider, $provider)),
        $providerAlias
    );

    $doc = str_replace(
        ['%PROVIDER%', '%PROVIDER_LOWER%', '%PROVIDER_UPPER%', '%PROVIDER_ALIAS%'],
        [$provider, strtolower($provider), strtoupper($provider), $providerAlias[1] ?? $provider],
        $newContent
    );

    file_put_contents($path, $doc);

    echo sprintf("Updated doc for provider: %s\n", $provider);
}

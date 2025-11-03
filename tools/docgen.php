<?php

$stub = <<<DOC
# %PROVIDER%

```bash
composer require socialiteproviders/%PROVIDER_LOWER%
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'%PROVIDER_ALIAS%' => [
  'client_id' => env('%PROVIDER_UPPER%_CLIENT_ID'),
  'client_secret' => env('%PROVIDER_UPPER%_CLIENT_SECRET'),
  'redirect' => env('%PROVIDER_UPPER%_REDIRECT_URI')
],
```

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

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('%PROVIDER_ALIAS%')->redirect();
```

DOC;

$providersDir = realpath(__DIR__.'/../src');
$directories = array_map('basename', glob($providersDir.'/*', GLOB_ONLYDIR));

foreach ($directories as $provider) {
    $path = $providersDir.'/'.$provider.'/README.md';
    if (file_exists($path)) {
        continue;
    }

    preg_match(
        "/extendSocialite\('(.*?)',/",
        file_get_contents(sprintf('%s/../src/%s/%sExtendSocialite.php', __DIR__, $provider, $provider)),
        $providerAlias
    );

    $doc = str_replace(
        ['%PROVIDER%', '%PROVIDER_LOWER%', '%PROVIDER_UPPER%', '%PROVIDER_ALIAS%'],
        [$provider, strtolower($provider), strtoupper($provider), $providerAlias[1] ?? $provider],
        $stub
    );

    file_put_contents($path, $doc);

    echo sprintf("Generated doc for provider: %s\n", $provider);
}

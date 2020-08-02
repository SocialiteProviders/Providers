<?php

$stub = <<<DOC
# %PROVIDER%

```bash
composer require socialiteproviders/%PROVIDER_LOWER%
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'%PROVIDER_ALIAS%' => [    
  'client_id' => env('%PROVIDER_UPPER%_CLIENT_ID'),  
  'client_secret' => env('%PROVIDER_UPPER%_CLIENT_SECRET'),  
  'redirect' => env('%PROVIDER_UPPER%_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected \$listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\\%PROVIDER%\\\%PROVIDER%ExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('%PROVIDER_ALIAS%')->redirect();
```

DOC;

$directories = array_map('basename', glob('../src'.'/*', GLOB_ONLYDIR));

foreach ($directories as $provider) {
    $path = sprintf('%s/../src/%s/README.md', __DIR__, $provider);
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

---
title: "Acclaim"
---

```bash
// This assumes that you have composer installed globally
composer require socialiteproviders/acclaim
```

## Installation & Basic Usage

Please see the [Base Installation Guide](/INSTALL.md), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'acclaim' => [
    'client_id' => env('ACCLAIM_KEY'),
    'client_secret' => env('ACCLAIM_SECRET'),
    'redirect' => env('ACCLAIM_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](/INSTALL.md) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Acclaim\\AcclaimExtendSocialite@handle',
    ],
];
```

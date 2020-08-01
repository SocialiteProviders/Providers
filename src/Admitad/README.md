---
title: "Admitad"
---

```bash
// This assumes that you have composer installed globally
composer require socialiteproviders/admitad
```

## Installation & Basic Usage

Please see the [Base Installation Guide](/INSTALL.md), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'admitad' => [
    'client_id' => env('ADMITAD_KEY'),
    'client_secret' => env('ADMITAD_SECRET'),
    'redirect' => env('ADMITAD_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](/INSTALL.md) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Admitad\\AdmitadExtendSocialite@handle',
    ],
];
```

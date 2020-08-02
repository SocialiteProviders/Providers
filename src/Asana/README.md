---
title: "Asana"
---

```bash
composer require socialiteproviders/asana
```

## Installation & Basic Usage

Please see the [Base Installation Guide](/INSTALL.md), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'asana' => [
    'client_id' => env('ASANA_KEY'),
    'client_secret' => env('ASANA_SECRET'),
    'redirect' => env('ASANA_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](/INSTALL.md) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Asana\\AsanaExtendSocialite@handle',
    ],
];
```


### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('Asana')->redirect();
```

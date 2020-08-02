---
title: "Trakt"
---

```bash
composer require socialiteproviders/trakt
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage.html), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'trakt' => [    
  'client_id' => env('TRAKT_CLIENT_ID'),  
  'client_secret' => env('TRAKT_CLIENT_SECRET'),  
  'redirect' => env('TRAKT_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage.html) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Trakt\\TraktExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('Trakt')->redirect();
```

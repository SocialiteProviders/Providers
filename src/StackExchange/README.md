---
title: "StackExchange"
---

```bash
composer require socialiteproviders/stackexchange
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage.html), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'stackexchange' => [    
  'client_id' => env('STACKEXCHANGE_CLIENT_ID'),  
  'client_secret' => env('STACKEXCHANGE_CLIENT_SECRET'),  
  'redirect' => env('STACKEXCHANGE_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage.html) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\StackExchange\\StackExchangeExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('StackExchange')->redirect();
```

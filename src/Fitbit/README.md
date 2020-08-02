---
title: "Fitbit"
---

```bash
composer require socialiteproviders/fitbit
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage.html), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'fitbit' => [    
  'client_id' => env('FITBIT_CLIENT_ID'),  
  'client_secret' => env('FITBIT_CLIENT_SECRET'),  
  'redirect' => env('FITBIT_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage.html) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Fitbit\\FitbitExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('Fitbit')->redirect();
```

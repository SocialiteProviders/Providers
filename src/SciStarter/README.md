---
title: "SciStarter"
---

```bash
composer require socialiteproviders/scistarter
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage.html), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'scistarter' => [    
  'client_id' => env('SCISTARTER_CLIENT_ID'),  
  'client_secret' => env('SCISTARTER_CLIENT_SECRET'),  
  'redirect' => env('SCISTARTER_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage.html) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\SciStarter\\SciStarterExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('SciStarter')->redirect();
```

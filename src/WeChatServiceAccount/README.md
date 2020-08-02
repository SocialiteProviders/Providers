---
title: "WeChatServiceAccount"
---

```bash
composer require socialiteproviders/wechatserviceaccount
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage.html), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'wechat_service_account' => [    
  'client_id' => env('WECHATSERVICEACCOUNT_CLIENT_ID'),  
  'client_secret' => env('WECHATSERVICEACCOUNT_CLIENT_SECRET'),  
  'redirect' => env('WECHATSERVICEACCOUNT_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage.html) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\WeChatServiceAccount\\WeChatServiceAccountExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('wechat_service_account')->redirect();
```

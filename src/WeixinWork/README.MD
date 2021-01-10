# WeixinWork

```bash
composer require socialiteproviders/weixinwork
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'weixinwork' => [    
  'corpid' => env('WEIXINWORK_CORPID'),  //企业ID
  'client_id' => env('WEIXINWORK_CLIENT_ID'),  //应用ID
  'client_secret' => env('WEIXINWORK_CLIENT_SECRET'),  //企业自建应用Secret
  'redirect' => env('WEIXINWORK_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\WeixinWork\\WeixinWorkExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('weixinwork')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``avatar``
- ``email``
- ``mobile``
- ``gender``

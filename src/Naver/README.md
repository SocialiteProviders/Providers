# Naver

```bash
composer require socialiteproviders/naver
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'naver' => [
  'client_id' => env('NAVER_CLIENT_ID'),  
  'client_secret' => env('NAVER_CLIENT_SECRET'),  
  'redirect' => env('NAVER_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Naver\\NaverExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('naver')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``nickname``
- ``email``
- ``avatar``

### Reference

- [Naver API Reference](https://developers.naver.com/docs/common/openapiguide/)

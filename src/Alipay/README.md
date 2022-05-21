# Alipay

```bash
composer require socialiteproviders/alipay
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'alipay' => [
  'client_id' => env('ALIPAY_APP_ID'),
  'client_secret' => env('ALIPAY_RSA_PRIVATE_KEY'),
  'redirect' => env('ALIPAY_REDIRECT'),
  "sandbox" => env('ALIPAY_SANDBOX') == 'true'
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Alipay\\AlipayExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('alipay')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``email``
- ``avatar``
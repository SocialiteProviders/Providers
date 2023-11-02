# Acclaim

```bash
composer require socialiteproviders/dingtalk
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'dingtalk' => [
    'client_id' => env('DINGTALK_KEY'),
    'client_secret' => env('DINGTALK_SECRET'),
    'redirect' => env('DINGTALK_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\DingTalk\DingTalkExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('dingtalk')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``email``
- ``avatar``

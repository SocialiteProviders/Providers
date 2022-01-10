# Blackboard

```bash
composer require socialiteproviders/blackboard
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'blackboard' => [
    'subdomain' => env('BLACKBOARD_SUBDOMAIN')
    'client_id' => env('BLACKBOARD_CLIENT_ID'),
    'client_secret' => env('BLACKBOARD_CLIENT_SECRET'),
    'redirect' => env('BLACKBOARD_REDIRECT_URI'),
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Blackboard\\BlackboardExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('blackboard')->redirect();
```

### Returned User fields

- ``id``
- ``email`` (may be `null`)
- ``avatar`` (may be `null`)

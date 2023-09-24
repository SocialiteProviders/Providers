# Wave

```bash
composer require socialiteproviders/wave
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/),
then follow the provider specific instructions below.

### Prepare OAuth provider & application in Wave

Create a new application in the Wave Developer Portal to get the Client ID and Client Secret: (https://developer.waveapps.com/hc/en-us/articles/360019762711-Manage-Applications)

### Add configuration to `config/services.php`

```php
'wave' => [
    'client_id' => env('WAVE_CLIENT_ID'),
    'client_secret' => env('WAVE_CLIENT_SECRET'),
    'redirect' => 'https://example.com/wave/callback',
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`.
See the [Base Installation Guide](https://socialiteproviders.com/usage/) for
detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
    // ... other providers
    \SocialiteProviders\Wave\WaveExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use
Socialite (assuming you have the facade installed):

```php
return Socialite::driver('wave')->redirect();
```

To redirect to the authentication, and then:

```php
$user = Socialite::driver('wave')->user()
```

In the return function. The user will contain `id`, `first_name`, `last_name` and `email` fields populated from the OAuth source.

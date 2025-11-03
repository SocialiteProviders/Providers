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

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('wave', \SocialiteProviders\Wave\Provider::class);
});
```
<details>
<summary>
Laravel 10 or below
</summary>
Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Wave\WaveExtendSocialite::class.'@handle',
    ],
];
```
</details>

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

# VATSIM

```bash
composer require socialiteproviders/vatsim
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'vatsim' => [
  'client_id' => env('VATSIM_CLIENT_ID'),
  'client_secret' => env('VATSIM_CLIENT_SECRET'),
  'redirect' => env('VATSIM_REDIRECT_URI'),
  'test' => env('VATSIM_TEST'),
],
```

See [Configure VATSIM Connect Authentication](https://github.com/vatsimnetwork/developer-info/wiki/Connect)

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('vatsim', \SocialiteProviders\Vatsim\Provider::class);
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
        \SocialiteProviders\Vatsim\VatsimExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('vatsim')->redirect();
```

To add scopes to your Authentication you can use the below:

```php
return Socialite::driver('vatsim')->scopes(['full_name', 'email', 'vatsim_details', 'country'])->redirect();
```

To add required scopes (those the user cannot opt out from) to your Authentication you can use the below:

```php
return Socialite::driver('vatsim')->requiredScopes(['email'])->redirect();
```

### Returned User fields

- ``cid``
- ``first_name``
- ``last_name``
- ``full_name``
- ``email``
- ``rating``
- ``pilotrating``
- ``region``
- ``division``
- ``subdivision``

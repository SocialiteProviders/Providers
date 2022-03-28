# Steam

```bash
composer require socialiteproviders/steam
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

## Add configuration to `config/services.php`

```php
'steam' => [
  'client_id' => null,
  'client_secret' => env('STEAM_CLIENT_SECRET'),
  'redirect' => env('STEAM_REDIRECT_URI'),
  'allowed_hosts' => [
    'example.com',
  ]
],
```

### allowed_hosts
Set this for protect against authorization domain spoofing. When the user returns from the Steam login page, along with the OpenID validation, the return_to parameter will be checked against the available domains in `allowed_hosts`. 

If you don't specify the setting, then fraudsters have the opportunity to enter the application under other users

Issue resolved in https://github.com/SocialiteProviders/Providers/pull/817

By default this protection is disabled. It will only be active when allowed hosts is not equal to an empty array.


## Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Steam\SteamExtendSocialite::class.'@handle',
    ],
];
```

## Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('steam')->redirect();
```

## Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``avatar``

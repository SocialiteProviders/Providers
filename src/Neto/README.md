# Neto

```bash
composer require socialiteproviders/neto
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'neto' => [
  'client_id'         => env('NETO_CLIENT_ID'),
  'client_secret'     => env('NETO_CLIENT_SECRET'),
  'redirect'          => env('NETO_REDIRECT_URI'),
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Neto\\NetoExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed).

Make sure to set the `store_name` to the domain of the user's store

```php
return Socialite::driver('neto')->with(['store_domain' => 'mystore.neto.com.au'])->redirect();
```

# NFDI Login

```bash
composer require socialiteproviders/nfdilogin
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'nfdilogin' => [
  'client_id' => env('NFDILOGIN_CLIENT_ID'),
  'client_secret' => env('NFDILOGIN_CLIENT_SECRET'),
  'redirect' => env('NFDILOGIN_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('nfdilogin', \SocialiteProviders\NfdiLogin\Provider::class);
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
        \SocialiteProviders\NfdiLogin\NfdiLoginExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('nfdilogin')->redirect();
```

### Returned User Fields

- ``id``
- ``name``
- ``given_name``
- ``family_name``
- ``email``

## Funding

The development of this provider was supported by the German Research Foundation (DFG) within the project “Establishment of the National Research Data Infrastructure (NFDI)” in the consortium NFDI4Biodiversity (project number 442032008).

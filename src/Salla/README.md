# Salla

```bash
composer require socialiteproviders/salla
```
## Create Account & App on Salla Partner
First you need to register account on [Salla Partners Portal](https://salla.partners/) and create your app to get credentials `client_id` & `client_secret`.
See the docs for more info [Salla API Docs](https://docs.salla.dev)

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'salla' => [    
  'client_id' => env('SALLA_CLIENT_ID'),  
  'client_secret' => env('SALLA_CLIENT_SECRET'),  
  'redirect' => env('SALLA_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('salla', \SocialiteProviders\Salla\Provider::class);
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
        \SocialiteProviders\Salla\SallaExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('salla')->redirect();
```
After redirect, You may return User instance:
```php
return Socialite::driver('salla')->user();
```

### Returned User fields

- ``id``
- ``name``
- ``email``
- ``mobile``
- ``role``
- ``merchant`` 
- - ``id``
- - ``username``
- - ``name``
- - ``avatar``
- - ``plan``
- - ``status``
- - ``domain``
- - ``tax_number``
- - ``commercial_number``

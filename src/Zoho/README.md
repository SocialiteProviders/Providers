# Zoho

```bash
composer require socialiteproviders/zoho
```

## Register an application 

Add new application at [zoho.com](https://accounts.zoho.com/developerconsole).

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'zoho' => [    
  'client_id' => env('ZOHO_CLIENT_ID'),  
  'client_secret' => env('ZOHO_CLIENT_SECRET'),  
  'redirect' => env('ZOHO_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('zoho', \SocialiteProviders\Zoho\Provider::class);
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
        \SocialiteProviders\Zoho\ZohoExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('zoho')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``avatar``

### Reference

- [Zoho API Reference](https://www.zoho.com/developer/rest-api.html)

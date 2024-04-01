# Exment

```bash
composer require socialiteproviders/exment
```

## What is Exment?
Exment is open source software for managing information assets on the Web.  
For Web Database, SFA, CRM, Business improvement, ...  
<a href="https://github.com/exceedone/exment" target="_blank">GitHub</a>  
<a href="https://exment.net/docs/#/">Manual</a>  
<a href="https://exment.net/docs/#/api">How to config API</a>  
<a href="https://exment.net/reference/webapi.html">API Reference</a>

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'exment' => [    
  'client_id' => env('EXMENT_CLIENT_ID'),  
  'client_secret' => env('EXMENT_CLIENT_SECRET'),  
  'redirect' => env('EXMENT_REDIRECT_URI'),
  'exment_uri' => env('EXMENT_URI'), // NEED THIS ENV. This is endpoint to access Exment.
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('exment', \SocialiteProviders\Exment\Provider::class);
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
        \SocialiteProviders\Exment\ExmentExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('exment')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``

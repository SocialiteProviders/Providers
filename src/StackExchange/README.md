# StackExchange

```bash
composer require socialiteproviders/stackexchange
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below. Obtain StackExchange App Credentials [Here](https://stackapps.com/apps/oauth/register).

### Add configuration to `config/services.php`

```php
'stackexchange' => [    
  'client_id' => env('STACKEXCHANGE_CLIENT_ID'),  
  'client_secret' => env('STACKEXCHANGE_CLIENT_SECRET'),  
  'key' => env('STACKEXCHANGE_CLIENT_KEY'),
  'site' => env('STACKEXCHANGE_CLIENT_SITE', 'stackoverflow'),
  'redirect' => env('STACKEXCHANGE_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('stackexchange', \SocialiteProviders\StackExchange\Provider::class);
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
        \SocialiteProviders\StackExchange\StackExchangeExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('stackexchange')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``avatar``

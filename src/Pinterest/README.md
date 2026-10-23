# Pinterest

```bash
composer require socialiteproviders/pinterest
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'pinterest' => [    
  'client_id' => env('PINTEREST_CLIENT_ID'),  
  'client_secret' => env('PINTEREST_CLIENT_SECRET'),  
  'redirect' => env('PINTEREST_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('pinterest', \SocialiteProviders\Pinterest\Provider::class);
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
        \SocialiteProviders\Pinterest\PinterestExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('pinterest')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``

### Upgrading from 4.x

`id` is now Pinterest's stable account ID. Earlier versions returned the username, which users can change, so an ID stored against a local account could later match a different Pinterest user.

If you stored `getId()` from an earlier version, migrate on the next login: when no account matches the new ID, look it up once by the old value (`getNickname()`), then overwrite the stored provider ID with `getId()`.

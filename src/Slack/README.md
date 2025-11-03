# Slack

## Comparison to offical provider

Socialite now has an official slack provider, but there are some important differences between the socialite one and the offical one.
Namley, this provider allows you to request both user and bot scopes, and thus get both bot tokens and user tokens. See the section below on that.


https://laravel.com/docs/11.x/socialite#installation

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'slack' => [    
  'client_id' => env('SLACK_CLIENT_ID'),  
  'client_secret' => env('SLACK_CLIENT_SECRET'),  
  'redirect' => env('SLACK_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('slack', \SocialiteProviders\Slack\Provider::class);
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
        \SocialiteProviders\Slack\SlackExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('slack')->redirect();
```

This package allows you to request both bot and user scopes. User scopes are set using the standard `->scopes()` method, and bot scopes are via the `->botScopes()` method.

```php
return Socialite::driver('slack')->scopes(['identity.basic', 'identity.email', 'identity.team'])->botScopes(['chat:write','commands'])->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``
- ``avatar``
- ``organization_id``

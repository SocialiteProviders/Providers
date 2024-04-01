# HabrCareer

```bash
composer require socialiteproviders/habrcareer
```

## Register an application 

Add new application at [career.habr.com](https://career.habr.com/profile/applications/new).
In rare cases, the review timeout can reach 20 working days.

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'habrcareer' => [    
  'client_id' => env('HABRCAREER_CLIENT_ID'),  
  'client_secret' => env('HABRCAREER_CLIENT_SECRET'),  
  'redirect' => env('HABRCAREER_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('habrcareer', \SocialiteProviders\HabrCareer\Provider::class);
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
        \SocialiteProviders\HabrCareer\HabrCareerExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('habrcareer')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``

### Reference

- [Habr Career API Reference](https://career.habr.com/info/api)

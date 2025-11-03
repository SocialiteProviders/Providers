# HeadHunter

```bash
composer require socialiteproviders/headhunter
```

## Register an application 

Add new application at [hh.ru](https://dev.hh.ru/admin).
In rare cases, the review timeout can reach 20 working days.

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'headhunter' => [    
  'client_id' => env('HEADHUNTER_CLIENT_ID'),  
  'client_secret' => env('HEADHUNTER_CLIENT_SECRET'),  
  'redirect' => env('HEADHUNTER_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('headhunter', \SocialiteProviders\HeadHunter\Provider::class);
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
        \SocialiteProviders\HeadHunter\HeadHunterExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('headhunter')->redirect();
```

### Returned User fields

- ``id``
- ``nickname`` (same as ``email``)
- ``name``
- ``email``

### Reference

- [HeadHunter API Reference](https://github.com/hhru/api/blob/master/docs_eng/general.md)

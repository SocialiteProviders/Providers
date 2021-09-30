# VKontakte

```bash
composer require socialiteproviders/vkontakte
```

## Register an application 

Add new application at [vk.com](https://vk.com/editapp?act=create).

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'vkontakte' => [    
  'client_id' => env('VKONTAKTE_CLIENT_ID'),  
  'client_secret' => env('VKONTAKTE_CLIENT_SECRET'),  
  'redirect' => env('VKONTAKTE_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\VKontakte\VKontakteExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('vkontakte')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``

### Reference

- [Vk.com API Reference](https://vk.com/dev/methods)

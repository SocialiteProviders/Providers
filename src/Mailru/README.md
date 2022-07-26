# Mail.ru

```bash
composer require socialiteproviders/mailru
```

## Register an application 

Add new application at [mail.ru](https://oauth.mail.ru/app/).

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'mailru' => [    
  'client_id' => env('MAILRU_CLIENT_ID'),  
  'client_secret' => env('MAILRU_CLIENT_SECRET'),  
  'redirect' => env('MAILRU_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Mailru\MailruExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('mailru')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``

### Reference

- [Mail.ru API Reference](https://oauth.mail.ru/docs/)

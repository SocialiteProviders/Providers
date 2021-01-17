# Basecamp

```bash
composer require socialiteproviders/basecamp
```

## Register an application 

Add new application at [Basecamp](https://launchpad.37signals.com/integrations).

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'basecamp' => [    
  'client_id' => env('BASECAMP_CLIENT_ID'),  
  'client_secret' => env('BASECAMP_CLIENT_SECRET'),  
  'redirect' => env('BASECAMP_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Basecamp\\BasecampExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('basecamp')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``

### Reference

- [Basecamp API Reference](https://github.com/basecamp/api/blob/master/sections/authentication.md)

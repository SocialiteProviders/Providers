# MoiKrug

```bash
composer require socialiteproviders/moikrug
```

## Register an application 

Add new application at [career.habr.com](https://career.habr.com/profile/applications/new).
In rare cases, the review timeout can reach 20 working days.

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'moikrug' => [    
  'client_id' => env('MOIKRUG_CLIENT_ID'),  
  'client_secret' => env('MOIKRUG_CLIENT_SECRET'),  
  'redirect' => env('MOIKRUG_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\MoiKrug\\MoiKrugExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('moikrug')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``

### Reference

- [Habr Career API Reference](https://career.habr.com/info/api)

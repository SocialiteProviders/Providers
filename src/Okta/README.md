# Okta

```bash
composer require socialiteproviders/okta
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'okta' => [    
  'client_id' => env('OKTA_CLIENT_ID'),  
  'client_secret' => env('OKTA_CLIENT_SECRET'),  
  'redirect' => env('OKTA_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Okta\\OktaExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('okta')->redirect();
```

### Returned User fields

- ``id``
- ``email``
- ``email_verified``
- ``nickname``
- ``name``
- ``first_name``
- ``last_name``
- ``profileUrl``
- ``address``
- ``phone``

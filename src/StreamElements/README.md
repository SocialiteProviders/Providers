# StreamElements

```bash
composer require socialiteproviders/streamelements
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'streamelements' => [
  'client_id' => env('STREAMELEMENTS_CLIENT_ID'),  
  'client_secret' => env('STREAMELEMENTS_CLIENT_SECRET'),  
  'redirect' => env('STREAMELEMENTS_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\StreamElements\StreamElementsExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('streamelements')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``alias``
- ``email``
- ``avatar``
- ``type``
- ``verified``
- ``partner``
- ``suspended``

### Reference

- [StreamElements API Reference](https://docs.streamelements.com/docs/getting-started-1)

# Flickr

```bash
composer require socialiteproviders/flickr
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'flickr' => [    
  'client_id' => env('FLICKR_CLIENT_ID'),  
  'client_secret' => env('FLICKR_CLIENT_SECRET'),  
  'redirect' => env('FLICKR_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Flickr\\FlickrExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('flickr')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``

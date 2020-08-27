# Twitter

```bash
composer require socialiteproviders/twitter
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'twitter' => [    
  'client_id' => env('TWITTER_CLIENT_ID'),  
  'client_secret' => env('TWITTER_CLIENT_SECRET'),  
  'redirect' => env('TWITTER_REDIRECT_URI') 
],
```

### Add URL to Twitter

Your redirect or callback URL needs to be added to your app in the [Twitter Developers Dashboard](https://developer.twitter.com/en/apps).

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Twitter\\TwitterExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('twitter')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``

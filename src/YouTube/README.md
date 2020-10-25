# YouTube

```bash
composer require socialiteproviders/youtube
```

### Important Note

If the user does not have a youtube channel, all the user object fields will be `null`. Youtube no longer automatically creates channels. Consider using the main Google provider instead.

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'youtube' => [    
  'client_id' => env('YOUTUBE_CLIENT_ID'),  
  'client_secret' => env('YOUTUBE_CLIENT_SECRET'),  
  'redirect' => env('YOUTUBE_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\YouTube\\YouTubeExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('youtube')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``avatar``

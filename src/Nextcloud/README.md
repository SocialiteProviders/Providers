# Nextcloud

```bash
composer require socialiteproviders/nextcloud
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'nextcloud' => [    
  'client_id' => env('NEXTCLOUD_CLIENT_ID'),  
  'client_secret' => env('NEXTCLOUD_CLIENT_SECRET'),  
  'redirect' => env('NEXTCLOUD_REDIRECT_URI'),
  'instance_uri'  => env('NEXTCLOUD_BASE_URI')
],
```

You must include `index.php` in `instance_uri` if pretty URL is not configured.

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Nextcloud\\NextcloudExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('nextcloud')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``

# SharePoint

```bash
composer require socialiteproviders/sharepoint
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

**Please note, the latest version of this package requires you to use `services` config file. Previously enviroment variables were read directly, which is no longer supported**

```php
'sharepoint' => [    
  'client_id' => env('SHAREPOINT_CLIENT_ID'),  
  'client_secret' => env('SHAREPOINT_CLIENT_SECRET'),  
  'redirect' => env('SHAREPOINT_REDIRECT_URI'),
  'site_url' => env('SHAREPOINT_SITE_URL'), // Optional
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\SharePoint\SharePointExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('sharepoint')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``

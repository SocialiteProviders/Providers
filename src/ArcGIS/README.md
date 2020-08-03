# ArcGIS

```bash
composer require socialiteproviders/arcgis
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'arcgis' => [    
  'client_id' => env('ARCGIS_CLIENT_ID'),  
  'client_secret' => env('ARCGIS_CLIENT_SECRET'),  
  'redirect' => env('ARCGIS_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\ArcGIS\\ArcGISExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('arcgis')->redirect();
```

### Returned User fields

- ``id``
- ``nickname`` (same as ``id``)
- ``name``
- ``email``
- ``avatar``

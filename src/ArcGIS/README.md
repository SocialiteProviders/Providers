# ArcGIS

```bash
composer require socialiteproviders/arcgis
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'arcgis' => [    
  'client_id' => env('ARCGIS_CLIENT_ID'),  
  'client_secret' => env('ARCGIS_CLIENT_SECRET'),  
  'redirect' => env('ARCGIS_REDIRECT_URI'),

  // For ArcGIS Enterprise, add the following :
  'arcgis_host' => env('ARCGIS_HOST'), // required
  'arcgis_port' => env('ARCGIS_PORT'), // optional
  'arcgis_directory' => env('ARCGIS_DIRECTORY'), // required - make sure the directory points to Portal for ArcGIS
],
```

By default, the endpoint is ArcGIS Online. It can be customized for ArcGIS Enterprise with optional configurations.

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('arcgis', \SocialiteProviders\ArcGIS\Provider::class);
});
```
<details>
<summary>
Laravel 10 or below
</summary>
Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\ArcGIS\ArcGISExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('arcgis')->redirect();
```

### Returned User fields

- ``id``
- ``nickname`` (same as ``id``)
- ``name``
- ``email``
- ``avatar``

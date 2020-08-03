# MediaCube

```bash
composer require socialiteproviders/mediacube
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'mediacube' => [    
  'client_id' => env('MEDIACUBE_CLIENT_ID'),  
  'client_secret' => env('MEDIACUBE_CLIENT_SECRET'),  
  'redirect' => env('MEDIACUBE_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\MediaCube\\MediaCubeExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('mediacube')->redirect();
```

### Returned User fields

- ``id``
- ``first_name``
- ``last_name``
- ``email``

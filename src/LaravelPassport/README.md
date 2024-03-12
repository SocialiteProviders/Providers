# LaravelPassport

```bash
composer require socialiteproviders/laravelpassport
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'laravelpassport' => [    
  'client_id' => env('LARAVELPASSPORT_CLIENT_ID'),  
  'client_secret' => env('LARAVELPASSPORT_CLIENT_SECRET'),  
  'redirect' => env('LARAVELPASSPORT_REDIRECT_URI'),
  'host' => env('LARAVELPASSPORT_HOST'),
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\LaravelPassport\LaravelPassportExtendSocialite::class.'@handle',
    ],
];
```

### Passport server configuration note

If you are experiencing successful authentication, but the returned user contains null attributes, you may need to change your `routes/api.php` file.  The default routes file uses the `auth:sanctum` middleware:

```php
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

It may need to be changed to `auth:api` in order to return the correct attributes:

```php
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('laravelpassport')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``

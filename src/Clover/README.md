# Clover

```bash
composer require socialiteproviders/clover
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

Ensure the app has permission to read employees.

### Add configuration to `config/services.php`

```php
'clover' => [
  'client_id' => env('CLOVER_CLIENT_ID'),
  'client_secret' => env('CLOVER_CLIENT_SECRET'),
  'redirect' => env('CLOVER_REDIRECT_URI')
  'environment' => env('CLOVER_ENVIRONMENT', 'production'),
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\\Clover\\CloverExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('clover')->redirect();
```

Presumably you are using this OAuth provider in order to retrieve an API token for calling other API endpoints.

The user includes a `token` property that you can use to retrieve the API token like this:

```php
Route::get('clover/auth/callback', function () {
    $user = Socialite::driver('clover')->user();

    // Save this token somewhere for other use.
    $token = $user->token;
});
```

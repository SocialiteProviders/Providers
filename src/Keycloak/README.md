# Keycloak

```bash
composer require socialiteproviders/keycloak
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'keycloak' => [
  'client_id' => env('KEYCLOAK_CLIENT_ID'),
  'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
  'redirect' => env('KEYCLOAK_REDIRECT_URI'),
  'base_url' => env('KEYCLOAK_BASE_URL'),   // Specify your keycloak server URL here
  'realms' => env('KEYCLOAK_REALM')         // Specify your keycloak realm
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Keycloak\KeycloakExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('keycloak')->redirect();
```

To logout of your app and Keycloak:
```php
public function logout() {
    Auth::logout(); // Logout of your app
    
    // Keycloak v18+ does not support a redirect URL
    return redirect(Socialite::driver('keycloak')->getLogoutUrl());
    
    // Keycloak before v18 does support a redirect URL
    $redirectUri = Config::get('app.url'); // The URL the user is redirected to
    return redirect(Socialite::driver('keycloak')->getLogoutUrl($redirectUri)); // Redirect to Keycloak
}
```

#### Keycloak <= 3.2

Keycloak below v3.2 requires no scopes to be set. Later versions require the `openid` scope for all requests.

```php
return Socialite::driver('keycloak')->scopes([])->redirect();
```

See [the upgrade guide](https://www.keycloak.org/docs/12.0/upgrading/#migrating-to-3-2-0).

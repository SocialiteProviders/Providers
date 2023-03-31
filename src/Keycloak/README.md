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
    // Logout of your app.
    Auth::logout();
    
    // The user will not be redirected back.
    return redirect(Socialite::driver('keycloak')->getLogoutUrl());
    
    // The URL the user is redirected to after logout.
    $redirectUri = Config::get('app.url');
    
    // Keycloak v18+ does support a post_logout_redirect_uri in combination with a
    // client_id or an id_token_hint parameter or both of them.
    // NOTE: You will need to set valid post logout redirect URI in Keycloak.
    return redirect(Socialite::driver('keycloak')->getLogoutUrl($redirectUri, env('KEYCLOAK_CLIENT_ID')));
    return redirect(Socialite::driver('keycloak')->getLogoutUrl($redirectUri, null, 'YOUR_ID_TOKEN_HINT'));
    return redirect(Socialite::driver('keycloak')->getLogoutUrl($redirectUri, env('KEYCLOAK_CLIENT_ID'), 'YOUR_ID_TOKEN_HINT'));
    
    // You may add additional allowed parameters as listed in
    // https://openid.net/specs/openid-connect-rpinitiated-1_0.html
    return redirect(Socialite::driver('keycloak')->getLogoutUrl($redirectUri, CLIENT_ID, null, ['state' => '...'], ['ui_locales' => 'de-DE']));
    
    // Keycloak before v18 does support a redirect URL
    // to redirect back to Keycloak.
    return redirect(Socialite::driver('keycloak')->getLogoutUrl($redirectUri));
}
```

#### Keycloak <= 3.2

Keycloak below v3.2 requires no scopes to be set. Later versions require the `openid` scope for all requests.

```php
return Socialite::driver('keycloak')->scopes([])->redirect();
```

See [the upgrade guide](https://www.keycloak.org/docs/12.0/upgrading/#migrating-to-3-2-0).

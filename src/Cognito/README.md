# Cognito

```bash
composer require socialiteproviders/cognito
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'cognito' => [
    'host' => env('COGNITO_HOST'),
    'client_id' => env('COGNITO_CLIENT_ID'),
    'client_secret' => env('COGNITO_CLIENT_SECRET'),
    'redirect' => env('COGNITO_CALLBACK_URL'),
    'scope' => explode(",", env('COGNITO_LOGIN_SCOPE')),
    'logout_uri' => env('COGNITO_SIGN_OUT_URL')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('cognito', \SocialiteProviders\Cognito\Provider::class);
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
        \SocialiteProviders\Cognito\CognitoExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('cognito')->redirect();
```


Logout of app and cognito then redirect to url
```php
public function cognitoLogout() {
    Auth::logout(); // Log out app
    return redirect(Socialite::driver('cognito')->logoutCognitoUser()); // Call cognito logout url
}
```

Logout of app and cognito then redirect back to login UI.
```php
public function cognitoSwitchAccount() {
    Auth::logout(); // Log out app
    $scopes = explode(",", env('COGNITO_LOGIN_SCOPE')); // Override default scopes if needed
    return redirect(Socialite::driver('cognito')->scopes($scopes)->switchCognitoUser()); // Call cognito logout url
}
```

Example env
```php
COGNITO_HOST=https://your-app.auth.ap-southeast-2.amazoncognito.com
COGNITO_CLIENT_ID=abc123
COGNITO_CLIENT_SECRET=abc123
COGNITO_CALLBACK_URL=https://your-app.ngrok.io/oauth2/callback
COGNITO_SIGN_OUT_URL=https://example.com
COGNITO_LOGIN_SCOPE="openid,profile"
```

#### Helpful tips
- Cognito requires SSL, try ngrok for local testing (works for everything except logout url).
- Returned user array contains all available attributes (set these in your cognito client app).
- If receiving state errors try this `$user = Socialite::driver('cognito')->stateless()->user();`
- "sub" is Cognito UUID, [more info on attributes](https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims)
- .env COGNITO_CALLBACK_URL must in your Cognito client app Callback URL(s)
- .env COGNITO_SIGN_OUT_URL must in your Cognito client app Sign out URL(s)

[project setup tutorial](https://blog.jamessiebert.com/laravel-socialite-aws-cognito-tutorial/)

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``
- ``user[]`` [Available Cognito Attributes](https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims)


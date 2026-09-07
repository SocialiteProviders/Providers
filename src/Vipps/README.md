---
category: Payments
---

# Vipps

```bash
composer require socialiteproviders/vipps
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

This provider implements the [Vipps MobilePay Login authorization-code flow](https://developer.vippsmobilepay.com/docs/APIs/login-api/api-guide/browser-flow-integration/) using sales unit credentials. It supports login from desktop and mobile browsers, and authorization parameters for native app flows.

Enable Login for your sales unit in the [Vipps MobilePay business portal](https://portal.vippsmobilepay.com), obtain its client ID and secret, and register your redirect URI. The URI must exactly match your configured `redirect`, including capitalization and trailing slashes. See the [Login quick start](https://developer.vippsmobilepay.com/docs/APIs/login-api/login-api-quick-start/).

### Add configuration to `config/services.php`

```php
'vipps' => [
  'client_id' => env('VIPPS_CLIENT_ID'),
  'client_secret' => env('VIPPS_CLIENT_SECRET'),
  'redirect' => env('VIPPS_REDIRECT_URI'),
  'test_mode' => env('VIPPS_TEST_MODE', false),
  'token_auth_method' => env('VIPPS_TOKEN_AUTH_METHOD', 'client_secret_basic'),
  'merchant_serial_number' => env('VIPPS_MERCHANT_SERIAL_NUMBER'),
],
```

`test_mode` selects `apitest.vipps.no`; production uses `api.vipps.no`. Use the matching environment's credentials and Vipps MobilePay test app when testing.

`token_auth_method` supports `client_secret_basic` (the default) and `client_secret_post`. It must match the sales unit's setting in the portal. `merchant_serial_number` is optional and adds the recommended `Merchant-Serial-Number` header. The provider also sends the Vipps system and plugin identification headers.

Endpoints are discovered from Vipps's OpenID configuration and cached for one hour using Laravel's default cache store, separately for each environment.

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('vipps', \SocialiteProviders\Vipps\Provider::class);
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
        \SocialiteProviders\Vipps\VippsExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('vipps')->redirect();
```

In the callback:

```php
$user = Socialite::driver('vipps')->user();

$id = $user->getId();
$name = $user->getName();
$email = $user->getEmail();
```

Both routes must use session middleware. The provider uses Socialite's session state validation and S256 PKCE by default. Preserve the same session between redirect and callback; do not use `stateless()` for this flow. Handle callback `error` parameters (including cancelled logins) in your application before calling `user()`.

The user is fetched from the authenticated Userinfo endpoint. The provider does not decode or validate the ID token. If your application uses ID-token claims, including `acr` for [in-app confirmation](https://developer.vippsmobilepay.com/docs/APIs/login-api/api-guide/in_app_confirmation/), it must validate the token and the required claims using an OIDC library.

### Scopes and additional user information

The default scopes are `openid name email`. Use `scopes()` to add scopes, or `setScopes()` to request only the information your application needs. The required `openid` scope is always included.

```php
return Socialite::driver('vipps')
    ->setScopes(['name', 'phoneNumber'])
    ->redirect();
```

Vipps uses case-sensitive scopes such as `phoneNumber` and `birthDate`, and uses `name` rather than `profile`. Scope availability depends on your sales unit's plan. See the [full scope documentation](https://developer.vippsmobilepay.com/docs/APIs/login-api/api-guide/user-info/#scopes).

The complete Userinfo response is available through `getRaw()`:

```php
$profile = $user->getRaw();

$phoneNumber = $profile['phone_number'] ?? null;
$birthdate = $profile['birthdate'] ?? null;
$addresses = $profile['other_addresses'] ?? [];
```

Use `getId()` (Vipps's `sub`) to identify accounts. It is specific to the sales unit; phone numbers and email addresses can change. Optional fields can be absent when their scopes are not requested or available.

For [Custom Flow consents](https://developer.vippsmobilepay.com/docs/APIs/login-api/api-guide/collecting-consents/), configure the flow in the portal and request `customFlow`; results are preserved in `$profile['flowResult']`. Legacy `delegatedConsents` and [Login Connect](https://developer.vippsmobilepay.com/docs/APIs/login-api/api-guide/login-connect/) data are also preserved when returned. Your application handles consent persistence, including subsequent logins where consent data is absent.

### Market and mobile app flows

Pass authorization parameters using Socialite's `with()` method:

```php
return Socialite::driver('vipps')
    ->with(['market' => 'NO'])
    ->redirect();
```

Supported markets are `NO`, `SE`, `DK`, and `FI`. Vipps selects the Vipps or MobilePay theme from the market.

For [simple native app login](https://developer.vippsmobilepay.com/docs/APIs/login-api/api-guide/mobile-app-flows/simple-login/), pass `requested_flow => app_to_app_v2`. PKCE is already enabled. Register an app redirect URI and have the app return the authorization code and state to the backend with the original session so the backend can complete the exchange. Keep the client secret on the backend.

For the [advanced app flow](https://developer.vippsmobilepay.com/docs/APIs/login-api/api-guide/mobile-app-flows/advanced-login/), pass `requested_flow => app_to_app` and the registered `app_callback_uri`. The native app must handle the intermediate callback and resume the original browser before the final authorization-code callback. Use an external browser or platform authentication session, as described in the Vipps guides.

### Scope of this provider

This is a Socialite redirect-based provider. Merchant-initiated CIBA login, partner-key authentication, and webhook registration/handling require separate integrations. Vipps Login does not support refresh tokens or merchant-initiated global logout; use your application's session logout.

### Returned User fields

- `id` — the sales-unit-specific `sub`
- `name` — nullable
- `email` — nullable
- `nickname` and `avatar` — always `null`

The access token, `expiresIn`, approved scopes, and original token response are retained by Socialite. Use the returned expiry rather than assuming a fixed lifetime.

# Apple

```bash
composer require socialiteproviders/apple
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'apple' => [
  'client_id' => env('APPLE_CLIENT_ID'),
  'client_secret' => env('APPLE_CLIENT_SECRET'),
  'redirect' => env('APPLE_REDIRECT_URI')
],
```

See [Configure Apple ID Authentication](https://developer.okta.com/blog/2019/06/04/what-the-heck-is-sign-in-with-apple)

> Note: the client secret used for "Sign In with Apple" is a JWT token that can have a maximum lifetime of 6 months. The article above explains how to generate the client secret on demand and you'll need to update this every 6 months. To generate the client secret for each request, see [Generating A Client Secret For Sign In With Apple On Each Request](https://bannister.me/blog/generating-a-client-secret-for-sign-in-with-apple-on-each-request)

If you don't have secret token, or you don't want to it do manually, you can use a private key ([see official docs](https://developer.apple.com/documentation/sign_in_with_apple/generate_and_validate_tokens#3262048)).
Add lines to the configuration as follows:

```php
'apple' => [
  'client_id' => env('APPLE_CLIENT_ID'), // Required. Bundle ID from Identifier in Apple Developer.
  'client_secret' => env('APPLE_CLIENT_SECRET'), // Empty. We create it from private key.
  'key_id' => env('APPLE_KEY_ID'), // Required. Key ID from Keys in Apple Developer.
  'team_id' => env('APPLE_TEAM_ID'), // Required. App ID Prefix from Identifier in Apple Developer.
  'private_key' => env('APPLE_PRIVATE_KEY'), // Required. Must be absolute path, e.g. /var/www/cert/AuthKey_XYZ.p8
  'passphrase' => env('APPLE_PASSPHRASE'), // Optional. Set if your private key have a passphrase.
  'signer' => env('APPLE_SIGNER'), // Optional. Signer used for Configuration::forSymmetricSigner(). Default: \Lcobucci\JWT\Signer\Ecdsa\Sha256
  'redirect' => env('APPLE_REDIRECT_URI'), // Required.

  'jwt_issued_time_leeway' => env('APPLE_JWT_ISSUED_TIME_LEEWAY'), // Optional. Set this to add a leeway to your JWT issued_time value. See section below
],
```

If you receive error `400 Bad Request {"error":"invalid_client"}` , a possible solution is to use another Signer (Asymmetric algorithms), see [Asymmetric algorithms](https://lcobucci-jwt.readthedocs.io/en/stable/supported-algorithms/#asymmetric-algorithms).


### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
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
        \SocialiteProviders\Apple\AppleExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('apple')->redirect();
```

#### Callback state and nonce

Apple posts the callback to your redirect URL as a cross-site `POST`
(`response_mode=form_post`, which Apple requires whenever scopes are
requested). On the callback the provider checks the `state`
against the session, and checks the identity token's `nonce` against the one
issued on redirect, so a callback the app did not start is rejected with
`InvalidStateException`.

Both checks need the session cookie to arrive with that `POST`. Laravel's
default `SameSite=lax` cookie is not sent on a cross-site `POST`, so a
default install sees an empty session and rejects every callback. You have
to let the cookie cross the site boundary:

```
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true
```

`SameSite=none` loosens every cookie the app sets, not just Apple's. If you
would rather keep `lax` elsewhere, run the flow stateless with
`statelessNonce()` instead (see below).

#### Without a session (stateless)

If you cannot keep the session on the callback, run the flow stateless and
let the provider manage the nonce through the cache with `statelessNonce()`:

```php
// Redirect. The provider generates the state and nonce, caches the nonce
// keyed by the state, and sends both to Apple.
return Socialite::driver('apple')->stateless()->statelessNonce()->redirect();

// Callback. The provider reads the state Apple echoed back, pulls the nonce
// from the cache (once), and verifies it against the identity token.
$user = Socialite::driver('apple')->stateless()->statelessNonce()->user();
```

The nonce is generated server-side, sent only to Apple, and consumed on the
first callback, so a callback the app did not start cannot present a matching
`state`/nonce pair. An unknown or already-used state is rejected with
`InvalidStateException`.

The cache entry is shared rather than tied to one browser, so this is a
correlation on the token binding rather than the session-bound `state` check
of the default flow. It is the CSRF protection [RFC 9700][rfc9700] allows a
correctly enforced nonce to provide, but if you can keep the session, the
default flow is stronger.

Configure the store and lifetime if the defaults (the default cache store,
600 seconds) do not suit you:

```php
'apple' => [
  // ...
  'nonce_cache_store' => env('APPLE_NONCE_CACHE_STORE'), // default cache store
  'nonce_cache_ttl'   => env('APPLE_NONCE_CACHE_TTL', 600), // seconds
],
```

If you want to hold the nonce somewhere other than the cache, call
`setNonce()` with a value you generate, send to Apple, and hand back
yourself. Calling `stateless()` without either throws `InvalidStateException`
rather than accepting the callback unprotected.

For native iOS and Android clients that already hand you an identity token,
use `userByIdentityToken()` (below) instead.

[rfc9700]: https://www.rfc-editor.org/rfc/rfc9700

Versions before 6.0.0 accepted the callback without a session, which allowed
login CSRF.

#### Native apps (identity token)

Native iOS and Android clients hand your server an identity token directly. Pass
the nonce from the authorization request to have it verified, as Apple requires:

```php
$user = Socialite::driver('apple')->userByIdentityToken($identityToken, $nonce);
```

The nonce is optional for backwards compatibility, but omitting it means a token
can be replayed until it expires.

### Returned User fields

- ``id``
- ``name``
- ``email``

`name` comes from the `user` field of the callback `POST`, not from the
signed identity token, and Apple only sends it on the first authorization.
Treat it as user input: validate and sanitise it before storing.

### Known Issues

#### JWT Issued_at
Sometimes the plugin may throw an exception due to a mismatch in time - See #1354. Use `config('services.apple.jwt_issued_time_leeway')` to 'rewind' the time. Default value is 3 seconds (PT3S).

Examples of possible values are PT3S -> 3 seconds, PT1M -> 1 Minute etc ...

The thrown exception may look like this:
```
[object] (Laravel\\Socialite\\Two\\InvalidStateException(code: 0): The token violates some mandatory constraints, details:                                                                                           - The token was issued in the future at /vendor/socialiteproviders/apple/Provider.php:207)                      [stacktrace]              
```

#### Invalid audience

The identity token's `aud` claim must match `config('services.apple.client_id')`.
Native apps send their bundle ID as the audience, which is not always the Services
ID used for the web flow - if the two differ, configure the one your clients
actually send.

The thrown exception looks like this:
```
Laravel\Socialite\Two\InvalidStateException: The token violates some mandatory constraints, details:
- The token is not allowed to be used by this audience
```

### Reference

- [Apple API Reference](https://developer.apple.com/documentation/signinwithapplerestapi/)

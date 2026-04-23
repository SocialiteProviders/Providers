# OpenID Connect

```bash
composer require socialiteproviders/openidconnect
```

A generic [OpenID Connect](https://openid.net/connect/) provider for Laravel Socialite. The provider discovers its endpoints via the issuer's `/.well-known/openid-configuration` document, supports JWT signature verification (JWKS or a configured public key), nonce validation, and PKCE.

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/),
then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'openidconnect' => [
    'base_url'       => env('OIDC_BASE_URL'),
    'client_id'      => env('OIDC_CLIENT_ID'),
    'client_secret'  => env('OIDC_CLIENT_SECRET'),
    'redirect'       => env('OIDC_REDIRECT_URI'),

    // Optional: space-separated list of extra scopes to request in addition
    // to the defaults ('openid email profile').
    'scopes'         => env('OIDC_SCOPES'),

    // Optional: when true, id_token signatures will be verified using the
    // provider's JWKS (or 'jwt_public_key' below if set).
    'verify_jwt'     => env('OIDC_VERIFY_JWT', false),

    // Optional: PEM-encoded public key used to verify id_token signatures
    // instead of fetching the JWKS.
    'jwt_public_key' => env('OIDC_JWT_PUBLIC_KEY'),

    // Optional: signing algorithm hint (e.g. RS256, RS512, ES256, PS256).
    // Defaults to the alg in the id_token header, or RS256.
    'jwt_algorithm'  => env('OIDC_JWT_ALGORITHM'),

    // Optional: override the expected `iss` claim. Defaults to the issuer
    // reported by the discovery document.
    'issuer'         => env('OIDC_ISSUER'),

    // Optional: client authentication method at the token endpoint.
    // Accepts 'client_secret_basic' or 'client_secret_post'. If omitted,
    // the provider picks whichever the IdP advertises (preferring basic).
    'token_auth_method'        => env('OIDC_TOKEN_AUTH_METHOD'),

    // Optional: default post-logout redirect URI used by the logout() helper.
    'post_logout_redirect_uri' => env('OIDC_POST_LOGOUT_REDIRECT_URI'),

    // Optional: TTL (in seconds) for the cached discovery document and JWKS.
    // Defaults to 3600 (1 hour).
    'cache_ttl'                => env('OIDC_CACHE_TTL'),

    // Optional: clock skew leeway (in seconds) applied to exp/nbf/iat. 0 by default.
    'clock_skew'               => env('OIDC_CLOCK_SKEW'),

    // Optional: Guzzle connect/read timeouts for IdP calls. Defaults: 5s/10s.
    'http_connect_timeout'     => env('OIDC_HTTP_CONNECT_TIMEOUT'),
    'http_timeout'             => env('OIDC_HTTP_TIMEOUT'),
],
```

The Authorization Code Flow with PKCE is used by default (`response_type=code`, PKCE is enabled on the `Provider`).

`base_url` is the OIDC issuer URL (for example `https://id.example.com`). The provider appends `/.well-known/openid-configuration` automatically.

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('openidconnect', \SocialiteProviders\OpenIDConnect\Provider::class);
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
        \SocialiteProviders\OpenIDConnect\OpenIDConnectExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

```php
return Socialite::driver('openidconnect')->redirect();
```

```php
$user = Socialite::driver('openidconnect')->user();
```

The returned user is populated from the `id_token` claims, falling back to the `userinfo` endpoint when the id_token does not contain an email. Mapped fields include `id` (`sub`), `email`, `name`, `nickname`, `given_name`, `family_name`, `idp`, `role`, and `groups`. The raw user also contains `id_token`, which you should stash in the session at login if you want to drive RP-initiated logout later.

### RP-Initiated Logout

If the IdP advertises an `end_session_endpoint` in its discovery document, you can build a logout redirect:

```php
// In your login callback, stash the id_token so you can pass it back at logout.
session(['oidc_id_token' => $user->user['id_token']]);

// In your logout controller:
return Socialite::driver('openidconnect')
    ->logout(session('oidc_id_token'), route('home'));
```

Most IdPs require `id_token_hint` (the id_token from login) and a `post_logout_redirect_uri` that has been pre-registered with the client.

### Token Revocation

If the IdP advertises a `revocation_endpoint` (RFC 7009), you can revoke an access or refresh token server-side — useful at logout to invalidate the refresh token immediately rather than waiting for it to expire:

```php
// In your login callback, stash the refresh_token alongside the id_token.
$oidcUser = Socialite::driver('openidconnect')->user();
session([
    'oidc_id_token'      => $oidcUser->user['id_token'],
    'oidc_refresh_token' => $oidcUser->refreshToken,
]);

// In your logout controller:
if ($refresh = session('oidc_refresh_token')) {
    Socialite::driver('openidconnect')->revoke($refresh, 'refresh_token');
}

return Socialite::driver('openidconnect')
    ->logout(session('oidc_id_token'), route('home'));
```

The second argument to `revoke()` is a hint (`access_token` or `refresh_token`) and defaults to `refresh_token`. Returns `true` on a successful (200/204) response.

### Storing tokens persistently

The examples above use the session, which is fine when the user always logs out from the same browser session they logged in with. If you need refresh tokens to survive session expiry — for example, to call `revoke()` from a back-channel logout handler, or to refresh access tokens for background jobs — store them on the user record instead:

```php
// Migration
Schema::table('users', function (Blueprint $table) {
    $table->text('oidc_id_token')->nullable();
    $table->text('oidc_refresh_token')->nullable();
});

// User model
protected $casts = [
    'oidc_id_token'      => 'encrypted',
    'oidc_refresh_token' => 'encrypted',
];

// Login callback
$oidcUser = Socialite::driver('openidconnect')->user();
$user = User::updateOrCreate(
    ['email' => $oidcUser->email],
    [
        'name'               => $oidcUser->name,
        'oidc_id_token'      => $oidcUser->user['id_token'],
        'oidc_refresh_token' => $oidcUser->refreshToken,
    ],
);
Auth::login($user);

// Logout controller
if ($user->oidc_refresh_token) {
    Socialite::driver('openidconnect')->revoke($user->oidc_refresh_token, 'refresh_token');
}
$idToken = $user->oidc_id_token;
$user->update(['oidc_id_token' => null, 'oidc_refresh_token' => null]);

return Socialite::driver('openidconnect')->logout($idToken, route('home'));
```

Both tokens are bearer credentials — always encrypt them at rest (`encrypted` cast) and never expose them to the browser via JavaScript-readable storage.

### Back-Channel Logout

If you register a `backchannel_logout_uri` with the IdP, the IdP will POST a signed `logout_token` to that URL whenever the user logs out elsewhere (for example, another client in the same SSO federation). Your app must verify the token and destroy the matching local session.

#### Mapping IdP sessions to Laravel sessions

The IdP has no idea what your Laravel session ID is. It identifies the session by a `sid` claim that it mints itself and includes in both the id_token at login and the logout_token at logout. You are responsible for storing a mapping from `sid` → Laravel session ID at login time, and consulting it at logout time.

Prerequisites:

- The IdP advertises `backchannel_logout_session_supported: true` in its discovery document.
- Your client registration has `backchannel_logout_session_required` enabled so the IdP actually includes `sid` in issued tokens.

If the IdP does not support `sid`, logout tokens will only carry `sub`. You can still implement back-channel logout — you just have to kill **all** of the user's local sessions instead of only the one that ended. That is correct but coarser: a user logged in on three devices will be signed out of all three when they log out of one.

```php
// Migration
Schema::create('oidc_sessions', function (Blueprint $table) {
    $table->string('sid')->primary();             // the IdP's session id
    $table->string('laravel_session_id');         // session()->getId()
    $table->foreignId('user_id')->constrained();
    $table->timestamps();
    $table->index('user_id');
});
```

#### Recording the mapping at login

```php
$oidcUser = Socialite::driver('openidconnect')->user();
// ...find or create $user, Auth::login($user)...

if ($sid = $oidcUser->user['sid'] ?? null) {
    DB::table('oidc_sessions')->updateOrInsert(
        ['sid' => $sid],
        [
            'laravel_session_id' => session()->getId(),
            'user_id'            => $user->id,
            'updated_at'         => now(),
            'created_at'         => now(),
        ],
    );
}
```

`sid` arrives via the raw id_token claims on the user (`$oidcUser->user['sid']`), not as a first-class field on the Socialite user — it's a session attribute, not a user attribute.

#### Handling the logout POST

```php
Route::post('/oidc/backchannel-logout', function (Request $request) {
    try {
        $claims = Socialite::driver('openidconnect')
            ->verifyLogoutToken($request->input('logout_token'));
    } catch (\InvalidArgumentException $e) {
        return response('', 400);
    }

    // Replay protection: refuse a jti we've already processed.
    $jtiKey = 'oidc_logout_jti_'.$claims['jti'];
    if (Cache::has($jtiKey)) {
        return response('', 400);
    }
    Cache::put($jtiKey, true, now()->addHour());

    // Prefer sid (per-session); fall back to sub (all sessions for that user).
    $rows = ! empty($claims['sid'])
        ? DB::table('oidc_sessions')->where('sid', $claims['sid'])->get()
        : DB::table('oidc_sessions')
            ->join('users', 'users.id', '=', 'oidc_sessions.user_id')
            ->where('users.oidc_sub', $claims['sub'] ?? '')
            ->select('oidc_sessions.*')
            ->get();

    $handler = Session::getHandler();
    foreach ($rows as $row) {
        $handler->destroy($row->laravel_session_id);
    }

    DB::table('oidc_sessions')
        ->whereIn('laravel_session_id', $rows->pluck('laravel_session_id'))
        ->delete();

    return response('', 200);
})->withoutMiddleware(['web']); // no CSRF token or session cookie on IdP requests
```

The `sub`-fallback path assumes you store the IdP's `sub` claim on the user record (e.g. in an `oidc_sub` column) at login. If you don't need that fallback — because your IdP always emits `sid` — you can skip it.

`verifyLogoutToken()` validates the signature, `iss`, `aud`, `iat`/`exp`, `jti`, the required `events` claim, and the absence of a `nonce`. The caller is responsible for replay protection (de-duping `jti`) and actually invalidating the session.

# Logout, revocation & tokens

Ending sessions at the IdP, killing tokens, and reacting when the IdP ends a session first. Examples use `oidc_keycloak`; substitute your driver.

## Refreshing tokens

```php
$response = Socialite::driver('oidc_keycloak')->refreshToken($refreshToken);
// ['access_token' => ..., 'refresh_token' => ..., 'expires_in' => ...]
```

## RP-initiated logout

If the IdP advertises an `end_session_endpoint`, `logout()` builds the redirect that ends the session there too:

```php
// At login, stash the id_token. Most IdPs require it as id_token_hint:
session(['oidc_id_token' => $user->accessTokenResponseBody['id_token']]);

// In your logout controller:
return Socialite::driver('oidc_keycloak')
    ->logout(session('oidc_id_token'), route('home'));
```

The post-logout URI must be pre-registered with the client. The second argument overrides the `post_logout_redirect_uri` config.

`logout()` mints a `state` and stores it in the session. Validate it in the controller backing your post-logout URI. The stored value is consumed on first use, so a replayed redirect fails:

```php
if (! Socialite::driver('oidc_keycloak')->validateLogoutState($request)) {
    abort(403);
}

Auth::logout();
$request->session()->invalidate();
```

## Token revocation

If the IdP advertises a `revocation_endpoint` (RFC 7009), you can revoke tokens server-side. Useful at logout so a refresh token dies immediately instead of waiting out its lifetime:

```php
try {
    Socialite::driver('oidc_keycloak')->revoke($refreshToken, 'refresh_token');
} catch (Throwable $e) {
    report($e); // best-effort: never block the logout on it
}
```

Returns `true` for 200/204 and `false` otherwise. A conforming server answers 200 even for an already-revoked token, so `false` means the revocation genuinely didn't happen (rejected credentials, for example). Transport failures still throw, hence the wrap.

## Back-channel logout

If you register a `backchannel_logout_uri` with the IdP, it POSTs a signed `logout_token` there whenever the user logs out elsewhere (another app in the SSO federation, or an admin revoking the session). Your job: verify the token, destroy the matching local sessions.

### The endpoint

```php
Route::post('/oidc/backchannel-logout', function (Request $request) {
    try {
        $claims = Socialite::driver('oidc_keycloak')
            ->verifyLogoutToken($request->input('logout_token'));
    } catch (\InvalidArgumentException $e) {
        return response('', 400);
    }

    // Destroy sessions matching $claims['sid'] (that one session)
    // or $claims['sub'] (all of the user's sessions), see below.

    return response('', 200);
})->withoutMiddleware(['web']); // no CSRF token or session cookie on IdP requests
```

`verifyLogoutToken()` enforces the full spec: the signature (always, even with `verify_jwt => false`, because this token arrives unsolicited on an unauthenticated endpoint), `iss`, `aud`, `iat`/`exp`, the required `events` claim, the presence of `sub` and/or `sid`, and the absence of a `nonce`. It also records the `jti` atomically and refuses a token already acted on, so IdP retries and replays both land in the `catch`. Set `logout_token_replay_ttl` to `0` to handle replay yourself.

### Mapping `sid` to Laravel sessions

The IdP identifies the session with a `sid` claim it mints itself, present in both the id_token at login and the logout_token at logout. Store the mapping at login, consult it at logout:

```php
// Migration
Schema::create('oidc_sessions', function (Blueprint $table) {
    $table->string('sid')->primary();
    $table->string('laravel_session_id');
    $table->foreignId('user_id')->constrained();
    $table->timestamps();
});

// Login callback. sid arrives via the raw claims, not as a first-class field:
if ($sid = $oidcUser->getRaw()['sid'] ?? null) {
    DB::table('oidc_sessions')->updateOrInsert(['sid' => $sid], [
        'laravel_session_id' => session()->getId(),
        'user_id'            => $user->id,
        'updated_at'         => now(),
        'created_at'         => now(),
    ]);
}

// Back-channel endpoint:
$rows = DB::table('oidc_sessions')->where('sid', $claims['sid'])->get();

$handler = Session::getHandler();
foreach ($rows as $row) {
    $handler->destroy($row->laravel_session_id);
}

DB::table('oidc_sessions')->whereIn('sid', $rows->pluck('sid'))->delete();
```

If the IdP doesn't emit `sid` (check `backchannel_logout_session_supported` in its discovery document), logout tokens only carry `sub`. You can still implement this, but you kill all of that user's sessions instead of the one that ended.

## Storing tokens beyond the session

Session storage is fine when the user always logs out from the browser they logged in with. To revoke or refresh from a back-channel handler or a background job, store tokens on the user record instead. Encrypted, always:

```php
// User model
protected $casts = [
    'oidc_id_token'      => 'encrypted',
    'oidc_refresh_token' => 'encrypted',
];

// Login callback
$user = User::updateOrCreate(['email' => $oidcUser->email], [
    'name'               => $oidcUser->name,
    'oidc_id_token'      => $oidcUser->accessTokenResponseBody['id_token'],
    'oidc_refresh_token' => $oidcUser->refreshToken,
]);

// Logout controller
if ($user->oidc_refresh_token) {
    Socialite::driver('oidc_keycloak')->revoke($user->oidc_refresh_token);
}
$idToken = $user->oidc_id_token;
$user->update(['oidc_id_token' => null, 'oidc_refresh_token' => null]);

return Socialite::driver('oidc_keycloak')->logout($idToken, route('home'));
```

Both tokens are bearer credentials. Never expose them to JavaScript-readable storage.

# Onelogin

```bash
composer require socialiteproviders/onelogin
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'onelogin' => [
  'base_url' => env('ONELOGIN_BASE_URL'),
  'client_id' => env('ONELOGIN_CLIENT_ID'),
  'client_secret' => env('ONELOGIN_CLIENT_SECRET'),
  'redirect' => env('ONELOGIN_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('onelogin', \SocialiteProviders\Onelogin\Provider::class);
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
        \SocialiteProviders\Onelogin\OneloginExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('onelogin')->redirect();
```
Store a local copy in your callback:

```php
public function handleProviderCallback(\Illuminate\Http\Request $request)
{
    $user = Socialite::driver('onelogin')->user();
    $localUser = User::updateOrCreate(['email' => $user->email], [
        'email'         => $user->email,
        'name'          => $user->name,
        'token'         => $user->token,
        'id_token'      => $user->id_token,
        'refresh_token' => $user->refreshToken,
    ]);

    try {
        Auth::login($localUser);
    }
    catch (\Throwable $e) {
        return redirect('/login');
    }

    return redirect('/home');
}
```

Generate the logout url from your controller:
NOTE: https://developers.onelogin.com/openid-connect/api/logout
```php
public function logout(\Illuminate\Http\Request $request)
{
    $idToken = $request->user()->id_token;
    $logoutUrl = Socialite::driver('onelogin')->getLogoutUrl($idToken, URL::to('/'));
    Auth::logout();

    return redirect($logoutUrl);
}
```
#### Refresh Token
Using a refresh token allows an active user to maintain their session:


```php
$localUser = Auth::user();
$response = (object) Socialite::driver('onelogin')
    ->setScopes(['offline_access'])
    ->getRefreshTokenResponse($localUser->refresh_token);

$localUser->token         = $response->access_token;
$localUser->refresh_token = $response->refresh_token;

$localUser->save();
Auth::setUser($localUser);
```
NOTE: obtaining a `refresh_token` requires the scope `offline_access` on the initial login.

#### Client Token
To obtain a client access token for authenticating to other apps without a user:

```php
$response = (object) Socialite::driver('onelogin')->getClientAccessTokenResponse();
$token = $response->access_token;
```
NOTE: no caching of this token is performed. It's strongly suggested caching the token locally for its ttl


#### Revoke Token
Mark a token as revoked when checked against an introspection endpoint

```php
$repo = Socialite::driver('onelogin');
$repo->revokeToken($token, 'access_token');
// verify against introspection endpoint
$state = $repo->introspectToken($token, 'access_token');
if($state['active']){...};
```
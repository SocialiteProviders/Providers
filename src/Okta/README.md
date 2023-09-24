# Okta

```bash
composer require socialiteproviders/okta
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'okta' => [    
  'base_url' => env('OKTA_BASE_URL'),
  'client_id' => env('OKTA_CLIENT_ID'),  
  'client_secret' => env('OKTA_CLIENT_SECRET'),  
  'redirect' => env('OKTA_REDIRECT_URI') 
],
```

#### Multi Tenant SSO

If you need to authenticate users from multiple okta instances, you can dynamically set the configuration values prior to calling the `redirect`/`user` methods. You'll still need to add the services entry as per above, but you can leave all the values as `null`.

```php
$config = new \SocialiteProviders\Manager\Config(
    'client_id',
    'client_secret',
    route('okta.callback'),
    [
        'base_url' => 'https://1234.okta.com',
    ]
);

\Laravel\Socialite\Facades\Socialite::driver('okta')
    ->setConfig($config)
    ->redirect();
```

#### Custom Auth Server

If you're using Okta Developer you should set `auth_server_id` config option appropriately. It should be set to "default", or to the server id of your Custom Authorization Server.

For more information, see the [okta docs](https://developer.okta.com/docs/concepts/auth-servers/).

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Okta\OktaExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('okta')->redirect();
```

Store a local copy in your callback:

```php
public function handleProviderCallback(\Illuminate\Http\Request $request)
{
    $user = Socialite::driver('okta')->user();
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
        return redirect('/login-okta');
    }

    return redirect('/home');
}
```

Generate the logout url from your controller:

```php
public function logout(\Illuminate\Http\Request $request)
{
    $idToken = $request->user()->id_token;
    $logoutUrl = Socialite::driver('okta')->getLogoutUrl($idToken, URL::to('/'));
    Auth::logout();

    return redirect($logoutUrl);
}
```
#### Refresh Token
Using a refresh token allows an active user to maintain their session:


```php
$localUser = Auth::user();
$response = (object) Socialite::driver('okta')
    ->setScopes(['offline_access'])
    ->getRefreshTokenResponse($localUser->refresh_token);

$localUser->token         = $response->access_token;
$localUser->refresh_token = $response->refresh_token;

$localUser->save();
Auth::setUser($localUser);
```
NOTE: obtaining a `refresh_token` requires the scope `offline_access` on the initial login.
See additional documentation [here](https://developer.okta.com/docs/reference/api/oidc/#token).

#### Client Token
To obtain a client access token for authenticating to other apps without a user:

```php
$response = (object) Socialite::driver('okta')->getClientAccessTokenResponse();
$token = $response->access_token;
```
NOTE: no caching of this token is performed. It's strongly suggested caching the token locally for its ttl


#### Revoke Token
Mark a token as revoked when checked against an introspection endpoint

```php
$repo = Socialite::driver('okta');
$repo->revokeToken($token, 'access_token');
// verify against introspection endpoint
$state = $repo->introspectToken($token, 'access_token');
if($state['active']){...};
```

### Returned User fields

- ``id``
- ``email``
- ``email_verified``
- ``nickname``
- ``name``
- ``first_name``
- ``last_name``
- ``profileUrl``
- ``address``
- ``phone``

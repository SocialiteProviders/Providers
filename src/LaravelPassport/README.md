# Laravel Passport Provider

## INSTALLATION

### 1.COMPOSER

```bash
// This assumes that you have composer installed globally
composer require recca0120/laravelpassport-provider
```

### 2. SERVICE PROVIDER
- Remove Laravel\Socialite\SocialiteServiceProvider from your providers[] array in config\app.php if you have added it already.
- Add \SocialiteProviders\Manager\ServiceProvider::class to your providers[] array in config\app.php.

For example:

```php
'providers' => [
    // a whole bunch of providers
    // remove 'Laravel\Socialite\SocialiteServiceProvider',
    \SocialiteProviders\Manager\ServiceProvider::class, // add
];
```

- Note: If you would like to use the Socialite Facade, you need to [install it](https://github.com/laravel/socialite).

### 3. ADD THE EVENT AND LISTENERS
- Add SocialiteProviders\Manager\SocialiteWasCalled event to your listen[] array in  <app_name>/Providers/EventServiceProvider.

- Add your listeners (i.e. the ones from the providers) to the SocialiteProviders\Manager\SocialiteWasCalled[] that you just created.

- The listener that you add for this provider is 'SocialiteProviders\HumanApi\HumanApiExtendSocialite@handle',.

- Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

For example:

```php
/**
 * The event handler mappings for the application.
 *
 * @var array
 */
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // add your listeners (aka providers) here
        'SocialiteProviders\LaravelPassport\LaravelPassportExtendSocialite@handle',
    ],
];
```

`REFERENCE`
- [Laravel docs about events](https://laravel.com/docs/5.0/events)
- [Laracasts video on events in Laravel 5](https://laracasts.com/lessons/laravel-5-events)

### 4. CONFIGURATION SETUP
For development purpose, needed configuration is automatically retrieved from your .env if they are written as exactly shown below. However we recommend to **manually add an entry to the services configuration file** because after config files are cached for usage in production environment (Laravel command  artisan config:cache), **values stored in the .env file are not accessible anymore by the application and the provider won’t work**.

`APPEND PROVIDER VALUES TO YOUR **.ENV** FILE`

```ini
SERVICE_LARAVELPASSPORT_HOST=http://server.dev
SERVICE_LARAVELPASSPORT_CLIENT_ID=client_id
SERVICE_LARAVELPASSPORT_CLIENT_SECRET=client_secret
SERVICE_LARAVELPASSPORT_REDIRECT=http://client.dev/auth/laravelpassport/callback
```

`ADD TO **CONFIG/SERVICES.PHP**.`

```php
'laravelpassport' => [
    'host' => env('SERVICE_LARAVELPASSPORT_HOST'),
    'client_id' => env('SERVICE_LARAVELPASSPORT_CLIENT_ID'),
    'client_secret' => env('SERVICE_LARAVELPASSPORT_CLIENT_SECRET'),
    'redirect' => env('SERVICE_LARAVELPASSPORT_REDIRECT'),

    // optional
    'authorize_uri' => 'oauth/authorize', // if your authorize_uri isn't same, you can change it
    'token_uri' => 'oauth/token', // if your token_uri isn't same, you can change it
    'userinfo_uri' => 'api/user', // if your userinfo_uri isn't same, you can change it
    'userinfo_key' => '', // if your userinfo response is like {"data": {"id" => "xxx", "email" => "xxx@test.com"}} you can set userinfo_key 'userinfo_info' => 'data'
]
```

`REFERENCE`
- [Laravel docs on configuration](https://laravel.com/docs/master/configuration)

## USAGE
- You should now be able to use it like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('laravelpassport')->redirect();
```

### LUMEN SUPPORT

You can use Socialite providers with Lumen. Just make sure that you have facade support turned on and that you follow the setup directions properly.

**Note:** If you are using this with Lumen, all providers will automatically be stateless since Lumen does not keep track of state.

Also, configs cannot be parsed from the services[] in Lumen. You can only set the values in the .env file as shown exactly in this document. If needed, you can also override a config (shown below).

### STATELESS

- You can set whether or not you want to use the provider as stateless. Remember that the OAuth provider (Twitter, Tumblr, etc) must support whatever option you choose.

**Note:** If you are using this with Lumen, all providers will automatically be stateless since Lumen does not keep track of state.

```php
// to turn off stateless
return Socialite::with('laravelpassport')->stateless(false)->redirect();

// to use stateless
return Socialite::with('laravelpassport')->stateless()->redirect();
```

### OVERRIDING A CONFIG

If you need to override the provider’s environment or config variables dynamically anywhere in your application, you may use the following:

```php
$clientId = "secret";
$clientSecret = "secret";
$redirectUrl = "http://yourdomain.com/api/redirect";
$additionalProviderConfig = ['site' => 'meta.stackoverflow.com'];
$config = new \SocialiteProviders\Manager\Config($clientId, $clientSecret, $redirectUrl, $additionalProviderConfig);
return Socialite::with('laravelpassport')->setConfig($config)->redirect();
```

### RETRIEVING THE ACCESS TOKEN RESPONSE BODY

Laravel Socialite by default only allows access to the access_token. Which can be accessed via the  \Laravel\Socialite\User->token public property. Sometimes you need access to the whole response body which may contain items such as a refresh_token.

You can get the access token response body, after you called the user() method in Socialite, by accessing the property $user->accessTokenResponseBody;

```php
$user = Socialite::driver('laravelpassport')->user();
$accessTokenResponseBody = $user->accessTokenResponseBody;
```

laravelpassport

`REFERENCE`

- [Laravel Socialite Docs](https://github.com/laravel/socialite)
- [Laracasts Socialite video](https://laracasts.com/series/whats-new-in-laravel-5/episodes/9)

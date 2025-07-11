# RunSignup

```bash
composer require socialiteproviders/runsignup
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific
instructions below.

## Configuration

First, add your RunSignup app credentials to `config/services.php`:

```php
'runsignup' => [
    'rsu_env'        => env('RUNSIGNUP_ENV', 'prod'),		// RunSignup Environment (`prod`, `test`)
    'client_id'      => env('RUNSIGNUP_CLIENT_ID'),			// Your Client ID
    'client_secret'  => env('RUNSIGNUP_CLINET_SECRET'),		// Your Client Secret
    'redirect'       => env('RUNSIGNUP_REDIRECT_URI'),		// Your Callback URI
],
```

Make sure you have these in your `.env`:

```bash
RUNSIGNUP_ENV=selected_rsu_environment
RUNSIGNUP_CLIENT_ID=your_partner_center_app_key
RUNSIGNUP_CLINET_SECRET=your_partner_center_app_secret
RUNSIGNUP_REDIRECT_URI=https://yourapp.com/auth/runsignup/callback
```

### Register an application
1. Create a user on https://runsignup.com.
2. Navigate to https://runsignup.com/Profile/OAuth2 and click `App Development`.
3. Click the `Add Client` button and provide a name and a description. Additionally include the redirect URL that will be used in the integration.
4. Upon saving the new client details the Client ID and Client Secret will be displayed on the screen.  Be sure to note the Client Secret as it will only be displayed once. 

#### Test Environment
RunSignup offers a separate test platform which replicates the production environment and is ideal for use while in development. To use the test environment follow the above steps to register an application and replace all usages of https://runsignup.com with https://test.runsignup.com. Additionally be sure to add `RUNSIGNUP_ENV=test` to your `.env`.


### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('runsignup', \SocialiteProviders\RunSignup\Provider::class);
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
        \SocialiteProviders\RunSignup\RunSignupExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
use Laravel\Socialite\Facades\Socialite;

return Socialite::driver('runsignup')->redirect();
```

And handle the callback:

```php
$rsuUser = Socialite::driver('runsignup')->user();

// Access mapped fields:
$rsuUser->getId();
$rsuUser->getName();
$rsuUser->getEmail();
$rsuUser->getAvatar();
$rsuUser->token;
$rsuUser->refreshToken;
```

### Returned User fields

- ``id``
- ``name``
- ``email``
- ``avatar``

## About RunSignup
[RunSignup](https://runsignup.com) is an employee‑owned event‑tech company that offers a free, all‑in‑one platform for endurance races, ticketed events, and peer‑to‑peer fundraising events.  RunSignup handles everything from registration and marketing to race‑day tools and fundraising support.

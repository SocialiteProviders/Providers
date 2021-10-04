# WHMCS

```bash
composer require socialiteproviders/whmcs
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

## Specific configuration

Follow [this link](https://docs.whmcs.com/OpenID_Connect) to create OpenID credentials in your WHMCS installation

### Add configuration to `config/services.php`

```php
'whmcs' => [
    'client_id' => env('WHMCS_CLIENT_ID'),
    'client_secret' => env('WHMCS_CLIENT_SECRET'),
    'redirect' => env('WHMCS_REDIRECT_URI'),
    'url' => env('WHMCS_URL'), // URL of your WHMCS installation
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Whmcs\WhmcsExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('whmcs')->redirect();
```

### Returned User fields

- `id`
- `name`
- `email`

More fields are available under the `user` subkey:

```php
$user = Socialite::driver('whmcs')->user();

$locale = $user->user['locale'];
$email_verified = $user->user['email_verified'];
```

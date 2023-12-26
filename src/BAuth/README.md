# BAuth

```bash
composer require socialiteproviders/bauth
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'bauth' => [
  'client_id' => env('BAUTH_CLIENT_ID'),
  'client_secret' => env('BAUTH_CLIENT_SECRET'),
  'redirect' => env('BAUTH_REDIRECT_URI'),
],
```

And add the following lines to your `.env` file

```dotenv
BAUTH_CLIENT_ID=
BAUTH_CLIENT_SECRET=
BAUTH_REDIRECT_URI=
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\BAuth\BAuthExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('bauth')->redirect();
```

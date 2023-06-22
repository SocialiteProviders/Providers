# Leader ID

```bash
composer require socialiteproviders/leader-id
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'leader-id' => [
  'client_id' => env('LEADERID_CLIENT_ID'),
  'client_secret' => env('LEADERID_CLIENT_SECRET'),
  'redirect' => env('LEADERID_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\LeaderId\LeaderIdExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('leader-id')->redirect();
```

# Returned User Fields

- id
- email
- firstname
- lastname
- fathername

# Reference

- [Leader-id API documentation](https://apps.leader-id.ru/swagger/#/oauth/post_oauth_token)
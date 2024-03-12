# Clerk

This is a provider for [Clerk](https://clerk.com/)

```bash
composer require socialiteproviders/clerk
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'clerk' => [
  'client_id' => env('CLERK_CLIENT_ID'),
  'client_secret' => env('CLERK_CLIENT_SECRET'),
  'redirect' => env('CLERK_CALLBACK_URL'),
  'base_url' => env('CLERK_BASE_URL'),
],
```

### Add base URL to `.env`

Clerk provides a customer URL for your different projects. For this reason you need to provide a `base_url`.

```bash
CLERK_BASE_URL=https://example.clerk.accounts.dev
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Clerk\ClerkExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('clerk')->redirect();
```

### Returned User fields

-   `id`
-   `nickname`
-   `name`
-   `email`
-   `avatar`

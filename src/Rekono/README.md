# Rekono

```bash
composer require socialiteproviders/rekono
```

## About Rekono

[Rekono](https://rekono.si) is an electronic identity service that allows you to create and manage a single user account to log in to all services included in the Rekono system.

## Register an application

Client ID and secret can be obtained by requesting via Rekono Support email [mailto:support@rekono.si](support@rekono.si).
After registering a new client, access to technical documentation will be provided.

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'rekono' => [    
    'client_id' => env('REKONO_CLIENT_ID'),  
    'client_secret' => env('REKONO_CLIENT_SECRET'),  
    'redirect' => env('REKONO_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Rekono\\RekonoExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('rekono')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``

More fields - defined upon client registration - are available under the user subkey:

```php
$user = Socialite::driver('rekono')->user();

$locale = $user->user['locale'];
$email_verified = $user->user['email_verified'];
```
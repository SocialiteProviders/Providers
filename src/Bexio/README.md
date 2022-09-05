# Bexio

```bash
composer require socialiteproviders/bexio
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Create Bexio Application
Follow the "First steps" section from the Bexio developer's documentation
https://docs.bexio.com/#section/First-steps


### Add configuration to `config/services.php`
Use credentials obtained on the previous step.

```php
'bexio' => [
  'client_id' => env('BEXIO_CLIENT_ID'),
  'client_secret' => env('BEXIO_CLIENT_SECRET'),
  'redirect' => env('BEXIO_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Bexio\BexioExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('bexio')->redirect();
```

Getting a user on callback URL
```php
$externalUserData = Socialite::driver($driver)->user();
```

The $externalUserData variable will have a \SocialiteProviders\Manager\OAuth2\User instance 
with the following provider specific data:
```php
$externalUserData->id,// null
$externalUserData->email,// User's email
$externalUserData->nickname,// null
$externalUserData->given_name,// First name from bexio profile
$externalUserData->family_name,// Last name from bexio profile
$externalUserData->name,// First and Last names combined
$externalUserData->gender,// Gender from bexio profile
$externalUserData->locale,// en_GB string or other locale definition
```

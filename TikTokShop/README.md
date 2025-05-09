# TikTok Shop

```bash
composer require socialiteproviders/manager
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

## Configuration

First, add your TikTok Shop app credentials to `config/services.php`:

```php
'tiktokshop' => [
    'app_key'     => env('TIKTOKSHOP_APP_KEY'),       // Your Partner Center App Key
    'app_secret'  => env('TIKTOKSHOP_APP_SECRET'),    // Your Partner Center App Secret
    'redirect'    => env('TIKTOKSHOP_REDIRECT_URI'),  // Your callback URI
],
```

Make sure you have these in your `.env`:

```bash
TIKTOKSHOP_APP_KEY=your_partner_center_app_key
TIKTOKSHOP_APP_SECRET=your_partner_center_app_secret
TIKTOKSHOP_REDIRECT_URI=https://yourapp.com/auth/tiktokshop/callback
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, register the listener directly in your `AppServiceProvider@boot`:

```php
use SocialiteProviders\Manager\SocialiteWasCalled;
use App\Providers\Socialite\TikTokShop\TikTokShopExtendSocialite;
use Illuminate\Support\Facades\Event;

public function boot()
{
    Event::listen(function (SocialiteProviders\Manager\SocialiteWasCalled $event) {
        $event->extendSocialite('tiktokshop', \App\Providers\Socialite\TikTokShop\Provider::class);
    });
}
```

<details>
<summary>Laravel 10 or below</summary>

Configure the package’s listener in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        \App\Providers\Socialite\TikTokShop\TikTokShopExtendSocialite::class.'@handle',
    ],
];
```

</details>

## Usage

You can now redirect to TikTok Shop for authorization:

```php
use Laravel\Socialite\Facades\Socialite;

return Socialite::driver('tiktokshop')->redirect();
```

And handle the callback:

```php
$shopUser = Socialite::driver('tiktokshop')->user();

// Access mapped fields:
$shopUser->getId();
$shopUser->getName();
$shopUser->token;
$shopUser->refreshToken;
$shopUser->expiresIn;
```

### Returned User fields

* `id`              – the TikTok Shop `open_id`
* `nickname` / `name` – the `seller_name`
* `token`           – the `access_token`
* `refreshToken`    – the `refresh_token`
* `expiresIn`       – seconds until token expiry

## References

* [TikTok Shop Partner Center Authorization Overview (202407)](https://partner.tiktokshop.com/docv2/page/678e3a3292b0f40314a92d75)

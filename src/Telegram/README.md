# Telegram

```bash
composer require socialiteproviders/telegram
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

## Configuration

First of all, you must create a bot by contacting [@BotFather](http://t.me/BotFather) (https://core.telegram.org/bots#6-botfather)

> Don't forget to set your website URL using `/setdomain`

Then, you need to add your bot's configuration to `config/services.php`. The bot username is required, `client_id` must be `null`. The provider will also ask permission for the bot to write to the user.

```php
'telegram' => [
    'bot' => env('TELEGRAM_BOT_NAME'),  // The bot's username
    'client_id' => null,
    'client_secret' => env('TELEGRAM_TOKEN'),
    'redirect' => env('TELEGRAM_REDIRECT_URI'),
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Telegram\\TelegramExtendSocialite@handle',
    ],
];
```

### Usage

Now, Telegram is technically using `OAuth`, but not the usual workflow.

First or all, you **must** add a javascript to your page, anywhere you want (in the `<head>` or bottom of page) with this snippet:

```php
{!! Socialite::driver('telegram')->getScript() !!}
```

You also **must** call `_TWidgetLogin.auth()` on click on your login button, which will open a popup showing the Telegram OAuth access request. Because of browser's security, you can't automatically call this, it must be called as a result of a user's action.

If the user **accept** the access request, the browser is redirected to your `services.telegram.redirect` config key and you will have access to the logged-in user data the classic `Socialite` way:

```php
Socialite::driver('telegram')->user();
```

If the user **declines**, an `InvalidArgumentException` exception will be thrown.

Using `Socialite::driver('telegram')->redirect()` will show you a blank page with only the login button.

If you want to see the Telegram Widget configuration page: https://core.telegram.org/widgets/login

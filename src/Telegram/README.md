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
        \SocialiteProviders\Telegram\TelegramExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('telegram')->redirect();
```

You **can** add the login button to your page, anywhere you want  with this snippet:

```php
{!! Socialite::driver('telegram')->getButton() !!}
``` 

If you want to see the Telegram Widget configuration page: https://core.telegram.org/widgets/login

### Returned User fields

- ``id``
- ``first_name``
- ``last_name``
- ``username``
- ``photo_url``

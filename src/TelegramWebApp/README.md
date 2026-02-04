# TelegramWebApp

```bash
composer require socialiteproviders/telegramwebapp
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

## Configuration

First of all, you must create a bot by contacting [@BotFather](http://t.me/BotFather) (https://core.telegram.org/bots#6-botfather)

Next you must add WebApp script to your page, please see the [Initializing Mini Apps Guide](https://core.telegram.org/bots/webapps#initializing-mini-apps).

> Don't forget to set your website URL using `/setdomain`

Then, you need to add your bot's configuration to `config/services.php`. The bot username is required, `client_id` must be `null`. The provider will also ask permission for the bot to write to the user.

```php
'telegramwebapp' => [
    'client_id' => null,
    'client_secret' => env('TELEGRAM_TOKEN'),
    'redirect' => env('TELEGRAM_REDIRECT_URI'),
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('telegramwebapp', \SocialiteProviders\TelegramWebApp\Provider::class);
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
        \SocialiteProviders\TelegramWebApp\TelegramWebAppExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('telegramwebapp')->redirect();
```

### Returned User fields

- ``id``
- ``first_name``
- ``last_name``
- ``username``
- ``photo_url``

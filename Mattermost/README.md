# Socialite Providers for mattermost 

use laravel/.env 
```
MATTERMOST_KEY=your_key
MATTERMOST_SECRET=your_secret
MATTERMOST_REDIRECT_URI=https://localhost:8000/login/mattermost/callback
MATTERMOST_INSTANCE_URI=https://your-instance.mattermost.example/
```



# install using composer
```sh
composer install 
```
### composer.json

## add reference to EventServiceProvider.php
```php
class EventServiceProvider extends ServiceProvider {

  protected $listen = [
    // add for login
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
       'SocialiteProviders\\Mattermost\\MattermostExtendSocialite@handle',
    ],
  ];

```
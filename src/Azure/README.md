# Azure

## Deprecated

This provider is deprecated. You should use the [Microsoft](https://github.com/SocialiteProviders/Microsoft) provider instead. It uses the same Microsoft identity platform and Microsoft Graph endpoints, supports the same `tenant` and `proxy` options and additionally validates the ID token and can return roles, groups, the avatar and tenant details. See [Migrating to the Microsoft provider](#migrating-to-the-microsoft-provider).

```bash
composer require socialiteproviders/microsoft-azure
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'azure' => [    
  'client_id' => env('AZURE_CLIENT_ID'),
  'client_secret' => env('AZURE_CLIENT_SECRET'),
  'redirect' => env('AZURE_REDIRECT_URI'),
  'tenant' => env('AZURE_TENANT_ID'),
  'proxy' => env('PROXY'), // optionally
  'graph_url' => env('AZURE_GRAPH_URL'), // optionally, defaults to https://graph.microsoft.com/v1.0/me; only set to trusted endpoints because access tokens are sent here, especially when using mocks
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('azure', \SocialiteProviders\Azure\Provider::class);
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
        \SocialiteProviders\Azure\AzureExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('azure')->redirect();
```

To logout of your app and Azure:
```php
public function logout(Request $request) 
{
     Auth::guard()->logout();
     $request->session()->flush();
     $azureLogoutUrl = Socialite::driver('azure')->getLogoutUrl(route('login'));
     return redirect($azureLogoutUrl);
}
```

### Returned User fields

- ``id``
- ``name``
- ``email``

## Advanced usage

In order to have multiple / different Active directories on Azure (i.e. multiple tenants) The same driver can be used but with a different config:

```php
/**
 * Returns a custom config for this specific Azure AD connection / directory
 * @return \SocialiteProviders\Manager\Config
 */
function getConfig(): \SocialiteProviders\Manager\Config
{
  return new \SocialiteProviders\Manager\Config(
    env('AD_CLIENT_ID', 'some-client-id'), // a different clientID for this separate Azure directory
    env('AD_CLIENT_SECRET'), // a different secret for this separate Azure directory
    url(env('AD_REDIRECT_PATH', '/azuread/callback')), // the redirect path i.e. a different callback to the other azureAD callbacks
    ['tenant' => env('AD_TENANT_ID', 'common')], // this could be something special if need be, but can also be left out entirely
  );
}
//....//
Socialite::driver('azure')
    ->setConfig(getConfig())
    ->redirect();
```

This also applies to the callback for getting the user credentials that one has to remember to inject the ```->setConfig($config)```-method i.e.:
```php
$socialUser = Socialite::driver('azure')
    ->setConfig(getConfig())
    ->user();
```

If the application that you are authenticating against is anything other single tenant, use the following values in place of the tenant_id:
 - Multitenant applications: "organizations"
 - Multitenant and personal accounts: "common"
 - Personal accounts only: "consumers"

## Migrating to the Microsoft provider

Both providers sign users in through the same Microsoft identity platform v2.0 endpoints and read them from Microsoft Graph `/me`. `id`, `name` and `email` are mapped the same way, so users you already linked by their ID stay linked.

1. Replace the package:

    ```bash
    composer require socialiteproviders/microsoft
    composer remove socialiteproviders/microsoft-azure
    ```

2. Register the Microsoft provider under the `azure` driver name. Your `azure` entry in `config/services.php`, your `Socialite::driver('azure')` calls, the redirect URI and any driver names you stored keep working unchanged:

    ```php
    Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
        $event->extendSocialite('azure', \SocialiteProviders\Microsoft\Provider::class);
    });
    ```

    On Laravel 10 or below, put this in the `boot` method of your `EventServiceProvider` and remove `AzureExtendSocialite` from the `$listen` array. If you prefer the `microsoft` driver name, follow the [Microsoft provider's installation guide](https://github.com/SocialiteProviders/Microsoft) and rename the driver everywhere instead.

3. Replace the Azure specific user methods:

    | Azure provider | Microsoft provider |
    |---|---|
    | `$user->getPrincipalName()`, `$user->principalName` | `$user->userPrincipalName` |
    | `$user->getMail()` | `$user->mail` |

4. Check these differences:
    - The default scopes are `openid profile User.Read` instead of `User.Read`, and the returned ID token is validated.
    - Only a fixed set of Graph user fields is requested. Add any other fields you read from `getRaw()` with the `fields` config option.
    - `getAvatar()` fetches the photo from Microsoft Graph and returns a `MicrosoftAvatar` or `null`, instead of always returning `null`.
    - `getLogoutUrl()` returns the v2.0 logout endpoint (`/oauth2/v2.0/logout`) instead of `/oauth2/logout`.
    - There is no `graph_url` option. To fake the sign-in in tests, use [`Socialite::fake()`](https://laravel.com/docs/socialite#testing).

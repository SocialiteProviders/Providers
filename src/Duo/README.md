# Duo SSO

```bash
composer require socialiteproviders/duo
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Prerequisites

Before you begin, you must have:

1. **Duo SSO configured** - You need a Duo Premier, Advantage or Essentials plan with Single Sign-On enabled
2. **OIDC Application created in Duo Admin Panel**:
   - Navigate to **Applications -> Protect an Application**
   - Select **Generic OIDC Relying Party**
   - Configure your application and obtain the Client ID and Client Secret
3. **Duo SSO subdomain** - Your custom subdomain or the default one assigned to your account

Refer to the [Duo SSO for OIDC documentation](https://duo.com/docs/sso-oidc-generic) for detailed setup instructions.

### Add configuration to `config/services.php`

```php
'duo' => [
    'client_id' => env('DUO_CLIENT_ID'),
    'client_secret' => env('DUO_CLIENT_SECRET'),
    'redirect' => env('DUO_REDIRECT_URI'),
    'domain' => env('DUO_DOMAIN'), // Custom Duo SSO subdomain
],
```

### Add environment variables

Add these to your `.env` file:

```env
DUO_CLIENT_ID=your_client_id_from_duo_admin_panel
DUO_CLIENT_SECRET=your_client_secret_from_duo_admin_panel
DUO_REDIRECT_URI=https://yourdomain.com/auth/duo/callback
DUO_DOMAIN=custom-subdomain.sso.duosecurity.com
```

**Note:** For the `DUO_DOMAIN`, you can use either:
- **Full domain**: `acme.sso.duosecurity.com`
- **Just subdomain**: `acme` (automatically becomes `acme.sso.duosecurity.com`)
- **Full URL**: `https://acme.sso.duosecurity.com`

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('duo', \SocialiteProviders\Duo\Provider::class);
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
        \SocialiteProviders\Duo\DuoExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Configure OIDC Application in Duo Admin Panel

When creating your Generic OIDC Relying Party application in Duo:

1. Navigate to **Applications -> Application Catalog**
2. Search for "Generic OIDC Relying Party"
3. Click **Add** to create the application
4. Under **Scopes & Claims**, configure:
   - Enable `openid` scope (required - provides `sub` claim)
   - Enable `profile` scope (provides `name`, `preferred_username`, `picture`, etc.)
   - Enable `email` scope (provides `email` claim)
   - Map each claim to the appropriate attribute from your authentication source

**About Claims:** Duo SSO acts as a bridge between your authentication source (AD/SAML/Duo Directory) and your application. The claims sent to your application come from the attributes in your authentication source. For example:
- `email` claim maps to the `<Email Address>` default attribute
- `name` claim maps to the `<Display Name>` default attribute
- Additional mappings can be configured in the Duo Admin Panel

For more information, see [Duo's OIDC documentation](https://duo.com/docs/sso-oidc-generic).

### Configure Redirect URI

In your Duo Admin Panel, add your callback URL to the OIDC application's allowed redirect URIs:

1. Navigate to your OIDC application in the Duo Admin Panel
2. Scroll to **Client Flow Configuration -> Sign-In Redirect URLs**
3. Add: `https://yourdomain.com/auth/duo/callback`
4. Click **Save**

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('duo')->redirect();
```

### Callback Example

```php
use Laravel\Socialite\Facades\Socialite;

Route::get('/auth/duo/callback', function () {
    $user = Socialite::driver('duo')->user();

    // $user->token
    // $user->id
    // $user->name
    // $user->email
});
```

### Returned User fields

The provider maps standard OpenID Connect claims to Socialite user fields. With the default scopes (`openid`, `profile`, `email`):

- `id` - User's unique identifier (from `sub` claim per OIDC spec)
- `nickname` - User's username (from `preferred_username` or `email` claim)
- `name` - User's full name (from `name` claim)
- `email` - User's email address (from `email` claim)
- `avatar` - User's profile picture URL (from `picture` claim)

**Note:** Duo SSO implements OpenID Connect and follows the [OpenID Connect Core 1.0 specification](https://openid.net/specs/openid-connect-core-1_0.html) for standard claims. The actual claims available depend on:
1. Which scopes you request (`openid`, `profile`, `email`, etc.)
2. The claim mappings configured in your Duo Admin Panel
3. The attributes available from your authentication source (Active Directory, SAML IdP or Duo Directory)

Duo SSO passes through the attributes from your authentication source to the OIDC claims based on your application's configuration.

### Optional: Custom Scopes

By default, the provider requests `openid`, `profile` and `email` scopes. You can customize these:

```php
return Socialite::driver('duo')
    ->scopes(['openid', 'profile', 'email', 'groups'])
    ->redirect();
```

Available scopes depend on your Duo SSO OIDC application configuration in the Duo Admin Panel.

### Testing Integration

To see exactly which claims your Duo SSO instance returns:

```php
Route::get('/auth/duo/callback', function (): void {
    $user = Socialite::driver('duo')->user();

    dd($user->getRaw());
});
```

This will show you all available claims from Duo's UserInfo endpoint for your specific configuration.

### Reference

- [Duo Single Sign-On Documentation](https://duo.com/docs/sso)
- [Duo SSO for Generic OIDC](https://duo.com/docs/sso-generic-oidc)
- [Duo OAuth 2.1 and OIDC](https://duo.com/docs/sso-oauth-server)

# Saml2 Service Provider

```bash
composer require socialiteproviders/saml2
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

**Note the section on [SAML Protocol](#saml-protocol).**

### Add configuration to `config/services.php`

Any of these methods of configuring the identity provider can be used.
Using a metadata URL is highly recommended if your IDP supports it, so that certificate rollover on the IDP side does not cause any service interruption.

#### Using an Identity Provider metadata URL:
```php
'saml2' => [
  'metadata' => 'https://idp.co/metadata/xml',
],
```

#### Using an Identity Provider metadata XML file:
```php
'saml2' => [
  'metadata' => file_get_contents('/path/to/metadata/xml'),
],
```

#### Manually configuring the Identity Provider with a certificate string:
```php
'saml2' => [
  'acs' => 'https://idp.co/auth/acs', // (the IDP's 'Assertion Consumer Service' URL. Also known as the assertion callback URL or SAML assertion consumer endpoint)
  'entityid' => 'http://saml.to/trust', // (the IDP's globally unique "Entity ID", normally formatted as a URI, but it is not a real URL)
  'certificate' => 'MIIC4jCCAcqgAwIBAgIQbDO5YO....', // (the IDP's assertion signing certificate)
],
```

#### Manually configuring the Identity Provider with a certificate file:
```php
'saml2' => [
  'acs' => 'https://idp.co/auth/acs',
  'entityid' => 'http://saml.to/trust',
  'certificate' => file_get_contents('/path/to/certificate.pem'),
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Saml2\Saml2ExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

To initiate the auth flow:
```php
Route::get('/auth/redirect', function () {
    return Socialite::driver('saml2')->redirect();
});
```

To receive the callback:
```php
Route::get('/auth/callback', function () {
    $user = Socialite::driver('saml2')->user();
});
```

### SAML Protocol

For maximum compatibility with Socialite and Laravel out of the box, the Saml2 provider uses a GET route for the authentication callback by default.
Or in SAML terminology, it uses `HTTP-Redirect` binding on the service provider assertion consumer url:

```php
Route::get('/auth/callback', function () {
    $user = Socialite::driver('saml2')->user();
});
```

While this aligns to Socialite's way of doing things, it is *NOT* the most common SAML callback style and many identity providers do not support it.
The normal method is to use an `HTTP-POST` binding, which Saml2 also supports. To use this simply define your Laravel route as a `POST` route:

```php
Route::post('/auth/callback', function () {
    $user = Socialite::driver('saml2')->user();
});
```

However, note that this is *not compatible* with Laravel's CSRF filtering performed by default on `POST` routes in the `routes/web.php` file.
To make this callback style work, you can either define this route outside `web.php` or add it as an exception in your `VerifyCsrfToken` HTTP middleware.

### Identity provider metadata

When using a metadata URL for the identity provider the fetched metadata is cached for 24 hours by default.
To modify this time-to-live value use the 'ttl' key in config/services.php:

```php
'saml2' => [
  'metadata' => 'https://idp.co/metadata/xml',
  'ttl' => 3600, // TTL in seconds
],
```

To clear the cache programatically, you can use:
```php
Socialite::driver('saml2')->clearIdentityProviderMetadataCache();
```

The metadata will be refetched every 24 hours, but if the fetch fails the previously fetched metadata will be used for a further 24 hours. If the first fetch
of metadata fails a `GuzzleException` will be thrown.

### Service provider metadata

To simplify the configuration of your Laravel service provider on the identity provider side you can expose the service provider XML
metadata on a route:

```php
Route::get('/auth/saml2/metadata', function () {
    return Socialite::driver('saml2')->getServiceProviderMetadata();
});
```

Note that the assertion consumer service URL of your Laravel service is populated in the metadata, and therefore must be set in config/services.php
in the `sp_acs` key if it is not the Socialite default of '/auth/callback'.

For example if this is your callback route:
```php
Route::get('/auth/saml2/callback', function () {
    $user = Socialite::driver('saml2')->user();
});
```
the ACS route should be configured in `config/services.php` as:
```php
'saml2' => [
  'metadata' => 'https://idp.co/metadata/xml',
  'sp_acs' => 'auth/saml2/callback',
],
```

The default entity ID of the service provider is a url to '/auth/saml2' (for example `https://your.domain.com/auth/saml2`), if you need it can be manually configured in `config/services.php` as:
```php
'saml2' => [
  'metadata' => 'https://idp.co/metadata/xml',
  'sp_entityid' => 'https://my.domain.com/my/custom/entityid',
],
```

The entity ID and assertion consumer URL of the service provider can also be programmatically retrieved using:

```php
Socialite::driver('saml2')->getServiceProviderEntityId()
Socialite::driver('saml2')->getServiceProviderAssertionConsumerUrl()
```

### User attributes and Name ID

By SAML convention, the "Name ID" sent by the identity provider is used as the ID in the `User` class instance returned in the callback.

The two well-known SAML attributes 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name' and 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress' are mapped into Name and Email Address respectively in the `User` class.

All other attributes returned by the identity provider are stored in the "raw" property of the `User` class and can be retrieved with `$user->getRaw()`.

The entire assertion is also stored in the `User` instance and can be retrieved with `$user->getAssertion()`.

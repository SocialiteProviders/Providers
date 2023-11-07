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

The provider will automatically choose the first IdP descriptor in your metadata.
If your metadata contains multiple descriptors, you can choose the one to use by using both the `metadata` and `entityid`
configuration options at the same time.

#### Using an Identity Provider metadata URL, selecting a specific descriptor
```php
'saml2' => [
  'metadata' => 'https://idp.co/metadata/xml',
  'entityid' => 'http://saml.to/trust',
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

If you add both routes to support both binding methods, you can select the default one in `config/services.php` like this:
```php
'saml2' => [
  'sp_default_binding_method' => \LightSaml\SamlConstants::BINDING_SAML2_HTTP_POST,
],
```

### Stateless

The provider supports SAML2 unsolicited / IdP-initiated requests. To use this technique the *callback* route must be set up as stateless.

```php
Route::get('/auth/callback', function () {
    $user = Socialite::driver('saml2')->stateless()->user();
});
```

(Note this differs from the [standard](https://socialiteproviders.com/usage/#stateless) Socialite usage where the *redirect* is marked stateless.)

### Single logout

**Warning!** Please note that the SAML2 Single Logout feature is a best effort way of centralized logout. With the current state of affairs it requires special circumstances to work. You have to set your session config `same_site = 'none'` and `secure = true` for it to work which has serious security implications. Please always make sure you understand the risks before using this feature.

You can enable the SingleLogoutService on your Service Provider by adding a GET route where you log the user out and generate the SAML2 logout response:
```php
Route::get('/auth/saml2/logout', function () {
    $response = Socialite::driver('saml2')->logoutResponse();
});
```

To publish the SingleLogoutService in your service provider metadata, you also have to configure route in `config/services.php` as:
```php
'saml2' => [
  'sp_sls' => 'auth/saml2/logout',
],
```

### Signing and encryption

SAML2 supports the signing and encryption of messages and assertions. Many Identity Providers make one or both mandatory. To enable this feature, you can generate a certificate for your application and provide it in `config/services.php` as:
```php
'saml2' => [
  'sp_certificate' => file_get_contents('path/to/sp_saml.crt'),
  'sp_private_key' => file_get_contents('path/to/sp_saml.pem'),
  'sp_private_key_passphrase' => 'passphrase to your private key, provide it only if you have one',
  'sp_sign_assertions' => true, // or false to disable assertion signing
],
```

The `sp_private_key_passphrase` is optional and should not be given if the private key is not encrypted.

Always protect your private key and store it in a place where it is not accessible by the general public.

An example command to generate a certificate and private key with openssl:
```
openssl req -x509 -sha256 -nodes -days 365 -newkey rsa:2048 -keyout sp_saml.pem -out sp_saml.crt
```

### Validation

The provider validates the timestamps in the assertion including `NotBefore` and `NotOnOrAfter`.
The default clock skew is 120 seconds but this can be changed as part of the config:
```
'saml2' => [
  'metadata' => 'https://idp.co/metadata/xml',
  'validation' => [
    'clock_skew' => 30, // Time in seconds
  ],
],
```

The provider checks that the identity provider never repeats an assertion ID. IDs are remembered forever by default, but this can be configured:
```
'saml2' => [
  'metadata' => 'https://idp.co/metadata/xml',
  'validation' => [
    'repeated_id_ttl' => 365 * 24 * 60 * 60, // Time in seconds, or null to cache forever
  ],
],
```

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

You can also publish the service provider's organization and technical support contact in the metadata by configuring them in `config/services.php` as:
```php
'saml2' => [
  'sp_tech_contact_surname' => 'Doe',
  'sp_tech_contact_givenname' => 'John',
  'sp_tech_contact_email' => 'john.doe@example.com',
  'sp_org_lang' => 'en',
  'sp_org_name' => 'Example Corporation Ltd.',
  'sp_org_display_name' => 'Example Corporation',
  'sp_org_url' => 'https://corp.example',
],
```

In case you would like to include this information, you have to configure at least the `sp_org_name` for the organization to be included, and the `sp_tech_contact_email` for the contact to be included. The `sp_org_lang` has English (`en`) as default.

The signing and encryption certificates are automatically included in the metadata when a service provider certificate is configured.

### User attributes and Name ID

By SAML convention, the "Name ID" sent by the identity provider is used as the ID in the `User` class instance returned in the callback.

Well-known SAML attributes from the 'http://schemas.xmlsoap.org/...' and the 'urn:oid:...' namespaces are mapped into `name`, `email`, `first_name`, `last_name` and `upn` in the `User` class.

All other attributes returned by the identity provider are stored in the "raw" property of the `User` class and can be retrieved with `$user->getRaw()`.

It is possible to extend/override the default mapping by providing a partial/full custom map in `config/services.php` as:
```php
'saml2' => [
  'attribute_map' => [
    // Add mappings as 'mapped_name' => 'saml_attribute' or 'mapped_name' => ['saml_attribute', ...], for example:
    'email' => [
      \SocialiteProviders\Saml2\OasisAttributeNameUris::MAIL,
      \LightSaml\ClaimTypes::EMAIL_ADDRESS,
    ],
    'phone' => \SocialiteProviders\Saml2\OasisAttributeNameUris::PHONE,
  ],
],
```

The entire assertion is also stored in the `User` instance and can be retrieved with `$user->getAssertion()`.

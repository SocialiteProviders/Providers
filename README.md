# A Collection of Providers for Laravel Socialite

## Documentation

Full documentation for using these providers can be found at the [Documentation](https://socialiteproviders.com/).

## Contribute

Submit Pull Requests here for new providers. [See the docs](https://socialiteproviders.com/contribute/) for more information.

### Adding a new provider

Scaffold it rather than copying another provider by hand:

```bash
php tools/make-provider.php
```

It asks for the name, driver alias, OAuth version, category and any extra config keys, then writes
the provider, the listener, `composer.json`, the README and an optional test suite. Every answer can
be passed as a flag instead, so it also runs unattended:

```bash
php tools/make-provider.php --name=Frappe --oauth=2 --category="Productivity / Business" --config=base_url
```

Then fill in the `TODO`s against the provider's own OAuth docs, and link those docs in the README.

If you add tests under `tests/<Provider>/`, they run in CI automatically.

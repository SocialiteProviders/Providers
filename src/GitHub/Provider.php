<?php

namespace SocialiteProviders\GitHub;

use Laravel\Socialite\Two\GithubProvider;
use SocialiteProviders\Manager\ConfigTrait;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;

class Provider extends GithubProvider implements ProviderInterface
{
    use ConfigTrait;

    public const IDENTIFIER = 'GITHUB';
}

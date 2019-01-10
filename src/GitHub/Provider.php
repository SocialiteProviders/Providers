<?php

namespace SocialiteProviders\GitHub;

use Laravel\Socialite\Two\GithubProvider;
use SocialiteProviders\Manager\ConfigTrait;

class Provider extends GithubProvider
{
    use ConfigTrait;

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'GITHUB';
}

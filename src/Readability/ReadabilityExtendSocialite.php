<?php

namespace SocialiteProviders\Readability;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ReadabilityExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('readability', Provider::class, Server::class);
    }
}

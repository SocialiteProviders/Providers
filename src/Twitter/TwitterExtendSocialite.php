<?php

namespace SocialiteProviders\Twitter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TwitterExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('twitter', Provider::class, Server::class);
    }
}

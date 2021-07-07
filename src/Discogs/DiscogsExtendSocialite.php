<?php

namespace SocialiteProviders\Discogs;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DiscogsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('discogs', Provider::class, Server::class);
    }
}

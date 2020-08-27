<?php

namespace SocialiteProviders\Discogs;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DiscogsExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('discogs', Provider::class, Server::class);
    }
}

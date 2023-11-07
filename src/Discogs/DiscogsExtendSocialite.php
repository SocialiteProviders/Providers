<?php

namespace SocialiteProviders\Discogs;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DiscogsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('discogs', Provider::class, Server::class);
    }
}

<?php

namespace SocialiteProviders\Readability;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ReadabilityExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('readability', Provider::class, Server::class);
    }
}

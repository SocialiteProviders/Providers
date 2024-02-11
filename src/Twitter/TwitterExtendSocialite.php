<?php

namespace SocialiteProviders\Twitter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TwitterExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('twitter', Provider::class, Server::class);
    }
}

<?php

namespace SocialiteProviders\Tumblr;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TumblrExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('tumblr', Provider::class, Server::class);
    }
}

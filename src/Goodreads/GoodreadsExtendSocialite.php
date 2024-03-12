<?php

namespace SocialiteProviders\Goodreads;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GoodreadsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('goodreads', Provider::class, Server::class);
    }
}

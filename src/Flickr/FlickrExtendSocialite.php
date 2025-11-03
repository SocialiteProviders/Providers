<?php

namespace SocialiteProviders\Flickr;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FlickrExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('flickr', Provider::class, Server::class);
    }
}

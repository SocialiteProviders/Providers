<?php

namespace SocialiteProviders\Tumblr;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TumblrExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('tumblr', Provider::class, Server::class);
    }
}

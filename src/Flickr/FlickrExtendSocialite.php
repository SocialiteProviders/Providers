<?php

namespace SocialiteProviders\Flickr;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FlickrExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('flickr', Provider::class, Server::class);
    }
}

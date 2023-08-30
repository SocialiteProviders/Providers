<?php

namespace SocialiteProviders\Goodreads;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GoodreadsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('goodreads', Provider::class, Server::class);
    }
}

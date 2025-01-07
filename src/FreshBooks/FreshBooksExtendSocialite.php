<?php

namespace SocialiteProviders\FreshBooks;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FreshBooksExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     * @return void
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('freshbooks', Provider::class);
    }
}

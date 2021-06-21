<?php

namespace SocialiteProviders\Notion;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NotionExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('notion', Provider::class);
    }
}

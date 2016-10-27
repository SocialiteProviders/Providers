<?php

namespace SocialiteProviders\Zendesk;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZendeskExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'zendesk', __NAMESPACE__.'\Provider'
        );
    }
}

<?php

namespace SocialiteProviders\Trello;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TrelloExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'trello', __NAMESPACE__.'\Provider', __NAMESPACE__.'\Server'
        );
    }
}

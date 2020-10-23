<?php

namespace SocialiteProviders\Jira;

use SocialiteProviders\Manager\SocialiteWasCalled;

class JiraExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('jira', Provider::class, Server::class);
    }
}

<?php

namespace SocialiteProviders\Jira;

use SocialiteProviders\Manager\SocialiteWasCalled;

class JiraExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('jira', Provider::class, Server::class);
    }
}

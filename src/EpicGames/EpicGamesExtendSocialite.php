<?php

namespace SocialiteProviders\EpicGames;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EpicGamesExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('epic-games', Provider::class);
    }
}

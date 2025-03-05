<?php

namespace SocialiteProviders\Kick;

use SocialiteProviders\Manager\SocialiteWasCalled;

class KickExtendSocialite
{
  /**
   * Register the provider.
   *
   * @return void
   */
  public function handle(SocialiteWasCalled $socialiteWasCalled)
  {
    $socialiteWasCalled->extendSocialite('kick', Provider::class);
  }
}

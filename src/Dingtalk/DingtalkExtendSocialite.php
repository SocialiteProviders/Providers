<?php
namespace SocialiteProviders\Dingtalk;
use SocialiteProviders\Manager\SocialiteWasCalled;
class DingtalkExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('dingtalk', __NAMESPACE__.'\Provider');
    }
}
<?php

namespace Platform\CmsConnector\Http\Controllers;

use Illuminate\Http\Request;
use Platform\CmsConnector\Bridges\AuthBridge;

class SsoRedirectController
{
    public function redirect(AuthBridge $bridge)
    {
        return $bridge->redirect();
    }
}

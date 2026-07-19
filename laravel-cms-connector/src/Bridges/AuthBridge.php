<?php

namespace Platform\CmsConnector\Bridges;

use Platform\CmsConnector\ConnectorManager;

class AuthBridge
{
    public function __construct(protected ConnectorManager $manager) {}

    public function redirect(): \Illuminate\Http\RedirectResponse
    {
        if (! auth()->check()) abort(403, 'Must be authenticated on host to use SSO bridge.');
        return redirect($this->manager->ssoRedirectUrl());
    }
}

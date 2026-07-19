<?php

namespace App\Domain\WhiteLabel\Middleware;

use App\Domain\WhiteLabel\Services\WhiteLabelService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyWhiteLabel
{
    public function __construct(protected WhiteLabelService $whitelabel) {}

    public function handle(Request $request, Closure $next): Response
    {
        $branding = $this->whitelabel->getBranding();

        // Share branding with all views
        view()->share('cms_branding', $branding);
        view()->share('cms_css_vars', $this->whitelabel->getCssVariables());
        view()->share('show_platform_branding', $this->whitelabel->showPlatformBranding());

        $response = $next($request);

        // Inject custom CSS/JS into HTML responses
        if (str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            $content = $response->getContent();

            $customCss = $this->whitelabel->getCustomCss();
            if ($customCss) {
                $content = str_replace('</head>', "<style>{$customCss}</style></head>", $content);
            }

            $customJs = $this->whitelabel->getCustomJs();
            if ($customJs) {
                $content = str_replace('</body>', "<script>{$customJs}</script></body>", $content);
            }

            $response->setContent($content);
        }

        return $response;
    }
}

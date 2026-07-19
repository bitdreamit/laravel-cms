<?php

namespace App\Providers;

use App\Domain\Theme\Services\ThemeCustomizer;
use App\Domain\Theme\Services\ThemeResolver;
use App\Domain\Theme\Services\ThemeVariableCompiler;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ThemeBladeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerBladeDirectives();
    }

    public function register(): void
    {
        $this->app->singleton(ThemeResolver::class);
        $this->app->singleton(ThemeCustomizer::class);
        $this->app->singleton(ThemeVariableCompiler::class);
    }

    protected function registerBladeDirectives(): void
    {
        // @theme('key') — get a theme setting value
        Blade::directive('theme', function ($expression) {
            return "<?php echo app(\App\Domain\Theme\Services\ThemeCustomizer::class)->getSettings()[{$expression}] ?? ''; ?>";
        });

        // @iftheme('key') / @endiftheme — conditional on theme setting
        Blade::directive('iftheme', function ($expression) {
            return "<?php if(!empty(app(\App\Domain\Theme\Services\ThemeCustomizer::class)->getSettings()[{$expression}] ?? '')): ?>";
        });
        Blade::directive('endiftheme', function () {
            return '<?php endif; ?>';
        });

        // @themeAsset('path') — get versioned theme asset URL
        Blade::directive('themeAsset', function ($expression) {
            return "<?php echo app(\App\Domain\Theme\Services\ThemeResolver::class)->resolveAsset({$expression}) ?? ''; ?>";
        });

        // @includeTheme('view') — include from theme with cascade fallback
        Blade::directive('includeTheme', function ($expression) {
            return "<?php echo view('theme::' . {$expression})->render(); ?>";
        });

        // @themeHasFeature('feature') / @endThemeHasFeature
        Blade::directive('themeHasFeature', function ($expression) {
            return "<?php if(app('current.theme')?->hasFeature({$expression})): ?>";
        });
        Blade::directive('endThemeHasFeature', function () {
            return '<?php endif; ?>';
        });

        // @themeCssVars — inject compiled CSS variables
        Blade::directive('themeCssVars', function () {
            return "<?php echo '<style>' . app(\App\Domain\Theme\Services\ThemeVariableCompiler::class)->compile() . '</style>'; ?>";
        });
    }
}

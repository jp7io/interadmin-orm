<?php

namespace Jp7\Laravel5;

use Blade;

class BladeExtension
{
    public static function apply()
    {
        Blade::extend(function ($view) {
            // @include with partials/_partial instead of partials/partial
            $pattern = '/(?<!\w)(\s*)@include\(([^,\)]+)/';
            
            return preg_replace($pattern, '$1@include(' . self::class . '::inc($2)', $view);
        });
        
        Blade::directive('ia', function ($expression) {
            return "<?php echo interadmin_data{$expression}; ?>";
        });
    }

    public static function inc($file)
    {
        $parts = explode('.', $file);
        $parts[] = '_'.array_pop($parts);

        return implode('.', $parts);
    }
}

<?php

namespace Jp7\Laravel5;

use Blade;

class BladeExtension
{
    public static function apply()
    {
        // @include('partials/partial')
        // include 'partials/_partial.blade.php', instead of 'partials/partial.blade.php'
        Blade::extend(function ($view) {
            $pattern = '/(?<!\w)(\s*)@include\(([^,\)]+)/';
            
            return preg_replace($pattern, '$1@include(' . self::class . '::inc($2)', $view);
        });
        
        // @ia($model)
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

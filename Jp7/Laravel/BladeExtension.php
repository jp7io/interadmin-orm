<?php

namespace Jp7\Laravel;

use Blade;

class BladeExtension
{
    public static function apply()
    {
        Blade::extend(function ($view, $compiler) {
            // @include with partials/_partial instead of partials/partial
            $pattern = '/(?<!\w)(\s*)@include\(([^,\)]+)/';

            return preg_replace($pattern, '$1@include(\Jp7\Laravel\BladeExtension::inc($2)', $view);
        });

        Blade::extend(function ($view, $compiler) {
             $pattern = $compiler->createMatcher('ia');

             return preg_replace($pattern, '$1<?php echo interadmin_data$2; ?>', $view);
        });

        Blade::setEscapedContentTags('{{', '}}');
        Blade::setContentTags('{!!', '!!}');
    }

    public static function inc($file)
    {
        $parts = explode('.', $file);
        $parts[] = '_'.array_pop($parts);

        return implode('.', $parts);
    }
}

<?php

namespace Jp7\InterAdmin;

/** Forwards to classes' Schema\DynamicLoader until round 3 deletes this half, whose Record and Type still ask it. */
class DynamicLoader
{
    public static function isDeclarable($class): bool
    {
        return Schema\DynamicLoader::isDeclarable($class);
    }
}

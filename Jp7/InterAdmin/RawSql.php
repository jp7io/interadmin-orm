<?php

namespace Jp7\InterAdmin;

use DB;
use Illuminate\Contracts\Database\Query\Expression;

/**
 * Unwraps an `Illuminate\Database\Query\Expression` (what `DB::raw()` returns) into the SQL
 * string this ORM concatenates into its clauses.
 *
 * Laravel 10 dropped `Expression::__toString()` in favour of `getValue(Grammar $grammar)`.
 * Every place here that handed a raw expression to a string parameter used to work purely on
 * that implicit cast, so on Laravel 10+ they became a hard TypeError instead -- reachable from
 * `Query::update()` and `Query::increment()`, i.e. any write with a `DB::raw()` value.
 *
 * The ORM's own test suite could not see this: it pinned `illuminate/* ~5.2` until the
 * dependencies were standardized on Laravel 12, while the apps consuming it had been on
 * Laravel 12 for months.
 */
class RawSql
{
    /**
     * @param Expression|string $value
     * @return string
     */
    public static function toSql($value): string
    {
        if ($value instanceof Expression) {
            return (string) $value->getValue(DB::connection()->getQueryGrammar());
        }
        return (string) $value;
    }
}

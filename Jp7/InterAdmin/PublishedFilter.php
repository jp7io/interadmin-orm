<?php

namespace Jp7\InterAdmin;

/**
 * Builds the "is this row currently visible?" SQL predicates that the query compiler
 * prepends to a WHERE (and splices into a JOIN's ON clause).
 *
 * Extracted verbatim from RecordAbstract::getPublishedFilters (REFACTORING #5): the
 * compiler had grown its own notions of the publishing calendar, the preview mode and
 * which kind of table it was looking at. Those are one concern, they are pure, and they
 * are now testable without a database.
 *
 * IMPORTANT -- this is NOT the seam to call from application code. RecordAbstract::
 * getPublishedFilters() stays the entry point because it is polymorphic: Log overrides
 * it to return nothing (log rows have no publishing calendar), and callers reach it via
 * late static binding (`static::getPublishedFilters()` / `$this->getPublishedFilters()`).
 * Calling this class directly would silently re-apply filters to the types that opt out.
 *
 * @see RecordAbstract::getPublishedFilters()
 * @see Log::getPublishedFilters()
 */
class PublishedFilter
{
    /**
     * The trailing 'AND ' is deliberate: every caller concatenates this straight onto the
     * front of its own clause, so the filter always ends in a joiner rather than the
     * caller having to know whether it is empty.
     *
     * @param string $table Table name, prefixed (e.g. 'interadmin_ci_records')
     * @param string $alias Alias the predicates are qualified with (e.g. 'main')
     *
     * @return string|null Null when the table has nothing to filter (see the tags branch)
     */
    public static function sql($table, $alias): ?string
    {
        $tableParts = explode('_', $table);
        $table = end($tableParts);

        // NOTE (characterized, not designed): the `count($tableParts) === 3` guards mean
        // only a PREFIXED name is recognized -- 'interadmin_ci_types' takes the types
        // branch, but a bare 'types' falls through to the records branch below and is
        // filtered on columns it does not have. Every in-ORM caller passes a prefixed
        // name, so this is latent rather than live. Preserved exactly; changing it is a
        // behavior change, not part of the extraction.
        if ($table === 'types' && count($tableParts) === 3) {
            return $alias.'.visible = 1 AND '.$alias.'.deleted_at IS NULL AND ';
        } elseif ($table === 'tags' && count($tableParts) === 3) {
            // Tags carry no publishing state of their own -- returns null, and callers
            // concatenate that as ''.
            return null;
        } elseif ($table === 'files') {
            // Same `visible` as the types branch since increment 7, and still a branch of its own:
            // a type is soft-deleted through `deleted_at`, a file through `deleted`.
            return $alias.'.visible = 1 AND '.$alias.'.deleted = 0 AND ';
        }

        return self::recordsSql($alias);
    }

    /**
     * The records calendar: published already, not yet expired (or never expiring),
     * flagged visible, not soft-deleted.
     */
    private static function recordsSql($alias): string
    {
        $now = Record::getTimestamp();

        $filter = $alias.".date_publish <= '".date('Y-m-d H:i:59', $now)."'".
            ' AND ('.$alias.".date_expire > '".date('Y-m-d H:i:00', $now)."' OR ".$alias.
                ".date_expire = '0000-00-00 00:00:00' OR ".$alias.'.date_expire IS NULL)'.
            ' AND '.$alias.'.bool_key = 1'.
            ' AND '.$alias.'.deleted = 0'.
            ' AND ';

        // Preview mode (the admin) also shows unpublished rows and any child row.
        if (config('interadmin.preview')) {
            $filter .= '('.$alias.'.publish = 1 OR '.$alias.'.parent_id > 0) AND ';
        }

        return $filter;
    }
}

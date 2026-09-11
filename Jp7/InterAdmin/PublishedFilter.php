<?php

namespace Jp7\InterAdmin;

use Jp7\InterAdmin\Schema\PublishedFilterSql;

// PublishedFilterSql, fed the ORM's clock and the preview config. ⚠ Not the seam to call:
// RecordAbstract::getPublishedFilters() is, and Log overrides it to nothing.
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
        return PublishedFilterSql::build(
            (string) $table,
            (string) $alias,
            (int) Record::getTimestamp(),
            (bool) config('interadmin.preview'),
        );
    }
}

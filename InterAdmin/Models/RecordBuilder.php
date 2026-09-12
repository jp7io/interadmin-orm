<?php

namespace InterAdmin\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * The Eloquent builder every record query is, for the lookups the ORM answered its own way.
 * @extends Builder<Record>
 */
final class RecordBuilder extends Builder
{
    /**
     * A non-numeric string finds by `id_slug` where the table has one, as the ORM's find() did:
     * tenant code hands URL slugs straight in (`Pais::find($attr['pais'])`). ⚠ An empty string
     * stays Eloquent's, which matches nothing; as a slug it would match every row that has none.
     */
    public function find($id, $columns = ['*'])
    {
        if (is_string($id) && $id !== '' && !is_numeric($id)
            && in_array('id_slug', $this->model->getColumns(), true)) {
            return $this->where($this->model->qualifyColumn('id_slug'), $id)->first($columns);
        }

        return parent::find($id, $columns);
    }
}

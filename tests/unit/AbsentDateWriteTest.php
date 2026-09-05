<?php

use Jp7\InterAdmin\Record;

/**
 * The WRITE half of docs/zero-date-plan.md step 2, and the reason it cannot ship on its own.
 *
 * ⚠ Under the app's own sql_mode (NO_ENGINE_SUBSTITUTION) a nullable DATETIME handed '' still
 * stores '0000-00-00 00:00:00'. So nulling the columns without this change is worse than either
 * endpoint: the next save puts the sentinel back into a nullable column and the tenant ends up
 * with both encodings in one column.
 *
 * ⚠ And a blanket rule is not survivable either. NULL into a NOT NULL datetime is lenient in one
 * direction only: an UPDATE and a MULTI-row INSERT quietly store the sentinel, but the SINGLE-row
 * INSERT this ORM creates records with is ERROR 1048. So writing NULL for every `date_` column
 * would make creating a record a hard fatal on every table the migration has not reached -- jp7,
 * a tenant mid-window, any fixture. Hence writesNull() asks the schema.
 *
 * The dump carries one of each on purpose: `date_5` is nullable, `date_hit` is not.
 */
class AbsentDateWriteTest extends TestCase
{
    private const NULLABLE = 'date_5';

    private const NOT_NULL = 'date_hit';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('nullable,,interadmin_teste_records');
    }

    private function saved(array $attributes): object
    {
        $record = new Record();
        $record->type_id = 1;
        foreach ($attributes as $column => $value) {
            $record->$column = $value;
        }
        $record->save();

        return self::pdo()
            ->query('SELECT * FROM interadmin_teste_records WHERE id = '.(int) $record->id)
            ->fetch(PDO::FETCH_OBJ);
    }

    public function testANullDateGoesIntoANullableColumnAsNull(): void
    {
        $row = $this->saved([self::NULLABLE => null]);

        $this->assertNull($row->{self::NULLABLE});
    }

    /**
     * ⚠ The single-row INSERT is the one MySQL refuses outright. If this ever throws 1048 rather
     * than storing the sentinel, the write path has stopped consulting the schema and every
     * unmigrated tenant has lost record creation.
     */
    public function testANullDateGoesIntoANotNullColumnAsTheSentinelRatherThanThrowing(): void
    {
        $row = $this->saved([self::NOT_NULL => null]);

        $this->assertSame('0000-00-00 00:00:00', $row->{self::NOT_NULL});
    }

    /** Both columns in one save, which is what a record form posts. */
    public function testOneSaveWritesEachColumnTheWayItsOwnSchemaAllows(): void
    {
        $row = $this->saved([self::NULLABLE => null, self::NOT_NULL => null]);

        $this->assertNull($row->{self::NULLABLE});
        $this->assertSame('0000-00-00 00:00:00', $row->{self::NOT_NULL});
    }

    /**
     * ⚠ The `date_` gate. Everywhere else in this schema "empty" is '', so a nullable non-date
     * column must not start collecting NULLs off the back of this change.
     */
    public function testANullNonDateColumnIsStillWrittenAsTheEmptyString(): void
    {
        $row = $this->saved(['varchar_1' => null]);

        $this->assertSame('', $row->varchar_1);
    }

    public function testARealDateIsWrittenAsItself(): void
    {
        $row = $this->saved([self::NULLABLE => '2026-09-04 10:00:00']);

        $this->assertSame('2026-09-04 10:00:00', $row->{self::NULLABLE});
    }

    /** An UPDATE takes the same path as the INSERT above, and is where a stale cache would show. */
    public function testAnUpdateClearsANullableDateToNull(): void
    {
        $record = new Record();
        $record->type_id = 1;
        $record->{self::NULLABLE} = '2026-09-04 10:00:00';
        $record->save();

        $record->{self::NULLABLE} = null;
        $record->save();

        $row = self::pdo()
            ->query('SELECT * FROM interadmin_teste_records WHERE id = '.(int) $record->id)
            ->fetch(PDO::FETCH_OBJ);

        $this->assertNull($row->{self::NULLABLE});
    }

    public function testTheNullableListingIsReadFromTheSchemaAndCached(): void
    {
        $record = new Record();
        $record->type_id = 1;

        $nullable = $record->getNullableColumns();

        $this->assertContains(self::NULLABLE, $nullable);
        $this->assertNotContains(self::NOT_NULL, $nullable);
        $this->assertSame($nullable, Cache::get('nullable,,interadmin_teste_records'));
    }
}

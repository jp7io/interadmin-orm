<?php

use Jp7\InterAdmin\Query\TypeQuery;
use Jp7\InterAdmin\RecordClassMap;
use Jp7\InterAdmin\Type;

/**
 * Under ONLY_FULL_GROUP_BY -- the mode InterAdmin connects with -- a query may read only what
 * its grouping left standing. So this ORM adds nothing of its own beside an aggregate: not the
 * key columns a hydrated record wants, and not the type's default sort.
 *
 * ⚠ Asserted by RUNNING each shape with the mode on, because every one of them compiled and
 * returned rows without it: MySQL picked an arbitrary row per group and nothing said so.
 */
class AggregateQueryTest extends TestCase
{
    private Type $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = $this->createType(['name' => 'User'], [
            ['type' => 'varchar_key', 'name' => 'Username'],
            ['type' => 'varchar_2', 'name' => 'E-mail'],
            ['type' => 'bool_key', 'name' => 'Show'],
            // orderby, so getInterAdminsOrder() has something to append and dropping it means
            // something. Without it every assertion below passes for the wrong reason.
            ['type' => 'int_key', 'name' => 'Position', 'orderby' => 1],
        ]);

        // The dynamic Test_User class is minted per types row, and the map that resolves it
        // caches across test classes; without this the whole class errors as "not found".
        RecordClassMap::getInstance()->clearCache();

        $this->createUser(['varchar_key' => 'a', 'varchar_2' => 'shared@jp7.io', 'position' => 2]);
        $this->createUser(['varchar_key' => 'b', 'varchar_2' => 'shared@jp7.io', 'position' => 1]);
        $this->createUser(['varchar_key' => 'c', 'varchar_2' => 'other@jp7.io', 'position' => 3]);

        DB::connection()->statement("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES'");
    }

    protected function tearDown(): void
    {
        DB::connection()->statement("SET SESSION sql_mode = ''");

        parent::tearDown();
    }

    /** The type's default sort names a column the aggregate collapses, so it is not appended. */
    public function testCountCarriesNoDefaultOrder()
    {
        $this->assertSame(3, $this->type->records()->published(false)->count());
    }

    public function testAnAggregateBesideItsGroupingColumnDropsTheRecordKeys()
    {
        $rows = $this->type->records()
            ->select('COUNT(e_mail) AS total', 'e_mail')
            ->groupBy('varchar_2')
            ->published(false)
            ->get();

        $this->assertCount(2, $rows);
        $this->assertSame(
            ['other@jp7.io' => 1, 'shared@jp7.io' => 2],
            $rows->pluck('total', 'e_mail')->map(fn ($total) => (int) $total)->sort()->all()
        );
    }

    /** min()/max() replace the field list after the default sort was appended, so it goes too. */
    public function testTheAggregateHelpersCarryNoDefaultOrder()
    {
        $this->assertSame('3', (string) $this->type->deprecated_max('position', ['use_published_filters' => false]));
    }

    /**
     * The types table has the same two, and no suite reached this one: it is COUNT(type_id)
     * beside a bare type_id, ordered by `position, name`.
     */
    public function testATypeCountCarriesNeitherTheKeyNorTheDefaultOrder()
    {
        $this->assertSame(1, (new TypeQuery(new Type))->where('name', 'User')->count());
    }

    /**
     * The rule is keyed on the caller asking for an aggregate, so an ordinary query still
     * gets both -- the keys it hydrates from and the sort the type declares.
     */
    public function testAnOrdinaryQueryKeepsItsKeysAndItsOrder()
    {
        $rows = $this->type->records()->select('username')->published(false)->get();

        $this->assertSame([1, 2, 3], $rows->pluck('position')->map(fn ($p) => (int) $p)->all());
        $this->assertNotNull($rows->first()->id);
    }
}

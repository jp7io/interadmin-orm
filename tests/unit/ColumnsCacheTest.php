<?php

use Jp7\InterAdmin\Type;

/**
 * The failure these cover: an empty column listing, once cached, wedges a whole tenant.
 *
 * getColumns() IS Type::getAttributesNames(), so an empty listing stops Type::__get()
 * recognising `campos` as a column. The tipos row then never lazy-loads, getFields() parses ''
 * into an empty field map, and _resolveFieldsAlias() throws `The field "x" cannot be used with
 * select()` for every relation field -- while the database is fine and the tipos row still
 * holds the full field map. It reads as a schema problem and is not one.
 *
 * Nothing here sleeps waiting for a TTL, deliberately: "the bad entry expires in 5 seconds" is
 * the assumption that did not hold in production, because remember() rewrote the empty value on
 * every miss. The assertions are that an empty read is never written, and that a good read is
 * visible on the next call rather than at the end of some window.
 */
class ColumnsCacheTest extends TestCase
{
    private const TIPOS_KEY = 'columns,,interadmin_teste_tipos';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(self::TIPOS_KEY);
    }

    protected function tearDown(): void
    {
        // A leaked empty entry would surface as unrelated failures in later test classes.
        Cache::forget(self::TIPOS_KEY);
        parent::tearDown();
    }

    public function testCachesASuccessfulListing()
    {
        $columns = (new Type)->getColumns();

        $this->assertContains('campos', $columns);
        $this->assertSame($columns, Cache::get(self::TIPOS_KEY));
    }

    public function testDoesNotCacheAnEmptyListing()
    {
        $type = new MissingTableType;

        $this->assertSame([], $type->getColumns());
        $this->assertNull(
            Cache::get('columns,,interadmin_teste_ghost'),
            'A column listing that came back empty must not be persisted.'
        );
    }

    /**
     * Heals a cache poisoned by an earlier version of getColumns(), with no flush needed.
     */
    public function testIgnoresAndReplacesAnEmptyCachedListing()
    {
        Cache::put(self::TIPOS_KEY, [], 5);

        $columns = (new Type)->getColumns();

        $this->assertContains('campos', $columns);
        $this->assertContains('campos', Cache::get(self::TIPOS_KEY));
    }

    /**
     * The old strip was an unanchored str_replace(), so a table name that repeats the prefix
     * was rewritten into a different table -- here, into one that exists, which answers with
     * 50 columns that belong to something else.
     */
    public function testStripsTheTablePrefixFromTheFrontOnly()
    {
        $type = new DoublePrefixTableType;

        $this->assertSame(
            [],
            $type->getColumns(),
            'interadmin_teste_interadmin_teste_registros does not exist; '.
            'it must not be answered with interadmin_teste_registros'
        );
    }

    /**
     * The whole chain, end to end: a poisoned listing used to leave the type with no fields.
     */
    public function testAnEmptyCachedListingDoesNotWedgeTheType()
    {
        $userType = $this->createUserType();
        Cache::put(self::TIPOS_KEY, [], 5);
        Cache::tag(Type::CACHE_TAG)->flush(); // force campos to be re-derived from the row

        $campos = (new Type($userType->id_tipo))->getFields();

        $this->assertArrayHasKey('varchar_key', $campos);
    }

    /**
     * The second half of the wedge: getFields() caching the empty map it just parsed from ''.
     */
    public function testDoesNotCacheAnEmptyFieldMap()
    {
        $type = $this->createType(['nome' => 'Fieldless'], []);
        $cache = Cache::tag(Type::CACHE_TAG);
        $cacheKey = 'campos,,'.$type->id_tipo;

        $this->assertSame([], $type->getFields());
        $this->assertNull($cache->get($cacheKey), 'An empty field map must not be persisted.');

        $type->campos = $this->createFields([['tipo' => 'varchar_key', 'nome' => 'Title']]);

        $this->assertArrayHasKey('varchar_key', $type->getFields());
        $this->assertNotEmpty($cache->get($cacheKey));
    }
}

class MissingTableType extends Type
{
    public function getTableName()
    {
        return 'interadmin_teste_ghost';
    }
}

class DoublePrefixTableType extends Type
{
    public function getTableName()
    {
        return 'interadmin_teste_interadmin_teste_registros';
    }
}

<?php

use Jp7\Interadmin\Record;
use Jp7\Interadmin\RecordClassMap;

class RecordTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private $oldTimestamp;
    private $oldConfig;

    public function setUp()
    {
        parent::setUp();

        global $config;
        $this->oldConfig = $config;
        $this->oldTimestamp = Record::hasTimestamp() ? Record::getTimestamp() : null;

        $config = $config ? clone $config : new stdClass;
        $config->interadmin_preview = true;

        Record::setTimestamp(strtotime('2016-01-01 02:00:00'));
    }

    public function tearDown()
    {
        global $config;
        $config = $this->oldConfig;
        Record::setTimestamp($this->oldTimestamp);

        parent::tearDown();
    }

    public function testSetAndGet()
    {
        $this->tester->createUserType();
        RecordClassMap::getInstance()->clearCache();

        $user = Test_User::build();
        $username = 'jp7_kant';
        $user->username = $username;
        $this->assertEquals($user->username, $username);
        $this->assertEquals($user->varchar_key, $username);

        $this->assertFalse(isset($user->newProp));
        $user->newProp = [];
        $this->assertTrue(isset($user->newProp));

        $user->newProp[] = 1;
        $user->newProp[] = 2;
        $this->assertEquals($user->newProp, [1, 2]);

        unset($user->newProp);
        $this->assertFalse(isset($user->newProp));

        $user->date_publish = date('c');
        $this->assertInstanceOf('Date', $user->date_publish);
    }

    /**
     * @dataProvider publishedProvider
     */
    public function testPublished(array $attributes)
    {
        $record = new Record($attributes);
        $this->assertTrue($record->isPublished());
    }

    /**
     * @dataProvider unpublishedProvider
     */
    public function testUnpublished(array $attributes)
    {
        $record = new Record($attributes);
        $this->assertFalse($record->isPublished());
    }

    public function publishedProvider()
    {
        return [
            [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted_at'  => null,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00' // sem date_expire
            ]],
            [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted_at'  => null,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:00:01' // date_expire no futuro
            ]],
            [[
                'char_key' => 'S',
                'publish'  => '', // sem publish
                'deleted_at'  => null,
                'parent_id'  => 123, // com parent
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:00:01'
            ]],
        ];
    }

    public function unpublishedProvider()
    {
        return [
            [[
                'char_key' => '', // sem mostrar
                'publish'  => 'S',
                'deleted_at'  => null,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted_at'  => 'S', // com deleted
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted_at'  => null,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:00:00',
                'date_expire' => '2016-01-01 01:59:59' // date_expire no passado
            ]],
            [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted_at'  => null,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 02:00:01', // date_publish no futuro
                'date_expire' => '2016-01-01 03:00:00'
            ]],
            [[
                'char_key' => 'S',
                'publish'  => '', // sem publish
                'deleted_at'  => null,
                'parent_id'  => 0, // sem parent
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:00:01'
            ]],
        ];
    }
}

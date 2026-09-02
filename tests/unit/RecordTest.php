<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Jp7\InterAdmin\Record;
use Jp7\InterAdmin\RecordClassMap;

class RecordTest extends TestCase
{
    private $oldTimestamp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->oldTimestamp = Record::hasTimestamp() ? Record::getTimestamp() : null;

        Record::setTimestamp(strtotime('2016-01-01 02:00:00'));
    }

    protected function tearDown(): void
    {
        Record::setTimestamp($this->oldTimestamp);

        parent::tearDown();
    }

    public function testSetAndGet()
    {
        $this->createUserType();
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

    #[DataProvider('publishedProvider')]
    public function testPublished(array $attributes)
    {
        $record = new Record($attributes);
        $this->assertTrue($record->isPublished());
    }

    #[DataProvider('unpublishedProvider')]
    public function testUnpublished(array $attributes)
    {
        $record = new Record($attributes);
        $this->assertFalse($record->isPublished());
    }

    public static function publishedProvider()
    {
        return [
            [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted'  =>  0,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00' // sem date_expire
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted'  =>  0,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:00:01' // date_expire no futuro
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  0, // sem publish
                'deleted'  =>  0,
                'parent_id'  => 123, // com parent
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:00:01'
            ]],
        ];
    }

    public static function unpublishedProvider()
    {
        return [
            [[
                'bool_key' => 0, // not shown
                'publish'  =>  1,
                'deleted'  =>  0,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted'  =>  1, // com deleted
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted'  =>  0,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:00:00',
                'date_expire' => '2016-01-01 01:59:59' // date_expire no passado
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted'  =>  0,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 02:00:01', // date_publish no futuro
                'date_expire' => '2016-01-01 03:00:00'
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  0, // sem publish
                'deleted'  =>  0,
                'parent_id'  => 0, // sem parent
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:00:01'
            ]],
        ];
    }
}

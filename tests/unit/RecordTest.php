<?php

namespace Tests;

use Jp7\Interadmin\Record;
use stdClass;

class RecordTest extends \PHPUnit_Framework_TestCase
{
    private $oldTimestamp;
    private $oldConfig;

    public function setUp()
    {
        global $config;
        $this->oldConfig = $config;
        $this->oldTimestamp = Record::getTimestamp();

        $config = $config ? clone $config : new stdClass;
        $config->interadmin_preview = true;

        Record::setTimestamp(strtotime('2016-01-01 02:00:00'));
    }

    public function tearDown()
    {
        global $config;
        $config = $this->oldConfig;
        Record::setTimestamp($this->oldTimestamp);
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
                'deleted'  => '',
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00' // sem date_expire
            ]],
            [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted'  => '',
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:00:01' // date_expire no futuro
            ]],
            [[
                'char_key' => 'S',
                'publish'  => '', // sem publish
                'deleted'  => '',
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
                'deleted'  => '',
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted'  => 'S', // com deleted
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted'  => '',
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:00:00',
                'date_expire' => '2016-01-01 01:59:59' // date_expire no passado
            ]],
            [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted'  => '',
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 02:00:01', // date_publish no futuro
                'date_expire' => '2016-01-01 03:00:00'
            ]],
            [[
                'char_key' => 'S',
                'publish'  => '', // sem publish
                'deleted'  => '',
                'parent_id'  => 0, // sem parent
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:00:01'
            ]],
        ];
    }
}

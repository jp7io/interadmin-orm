<?php

namespace Tests\Interadmin;

use Jp7_Interadmin_Upload as Upload;

class UploadInterventionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Upload::setAdapter(new \Jp7_Interadmin_Upload_Intervention);
    }

    public function tearDown()
    {

    }

     /**
     * @dataProvider storageProvider
     */
    public function testUrlStorage($filePath, $expected, $template = null)
    {
        global $config;
        $config = (object) [
            'storage' => ['host' => $this->storageHost()],
            'imagecache' => true,
        ];

        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }

    public function storageProvider()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', 'http://'.$this->storageHost().'/imagecache/original/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.png', 'http://'.$this->storageHost().'/imagecache/original/mediabox/00202630.png'],
            ['../../upload/mediabox/00202630.jpeg', 'http://'.$this->storageHost().'/imagecache/thumb_interadmin/mediabox/00202630.jpeg', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.jpeg?v=2', 'http://'.$this->storageHost().'/imagecache/thumb_interadmin/mediabox/00202630.jpeg?v=2', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', 'http://'.$this->storageHost().'/upload/mediabox/00202630.pdf'],
            ['../../upload/mediabox/00202630.pdf?v=2', 'http://'.$this->storageHost().'/upload/mediabox/00202630.pdf?v=2'],
            ['_default/file.css', '_default/file.css'],
            [$this->externalUrl().'/upload/image.jpg', $this->externalUrl().'/upload/image.jpg'],
            [$this->externalUrl().'/upload/image.jpg', $this->externalUrl().'/upload/image.jpg', 'thumb_interadmin']
        ];
    }

    private function url($filePath, $template)
    {
        if (isset($template)) {
            return Upload::url($filePath, $template);
        }
        return Upload::url($filePath);
    }

    private function storageHost()
    {
        return 'storage.fakeurl.com';
    }

    private function externalUrl()
    {
        return 'http://www.external.com';
    }
}

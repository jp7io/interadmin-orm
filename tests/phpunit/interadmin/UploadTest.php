<?php

use Jp7_InterAdmin_Upload as Upload;

class UploadTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $config;
        $config = (object) [ 
            'server' => (object)['host' => 'www.client.com.br'],
            'name_id' => 'client'
        ];
    }

    public function tearDown()
    {

    }

     /**
     * @dataProvider storageProvider
     */
    public function testUrlstorage($filePath, $expected, $template = null)
    {
        global $config;
        $config = (object) ['storage' => ['host' => 'client.fakeurl.com'] ];

        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }  

    public function storageProvider()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', $this->storageUrl().'/imagecache/original/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.jpeg', $this->storageUrl().'/imagecache/thumb_interadmin/mediabox/00202630.jpeg', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', $this->storageUrl().'/upload/mediabox/00202630.pdf'],
            ['_default/file.css', '_default/file.css'],
            [$this->externalUrl().'/upload/image.jpg', $this->externalUrl().'/upload/image.jpg'],
            [$this->externalUrl().'/upload/image.jpg', $this->externalUrl().'/upload/image.jpg', 'thumb_interadmin']
        ];
    }

    /**
     * @dataProvider LegacyProvider
     */
    public function testUrlLegacy($filePath, $expected, $template = null)
    {
        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }

    public function LegacyProvider()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', $this->appUrl().'/client/imagecache/original/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.jpeg', $this->appUrl().'/client/imagecache/thumb_interadmin/mediabox/00202630.jpeg', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', $this->appUrl().'/client/upload/mediabox/00202630.pdf'],
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
        return $url = Upload::url($filePath);
    }

    private function appUrl()
    {
        return 'http://www.client.com.br';
    }

    private function storageUrl()
    {
        return 'http://client.fakeurl.com';
    }

    private function externalUrl()
    {
        return 'http://client.fakeurl.com';
    }

}

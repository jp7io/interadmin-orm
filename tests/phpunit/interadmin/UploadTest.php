<?php

use Jp7_InterAdmin_Upload as Upload;

class UploadTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $config;
        $config = (object) [ 
            'server' => (object)['host' => $this->appUrl()],
            'name_id' => 'client',
            'imagecache' => null,
        ];
    }

    public function tearDown()
    {

    }

    /**
     * @dataProvider storageProvider
     * @group fail
     */
    public function testUrlstorage($filePath, $expected, $template = null)
    {
        global $config;
        $config = (object) [
        'storage' => ['host' => $this->storageUrl()],
        ];

        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }  

    public function storageProvider()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', $this->storageUrl().'/upload/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.png', $this->storageUrl().'/upload/mediabox/00202630.png'],
            ['../../upload/mediabox/00202630.jpeg', $this->storageUrl().'/upload/mediabox/00202630.jpeg?size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.jpeg?v=2', $this->storageUrl().'/upload/mediabox/00202630.jpeg?v=2&size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', $this->storageUrl().'/upload/mediabox/00202630.pdf'],
            ['../../upload/mediabox/00202630.pdf?v=2', $this->storageUrl().'/upload/mediabox/00202630.pdf?v=2'],
            ['_default/file.css', '_default/file.css'],
            [$this->externalUrl().'/upload/image.jpg', $this->externalUrl().'/upload/image.jpg'],
            [$this->externalUrl().'/upload/image.jpg', $this->externalUrl().'/upload/image.jpg', 'thumb_interadmin']
        ];
    }

     /**
     * @dataProvider storageWithImageCacheProvider
     */
    public function testUrlstorageWithImageCache($filePath, $expected, $template = null)
    {
        global $config;
        $config = (object) [
        'storage' => ['host' => $this->storageUrl()],
        'imagecache' => true, 
        ];

        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }  

    public function storageWithImageCacheProvider()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', $this->storageUrl().'/imagecache/original/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.png', $this->storageUrl().'/imagecache/original/mediabox/00202630.png'],
            ['../../upload/mediabox/00202630.jpeg', $this->storageUrl().'/imagecache/thumb_interadmin/mediabox/00202630.jpeg', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.jpeg?v=2', $this->storageUrl().'/imagecache/thumb_interadmin/mediabox/00202630.jpeg?v=2', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', $this->storageUrl().'/upload/mediabox/00202630.pdf'],
            ['../../upload/mediabox/00202630.pdf?v=2', $this->storageUrl().'/upload/mediabox/00202630.pdf?v=2'],
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
            ['../../upload/mediabox/00202630.jpeg', $this->appUrl().'/client/upload/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.png', $this->appUrl().'/client/upload/mediabox/00202630.png'],
            ['../../upload/mediabox/00202630.jpeg', $this->appUrl().'/client/upload/mediabox/00202630.jpeg?size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.jpeg?v=2', $this->appUrl().'/client/upload/mediabox/00202630.jpeg?v=2&size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', $this->appUrl().'/client/upload/mediabox/00202630.pdf'],
            ['../../upload/mediabox/00202630.pdf?v=2', $this->appUrl().'/client/upload/mediabox/00202630.pdf?v=2'],
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
        return 'http://www.site.com';
    }

}

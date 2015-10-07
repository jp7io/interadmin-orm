<?php

namespace Tests\Interadmin;

use Jp7_InterAdmin_Upload as Upload;

class UploadImgixTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Upload::setAdapter(new \Jp7_InterAdmin_Upload_Imgix);
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
            'imgix' => [
                'host' => $this->imgixHost(),
                'templates' => [
                    'thumb_interadmin' => ['w' => 40, 'h' => 40]
                ]
            ],
            'imagecache' => 'imgix',
        ];

        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }

    public function storageProvider()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', 'http://'.$this->imgixHost().'/upload/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.png', 'http://'.$this->imgixHost().'/upload/mediabox/00202630.png'],
            ['../../upload/mediabox/00202630.jpeg', 'http://'.$this->imgixHost().'/upload/mediabox/00202630.jpeg?w=40&h=40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.jpeg?v=2', 'http://'.$this->imgixHost().'/upload/mediabox/00202630.jpeg?v=2&w=40&h=40', 'thumb_interadmin'],
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
        return $url = Upload::url($filePath);
    }

    private function storageHost()
    {
        return 'storage.fakeurl.com';
    }

    private function imgixHost()
    {
        return 'client.imgix.net';
    }

    private function externalUrl()
    {
        return 'http://www.external.com';
    }
}

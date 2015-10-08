<?php

namespace Tests\Interadmin;

use Jp7_InterAdmin_Upload as Upload;

class UploadLegacyTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Upload::setAdapter(new \Jp7_InterAdmin_Upload_Legacy);
    }

    public function tearDown()
    {

    }

    /**
     * @dataProvider legacyProvider
     */
    public function testUrlLegacy($filePath, $expected, $template = null)
    {
        global $config;
        $config = (object) [
            'storage' => ['host' => $this->appHost()]
        ];

        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }

    public function legacyProvider()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', 'http://'.$this->appHost().'/upload/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.png', 'http://'.$this->appHost().'/upload/mediabox/00202630.png'],
            ['../../upload/mediabox/00202630.jpeg', 'http://'.$this->appHost().'/upload/mediabox/00202630.jpeg?size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.jpeg?v=2', 'http://'.$this->appHost().'/upload/mediabox/00202630.jpeg?v=2&size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', 'http://'.$this->appHost().'/upload/mediabox/00202630.pdf'],
            ['../../upload/mediabox/00202630.pdf?v=2', 'http://'.$this->appHost().'/upload/mediabox/00202630.pdf?v=2'],
            ['_default/file.css', '_default/file.css'],
            [$this->externalUrl().'/upload/image.jpg', $this->externalUrl().'/upload/image.jpg'],
            [$this->externalUrl().'/upload/image.jpg', $this->externalUrl().'/upload/image.jpg', 'thumb_interadmin']
        ];
    }

    /**
     * @group path
     * @dataProvider legacyProviderWithPath
     */
    public function testUrlLegacyWithPath($filePath, $expected, $template = null)
    {
        global $config;
        $config = (object) [
            'storage' => [
                'host' => $this->appHost(),
                'path' => 'client'
            ]
        ];
        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }

    public function legacyProviderWithPath()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', 'http://'.$this->appHost().'/client/upload/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.png', 'http://'.$this->appHost().'/client/upload/mediabox/00202630.png'],
            ['../../upload/mediabox/00202630.jpeg', 'http://'.$this->appHost().'/client/upload/mediabox/00202630.jpeg?size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.jpeg?v=2', 'http://'.$this->appHost().'/client/upload/mediabox/00202630.jpeg?v=2&size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', 'http://'.$this->appHost().'/client/upload/mediabox/00202630.pdf'],
            ['../../upload/mediabox/00202630.pdf?v=2', 'http://'.$this->appHost().'/client/upload/mediabox/00202630.pdf?v=2'],
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

    private function appHost()
    {
        return 'www.app.com.br';
    }

    private function externalUrl()
    {
        return 'http://www.external.com';
    }
}

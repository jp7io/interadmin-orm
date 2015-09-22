<?php

namespace Jp7\Laravel;

use Illuminate\Routing\Controller as BaseController;
use Intervention\Image\ImageManagerStatic as Image;
use Storage;

class ImageCacheController extends BaseController
{
    public function create($template, $filepath)
    {
        $original = Storage::get('upload/' . $filepath);
        $img = Image::make($original);
        
        if ($template !== 'original') {
            $templates = config('imagecache.templates');
            $closure = $templates[$template];
            $img = $closure($img);
        }
        $img->encode();

        $saveOn = 'imagecache/'.$template.'/'.$filepath;
        Storage::put($saveOn, $img->__toString(), 'public');

        return $img->response();
    }

    public function clear($file)
    {
        $templates = array_keys(config('imagecache.templates'));
        foreach ($templates as $template) {
            $deleteAt = 'imagecache/'.$template.'/'.$file;
            if (Storage::has($deleteAt)) {
                Storage::delete($deleteAt);
            }
        }
    }
}

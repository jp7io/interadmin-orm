<?php

interface Jp7_InterAdmin_Upload_AdapterInterface
{
    public function imageUrl($path, $template);

    public function url($path);
}
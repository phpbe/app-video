<?php

namespace Be\App\Video;


class Property extends \Be\App\Property
{

    protected string $label = '视频';
    protected string $icon = 'bi-play';
    protected string $description = '视频管理系统';

    public function __construct() {
        parent::__construct(__FILE__);
    }

}

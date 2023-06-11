<?php

namespace Be\App\Video\Section\Category\LatestTopNSide;

use Be\Be;
use Be\Theme\Section;

class Template extends Section
{

    public array $positions = ['west', 'east'];

    public array $routes = ['Video.Category.videos'];

    public function display()
    {
        if ($this->config->enable === 0) {
            return;
        }

        $videos = Be::getService('App.Video.Video')->getCategoryLatestTopNVideos($this->page->category->id, $this->config->quantity);
        if (count($videos) === 0) {
            return;
        }

        $defaultMoreLink = beUrl('Video.Video.latest');
        echo Be::getService('App.Video.Section')->makeSideVideosSection($this, 'app-video-category-latest-top-n-side', $videos, $defaultMoreLink);
    }
}


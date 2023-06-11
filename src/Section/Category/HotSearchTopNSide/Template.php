<?php

namespace Be\App\Video\Section\Category\HotSearchTopNSide;

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

        $request = Be::getRequest();
        if ($request->getRoute() !== 'Video.Category.videos') {
            return;
        }

        $videos = Be::getService('App.Video.Video')->getCategoryHotSearchTopNVideos($this->page->category->id, $this->config->quantity);
        if (count($videos) === 0) {
            return;
        }

        echo Be::getService('App.Video.Section')->makeVideosSection($this, 'app-video-category-hot-search-top-n-side', $videos);
    }
}


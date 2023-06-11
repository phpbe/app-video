<?php

namespace Be\App\Video\Section\Video\Detail\Similar;

use Be\Be;
use Be\Theme\Section;

class Template extends Section
{

    public array $positions = ['middle', 'west', 'center', 'east'];

    public array $routes = ['Video.Video.detail'];

    public function display()
    {
        if ($this->config->enable === 0) {
            return;
        }

        $request = Be::getRequest();
        $route = $request->getRoute();

        // 仅详情页，预览页可用
        if (!in_array($route, ['Video.Video.detail', 'Video.Video.preview'])) {
            return;
        }

        // 无视频数据时不显示
        if (!isset($this->page->video) || !$this->page->video) {
            return;
        }

        $video = $this->page->video;

        $videos = Be::getService('App.Video.Video')->getSimilarVideos($video->id, $video->title, $this->config->quantity);
        if (count($videos) === 0) {
            return;
        }

        echo Be::getService('App.Video.Section')->makeSideVideosSection($this, 'app-video-similar', $videos);
    }
}


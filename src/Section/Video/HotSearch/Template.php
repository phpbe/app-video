<?php

namespace Be\App\Video\Section\Video\HotSearch;

use Be\Be;
use Be\Theme\Section;

class Template extends Section
{

    public array $positions = ['middle', 'center'];

    public function display()
    {
        if ($this->config->enable === 0) {
            return;
        }

        $request = Be::getRequest();
        $response = Be::getResponse();

        $page = $request->get('page', 1);
        if ($page > $this->config->maxPages) {
            $page = $this->config->maxPages;
        }
        $params = [
            'pageSize' => $this->config->pageSize,
            'page' => $page,
        ];

        $result = Be::getService('App.Video.Video')->getHotSearchVideos($params);

        echo Be::getService('App.Video.Section')->makePagedVideosSection($this, 'app-video-video-hot-search', $result);
    }

}


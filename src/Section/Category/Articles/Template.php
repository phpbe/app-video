<?php

namespace Be\App\Video\Section\Category\Videos;

use Be\Be;
use Be\Theme\Section;

class Template extends Section
{

    public array $positions = ['middle', 'center'];

    public array $routes = ['Video.Category.videos'];

    public function display()
    {
        if ($this->config->enable === 0) {
            return;
        }

        $request = Be::getRequest();
        $page = $request->get('page', 1);
        if ($page > $this->config->maxPages) {
            $page = $this->config->maxPages;
        }
        $params = [
            'categoryId' => $this->page->category->id,
            'orderBy' => ['is_on_top', 'publish_time'],
            'orderByDir' => ['desc', 'desc'],
            'pageSize' => $this->config->pageSize,
            'page' => $page,
        ];

        $result = Be::getService('App.Video.Video')->search('', $params);

        echo Be::getService('App.Video.Section')->makePagedVideosSection($this, 'app-video-category-videos', $result);
    }
}


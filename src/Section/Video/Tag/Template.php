<?php

namespace Be\App\Video\Section\Video\Tag;

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

        $tag = $request->get('tag', '');
        $tag = trim($tag);
        if ($tag === '') {
            $response->error(beLang('App.Video', 'VIDEO.TAG_IS_MISSING'));
            return;
        }

        $page = $request->get('page', 1);
        if ($page > $this->config->maxPages) {
            $page = $this->config->maxPages;
        }
        $params = [
            'tag' => $tag,
            'pageSize' => $this->config->pageSize,
            'page' => $page,
        ];

        $result = Be::getService('App.Video.Video')->search('', $params);

        echo Be::getService('App.Video.Section')->makePagedVideosSection($this, 'app-video-video-tag', $result);
    }
}


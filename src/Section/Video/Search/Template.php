<?php

namespace Be\App\Video\Section\Video\Search;

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

        $keywords = $request->get('keywords', '');
        $keywords = trim($keywords);

        /*
        if ($keywords === '') {
            $response->error(beLang('App.Video', 'VIDEO.SEARCH_KEYWORDS_IS_MISSING'));
            return;
        }
        */

        $page = $request->get('page', 1);
        if ($page > $this->config->maxPages) {
            $page = $this->config->maxPages;
        }
        $params = [
            'pageSize' => $this->config->pageSize,
            'page' => $page,
        ];

        $result = Be::getService('App.Video.Video')->search($keywords, $params);

        echo Be::getService('App.Video.Section')->makePagedVideosSection($this, 'app-video-video-search', $result);
    }
}


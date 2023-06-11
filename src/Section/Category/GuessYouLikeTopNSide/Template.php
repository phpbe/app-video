<?php

namespace Be\App\Video\Section\Category\GuessYouLikeTopNSide;

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

        $videos = Be::getService('App.Video.Video')->getCategoryGuessYouLikeTopNVideos($this->page->category->id, $this->config->quantity);
        if (count($videos) === 0) {
            return;
        }

        $defaultMoreLink = beUrl('Video.Video.guessYouLike');
        echo Be::getService('App.Video.Section')->makeSideVideosSection($this, 'app-video-category-guess-you-like-top-n-side', $videos, $defaultMoreLink);
    }

}


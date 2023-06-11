<?php

namespace Be\App\Video\Section\Video\GuessYouLikeTopNSide;

use Be\Be;
use Be\Theme\Section;

class Template extends Section
{

    public array $positions = ['west', 'east'];

    public function display()
    {
        if ($this->config->enable === 0) {
            return;
        }

        $videos = Be::getService('App.Video.Video')->getGuessYouLikeTopNVideos($this->config->quantity);
        if (count($videos) === 0) {
            return;
        }

        $defaultMoreLink = beUrl('Video.Video.guessYouLike');
        echo Be::getService('App.Video.Section')->makeSideVideosSection($this, 'app-video-video-guess-you-like-top-n-side', $videos, $defaultMoreLink);
    }

}


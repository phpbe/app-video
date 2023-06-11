<?php

namespace Be\App\Video\Config\Page\Video;

class guessYouLike
{



    public int $west = 0;
    public int $center = 75;
    public int $east = 25;


    public array $centerSections = [
        [
            'name' => 'App.Video.PageTitle',
        ],
        [
            'name' => 'App.Video.GuessYouLike',
        ],
    ];

    public array $eastSections = [
        [
            'name' => 'App.Video.Video.SearchFormSide',
        ],
        [
            'name' => 'App.Video.Video.LatestTopNSide',
        ],
        [
            'name' => 'App.Video.Video.HottestTopNSide',
        ],
        [
            'name' => 'App.Video.Video.HotSearchTopNSide',
        ],
        [
            'name' => 'App.Video.Video.GuessYouLikeTopNSide',
        ],
        [
            'name' => 'App.Video.Video.TagTopNSide',
        ],
    ];

}

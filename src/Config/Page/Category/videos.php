<?php

namespace Be\App\Video\Config\Page\Category;

class videos
{

    public int $west = 0;
    public int $center = 75;
    public int $east = 25;

    public array $centerSections = [
        [
            'name' => 'App.Video.Category.Videos',
        ],
    ];

    public array $eastSections = [
        [
            'name' => 'App.Video.Category.TopNSide',
        ],
        [
            'name' => 'App.Video.Category.LatestTopNSide',
        ],
        [
            'name' => 'App.Video.Category.HottestTopNSide',
        ],
        [
            'name' => 'App.Video.Category.HotSearchTopNSide',
        ],
        [
            'name' => 'App.Video.Category.GuessYouLikeTopNSide',
        ],
        [
            'name' => 'App.Video.Video.TagTopNSide',
        ],
    ];


}

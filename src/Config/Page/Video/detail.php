<?php

namespace Be\App\Video\Config\Page\Video;


class detail
{

    public int $west = 0;
    public int $center = 75;
    public int $east = 25;

    public array $centerSections = [
        [
            'name' => 'App.Video.Video.Detail.Main',
        ],
        [
            'name' => 'App.Video.Video.Detail.Comments',
        ],
        [
            'name' => 'App.Video.Video.Detail.CommentForm',
        ],
        [
            'name' => 'App.Video.Video.Detail.Similar',
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

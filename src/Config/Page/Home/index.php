<?php

namespace Be\App\Video\Config\Page\Home;

/**
 * @BeConfig("首页")
 */
class index
{

    public int $west = 0;
    public int $center = 75;
    public int $east = 25;

    public array $centerSections = [
        [
            'name' => 'App.Video.Home',
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

    /**
     * @BeConfigItem("HEAD头标题",
     *     description="HEAD头标题，用于SEO",
     *     driver = "FormItemInput"
     * )
     */
    public string $title = '首页';

    /**
     * @BeConfigItem("Meta描述",
     *     description="填写页面内容的简单描述，用于SEO",
     *     driver = "FormItemInputTextArea"
     * )
     */
    public string $metaDescription = '首页';

    /**
     * @BeConfigItem("Meta关键词",
     *     description="填写页面内容的关键词，用于SEO",
     *     driver = "FormItemInput"
     * )
     */
    public string $metaKeywords = '首页';

    /**
     * @BeConfigItem("页面标题",
     *     description="展示在页面内容中的标题，一般与HEAD头标题一致，两者相同时可不填写此项",
     *     driver = "FormItemInput"
     * )
     */
    public string $pageTitle = '';

}

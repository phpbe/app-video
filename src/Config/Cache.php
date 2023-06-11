<?php
namespace Be\App\Video\Config;

/**
 * @BeConfig("缓存")
 */
class Cache
{

    /**
     * @BeConfigItem("分类列表", driver="FormItemInputNumberInt")
     */
    public int $categories = 600;

    /**
     * @BeConfigItem("分类", driver="FormItemInputNumberInt")
     */
    public int $category = 600;

    /**
     * @BeConfigItem("视频详情", driver="FormItemInputNumberInt")
     */
    public int $video = 600;

    /**
     * @BeConfigItem("视频列表类", driver="FormItemInputNumberInt")
     */
    public int $videos = 600;

    /**
     * @BeConfigItem("热门标签", driver="FormItemInputNumberInt")
     */
    public int $tag = 600;

    /**
     * @BeConfigItem("热搜关键词", driver="FormItemInputNumberInt")
     */
    public int $hotKeywords = 600;

}


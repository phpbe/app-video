<?php

namespace Be\App\Video\Service;

use Be\App\ServiceException;
use Be\Be;

class Video
{

    /**
     * 获取视频
     *
     * @param string $videoId 视频ID
     * @return object 视频对象
     * @throws ServiceException
     */
    public function getVideo(string $videoId): object
    {
        $key = 'App:Video:' . $videoId;
        if (Be::hasContext($key)) {
            $video = Be::getContext($key);
        } else {
            $cache = Be::getCache();
            $video = $cache->get($key);
            if ($video === false) {
                try {
                    $video = $this->getVideoFromDb($videoId);
                } catch (\Throwable $t) {
                    $video = '-1';
                }

                $configCache = Be::getConfig('App.Video.Cache');
                $cache->set($key, $video, $configCache->video);
            }

            Be::setContext($key, $video);
        }

        if ($video === '-1') {
            throw new ServiceException(beLang('App.Video', 'VIDEO.NOT_EXIST'));
        }

        return $video;
    }

    /**
     * 获取视频
     *
     * @param string $videoId 视频ID
     * @return object 视频对象
     * @throws ServiceException
     */
    public function getVideoFromDb(string $videoId): object
    {
        $db = Be::getDb();

        $sql = 'SELECT * FROM `video` WHERE id=?';
        $video = $db->getObject($sql, [$videoId]);
        if (!$video) {
            throw new ServiceException(beLang('App.Video', 'VIDEO.NOT_EXIST'));
        }

        $video->is_enable = (int)$video->is_enable;
        $video->is_delete = (int)$video->is_delete;
        if ($video->is_enable !== 1 || $video->is_delete !== 0) {
            throw new ServiceException(beLang('App.Video', 'VIDEO.NOT_EXIST'));
        }

        $categories = [];
        $sql = 'SELECT category_id FROM video_category WHERE video_id = ?';
        $categoryIds = $db->getValues($sql, [$videoId]);
        if (count($categoryIds) > 0) {
            $sql = 'SELECT id, `name` FROM video_category WHERE is_delete=0 AND id IN (\'' . implode('\',\'', $categoryIds) . '\') ORDER BY ordering ASC';
            $categories = $db->getObjects($sql);
        }
        $video->categories = $categories;
        $video->category_ids = array_column($categories, 'id');

        $sql = 'SELECT tag FROM video_tag WHERE video_id = ?';
        $video->tags = $db->getValues($sql, [$videoId]);

        $newVideo = new \stdClass();
        $newVideo->id = $video->id;
        $newVideo->image = $video->image;
        $newVideo->title = $video->title;
        $newVideo->summary = $video->summary;
        $newVideo->description = $video->description;
        $newVideo->url = $video->url;
        //$newVideo->url_custom = (int)$video->url_custom;
        $newVideo->author = $video->author;
        $newVideo->publish_time = $video->publish_time;
        $newVideo->seo_title = $video->seo_title;
        //$newVideo->seo_title_custom = (int)$video->seo_title_custom;
        $newVideo->seo_description = $video->seo_description;
        //$newVideo->seo_description_custom = (int)$video->seo_description_custom;
        $newVideo->seo_keywords = $video->seo_keywords;
        //$newVideo->ordering = (int)$video->ordering;
        $newVideo->hits = $video->hits;
        //$newVideo->is_push_home = (int)$video->is_push_home;
        // $newVideo->is_on_top = (int)$video->is_on_top;

        $newVideo->categories = $video->categories;
        $newVideo->category_ids = $video->category_ids;
        $newVideo->tags = $video->tags;

        return $newVideo;
    }

    /**
     * 从缓存获取多个视频数据
     *
     * @param array $videoIds 多个商品ID
     * @param bool $throwException 不存在的视频是否抛出异常
     * @return array
     */
    public function getVideos(array $videoIds = [], bool $throwException = true): array
    {
        $configCache = Be::getConfig('App.Video.Cache');
        $cache = Be::getCache();

        $keys = [];
        foreach ($videoIds as $videoId) {
            $keys[] = 'App:Video:' . $videoId;
        }

        $videos = $cache->getMany($keys);

        $noVideos = true;
        foreach ($videos as $video) {
            if ($video) {
                $noVideos = false;
            }
        }

        // 缓存中没有任何商品，全部从数据库中读取并缓存
        if ($noVideos) {

            $newVideos = [];
            foreach ($videoIds as $videoId) {

                $key = 'App:Video:' . $videoId;
                try {
                    $video = $this->getVideoFromDb($videoId);
                } catch (\Throwable $t) {
                    $video = '-1';
                }

                $cache->set($key, $video, $configCache->video);

                if ($video === '-1') {
                    if ($throwException) {
                        throw new ServiceException(beLang('App.Video', 'VIDEO.NOT_EXIST'));
                    } else {
                        continue;
                    }
                }

                $newVideos[] = $video;
            }

        } else {

            $newVideos = [];
            $i = 0;
            foreach ($videos as $video) {
                if ($video === false || $video === '-1') {
                    if ($throwException) {
                        throw new ServiceException(beLang('App.Video', 'VIDEO.NOT_EXIST'));
                    } else {
                        continue;
                    }
                }

                $newVideos[] = $video;
                $i++;
            }
        }

        return $newVideos;
    }

    /**
     * 查看视频并更新点击
     *
     * @param string $videoId 视频ID
     * @return object
     */
    public function hit(string $videoId): object
    {
        $my = Be::getUser();
        $cache = Be::getCache();

        $configVideo = Be::getConfig('App.Video.Video');

        $video = $this->getVideo($videoId);

        $historyKey = 'App:Video:History:' . $my->id;
        $history = $cache->get($historyKey);

        if (!$history || !is_array($history)) {
            $history = [];
        }

        $history[] = $video->title;

        $viewHistory = $configVideo->viewHistory > 0 ? $configVideo->viewHistory : 20;
        if (count($history) > $viewHistory) {
            $history = array_slice($history, -$viewHistory);
        }

        // 最近浏览的视频标题存入缓存，有效期 30 天
        $cache->set($historyKey, $history, 86400 * 30);

        // 点击量 使用缓存 存放
        $hits = (int)$video->hits;
        $hitsKey = 'App:Video:hits:' . $videoId;
        $cacheHits = $cache->get($hitsKey);
        if ($cacheHits !== false) {
            if (is_numeric($cacheHits)) {
                $cacheHits = (int)$cacheHits;
                if ($cacheHits > $video->hits) {
                    $hits = $cacheHits;
                }
            }
        }

        $hits++;

        $cache->set($hitsKey, $hits);

        // 每 100 次访问，更新到数据库
        if ($hits % 100 === 0) {
            $sql = 'UPDATE video SET hits=?, update_time=? WHERE id=?';
            Be::getDb()->query($sql, [$hits, date('Y-m-d H:i:s'), $videoId]);
        }

        $video->hits = $hits;

        return $video;
    }

    /**
     * 按关銉词搜索
     *
     * @param string $keywords 关銉词
     * @param array $params
     * @return array
     */
    public function search(string $keywords, array $params = []): array
    {
        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Video.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return $this->searchFromDb($keywords, $params);
        }

        $configVideo = Be::getConfig('App.Video.Video');

        $cache = Be::getCache();
        $es = Be::getEs();

        $keywords = trim($keywords);
        if ($keywords !== '') {
            // 将本用户搜索的关键词写入ES search_history
            $counterKey = 'App:Video:searchHistory';
            $counter = (int)$cache->get($counterKey);
            $query = [
                'index' => $configEs->indexVideoSearchHistory,
                'id' => $counter,
                'body' => [
                    'keyword' => $keywords,
                ]
            ];
            $es->index($query);

            // 累计写入1千个
            $counter++;
            if ($counter >= $configVideo->searchHistory) {
                $counter = 0;
            }

            $cache->set($counterKey, $counter);
        }

        $cacheKey = 'App:Video:search';
        if ($keywords !== '') {
            $cacheKey .= ':' . $keywords;
        }
        $cacheKey .= ':' . md5(serialize($params));

        $results = $cache->get($cacheKey);
        if ($results !== false) {
            return $results;
        }

        $query = [
            'index' => $configEs->indexVideo,
            'body' => [
            ]
        ];

        if ($keywords === '') {
            $query['body']['min_score'] = 0;
        } else {
            $query['body']['min_score'] = 0.01;

            if (!isset($query['body']['query'])) {
                $query['body']['query'] = [];
            }

            if (!isset($query['body']['query']['bool'])) {
                $query['body']['query']['bool'] = [];
            }

            $query['body']['query']['bool']['should'] = [
                [
                    'match' => [
                        'title' => [
                            'query' => $keywords,
                            'boost' => 2,
                        ]
                    ],
                ],
                [
                    'match' => [
                        'summary' => [
                            'query' => $keywords,
                            'boost' => 1,
                        ]
                    ],
                ],
                [
                    'match' => [
                        'description' => [
                            'query' => $keywords,
                            'boost' => 1,
                        ]
                    ],
                ],
            ];
        }

        if (isset($params['isPushHome']) && in_array($params['isPushHome'], [0, 1])) {

            if (!isset($query['body']['query'])) {
                $query['body']['query'] = [];
            }

            if (!isset($query['body']['query']['bool'])) {
                $query['body']['query']['bool'] = [];
            }

            if (!isset($query['body']['query']['bool']['filter'])) {
                $query['body']['query']['bool']['filter'] = [];
            }

            $query['body']['query']['bool']['filter'][] = [
                'term' => [
                    'is_push_home' => (bool)$params['isPushHome'],
                ]
            ];
        }

        if (isset($params['categoryId']) && $params['categoryId']) {

            if (!isset($query['body']['query'])) {
                $query['body']['query'] = [];
            }

            if (!isset($query['body']['query']['bool'])) {
                $query['body']['query']['bool'] = [];
            }

            if (!isset($query['body']['query']['bool']['filter'])) {
                $query['body']['query']['bool']['filter'] = [];
            }

            $query['body']['query']['bool']['filter'][] = [
                'nested' => [
                    'path' => 'categories',
                    'query' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'term' => [
                                        'categories.id' => $params['categoryId'],
                                    ],
                                ],
                            ]
                        ],
                    ],
                ]
            ];
        }

        if (isset($params['tag']) && $params['tag']) {

            if (!isset($query['body']['query'])) {
                $query['body']['query'] = [];
            }

            if (!isset($query['body']['query']['bool'])) {
                $query['body']['query']['bool'] = [];
            }

            if (!isset($query['body']['query']['bool']['filter'])) {
                $query['body']['query']['bool']['filter'] = [];
            }

            $query['body']['query']['bool']['filter'][] = [
                'term' => [
                    'tags' => $params['tag'],
                ]
            ];
        }

        if (isset($params['orderBy'])) {
            if (is_array($params['orderBy'])) {
                $len1 = count($params['orderBy']);
                if ($len1 > 0 && is_array($params['orderByDir'])) {
                    $len2 = count($params['orderByDir']);
                    if ($len1 === $len2) {
                        $query['body']['sort'] = [];
                        for ($i = 0; $i < $len1; $i++) {
                            $orderByDir = 'desc';
                            if (in_array($params['orderByDir'][$i], ['asc', 'desc'])) {
                                $orderByDir = $params['orderByDir'][$i];
                            }
                            $query['body']['sort'][] = [
                                $params['orderBy'][$i] => [
                                    'order' => $orderByDir
                                ]
                            ];
                        }
                    }
                }
            } elseif (is_string($params['orderBy']) && in_array($params['orderBy'], ['hits', 'publish_time'])) {
                $orderByDir = 'desc';
                if (in_array($params['orderByDir'], ['asc', 'desc'])) {
                    $orderByDir = $params['orderByDir'];
                }

                $query['body']['sort'] = [
                    [
                        $params['orderBy'] => [
                            'order' => $orderByDir
                        ]
                    ],
                ];
            }
        }

        // 分页
        $pageSize = null;
        if (isset($params['pageSize']) && is_numeric($params['pageSize']) && $params['pageSize'] > 0) {
            $pageSize = $params['pageSize'];
        } else {
            $pageSize = $configVideo->pageSize;
        }

        if ($pageSize > 200) {
            $pageSize = 200;
        }

        $page = null;
        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }

        $query['body']['size'] = $pageSize;
        $query['body']['from'] = ($page - 1) * $pageSize;

        $results = $es->search($query);

        $total = 0;
        if (isset($results['hits']['total']['value'])) {
            $total = $results['hits']['total']['value'];
        }

        $rows = [];
        foreach ($results['hits']['hits'] as $x) {
            $video = (object)$x['_source'];
            try {
                $video->absolute_url = beUrl('Video.Video.detail', ['id' => $video->id]);
            } catch (\Throwable $t) {
                continue;
            }

            $rows[] = $this->formatEsVideo($video);
        }

        $result = [
            'total' => $total,
            'pageSize' => $pageSize,
            'page' => $page,
            'rows' => $rows,
        ];

        $configCache = Be::getConfig('App.Video.Cache');
        $cache->set($cacheKey, $result, $configCache->videos);

        return $result;
    }

    /**
     * 按关銉词搜索
     *
     * @param string $keywords 关銉词
     * @param array $params
     * @return array
     */
    public function searchFromDb(string $keywords, array $params = []): array
    {
        $cache = Be::getCache();
        $cacheKey = 'App:Video:searchFromDb';
        if ($keywords !== '') {
            $cacheKey .= ':' . $keywords;
        }
        $cacheKey .= ':' . md5(serialize($params));

        $results = $cache->get($cacheKey);
        if ($results !== false) {
            return $results;
        }

        $configVideo = Be::getConfig('App.Video.Video');
        $tableVideo = Be::getTable('video');

        $tableVideo->where('is_enable', 1);
        $tableVideo->where('is_delete', 0);

        if ($keywords !== '') {
            $tableVideo->where('title', 'like', '%' . $keywords . '%');
        }

        if (isset($params['isPushHome']) && in_array($params['isPushHome'], [0, 1])) {
            $tableVideo->where('is_push_home', $params['isPushHome']);
        }

        $db = Be::getDb();
        if (isset($params['categoryId']) && $params['categoryId']) {
            $sql = 'SELECT video_id FROM video_category WHERE category_id = ?';
            $videoIds = $db->getValues($sql, [$params['categoryId']]);
            if (count($videoIds) > 0) {
                $tableVideo->where('id', 'IN', $videoIds);
            } else {
                $tableVideo->where('id', '');
            }
        }

        if (isset($params['tag']) && $params['tag']) {
            $sql = 'SELECT video_id FROM video_tag WHERE tag = ?';
            $videoIds = $db->getValues($sql, [$params['tag']]);
            if (count($videoIds) > 0) {
                $tableVideo->where('id', 'IN', $videoIds);
            } else {
                $tableVideo->where('id', '');
            }
        }

        $total = $tableVideo->count();

        if (isset($params['orderBy'])) {
            if (is_array($params['orderBy'])) {
                $len1 = count($params['orderBy']);
                if ($len1 > 0 && is_array($params['orderByDir'])) {
                    $len2 = count($params['orderByDir']);
                    if ($len1 === $len2) {
                        $orderByStrings = [];
                        for ($i = 0; $i < $len1; $i++) {
                            $orderByDir = 'desc';
                            if (in_array($params['orderByDir'][$i], ['asc', 'desc'])) {
                                $orderByDir = $params['orderByDir'][$i];
                            }
                            $orderByStrings[] = $params['orderBy'][$i] . ' ' . strtoupper($orderByDir);
                        }

                        $tableVideo->orderBy(implode(', ', $orderByStrings));
                    }
                }
            } elseif (is_string($params['orderBy']) && in_array($params['orderBy'], ['hits', 'publish_time'])) {
                $orderByDir = 'desc';
                if (isset($params['orderByDir']) && in_array($params['orderByDir'], ['asc', 'desc'])) {
                    $orderByDir = $params['orderByDir'];
                }

                $tableVideo->orderBy($params['orderBy'], strtoupper($orderByDir));
            }
        } else {
            $tableVideo->orderBy('is_on_top DESC, publish_time DESC');
        }

        // 分页
        $pageSize = null;
        if (isset($params['pageSize']) && is_numeric($params['pageSize']) && $params['pageSize'] > 0) {
            $pageSize = $params['pageSize'];
        } else {
            $pageSize = $configVideo->pageSize;
        }

        if ($pageSize > 200) {
            $pageSize = 200;
        }
        $tableVideo->limit($pageSize);

        $page = null;
        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }

        $tableVideo->offset(($page - 1) * $pageSize);

        $videoIds = $tableVideo->getValues('id');

        $rows = $this->getVideos($videoIds, false);

        $result = [
            'total' => $total,
            'pageSize' => $pageSize,
            'page' => $page,
            'rows' => $rows,
        ];

        $configCache = Be::getConfig('App.Video.Cache');
        $cache->set($cacheKey, $result, $configCache->videos);

        return $result;
    }

    /**
     * 跟据视频名称，获取相似视频
     *
     * @param string $videoId 视频ID
     * @param string $videoTitle 视频标题
     * @param int $n
     * @return array
     */
    public function getSimilarVideos(string $videoId, string $videoTitle, int $n = 12): array
    {
        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Video.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return $this->getSimilarVideosFromDb($videoId, $videoTitle, $n);
        }

        $cache = Be::getCache();
        $cacheKey = 'App:Video:SimilarVideos:' . $videoId . ':' . $n;
        $results = $cache->get($cacheKey);
        if ($results !== false) {
            return $results;
        }

        $query = [
            'index' => $configEs->indexVideo,
            'body' => [
                'size' => $n,
                'query' => [
                    'bool' => [
                        'must_not' => [
                            'term' => [
                                '_id' => $videoId
                            ]
                        ],
                        'must' => [
                            'match' => [
                                'title' => $videoTitle
                            ]
                        ],
                    ]
                ]
            ]
        ];

        $es = Be::getEs();
        $results = $es->search($query);

        $return = [];
        foreach ($results['hits']['hits'] as $x) {
            $video = (object)$x['_source'];
            try {
                $video->absolute_url = beUrl('Video.Video.detail', ['id' => $video->id]);
            } catch (\Throwable $t) {
                continue;
            }

            $return[] = $this->formatEsVideo($video);
        }

        $configCache = Be::getConfig('App.Video.Cache');
        $cache->set($cacheKey, $return, $configCache->videos);

        return $return;
    }

    /**
     * 跟据视频名称，获取相似视频
     *
     * @param string $videoId 视频ID
     * @param string $videoTitle 视频标题
     * @param int $n
     * @return array
     */
    public function getSimilarVideosFromDb(string $videoId, string $videoTitle, int $n = 12): array
    {
        $cache = Be::getCache();
        $cacheKey = 'App:Video:SimilarVideosFromDb:' . $videoId . ':' . $n;
        $results = $cache->get($cacheKey);
        if ($results !== false) {
            return $results;
        }

        $tableVideo = Be::getTable('video');
        $tableVideo->where('is_enable', 1)
            ->where('is_delete', 0)
            ->where('id', '!=', $videoId);

        if ($videoTitle !== '') {
            $tableVideo->where('title', 'like', '%' . $videoTitle . '%');
        }

        $tableVideo->limit($n);

        $videoIds = $tableVideo->getValues('id');

        $result = $this->getVideos($videoIds, false);

        $configCache = Be::getConfig('App.Video.Cache');
        $cache->set($cacheKey, $result, $configCache->videos);

        return $result;
    }

    /**
     * 获取按指定排序的前N个视频
     *
     * @param array $params 查询参数
     */
    public function getTopNVideos(array $params = []): array
    {
        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Video.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return $this->getTopVideosFromDb($params);
        }

        $cache = Be::getCache();
        $cacheKey = 'App:Video:TopVideos:' . md5(serialize($params));
        $results = $cache->get($cacheKey);
        if ($results !== false) {
            return $results;
        }


        $orderBy = $params['orderBy'];

        $orderByDir = 'desc';
        if (isset($params['orderByDir']) && in_array($params['orderByDir'], ['asc', 'desc'])) {
            $orderByDir = $params['orderByDir'];
        }

        // 分页
        if (isset($params['pageSize']) && is_numeric($params['pageSize']) && $params['pageSize'] > 0) {
            $pageSize = $params['pageSize'];
        } else {
            $pageSize = 12;
        }

        if ($pageSize > 200) {
            $pageSize = 200;
        }

        $query = [
            'index' => $configEs->indexVideo,
            'body' => [
                'size' => $pageSize,
                'sort' => [
                    $orderBy => [
                        'order' => $orderByDir
                    ]
                ]
            ]
        ];

        if (isset($params['categoryId']) && $params['categoryId'] !== '') {
            $query['body']['query'] = [
                'bool' => [
                    'filter' => [
                        [
                            'nested' => [
                                'path' => 'categories',
                                'query' => [
                                    'bool' => [
                                        'filter' => [
                                            [
                                                'term' => [
                                                    'categories.id' => $params['categoryId'],
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $es = Be::getEs();
        $results = $es->search($query);

        $return = [];
        foreach ($results['hits']['hits'] as $x) {
            $video = (object)$x['_source'];
            try {
                $video->absolute_url = beUrl('Video.Video.detail', ['id' => $video->id]);
            } catch (\Throwable $t) {
                continue;
            }

            $return[] = $this->formatEsVideo($video);
        }

        $configCache = Be::getConfig('App.Video.Cache');
        $cache->set($cacheKey, $return, $configCache->videos);

        return $return;
    }

    /**
     * 获取按指定排序的前N个视频
     *
     * @param array $params
     * @return array
     */
    public function getTopVideosFromDb(array $params = []): array
    {
        $cache = Be::getCache();
        $cacheKey = 'App:Video:TopVideosFromDb:' . md5(serialize($params));
        $result = $cache->get($cacheKey);
        if ($result !== false) {
            return $result;
        }

        $orderBy = $params['orderBy'];

        $orderByDir = 'desc';
        if (isset($params['orderByDir']) && in_array($params['orderByDir'], ['asc', 'desc'])) {
            $orderByDir = $params['orderByDir'];
        }

        // 分页
        if (isset($params['pageSize']) && is_numeric($params['pageSize']) && $params['pageSize'] > 0) {
            $pageSize = $params['pageSize'];
        } else {
            $pageSize = 12;
        }

        if ($pageSize > 200) {
            $pageSize = 200;
        }

        $tableVideo = Be::getTable('video')->where('is_enable', 1)->where('is_delete', 0);

        if (isset($params['categoryId']) && $params['categoryId']) {
            $db = Be::getDb();
            $sql = 'SELECT video_id FROM video_category WHERE category_id = ?';
            $videoIds = $db->getValues($sql, [$params['categoryId']]);
            if (count($videoIds) > 0) {
                $tableVideo->where('id', 'IN', $videoIds);
            } else {
                $tableVideo->where('id', '');
            }
        }

        $videoIds = $tableVideo->orderBy($orderBy, $orderByDir)
            ->limit($pageSize)
            ->getValues('id');

        $result = $this->getVideos($videoIds, false);

        $configCache = Be::getConfig('App.Video.Cache');
        $cache->set($cacheKey, $result, $configCache->videos);

        return $result;
    }

    /**
     * 最新视频
     *
     * @param int $n 结果数量
     * @return array
     */
    public function getLatestTopNVideos(int $n = 10): array
    {
        return $this->getTopNVideos([
            'orderBy' => 'publish_time',
            'orderByDir' => 'desc',
            'pageSize' => $n,
        ]);
    }

    /**
     * 热门视频
     *
     * @param int $n 结果数量
     * @return array
     */
    public function getHottestTopNVideos(int $n = 10): array
    {
        return $this->getTopNVideos([
            'orderBy' => 'hits',
            'orderByDir' => 'desc',
            'pageSize' => $n,
        ]);
    }

    /**
     * 指下究类的最新视频
     *
     * @param string $categoryId 分类ID
     * @param int $n 结果数量
     * @return array
     */
    public function getCategoryLatestTopNVideos(string $categoryId, int $n = 10): array
    {
        return $this->getTopNVideos([
            'categoryId' => $categoryId,
            'orderBy' => 'publish_time',
            'orderByDir' => 'desc',
            'pageSize' => $n,
        ]);
    }

    /**
     * 指下究类的热门视频
     *
     * @param string $categoryId 分类ID
     * @param int $n 结果数量
     * @return array
     */
    public function getCategoryHottestTopNVideos(string $categoryId, int $n = 10): array
    {
        return $this->getTopNVideos([
            'categoryId' => $categoryId,
            'orderBy' => 'hits',
            'orderByDir' => 'desc',
            'pageSize' => $n,
        ]);
    }


    /**
     * 热搜视频
     *
     * @param array $params 查询参数
     * @return array
     */
    public function getHotSearchVideos(array $params = []): array
    {
        // 分页
        if (isset($params['pageSize']) && is_numeric($params['pageSize']) && $params['pageSize'] > 0) {
            $pageSize = $params['pageSize'];
        } else {
            $pageSize = 12;
        }

        if ($pageSize > 200) {
            $pageSize = 200;
        }

        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }

        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Video.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return [
                'total' => 0,
                'pageSize' => $pageSize,
                'page' => $page,
                'rows' => [],
            ];
        }

        $keywords = $this->getHotSearchKeywords(5);
        if (!$keywords) {
            return [
                'total' => 0,
                'pageSize' => $pageSize,
                'page' => $page,
                'rows' => [],
            ];
        }

        $cache = Be::getCache();
        $cacheKey = 'App:Video:HotSearchVideos:' . md5(serialize($params));
        $results = $cache->get($cacheKey);
        if ($results !== false) {
            return $results;
        }

        $query = [
            'index' => $configEs->indexVideo,
            'body' => [
                'size' => $pageSize,
                'from' => ($page - 1) * $pageSize,
                'query' => [
                    'bool' => [
                        'must' => [
                            'match' => [
                                'title' => implode(', ', $keywords)
                            ]
                        ],
                    ]
                ]
            ]
        ];

        if (isset($params['categoryId']) && $params['categoryId'] !== '') {
            $query['body']['query']['bool']['filter'] = [
                [
                    'nested' => [
                        'path' => 'categories',
                        'query' => [
                            'bool' => [
                                'filter' => [
                                    [
                                        'term' => [
                                            'categories.id' => $params['categoryId'],
                                        ],
                                    ],
                                ]
                            ],
                        ],
                    ],
                ],
            ];
        }
        $es = Be::getEs();
        $results = $es->search($query);

        $total = 0;
        if (isset($results['hits']['total']['value'])) {
            $total = $results['hits']['total']['value'];
        }

        $rows = [];
        foreach ($results['hits']['hits'] as $x) {
            $video = (object)$x['_source'];
            try {
                $video->absolute_url = beUrl('Video.Video.detail', ['id' => $video->id]);
            } catch (\Throwable $t) {
                continue;
            }

            $rows[] = $this->formatEsVideo($video);
        }

        $return = [
            'total' => $total,
            'pageSize' => $pageSize,
            'page' => $page,
            'rows' => $rows,
        ];

        $configCache = Be::getConfig('App.Video.Cache');
        $cache->set($cacheKey, $return, $configCache->videos);

        return $return;
    }

    /**
     * 热搜视频
     *
     * @param array $params 查询参数
     * @return array
     */

    /**
     * 热搜视频
     *
     * @param int $n Top N 数量
     * @return array
     */
    public function getHotSearchTopNVideos(int $n = 10): array
    {
        $results = $this->getHotSearchVideos([
            'pageSize' => $n,
        ]);

        return $results['rows'];
    }

    /**
     * 指定分类下的热搜视频
     *
     * @param string $categoryId 分类ID
     * @param int $n Top N 数量
     * @return array
     */
    public function getCategoryHotSearchTopNVideos(string $categoryId, int $n = 10): array
    {
        $results = $this->getHotSearchVideos([
            'categoryId' => $categoryId,
            'pageSize' => $n,
        ]);

        return $results['rows'];
    }


    /**
     * 猜你喜欢
     *
     * @param array $params 查询参数
     * @return array
     */
    public function getGuessYouLikeVideos(array $params = []): array
    {
        // 分页
        if (isset($params['pageSize']) && is_numeric($params['pageSize']) && $params['pageSize'] > 0) {
            $pageSize = $params['pageSize'];
        } else {
            $pageSize = 12;
        }

        if ($pageSize > 200) {
            $pageSize = 200;
        }

        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }

        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Video.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return [
                'total' => 0,
                'pageSize' => $pageSize,
                'page' => $page,
                'rows' => [],
            ];
        }


        $my = Be::getUser();
        $es = Be::getEs();
        $cache = Be::getCache();

        $historyKey = 'App:Video:history:' . $my->id;
        $history = $cache->get($historyKey);

        $keywords = [];
        if ($history && is_array($history) && count($history) > 0) {
            $keywords = $history;
        }

        if (!$keywords) {
            $keywords = $this->getHotSearchKeywords(10);
        }

        if (!$keywords) {
            return [
                'total' => 0,
                'pageSize' => $pageSize,
                'page' => $page,
                'rows' => [],
            ];
        }

        $query = [
            'index' => $configEs->indexVideo,
            'body' => [
                'size' => $pageSize,
                'from' => ($page - 1) * $pageSize,
                'query' => [
                    'bool' => [
                        'must' => [
                            'match' => [
                                'title' => implode(', ', $keywords)
                            ]
                        ],
                    ]
                ]
            ]
        ];

        if (isset($params['excludeVideoId']) && $params['excludeVideoId'] !== '') {
            $query['body']['query']['bool']['must_not'] = [
                'term' => [
                    '_id' => $params['excludeVideoId']
                ]
            ];
        }

        if (isset($params['categoryId']) && $params['categoryId'] !== '') {
            $query['body']['query']['bool']['filter'] = [
                [
                    'nested' => [
                        'path' => 'categories',
                        'query' => [
                            'bool' => [
                                'filter' => [
                                    [
                                        'term' => [
                                            'categories.id' => $params['categoryId'],
                                        ],
                                    ],
                                ]
                            ],
                        ],
                    ],
                ],
            ];
        }

        $results = $es->search($query);

        $total = 0;
        if (isset($results['hits']['total']['value'])) {
            $total = $results['hits']['total']['value'];
        }

        $rows = [];
        foreach ($results['hits']['hits'] as $x) {
            $video = (object)$x['_source'];
            try {
                $video->absolute_url = beUrl('Video.Video.detail', ['id' => $video->id]);
            } catch (\Throwable $t) {
                continue;
            }

            $rows[] = $this->formatEsVideo($video);
        }

        $return = [
            'total' => $total,
            'pageSize' => $pageSize,
            'page' => $page,
            'rows' => $rows,
        ];

        return $return;
    }


    /**
     * 猜你喜欢 Top N
     *
     * @param int $n Top N 数量
     * @param string $excludeVideoId 要排除的视频ID
     * @return array
     */
    public function getGuessYouLikeTopNVideos(int $n = 40, string $excludeVideoId = null): array
    {
        $results = $this->getGuessYouLikeVideos([
            'pageSize' => $n,
            'excludeVideoId' => $excludeVideoId,
        ]);

        return $results['rows'];
    }

    /**
     * 指定分类下猜你喜欢
     *
     * @param string $categoryId 分类ID
     * @param int $n Top N 数量
     * @param string $excludeVideoId 要排除的视频ID
     * @return array
     */
    public function getCategoryGuessYouLikeTopNVideos(string $categoryId, int $n = 40, string $excludeVideoId = null): array
    {
        $results = $this->getGuessYouLikeVideos([
            'categoryId' => $categoryId,
            'pageSize' => $n,
            'excludeVideoId' => $excludeVideoId,
        ]);

        return $results['rows'];
    }

    /**
     * 格式化ES查询出来的视频
     *
     * @param object $video
     * @return object
     */
    private function formatEsVideo(object $video): object
    {
        $categories = [];
        if (is_array($video->categories) && count($video->categories) > 0) {
            foreach ($video->categories as $category) {
                $categories[] = (object)$category;
            }
        }
        $video->categories = $categories;

        return $video;
    }

    /**
     * 从搜索历史出提取热门搜索词
     *
     * @param int $n
     * @return array
     */
    public function getHotSearchKeywords(int $n = 6): array
    {
        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Video.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return [];
        }

        $cache = Be::getCache();
        $cacheKey = 'App:Video:HotSearchKeywords';
        $topSearchKeywords = $cache->get($cacheKey);
        if ($topSearchKeywords) {
            return $topSearchKeywords;
        }

        $es = Be::getEs();
        $query = [
            'index' => $configEs->indexVideoSearchHistory,
            'body' => [
                'size' => 0,
                'aggs' => [
                    'topN' => [
                        'terms' => [
                            'field' => 'keyword',
                            'size' => $n
                        ]
                    ]
                ]
            ]
        ];

        $result = $es->search($query);

        $hotKeywords = [];
        if (isset($result['aggregations']['topN']['buckets']) &&
            is_array($result['aggregations']['topN']['buckets']) &&
            count($result['aggregations']['topN']['buckets']) > 0
        ) {
            foreach ($result['aggregations']['topN']['buckets'] as $v) {
                $hotKeywords[] = $v['key'];
            }
        }

        $configCache = Be::getConfig('App.Video.Cache');
        $cache->set($cacheKey, $hotKeywords, $configCache->hotKeywords);

        return $hotKeywords;
    }

    /**
     * 获取视频伪静态页网址
     *
     * @param array $params
     * @return array
     * @throws ServiceException
     */
    public function getVideoUrl(array $params = []): array
    {
        $configVideo = Be::getConfig('App.Video.Video');
        $video = $this->getVideo($params['id']);

        $params1 = ['id' => $params['id']];
        unset($params['id']);
        return [$configVideo->urlPrefix . $video->url, $params1, $params];
    }


    /**
     * 获取标签
     *
     * @param int $n
     * @return array
     */
    public function getTopTags(int $n): array
    {
        $cache = Be::getCache();

        $key = 'App:Video:TopTags:' . $n;
        $tags = $cache->get($key);
        if ($tags === false) {
            try {
                $tags = $this->getTopTagsFromDb($n);
            } catch (\Throwable $t) {
                $tags = [];
            }

            $configCache = Be::getConfig('App.Video.Cache');
            $cache->set($key, $tags, $configCache->tag);
        }

        return $tags;
    }

    /**
     * 从数据库获取标签
     *
     * @param int $n
     * @return array
     */
    public function getTopTagsFromDb(int $n): array
    {
        $db = Be::getDb();
        $sql = 'SELECT tag FROM (SELECT tag, COUNT(*) AS cnt FROM `video_tag` GROUP  BY tag) t ORDER BY cnt DESC LIMIT ' . $n;
        return $db->getValues($sql);
    }


}

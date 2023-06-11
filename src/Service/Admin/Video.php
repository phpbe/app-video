<?php

namespace Be\App\Video\Service\Admin;

use Be\AdminPlugin\Form\Item\FormItemSelect;
use Be\AdminPlugin\Table\Item\TableItemImage;
use Be\App\ServiceException;
use Be\Be;
use Be\Db\DbException;
use Be\Runtime\RuntimeException;
use Be\Util\Str\Pinyin;

class Video
{

    /**
     * 编辑视频
     *
     * @param array $data 视频数据
     * @return object
     * @throws ServiceException
     * @throws DbException|RuntimeException
     */
    public function edit(array $data): object
    {
        $db = Be::getDb();

        $isNew = true;
        $videoId = null;
        if (isset($data['id']) && $data['id'] !== '') {
            $isNew = false;
            $videoId = $data['id'];
        }

        $tupleVideo = Be::getTuple('video');
        if (!$isNew) {
            try {
                $tupleVideo->load($videoId);
            } catch (\Throwable $t) {
                throw new ServiceException('视频（# ' . $videoId . '）不存在！');
            }
        }

        if (!isset($data['title']) || !is_string($data['title'])) {
            throw new ServiceException('视频标题未填写！');
        }
        $title = $data['title'];

        if (!isset($data['summary']) || !is_string($data['summary'])) {
            $data['summary'] = '';
        }

        if (!isset($data['description']) || !is_string($data['description'])) {
            $data['description'] = '';
        }

        if (!isset($data['author']) || !is_string($data['author'])) {
            $data['author'] = '';
        }

        if (!isset($data['publish_time']) || !is_string($data['publish_time']) || strtotime($data['publish_time']) === false) {
            $data['publish_time'] = date('Y-m-d H:i:s');
        }

        if (!isset($data['url_custom']) || $data['url_custom'] !== 1) {
            $data['url_custom'] = 0;
        }

        $url = null;
        if (!isset($data['url']) || !is_string($data['url'])) {
            $urlTitle = strtolower($title);
            $url = Pinyin::convert($urlTitle, '-');
            if (strlen($url) > 200) {
                $url = Pinyin::convert($urlTitle, '-', true);
                if (strlen($url) > 200) {
                    $url = Pinyin::convert($urlTitle, '', true);
                }
            }

            $data['url_custom'] = 0;
        } else {
            $url = $data['url'];
        }
        $urlUnique = $url;
        $urlIndex = 0;
        $urlExist = null;
        do {
            if ($isNew) {
                $urlExist = Be::getTable('video')
                        ->where('url', $urlUnique)
                        ->getValue('COUNT(*)') > 0;
            } else {
                $urlExist = Be::getTable('video')
                        ->where('url', $urlUnique)
                        ->where('id', '!=', $videoId)
                        ->getValue('COUNT(*)') > 0;
            }

            if ($urlExist) {
                $urlIndex++;
                $urlUnique = $url . '-' . $urlIndex;
            }
        } while ($urlExist);
        $url = $urlUnique;


        if (!isset($data['image']) || !is_string($data['image'])) {
            $data['image'] = '';
        }

        if (!isset($data['seo_title']) || !is_string($data['seo_title'])) {
            $data['seo_title'] = $title;
        }

        if (!isset($data['seo_title_custom']) || !is_numeric($data['seo_title_custom']) || $data['seo_title_custom'] !== 1) {
            $data['seo_title_custom'] = 0;
        }

        if (!isset($data['seo_description']) || !is_string($data['seo_description'])) {
            $data['seo_description'] = '';
        }
        $data['seo_description'] = strip_tags($data['seo_description']);

        if (!isset($data['seo_description_custom']) || !is_numeric($data['seo_description_custom']) || $data['seo_description_custom'] !== 1) {
            $data['seo_description_custom'] = 0;
        }

        if (!isset($data['seo_keywords']) || !is_string($data['seo_keywords'])) {
            $data['seo_keywords'] = '';
        }

        if (!isset($data['is_push_home']) || !is_numeric($data['is_push_home'])) {
            $data['is_push_home'] = 0;
        }

        if (!isset($data['is_on_top']) || !is_numeric($data['is_on_top'])) {
            $data['is_on_top'] = 0;
        }

        if (!isset($data['download_remote_image']) || !is_numeric($data['download_remote_image'])) {
            $data['download_remote_image'] = 1;
        } else {
            $data['download_remote_image'] = (int)$data['download_remote_image'];
        }

        if ($data['download_remote_image'] !== 0) {
            $data['download_remote_image'] = 1;
        }

        if (!isset($data['video_collect_id']) || !is_string($data['video_collect_id'])) {
            $data['video_collect_id'] = '';
        }

        if (!isset($data['is_enable']) || !is_numeric($data['is_enable'])) {
            $data['is_enable'] = 0;
        } else {
            $data['is_enable'] = (int)$data['is_enable'];
        }
        if (!in_array($data['is_enable'], [-1, 0, 1])) {
            $data['is_enable'] = 0;
        }

        $db->startTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $tupleVideo->image = $data['image'];
            $tupleVideo->title = $title;
            $tupleVideo->summary = $data['summary'];
            $tupleVideo->description = $data['description'];
            $tupleVideo->url = $url;
            $tupleVideo->url_custom = $data['url_custom'];
            $tupleVideo->author = $data['author'];
            $tupleVideo->publish_time = $data['publish_time'];
            $tupleVideo->seo_title = $data['seo_title'];
            $tupleVideo->seo_title_custom = $data['seo_title_custom'];
            $tupleVideo->seo_description = $data['seo_description'];
            $tupleVideo->seo_description_custom = $data['seo_description_custom'];
            $tupleVideo->seo_keywords = $data['seo_keywords'];
            $tupleVideo->is_push_home = $data['is_push_home'];
            $tupleVideo->is_on_top = $data['is_on_top'];
            $tupleVideo->download_remote_image = $data['download_remote_image'];
            if ($data['video_collect_id'] !== '') {
                $tupleVideo->video_collect_id = $data['video_collect_id'];
            }
            $tupleVideo->is_enable = $data['is_enable'];
            $tupleVideo->is_delete = 0;
            $tupleVideo->update_time = $now;
            if ($isNew) {
                $tupleVideo->create_time = $now;
                $tupleVideo->insert();
            } else {
                $tupleVideo->update();
            }

            if (isset($data['category_ids']) && is_array($data['category_ids']) && count($data['category_ids']) > 0) {
                if ($isNew) {
                    foreach ($data['category_ids'] as $category_id) {
                        $tupleVideoCategory = Be::getTuple('video_category');
                        $tupleVideoCategory->video_id = $tupleVideo->id;
                        $tupleVideoCategory->category_id = $category_id;
                        $tupleVideoCategory->insert();
                    }
                } else {
                    $existCategoryIds = Be::getTable('video_category')
                        ->where('video_id', $videoId)
                        ->getValues('category_id');

                    // 需要删除的分类
                    if (count($existCategoryIds) > 0) {
                        $removeCategoryIds = array_diff($existCategoryIds, $data['category_ids']);
                        if (count($removeCategoryIds) > 0) {
                            Be::getTable('video_category')
                                ->where('video_id', $videoId)
                                ->where('category_id', 'NOT IN', $removeCategoryIds)
                                ->delete();
                        }
                    }

                    // 新增的分类
                    $newCategoryIds = null;
                    if (count($existCategoryIds) > 0) {
                        $newCategoryIds = array_diff($data['category_ids'], $existCategoryIds);
                    } else {
                        $newCategoryIds = $data['category_ids'];
                    }
                    if (count($newCategoryIds) > 0) {
                        foreach ($newCategoryIds as $category_id) {
                            $tupleVideoCategory = Be::getTuple('video_category');
                            $tupleVideoCategory->video_id = $tupleVideo->id;
                            $tupleVideoCategory->category_id = $category_id;
                            $tupleVideoCategory->insert();
                        }
                    }
                }
            }

            // 标签
            if (isset($data['tags']) && is_array($data['tags']) && count($data['tags']) > 0) {
                if ($isNew) {
                    foreach ($data['tags'] as $tag) {
                        $tupleVideoTag = Be::getTuple('video_tag');
                        $tupleVideoTag->video_id = $tupleVideo->id;
                        $tupleVideoTag->tag = $tag;
                        $tupleVideoTag->insert();
                    }
                } else {
                    $existTags = Be::getTable('video_tag')
                        ->where('video_id', $videoId)
                        ->getValues('tag');

                    // 需要删除的标签
                    if (count($existTags) > 0) {
                        $removeTags = array_diff($existTags, $data['tags']);
                        if (count($removeTags) > 0) {
                            Be::getTable('video_tag')
                                ->where('video_id', $videoId)
                                ->where('tag', 'NOT IN', $removeTags)
                                ->delete();
                        }
                    }

                    // 新增的标签
                    $newTags = null;
                    if (count($existTags) > 0) {
                        $newTags = array_diff($data['tags'], $existTags);
                    } else {
                        $newTags = $data['tags'];
                    }
                    if (count($newTags) > 0) {
                        foreach ($newTags as $newTag) {
                            $tupleVideoTag = Be::getTuple('video_tag');
                            $tupleVideoTag->video_id = $tupleVideo->id;
                            $tupleVideoTag->tag = $newTag;
                            $tupleVideoTag->insert();
                        }
                    }
                }
            }

            $db->commit();

            Be::getService('App.System.Task')->trigger('Video.VideoSyncEsAndCache');

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException(($isNew ? '新建' : '编辑') . '视频发生异常！');
        }

        return $tupleVideo->toObject();
    }

    /**
     * 批量编辑视频分类
     *
     * @param array $videos 要编辑的视频数据
     * @return bool
     */
    public function bulkEditCategory(array $videos): bool
    {
        foreach ($videos as $video) {
            $tupleVideo = Be::getTuple('video');
            try {
                $tupleVideo->load($video['id']);
            } catch (\Throwable $t) {
                throw new ServiceException('视频（# ' . $video['id'] . '）不存在！');
            }

            if (!isset($video['category_ids']) || !is_array($video['category_ids'])) {
                $video['category_ids'] = [];
            }

            $existCategoryIds = Be::getTable('video_category')
                ->where('video_id', $video['id'])
                ->getValues('category_id');

            // 需要删除的分类
            if (count($existCategoryIds) > 0) {
                $removeCategoryIds = array_diff($existCategoryIds, $video['category_ids']);
                if (count($removeCategoryIds) > 0) {
                    Be::getTable('video_category')
                        ->where('video_id', $video['id'])
                        ->where('category_id', 'NOT IN', $removeCategoryIds)
                        ->delete();
                }
            }

            // 新增的分类
            $newCategoryIds = null;
            if (count($existCategoryIds) > 0) {
                $newCategoryIds = array_diff($video['category_ids'], $existCategoryIds);
            } else {
                $newCategoryIds = $video['category_ids'];
            }
            if (count($newCategoryIds) > 0) {
                foreach ($newCategoryIds as $category_id) {
                    $tupleVideoCategory = Be::getTuple('video_category');
                    $tupleVideoCategory->video_id = $tupleVideo->id;
                    $tupleVideoCategory->category_id = $category_id;
                    $tupleVideoCategory->insert();
                }
            }

            $tupleVideo->update_time = date('Y-m-d H:i:s');
            $tupleVideo->update();
        }

        return true;
    }


    /**
     * 获取视频
     *
     * @param string $videoId
     * @param array $with
     * @return object
     * @throws ServiceException
     * @throws DbException|RuntimeException
     */
    public function getVideo(string $videoId, array $with = []): object
    {
        $db = Be::getDb();

        $sql = 'SELECT * FROM `video` WHERE id=?';
        $video = $db->getObject($sql, [$videoId]);
        if (!$video) {
            throw new ServiceException('视频（# ' . $videoId . '）不存在！');
        }

        $video->url_custom = (int)$video->url_custom;
        $video->seo_title_custom = (int)$video->seo_title_custom;
        $video->seo_description_custom = (int)$video->seo_description_custom;
        $video->ordering = (int)$video->ordering;
        $video->is_push_home = (int)$video->is_push_home;
        $video->is_on_top = (int)$video->is_on_top;
        $video->is_enable = (int)$video->is_enable;
        $video->is_delete = (int)$video->is_delete;

        if (isset($with['categories'])) {
            $sql = 'SELECT category_id FROM video_category WHERE video_id = ?';
            $category_ids = $db->getValues($sql, [$videoId]);
            if (count($category_ids) > 0) {
                $video->category_ids = $category_ids;

                $sql = 'SELECT * FROM video_category WHERE id IN (?)';
                $categories = $db->getObjects($sql, ['\'' . implode('\',\'', $category_ids) . '\'']);
                foreach ($categories as $category) {
                    $category->ordering = (int)$category->ordering;
                }
                $video->categories = $categories;
            } else {
                $video->category_ids = [];
                $video->categories = [];
            }
        }

        if (isset($with['tags'])) {
            $sql = 'SELECT tag FROM video_tag WHERE video_id = ?';
            $video->tags = $db->getValues($sql, [$videoId]);
        }

        return $video;
    }

    /**
     * 获取菜单参数选择器
     *
     * @return array
     */
    public function getVideoMenuPicker(): array
    {
        $categoryKeyValues = Be::getService('App.Video.Admin.Category')->getCategoryKeyValues();
        return [
            'name' => 'id',
            'value' => '指定视频：{title}',
            'table' => 'video',
            'grid' => [
                'title' => '选择一篇视频',

                'filter' => [
                    ['is_enable', '=', '1'],
                    ['is_delete', '=', '0'],
                ],

                'form' => [
                    'items' => [
                        [
                            'name' => 'category_id',
                            'label' => '分类',
                            'driver' => FormItemSelect::class,
                            'keyValues' => $categoryKeyValues,
                            'buildSql' => function ($dbName, $formData) {
                                if (isset($formData['category_id']) && $formData['category_id']) {
                                    $videoIds = Be::getTable('video_category', $dbName)
                                        ->where('category_id', $formData['category_id'])
                                        ->getValues('video_id');
                                    if (count($videoIds) > 0) {
                                        return ['id', 'IN', $videoIds];
                                    } else {
                                        return ['id', '=', ''];
                                    }
                                }
                                return '';
                            },
                        ],
                        [
                            'name' => 'title',
                            'label' => '标题',
                        ],
                    ],
                ],

                'table' => [

                    // 未指定时取表的所有字段
                    'items' => [
                        [
                            'name' => 'image',
                            'label' => '封面图片',
                            'width' => '90',
                            'driver' => TableItemImage::class,
                            'ui' => [
                                'style' => 'max-width: 60px; max-height: 60px'
                            ],
                            'value' => function ($row) {
                                if ($row['image'] === '') {
                                    return Be::getProperty('App.Video')->getWwwUrl() . '/video/images/no-image.jpg';
                                }
                                return $row['image'];
                            },
                        ],
                        [
                            'name' => 'title',
                            'label' => '标题',
                            'align' => 'left',
                        ],
                        [
                            'name' => 'create_time',
                            'label' => '创建时间',
                            'width' => '180',
                            'sortable' => true,
                        ],
                        [
                            'name' => 'update_time',
                            'label' => '更新时间',
                            'width' => '180',
                            'sortable' => true,
                        ],
                    ],
                ],
            ]
        ];
    }

}

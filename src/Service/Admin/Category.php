<?php

namespace Be\App\Video\Service\Admin;

use Be\App\ServiceException;
use Be\Be;
use Be\Util\Str\Pinyin;

class Category
{

    /**
     * 获取分类列表
     *
     * @return array
     * @throws \Be\Db\DbException
     * @throws \Be\Runtime\RuntimeException
     */
    public function getCategories(): array
    {
        $sql = 'SELECT * FROM video_category WHERE is_delete = 0 ORDER BY ordering ASC';
        $categories = Be::getDb()->getObjects($sql);
        return $categories;
    }

    /**
     * 获取分类
     *
     * @param string $categoryId
     * @return \stdClass
     * @throws ServiceException
     * @throws \Be\Db\DbException
     * @throws \Be\Runtime\RuntimeException
     */
    public function getCategory(string $categoryId): \stdClass
    {
        $sql = 'SELECT * FROM video_category WHERE id=? AND is_delete = 0';
        $category = Be::getDb()->getObject($sql, [$categoryId]);
        if (!$category) {
            throw new ServiceException('分类（# ' . $categoryId . '）不存在！');
        }

        $category->url_custom = (int)$category->url_custom;
        $category->seo_title_custom = (int)$category->seo_title_custom;
        $category->seo_description_custom = (int)$category->seo_description_custom;
        $category->ordering = (int)$category->ordering;

        return $category;
    }

    /**
     * 获取分类键值对
     *
     * @return array
     * @throws \Be\Db\DbException
     * @throws \Be\Runtime\RuntimeException
     */
    public function getCategoryKeyValues(): array
    {
        $sql = 'SELECT id, `name` FROM video_category WHERE is_delete = 0 ORDER BY ordering ASC';
        return Be::getDb()->getKeyValues($sql);
    }

    /**
     * 编辑分类
     *
     * @param array $data 分类数据
     * @return object
     * @throws \Throwable
     */
    public function edit(array $data): object
    {
        $db = Be::getDb();

        $isNew = true;
        $categoryId = null;
        if (isset($data['id']) && is_string($data['id']) && $data['id'] !== '') {
            $isNew = false;
            $categoryId = $data['id'];
        }

        $tupleCategory = Be::getTuple('video_category');
        if (!$isNew) {
            try {
                $tupleCategory->load($categoryId);
            } catch (\Throwable $t) {
                throw new ServiceException('分类（# ' . $categoryId . '）不存在！');
            }

            if ($tupleCategory->is_delete === 1) {
                throw new ServiceException('分类（# ' . $categoryId . '）不存在！');
            }
        }

        if (!isset($data['name']) || !is_string($data['name'])) {
            throw new ServiceException('分类名称未填写！');
        }
        $name = $data['name'];

        if (!isset($data['description']) || !is_string($data['description'])) {
            $data['description'] = '';
        }

        if (!isset($data['url_custom']) || $data['url_custom'] !== 1) {
            $data['url_custom'] = 0;
        }

        $url = null;
        if (!isset($data['url']) || !is_string($data['url'])) {
            $urlName = strtolower($name);
            $url = Pinyin::convert($urlName, '-');
            if (strlen($url) > 200) {
                $url = Pinyin::convert($urlName, '-', true);
                if (strlen($url) > 200) {
                    $url = Pinyin::convert($urlName, '', true);
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
                $urlExist = Be::getTable('video_category')
                        ->where('url', $urlUnique)
                        ->getValue('COUNT(*)') > 0;
            } else {
                $urlExist = Be::getTable('video_category')
                        ->where('url', $urlUnique)
                        ->where('id', '!=', $categoryId)
                        ->getValue('COUNT(*)') > 0;
            }

            if ($urlExist) {
                $urlIndex++;
                $urlUnique = $url . '-' . $urlIndex;
            }
        } while ($urlExist);
        $url = $urlUnique;

        if (!isset($data['seo_title']) || !is_string($data['seo_title'])) {
            $data['seo_title'] = $name;
        }

        if (!isset($data['seo_title_custom']) || $data['seo_title_custom'] !== 1) {
            $data['seo_title_custom'] = 0;
        }

        if (!isset($data['seo_description']) || !is_string($data['seo_description'])) {
            $data['seo_description'] = $data['description'];
        }
        $data['seo_description'] = strip_tags($data['seo_description']);

        if (!isset($data['seo_description_custom']) || $data['seo_description_custom'] !== 1) {
            $data['seo_description_custom'] = 0;
        }

        if (!isset($data['seo_keywords']) || !is_string($data['seo_keywords'])) {
            $data['seo_keywords'] = '';
        }

        if (!isset($data['ordering']) || !is_numeric($data['ordering'])) {
            $data['ordering'] = 0;
        }

        $db->startTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $tupleCategory->name = $name;
            $tupleCategory->description = $data['description'];
            $tupleCategory->url = $url;
            $tupleCategory->url_custom = $data['url_custom'];
            $tupleCategory->seo_title = $data['seo_title'];
            $tupleCategory->seo_title_custom = $data['seo_title_custom'];
            $tupleCategory->seo_description = $data['seo_description'];
            $tupleCategory->seo_description_custom = $data['seo_description_custom'];
            $tupleCategory->seo_keywords = $data['seo_keywords'];
            $tupleCategory->ordering = $data['ordering'];
            $tupleCategory->update_time = $now;
            if ($isNew) {
                $tupleCategory->is_delete = 0;
                $tupleCategory->create_time = $now;
                $tupleCategory->insert();
            } else {
                $tupleCategory->update();
            }

            $videoIds = Be::getTable('video_category')
                ->where('category_id', '=',  $tupleCategory->id)
                ->getValues('video_id');
            if (count($videoIds) > 0) {
                Be::getTable('video')
                    ->where('id', 'IN', $videoIds)
                    ->update(['update_time' =>  $now]);
            }

            $db->commit();

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException(($isNew ? '新建' : '编辑') . '分类发生异常！');
        }

        Be::getService('App.System.Task')->trigger('Video.CategorySyncCache');
        Be::getService('App.System.Task')->trigger('Video.VideoSyncEsAndCache');

        return $tupleCategory->toObject();
    }

    /**
     * 删除分类
     *
     * @param array $categoryIds
     * @return void
     * @throws ServiceException
     * @throws \Be\Db\DbException
     * @throws \Be\Runtime\RuntimeException
     */
    public function delete(array $categoryIds)
    {
        if (count($categoryIds) === 0) return;

        $db = Be::getDb();
        $db->startTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            foreach ($categoryIds as $categoryId) {
                $tupleCategory = Be::getTuple('video_category');
                try {
                    $tupleCategory->loadBy([
                        'id' => $categoryId,
                        'is_delete' => 0
                    ]);
                } catch (\Throwable $t) {
                    throw new ServiceException('分类（# ' . $categoryId . '）不存在！');
                }

                $videoIds = Be::getTable('video_category')
                    ->where('category_id', '=', $categoryId)
                    ->getValues('video_id');
                if (count($videoIds) > 0) {
                    Be::getTable('video')
                        ->where('id', 'IN', $videoIds)
                        ->update(['update_time' =>  $now]);

                    Be::getTable('video_categoryd')
                        ->where('category_id', '=', $categoryId)
                        ->delete();
                }

                $tupleCategory->url = $categoryId;
                $tupleCategory->is_delete = 1;
                $tupleCategory->update_time = $now;
                $tupleCategory->update();
            }

            $db->commit();

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException('删除分类发生异常！');
        }

        Be::getService('App.System.Task')->trigger('Video.CategorySyncCache');
        Be::getService('App.System.Task')->trigger('Video.VideoSyncEsAndCache');
    }

    /**
     * 在分类下添加视频
     *
     * @param string $categoryId 分类ID
     * @param array $videoIds 视频ID列表
     * @return bool
     */
    public function addVideo(string $categoryId, array $videoIds): bool
    {
        try {
            Be::getTuple('video_category')
                ->loadBy([
                    'id' => $categoryId,
                    'is_delete' => 0
                ]);
        } catch (\Throwable $t) {
            throw new ServiceException('分类（# ' . $categoryId . '）不存在！');
        }

        $existVideoIds = Be::getTable('video_category')
            ->where('category_id', $categoryId)
            ->getValues('video_id');
        if (is_array($existVideoIds) && count($existVideoIds) > 0) {
            $videoIds = array_diff($videoIds, $existVideoIds);
        }

        if (count($videoIds) > 0) {
            $existVideoIds = Be::getTable('video')
                ->where('id', 'IN', $videoIds)
                ->getValues('id');

            if ($existVideoIds === false) {
                $existVideoIds = [];
            }

            if (count($existVideoIds) != count($videoIds)) {
                $diffVideoIds = array_diff($videoIds, $existVideoIds);
                throw new ServiceException('视频（#' . implode(', #', $diffVideoIds) . '）不存在！');
            }

            $db = Be::getDb();
            $db->startTransaction();
            try {
                foreach ($videoIds as $videoId) {
                    $tupleVideoCategory = Be::getTuple('video_category');
                    $tupleVideoCategory->video_id = $videoId;
                    $tupleVideoCategory->category_id = $categoryId;
                    $tupleVideoCategory->insert();
                }

                $now = date('Y-m-d H:i:s');
                Be::getTable('video')
                    ->where('id', 'IN', $videoIds)
                    ->update(['update_time' => $now]);

                $db->commit();
            } catch (\Throwable $t) {
                $db->rollback();
                Be::getLog()->error($t);

                throw new ServiceException('在分类下添加视频时发生异常！');
            }

            Be::getService('App.System.Task')->trigger('Video.VideoSyncEsAndCache');
        }
    }

    /**
     * 将视频从分类中删除
     *
     * @param string $categoryId 分类ID
     * @param array $videoIds 视频ID列表
     */
    public function deleteVideo(string $categoryId, array $videoIds)
    {
        try {
            Be::getTuple('video_category')
                ->loadBy([
                    'id' => $categoryId,
                    'is_delete' => 0
                ]);
        } catch (\Throwable $t) {
            throw new ServiceException('分类（# ' . $categoryId . '）不存在！');
        }

        $db = Be::getDb();
        $db->startTransaction();
        try {

            Be::getTable('video_category')
                ->where('category_id', $categoryId)
                ->where('video_id', 'IN', $videoIds)
                ->delete();

            $now = date('Y-m-d H:i:s');
            Be::getTable('video')
                ->where('id', 'IN', $videoIds)
                ->update(['update_time' => $now]);

            $db->commit();
        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException('从分类中的删除视频时发生异常！');
        }

        Be::getService('App.System.Task')->trigger('Video.VideoSyncEsAndCache');
    }

    /**
     * 获取菜单参数选择器
     *
     * @return array
     */
    public function getCategoryMenuPicker():array
    {
        return [
            'name' => 'id',
            'value' => '分类：{name}',
            'table' => 'video_category',
            'grid' => [
                'title' => '选择一个分类',

                'filter' => [
                    ['is_delete', '=', '0'],
                ],

                'form' => [
                    'items' => [
                        [
                            'name' => 'name',
                            'label' => '名称',
                        ],
                    ],
                ],

                'table' => [

                    // 未指定时取表的所有字段
                    'items' => [
                        [
                            'name' => 'name',
                            'label' => '名称',
                            'align' => 'left'
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

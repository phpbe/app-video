<?php

namespace Be\App\Video\Controller\Admin;


use Be\AdminPlugin\Detail\Item\DetailItemHtml;
use Be\AdminPlugin\Detail\Item\DetailItemToggleIcon;
use Be\AdminPlugin\Table\Item\TableItemImage;
use Be\AdminPlugin\Table\Item\TableItemLink;
use Be\AdminPlugin\Table\Item\TableItemSelection;
use Be\App\ControllerException;
use Be\App\System\Controller\Admin\Auth;
use Be\Be;

/**
 * @BeMenuGroup("视频")
 * @BePermissionGroup("视频")
 */
class Category extends Auth
{

    /**
     * 视频分类
     *
     * @BeMenu("分类", icon="el-icon-folder", ordering="1.3")
     * @BePermission("分类", ordering="1.3")
     */
    public function categories()
    {
        Be::getAdminPlugin('Curd')->setting([

            'label' => '视频分类',
            'table' => 'video_category',

            'grid' => [
                'title' => '视频分类',

                'filter' => [
                    ['is_delete', '=', '0'],
                ],

                'orderBy' => 'ordering',
                'orderByDir' => 'ASC',

                'form' => [
                    'items' => [
                        [
                            'name' => 'name',
                            'label' => '名称',
                        ],
                    ],
                ],

                'titleRightToolbar' => [
                    'items' => [
                        [
                            'label' => '新建视频分类',
                            'action' => 'create',
                            'target' => 'self', // 'ajax - ajax请求 / dialog - 对话框窗口 / drawer - 抽屉 / self - 当前页面 / blank - 新页面'
                            'ui' => [
                                'icon' => 'el-icon-plus',
                                'type' => 'primary',
                            ]
                        ],
                    ]
                ],

                'tableToolbar' => [
                    'items' => [
                        [
                            'label' => '批量删除',
                            'action' => 'delete',
                            'target' => 'ajax',
                            'confirm' => '确认要删除吗？',
                            'ui' => [
                                'icon' => 'el-icon-delete',
                                'type' => 'danger'
                            ]
                        ],
                    ]
                ],


                'table' => [

                    // 未指定时取表的所有字段
                    'items' => [
                        [
                            'driver' => TableItemSelection::class,
                            'width' => '50',
                        ],
                        [
                            'name' => 'name',
                            'label' => '名称',
                            'driver' => TableItemLink::class,
                            'align' => 'left',
                            'task' => 'detail',
                            'drawer' => [
                                'width' => '80%'
                            ],
                        ],
                        [
                            'name' => 'video_count',
                            'label' => '视频数量',
                            'align' => 'center',
                            'width' => '120',
                            'driver' => TableItemLink::class,
                            'value' => function ($row) {
                                $sql = 'SELECT COUNT(*) FROM video_category WHERE category_id = ?';
                                $count = Be::getDb()->getValue($sql, [$row['id']]);
                                return $count;
                            },
                            'action' => 'goVideos',
                            'target' => 'self',
                        ],
                        [
                            'name' => 'ordering',
                            'label' => '排序',
                            'width' => '120',
                            'sortable' => true,
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

                    'exclude' => ['description'],

                    'operation' => [
                        'label' => '操作',
                        'width' => '180',
                        'items' => [
                            [
                                'label' => '',
                                'tooltip' => '预览',
                                'action' => 'preview',
                                'target' => '_blank',
                                'ui' => [
                                    'type' => 'success',
                                    ':underline' => 'false',
                                    'style' => 'font-size: 20px;',
                                ],
                                'icon' => 'el-icon-view',
                            ],
                            [
                                'label' => '',
                                'tooltip' => '编辑',
                                'action' => 'edit',
                                'target' => 'self',
                                'ui' => [
                                    ':underline' => 'false',
                                    'style' => 'font-size: 20px;',
                                ],
                                'icon' => 'el-icon-edit',
                            ],
                            [
                                'label' => '',
                                'tooltip' => '删除',
                                'action' => 'delete',
                                'confirm' => '确认要删除么？',
                                'target' => 'ajax',
                                'ui' => [
                                    'type' => 'danger',
                                    ':underline' => 'false',
                                    'style' => 'font-size: 20px;',
                                ],
                                'icon' => 'el-icon-delete',
                            ],
                        ]
                    ],
                ],
            ],

            'detail' => [
                'title' => '视频分类详情',
                'theme' => 'Blank',
                'form' => [
                    'items' => [
                        [
                            'name' => 'id',
                            'label' => 'ID',
                        ],
                        [
                            'name' => 'name',
                            'label' => '名称',
                        ],
                        [
                            'name' => 'description',
                            'label' => '描述',
                            'driver' => DetailItemHtml::class,
                        ],
                        [
                            'name' => 'url',
                            'label' => '网址',
                            'value' => function ($row) {
                                // return Be::getRequest()->getRootUrl() . '/video/category/' . $row['url'];
                                return beUrl('Video.Category.videos', ['id' => $row['id']]);
                            }
                        ],
                        [
                            'name' => 'seo',
                            'label' => 'SEO 独立编辑',
                            'driver' => DetailItemToggleIcon::class,
                        ],
                        [
                            'name' => 'seo_title',
                            'label' => 'SEO 标题',
                        ],
                        [
                            'name' => 'seo_description',
                            'label' => 'SEO 描述',
                        ],
                        [
                            'name' => 'seo_keywords',
                            'label' => 'SEO 关键词',
                        ],
                        [
                            'name' => 'ordering',
                            'label' => '排序',
                        ],
                        [
                            'name' => 'create_time',
                            'label' => '创建时间',
                        ],
                        [
                            'name' => 'update_time',
                            'label' => '更新时间',
                        ],
                    ]
                ],
            ],
        ])->execute();
    }

    /**
     * 新建视频分类
     *
     * @BePermission("新建", ordering="1.31")
     */
    public function create()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        if ($request->isAjax()) {
            try {
                $category = Be::getService('App.Video.Admin.Category')->edit($request->json('formData'));
                $response->set('success', true);
                $response->set('message', '新建视频分类成功！');
                $response->set('category', $category);
                $response->json();
            } catch (\Throwable $t) {
                $response->set('success', false);
                $response->set('message', $t->getMessage());
                $response->json();
            }
        } else {
            $response->set('category', false);

            $configCategory = Be::getConfig('App.Video.Category');
            $response->set('configCategory', $configCategory);

            $response->set('title', '新建视频分类');
            $response->display('App.Video.Admin.Category.edit');
        }
    }

    /**
     * 编辑
     *
     * @BePermission("编辑", ordering="1.32")
     */
    public function edit()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        if ($request->isAjax()) {
            try {
                $category = Be::getService('App.Video.Admin.Category')->edit($request->json('formData'));
                $response->set('success', true);
                $response->set('message', '编辑视频分类成功！');
                $response->set('category', $category);
                $response->json();
            } catch (\Throwable $t) {
                $response->set('success', false);
                $response->set('message', $t->getMessage());
                $response->json();
            }
        } elseif ($request->isPost()) {
            $postData = $request->post('data', '', '');
            if ($postData) {
                $postData = json_decode($postData, true);
                if (isset($postData['row']['id']) && $postData['row']['id']) {
                    $response->redirect(beAdminUrl('Video.Category.edit', ['id' => $postData['row']['id']]));
                }
            }
        } else {
            $pageId = $request->get('id', '');
            $category = Be::getService('App.Video.Admin.Category')->getCategory($pageId);
            $response->set('category', $category);

            $configCategory = Be::getConfig('App.Video.Category');
            $response->set('configCategory', $configCategory);

            $response->set('title', '编辑视频分类');
            $response->display('App.Video.Admin.Category.edit');
        }
    }

    /**
     * 删除
     *
     * @BePermission("删除", ordering="1.33")
     */
    public function delete()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $postData = $request->json();

            $categoryIds = [];
            if (isset($postData['selectedRows'])) {
                foreach ($postData['selectedRows'] as $row) {
                    $categoryIds[] = $row['id'];
                }
            } elseif (isset($postData['row'])) {
                $categoryIds[] = $postData['row']['id'];
            }

            if (count($categoryIds) > 0) {
                Be::getService('App.Video.Admin.Category')->delete($categoryIds);
            }

            $response->set('success', true);
            $response->set('message', '删除成功！');
            $response->json();
        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }

    /**
     * 预览
     *
     * @BePermission("*")
     */
    public function preview() {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $postData = $request->post('data', '', '');
        if ($postData) {
            $postData = json_decode($postData, true);
            if (isset($postData['row']['id']) && $postData['row']['id']) {
                $response->redirect(beUrl('Video.Category.videos', ['id' => $postData['row']['id']]));
            }
        }
    }

    /**
     * 指定视频分类下的视频分类视频管理
     *
     * @BePermission("视频分类下视频管理", ordering="1.34")
     */
    public function goVideos()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $postData = $request->post('data', '', '');
        if ($postData) {
            $postData = json_decode($postData, true);
            if (isset($postData['row']['id']) && $postData['row']['id']) {
                $response->redirect(beAdminUrl('Video.Category.videos', ['id' => $postData['row']['id']]));
            }
        }
    }

    /**
     * 指定视频分类下的视频分类视频管理
     *
     * @BePermission("视频分类下视频管理")
     */
    public function videos()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $categoryId = $request->get('id', '');
        $category = Be::getService('App.Video.Admin.Category')->getCategory($categoryId);

        $filter = [
            ['is_delete', '=', '0'],
        ];

        $videoIds = Be::getTable('video_category')
            ->where('category_id', $categoryId)
            ->getValues('video_id');
        if (count($videoIds) > 0) {
            $filter[] = [
                'id', 'IN', $videoIds
            ];
        } else {
            $filter[] = [
                'id', '=', ''
            ];
        }

        Be::getAdminPlugin('Curd')->setting([
            'label' => '视频分类 ' . $category->name . ' 下的视频',
            'table' => 'video',
            'grid' => [
                'title' => '视频分类 ' . $category->name . ' 下的视频管理',

                'filter' => $filter,

                'titleRightToolbar' => [
                    'items' => [
                        [
                            'label' => '返回',
                            'url' => beAdminUrl('Video.Category.categories'),
                            'target' => 'self',
                            'ui' => [
                                'icon' => 'el-icon-back'
                            ]
                        ],
                        [
                            'label' => '添加视频',
                            'url' => beAdminUrl('Video.Category.addVideo', ['id' => $categoryId]),
                            'target' => 'drawer', // 'ajax - ajax请求 / dialog - 对话框窗口 / drawer - 抽屉 / self - 当前页面 / blank - 新页面'
                            'drawer' => [
                                'width' => '60%',
                            ],
                            'ui' => [
                                'icon' => 'el-icon-plus',
                                'type' => 'primary',
                            ]
                        ],
                    ]
                ],

                'tableToolbar' => [
                    'items' => [
                        [
                            'label' => '批量从此视频分类中移除',
                            'task' => 'fieldEdit',
                            'target' => 'ajax',
                            'confirm' => '确认要从此视频分类中移除吗？',
                            'postData' => [
                                'field' => 'is_delete',
                                'value' => '1',
                            ],
                            'ui' => [
                                'icon' => 'el-icon-delete',
                                'type' => 'danger'
                            ]
                        ],
                    ]
                ],

                'table' => [

                    'items' => [
                        [
                            'driver' => TableItemSelection::class,
                            'width' => '50',
                        ],
                        [
                            'name' => 'image',
                            'label' => '封面图片',
                            'width' => '90',
                            'driver' => TableItemImage::class,
                            'ui' => [
                                'style' => 'max-width: 60px; max-height: 60px'
                            ],
                            'value' => function($row) {
                                if ($row['image'] === '') {
                                    return Be::getProperty('App.Video')->getWwwUrl(). '/video/images//no-image.jpg';
                                }
                                return $row['image'];
                            },
                        ],
                        [
                            'name' => 'title',
                            'label' => '视频标题',
                            'driver' => TableItemLink::class,
                            'align' => 'left',
                            'url' => beAdminUrl('Video.Video.videos', ['task'=>'detail']),
                            'drawer' => [
                                'width' => '80%'
                            ],
                        ],
                    ],

                    'operation' => [
                        'label' => '操作',
                        'width' => '150',
                        'items' => [
                            [
                                'label' => '',
                                'tooltip' => '预览',
                                'url' => beAdminUrl('Video.Video.preview'),
                                'target' => '_blank',
                                'ui' => [
                                    'type' => 'success',
                                    ':underline' => 'false',
                                    'style' => 'font-size: 20px;',
                                ],
                                'icon' => 'el-icon-view',
                            ],
                            [
                                'label' => '',
                                'tooltip' => '从此视频分类中移除',
                                'url' => beAdminUrl('Video.Category.deleteVideo', ['id' => $categoryId]),
                                'confirm' => '确认要从此视频分类中移除么？',
                                'target' => 'ajax',
                                'ui' => [
                                    'type' => 'danger',
                                    ':underline' => 'false',
                                    'style' => 'font-size: 20px;',
                                ],
                                'icon' => 'el-icon-delete',
                            ],
                        ]
                    ],
                ],
            ],
        ])->execute();
    }

    /**
     * 指定视频分类下的视频 - 添加
     *
     * @BePermission("视频分类下视频管理")
     */
    public function addVideo()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $categoryId = $request->get('id', '');
        $category = Be::getService('App.Video.Admin.Category')->getCategory($categoryId);

        $filter = [
            ['is_delete', '=', '0'],
        ];

        $videoIds = Be::getTable('video_category')
            ->where('category_id', $categoryId)
            ->getValues('video_id');
        if (count($videoIds) > 0) {
            $filter[] = [
                'id', 'NOT IN', $videoIds
            ];
        }

        Be::getAdminPlugin('Curd')->setting([
            'label' => '向视频分类 ' . $category->name . ' 添加视频',
            'table' => 'video',
            'opLog' => false,
            'grid' => [
                'title' => '向视频分类 ' . $category->name . ' 添加视频',
                'theme' => 'Blank',

                'filter' => $filter,

                'form' => [
                    'items' => [
                        [
                            'name' => 'title',
                            'label' => '视频标题',
                        ],
                    ],
                ],

                'tableToolbar' => [
                    'items' => [
                        [
                            'label' => '添加到视频分类 ' . $category->name . ' 中',
                            'url' => beAdminUrl('Video.Category.addVideoSave', ['id' => $categoryId]),
                            'target' => 'ajax',
                            'ui' => [
                                'icon' => 'el-icon-plus',
                                'type' => 'primary'
                            ]
                        ],
                    ]
                ],

                'table' => [

                    'items' => [
                        [
                            'driver' => TableItemSelection::class,
                            'width' => '50',
                        ],
                        [
                            'name' => 'image',
                            'label' => '封面图片',
                            'width' => '90',
                            'driver' => TableItemImage::class,
                            'ui' => [
                                'style' => 'max-width: 60px; max-height: 60px'
                            ],
                            'value' => function($row) {
                                if ($row['image'] === '') {
                                    return Be::getProperty('App.Video')->getWwwUrl(). '/video/images//no-image.jpg';
                                }
                                return $row['image'];
                            },
                        ],
                        [
                            'name' => 'title',
                            'label' => '视频标题',
                            'align' => 'left',
                        ],
                    ],
                ],
            ],
        ])->execute();
    }

    /**
     * 指定视频分类下的视频 - 添加
     *
     * @BePermission("视频分类下视频管理")
     */
    public function addVideoSave()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $categoryId = $request->get('id', '');
            $selectedRows = $request->json('selectedRows');
            if (!is_array($selectedRows) || count($selectedRows) == 0) {
                throw new ControllerException('请选择视频！');
            }

            $videoIds = [];
            foreach ($selectedRows as $selectedRow) {
                $videoIds[] = $selectedRow['id'];
            }

            Be::getService('App.Video.Admin.Category')->addVideo($categoryId, $videoIds);
            $response->set('success', true);
            $response->set('message', '添加分类下视频成功！');
            $response->set('callback', 'parent.closeDrawerAndReload();');
            $response->json();
        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }

    /**
     * 指定视频分类下的视频 - 删除
     *
     * @BePermission("视频分类下视频管理")
     */
    public function deleteVideo()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();
        try {
            $categoryId = $request->get('id', '');
            $videoIds = [];
            $postData = $request->json();
            if (isset($postData['selectedRows'])) {
                if (is_array($postData['selectedRows']) && count($postData['selectedRows']) > 0) {
                    foreach ($postData['selectedRows'] as $selectedRow) {
                        $videoIds[] = $selectedRow['id'];
                    }
                }
            } elseif (isset($postData['row'])) {
                $videoIds[] = $postData['row']['id'];
            }

            if (count($videoIds) == 0) {
                throw new ControllerException('请选择视频！');
            }

            Be::getService('App.Video.Admin.Category')->deleteVideo($categoryId, $videoIds);
            $response->set('success', true);
            $response->set('message', '删除分类下视频成功！');
            $response->json();
        } catch (\Throwable $t) {
            $response->set('success', false);
            $response->set('message', $t->getMessage());
            $response->json();
        }
    }

}

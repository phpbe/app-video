<?php

namespace Be\App\Video\Controller\Admin;

use Be\AdminPlugin\Detail\Item\DetailItemHtml;
use Be\AdminPlugin\Detail\Item\DetailItemImage;
use Be\AdminPlugin\Detail\Item\DetailItemToggleIcon;
use Be\AdminPlugin\Form\Item\FormItemSelect;
use Be\AdminPlugin\Table\Item\TableItemImage;
use Be\AdminPlugin\Table\Item\TableItemLink;
use Be\AdminPlugin\Table\Item\TableItemSelection;
use Be\AdminPlugin\Table\Item\TableItemSwitch;
use Be\AdminPlugin\Toolbar\Item\ToolbarItemButtonDropDown;
use Be\AdminPlugin\Toolbar\Item\ToolbarItemDropDown;
use Be\App\System\Controller\Admin\Auth;
use Be\Be;
use Be\Request;
use Be\Response;

/**
 * @BeMenuGroup("视频", icon="el-icon-tickets", ordering="1")
 * @BePermissionGroup("视频", icon="el-icon-tickets", ordering="1")
 */
class Video extends Auth
{

    /**
     * 视频
     *
     * @BeMenu("视频列表", icon="el-icon-document-copy", ordering="1.1")
     * @BePermission("视频列表", ordering="1.1")
     */
    public function videos()
    {
        $categoryKeyValues = Be::getService('App.Video.Admin.Category')->getCategoryKeyValues();
        Be::getAdminPlugin('Curd')->setting([

            'label' => '视频',
            'table' => 'video',

            'grid' => [
                'title' => '视频列表',

                'filter' => [
                    ['is_delete', '=', '0'],
                    ['is_enable', '!=', '-1'],
                ],

                'orderBy' => 'create_time',
                'orderByDir' => 'DESC',

                'tab' => [
                    'name' => 'is_enable',
                    'value' => Be::getRequest()->request('is_enable', '-100'),
                    'nullValue' => '-100',
                    'counter' => true,
                    'keyValues' => [
                        '-100' => '全部',
                        '1' => '已发布',
                        '0' => '未发布',
                    ],
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
                            'name' => 'is_push_home',
                            'label' => '是否推送到首页',
                            'driver' => FormItemSelect::class,
                            'keyValues' => [
                                '1' => '是',
                                '0' => '否',
                            ],
                        ],
                        [
                            'name' => 'is_on_tome',
                            'label' => '是否置顶',
                            'driver' => FormItemSelect::class,
                            'keyValues' => [
                                '1' => '是',
                                '0' => '否',
                            ],
                        ],
                        [
                            'name' => 'title',
                            'label' => '标题',
                        ],
                    ],
                ],

                'titleToolbar' => [
                    'items' => [
                        [
                            'label' => '导出',
                            'driver' => ToolbarItemDropDown::class,
                            'ui' => [
                                'icon' => 'el-icon-download',
                            ],
                            'menus' => [
                                [
                                    'label' => 'CSV',
                                    'task' => 'export',
                                    'postData' => [
                                        'driver' => 'csv',
                                    ],
                                    'target' => 'blank',
                                ],
                                [
                                    'label' => 'EXCEL',
                                    'task' => 'export',
                                    'postData' => [
                                        'driver' => 'excel',
                                    ],
                                    'target' => 'blank',
                                ],
                            ]
                        ],
                    ]
                ],

                'titleRightToolbar' => [
                    'items' => [
                        [
                            'label' => '新建视频',
                            'action' => 'create',
                            'target' => 'self', // 'ajax - ajax请求 / dialog - 对话框窗口 / drawer - 抽屉 / self - 当前视频 / blank - 新视频'
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
                            'label' => '批量发布',
                            'task' => 'fieldEdit',
                            'postData' => [
                                'field' => 'is_enable',
                                'value' => '1',
                            ],
                            'target' => 'ajax',
                            'confirm' => '确认要发布吗？',
                            'ui' => [
                                'icon' => 'el-icon-check',
                                'type' => 'success',
                            ]
                        ],
                        [
                            'label' => '批量取消发布',
                            'task' => 'fieldEdit',
                            'postData' => [
                                'field' => 'is_enable',
                                'value' => '0',
                            ],
                            'target' => 'ajax',
                            'confirm' => '确认要取消发布吗？',
                            'ui' => [
                                'icon' => 'el-icon-close',
                                'type' => 'warning',
                            ]
                        ],
                        [
                            'label' => '批量删除',
                            'task' => 'fieldEdit',
                            'target' => 'ajax',
                            'confirm' => '确认要删除吗？',
                            'postData' => [
                                'field' => 'is_delete',
                                'value' => '1',
                            ],
                            'ui' => [
                                'icon' => 'el-icon-delete',
                                'type' => 'danger'
                            ]
                        ],
                        [
                            'label' => '批量编辑',
                            'driver' => ToolbarItemButtonDropDown::class,
                            'ui' => [
                                'class' => 'be-ml-50',
                                'icon' => 'el-icon-edit',
                                'type' => 'primary'
                            ],
                            'menus' => [
                                [
                                    'label' => '分类',
                                    'url' => beAdminUrl('Video.Video.bulkEditCategory'),
                                    'target' => 'drawer',
                                    'drawer' => [
                                        'title' => '批量编辑视频分类',
                                        'width' => '80%'
                                    ],
                                ],
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
                            'name' => 'image',
                            'label' => '封面图片',
                            'width' => '90',
                            'driver' => TableItemImage::class,
                            'ui' => [
                                'style' => 'max-width: 60px; max-height: 60px'
                            ],
                            'value' => function ($row) {
                                if ($row['image'] === '') {
                                    return Be::getProperty('App.Video')->getWwwUrl() . '/video/images/no-image-s.jpg';
                                }
                                return $row['image'];
                            },
                        ],
                        [
                            'name' => 'title',
                            'label' => '标题',
                            'driver' => TableItemLink::class,
                            'align' => 'left',
                            'task' => 'detail',
                            'drawer' => [
                                'width' => '80%'
                            ],
                        ],
                        [
                            'name' => 'is_push_home',
                            'label' => '推首页',
                            'driver' => TableItemSwitch::class,
                            'target' => 'ajax',
                            'task' => 'fieldEdit',
                            'width' => '80',
                            'exportValue' => function ($row) {
                                return $row['is_push_home'] ? '是' : '否';
                            },
                        ],
                        [
                            'name' => 'is_on_top',
                            'label' => '置顶',
                            'driver' => TableItemSwitch::class,
                            'target' => 'ajax',
                            'task' => 'fieldEdit',
                            'width' => '80',
                            'exportValue' => function ($row) {
                                return $row['is_on_top'] ? '是' : '否';
                            },
                        ],
                        [
                            'name' => 'is_enable',
                            'label' => '发布',
                            'driver' => TableItemSwitch::class,
                            'target' => 'ajax',
                            'task' => 'fieldEdit',
                            'width' => '80',
                            'exportValue' => function ($row) {
                                return $row['is_enable'] ? '是' : '否';
                            },
                        ],
                        [
                            'name' => 'create_time',
                            'label' => '创建时间',
                            'width' => '180',
                            'sortable' => true,
                        ],
                    ],

                    'exclude' => ['summary', 'description'],

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
                                'task' => 'fieldEdit',
                                'confirm' => '确认要删除么？',
                                'target' => 'ajax',
                                'postData' => [
                                    'field' => 'is_delete',
                                    'value' => 1,
                                ],
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
                'title' => '视频详情',
                'form' => [
                    'items' => [
                        [
                            'name' => 'id',
                            'label' => 'ID',
                        ],
                        [
                            'name' => 'image',
                            'label' => '封面图片',
                            'driver' => DetailItemImage::class,
                            'value' => function ($row) {
                                if ($row['image'] === '') {
                                    return Be::getProperty('App.Video')->getWwwUrl() . '/Template/Video/images/no-image.jpg';
                                }
                                return $row['image'];
                            },
                            'ui' => [
                                'style' => 'max-width: 128px;',
                            ],
                        ],
                        [
                            'name' => 'title',
                            'label' => '标题',
                        ],
                        [
                            'name' => 'summary',
                            'label' => '摘要',
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
                                // return Be::getRequest()->getRootUrl() . '/video/' . $row['url'];
                                return beUrl('Video.Video.detail', ['id' => $row['id']]);
                            }
                        ],
                        [
                            'name' => 'author',
                            'label' => '作者',
                        ],
                        [
                            'name' => 'publish_time',
                            'label' => '发布时间',
                        ],
                        [
                            'name' => 'categories',
                            'label' => '分类',
                            'driver' => DetailItemHtml::class,
                            'value' => function ($row) {
                                $categoryIds = Be::getTable('video_category')
                                    ->where('video_id', $row['id'])
                                    ->getValues('category_id');
                                if (count($categoryIds) > 0) {
                                    $categoryNames = Be::getTable('video_category')
                                        ->where('id', 'IN', $categoryIds)
                                        ->getValues('name');

                                    return '<span class="el-tag el-tag--primary el-tag--light">' . implode('</span> <span class="el-tag el-tag--primary el-tag--light">', $categoryNames) . '</span>';
                                }

                                return '';
                            }
                        ],
                        [
                            'name' => 'tags',
                            'label' => '标签',
                            'driver' => DetailItemHtml::class,
                            'value' => function ($row) {
                                $tags = Be::getTable('video_tag')
                                    ->where('video_id', $row['id'])
                                    ->getValues('tag');
                                if (count($tags) > 0) {
                                    return '<span class="el-tag el-tag--primary el-tag--light">' . implode('</span> <span class="el-tag el-tag--primary el-tag--light">', $tags) . '</span>';
                                }
                                return '';
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
                            'name' => 'is_enable',
                            'label' => '是否发布',
                            'driver' => DetailItemToggleIcon::class,
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

            'fieldEdit' => [
                'events' => [
                    'before' => function ($tuple) {
                        $postData = Be::getRequest()->json();
                        $field = $postData['postData']['field'];
                        if ($field === 'is_delete') {
                            $value = $postData['postData']['value'];
                            if ($value === 1) {
                                $tuple->url = $tuple->url . '-' . $tuple->id;
                            }
                        }

                        $tuple->update_time = date('Y-m-d H:i:s');
                    },
                    'success' => function () {
                        Be::getService('App.System.Task')->trigger('Video.VideoSyncEsAndCache');
                    },
                ],
            ],

        ])->execute();
    }

    /**
     * 新建视频
     *
     * @BePermission("新建", ordering="1.11")
     */
    public function create()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        if ($request->isAjax()) {
            try {
                $video = Be::getService('App.Video.Admin.Video')->edit($request->json('formData'));
                $response->set('success', true);
                $response->set('message', '新建视频成功！');
                $response->set('video', $video);
                $response->set('redirectUrl', beAdminUrl('Video.Video.videos'));
                $response->json();
            } catch (\Throwable $t) {
                $response->set('success', false);
                $response->set('message', $t->getMessage());
                $response->json();
            }
        } else {
            $response->set('video', false);

            $response->set('title', '新建视频');

            $categoryKeyValues = Be::getService('App.Video.Admin.Category')->getCategoryKeyValues();
            $response->set('categoryKeyValues', $categoryKeyValues);

            $configVideo = Be::getConfig('App.Video.Video');
            $response->set('configVideo', $configVideo);

            $response->set('backUrl', beAdminUrl('Video.Video.videos'));
            $response->set('formActionUrl', beAdminUrl('Video.Video.create'));

            $response->display('App.Video.Admin.Video.edit');
        }
    }

    /**
     * 编辑
     *
     * @BePermission("编辑", ordering="1.12")
     */
    public function edit()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();
        if ($request->isAjax()) {
            try {
                $video = Be::getService('App.Video.Admin.Video')->edit($request->json('formData'));
                $response->set('success', true);
                $response->set('message', '编辑视频成功！');
                $response->set('video', $video);
                $response->set('redirectUrl', beAdminUrl('Video.Video.videos'));
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
                    $response->redirect(beAdminUrl('Video.Video.edit', ['id' => $postData['row']['id']]));
                }
            }
        } else {
            $videoId = $request->get('id', '');
            $video = Be::getService('App.Video.Admin.Video')->getVideo($videoId, [
                'categories' => 1,
                'tags' => 1,
            ]);
            $response->set('video', $video);

            $response->set('title', '编辑视频');

            $categoryKeyValues = Be::getService('App.Video.Admin.Category')->getCategoryKeyValues();
            $response->set('categoryKeyValues', $categoryKeyValues);

            $configVideo = Be::getConfig('App.Video.Video');
            $response->set('configVideo', $configVideo);

            $response->set('backUrl', beAdminUrl('Video.Video.videos'));
            $response->set('formActionUrl', beAdminUrl('Video.Video.edit'));

            $response->display();
        }
    }

    /**
     * 批量编辑视频分类
     *
     * @BePermission("编辑", ordering="1.12")
     */
    public function bulkEditCategory()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $data = $request->post('data', '', '');
        $data = json_decode($data, true);

        $videos = $data['selectedRows'];
        if (count($videos) === 0) {
            $response->error('您未选择视频！');
            return;
        }

        foreach ($videos as &$video) {
            $video['category_ids'] = [];
        }
        unset($video);

        $response->set('title', '批量编辑视频分类');
        $response->set('videos', $videos);

        $categoryKeyValues = Be::getService('App.Video.Admin.Category')->getCategoryKeyValues();
        $response->set('categoryKeyValues', $categoryKeyValues);

        $response->display(null, 'Blank');
    }

    /**
     * 批量编辑视频分类保存
     *
     * @BePermission("编辑", ordering="1.12")
     */
    public function bulkEditCategorySave()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $formData = $request->json('formData');
            $videos = $formData['videos'];
            Be::getService('App.Video.Admin.Video')->bulkEditCategory($videos);
            $response->set('success', true);
            $response->set('message', '批量编辑视频分类成功！');
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
    public function preview()
    {
        $request = Be::getRequest();
        $data = $request->post('data', '', '');
        $data = json_decode($data, true);
        Be::getResponse()->redirect(beUrl('Video.Video.preview', ['id' => $data['row']['id']]));
    }


    public function comments()
    {
        $orderBy = Request::post('orderBy', 'createTime');
        $orderByDir = Request::post('orderByDir', 'DESC');
        $videoId = Request::post('videoId', 0, 'int');
        $key = Request::post('key', '');
        $status = Request::post('status', -1, 'int');
        $limit = Request::post('limit', -1, 'int');

        if ($limit == -1) {
            $adminConfigSystem = Be::getConfig('System.Admin');
            $limit = $adminConfigSystem->limit;
        }

        $adminServiceVideo = Be::getService('Video.Video');
        Response::setTitle('评论列表');

        $option = array('videoId' => $videoId, 'key' => $key, 'status' => $status);

        $pagination = Be::getUi('Pagination');
        $pagination->setLimit($limit);
        $pagination->setTotal($adminServiceVideo->getCommentCount($option));
        $pagination->setPage(Request::post('video', 1, 'int'));

        Response::set('pagination', $pagination);
        Response::set('orderBy', $orderBy);
        Response::set('orderByDir', $orderByDir);
        Response::set('key', $key);
        Response::set('status', $status);

        Response::set('videoId', $videoId);

        $option['orderBy'] = $orderBy;
        $option['orderByDir'] = $orderByDir;
        $option['offset'] = $pagination->getOffset();
        $option['limit'] = $limit;

        $videos = array();
        $comments = $adminServiceVideo->getComments($option);
        foreach ($comments as $comment) {
            if (!array_key_exists($comment->videoId, $videos)) {
                $tupleVideo = Be::getTuple('video');
                $tupleVideo->load($comment->videoId);
                $videos[$comment->videoId] = $tupleVideo;
            }

            $comment->video = $videos[$comment->videoId];
        }

        Response::set('comments', $comments);
        Response::display();

        $libHistory = Be::getLib('History');
        $libHistory->save('Admin.Video.Video.comments');
    }

    public function commentsUnblock()
    {
        $ids = Request::post('id', '');

        try {
            $serviceVideo = Be::getService('Video.Video');
            $serviceVideo->commentsUnblock($ids);
            Response::success('公开评论成功！');
            Be::getService('System.AdminLog')->addLog('公开视频评论：#' . $ids);
        } catch (\Exception $e) {
            Response::error($e->getMessage());
        }

        $libHistory = Be::getLib('History');
        $libHistory->back('Admin.Video.Video.comments');
    }

    public function commentsBlock()
    {
        $ids = Request::post('id', '');

        try {
            $serviceVideo = Be::getService('Video.Video');
            $serviceVideo->commentsBlock($ids);
            Response::success('屏蔽评论成功！');
            Be::getService('System.AdminLog')->addLog('屏蔽视频评论：#' . $ids);
        } catch (\Exception $e) {
            Response::error($e->getMessage());
        }

        $libHistory = Be::getLib('History');
        $libHistory->back('Admin.Video.Video.comments');
    }

    public function commentsDelete()
    {
        $ids = Request::post('id', '');

        try {
            $serviceVideo = Be::getService('Video.Video');
            $serviceVideo->commentsDelete($ids);
            Response::success('删除评论成功！');
            Be::getService('System.AdminLog')->addLog('删除视频评论：#' . $ids . ')');
        } catch (\Exception $e) {
            Response::error($e->getMessage());
        }

        $libHistory = Be::getLib('History');
        $libHistory->back('Admin.Video.Video.comments');
    }

}

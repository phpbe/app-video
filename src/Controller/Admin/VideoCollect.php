<?php

namespace Be\App\Video\Controller\Admin;


use Be\AdminPlugin\Detail\Item\DetailItemHtml;
use Be\AdminPlugin\Detail\Item\DetailItemImage;
use Be\AdminPlugin\Detail\Item\DetailItemToggleIcon;
use Be\AdminPlugin\Table\Item\TableItemImage;
use Be\AdminPlugin\Table\Item\TableItemLink;
use Be\AdminPlugin\Table\Item\TableItemSelection;
use Be\AdminPlugin\Table\Item\TableItemToggleIcon;
use Be\AdminPlugin\Toolbar\Item\ToolbarItemButtonDropDown;
use Be\AdminPlugin\Toolbar\Item\ToolbarItemDropDown;
use Be\App\System\Controller\Admin\Auth;
use Be\Be;

/**
 * @BeMenuGroup("采集", icon="el-icon-download", ordering="2")
 * @BePermissionGroup("采集", ordering="2")
 */
class VideoCollect extends Auth
{

    /**
     * 视频
     *
     * @BeMenu("采集的视频", icon="el-icon-document-copy", ordering="2.1")
     * @BePermission("采集的视频", ordering="2.1")
     */
    public function collectVideos()
    {
        Be::getAdminPlugin('Curd')->setting([

            'label' => '采集的视频',
            'table' => 'video',

            'grid' => [
                'title' => '采集的视频',

                'filter' => [
                    ['video_collect_id', '!=', ''],
                ],

                'tab' => [
                    'name' => 'status',
                    'value' => Be::getRequest()->request('status', '-1'),
                    'nullValue' => '-1',
                    'keyValues' => [
                        '-1' => '全部',
                        '0' => '未发布',
                        '1' => '已发布',
                    ],
                    'counter' => true,
                    'buildSql' => function ($dbName, $formData) {
                        if (isset($formData['status'])) {
                            if ($formData['status'] === '0') {
                                return ['is_enable', '=', '-1'];
                            } elseif ($formData['status'] === '1') {
                                return ['is_enable', '!=', '-1'];
                            }
                        }
                        return '';
                    },
                ],

                'form' => [
                    'items' => [
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

                'tableToolbar' => [
                    'items' => [
                        [
                            'label' => '批量发布',
                            'action' => 'publish',
                            'target' => 'ajax',
                            'confirm' => '确认要发布吗？',
                            'ui' => [
                                'icon' => 'el-icon-upload2',
                                'type' => 'success',
                            ]
                        ],
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
                            'ui' => [
                                'table-column' => [
                                    ':selectable' => 'function(row, index){return row.is_enable === \'-1\';}',
                                ],
                            ],
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
                                    return Be::getProperty('App.Video')->getWwwUrl(). '/video/images//no-image.jpg';
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
                            'name' => 'status',
                            'label' => '是否已发布',
                            'driver' => TableItemToggleIcon::class,
                            'width' => '90',
                            'value' => function ($row) {
                                return $row['is_enable'] === '-1' ? '0' : '1';
                            },
                            'exportValue' => function ($row) {
                                return $row['is_enable'] === '-1' ? '未发布' : '已发布';
                            },
                        ],
                        [
                            'name' => 'update_time',
                            'label' => '更新时间',
                            'width' => '180',
                            'sortable' => true,
                        ],
                    ],

                    'exclude' => ['summary', 'description'],

                    'operation' => [
                        'label' => '操作',
                        'width' => '240',
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
                                    ':disabled' => 'scope.row.is_enable !== \'-1\'',
                                    'icon' => 'el-icon-view',
                                ],
                            ],
                            [
                                'label' => '',
                                'tooltip' => '发布',
                                'action' => 'publish',
                                'target' => 'ajax',
                                'confirm' => '确认要发布吗？',
                                'ui' => [
                                    'type' => 'warning',
                                    ':underline' => 'false',
                                    'style' => 'font-size: 20px;',
                                    ':disabled' => 'scope.row.is_enable !== \'-1\'',
                                    'icon' => 'el-icon-upload2',
                                ],
                            ],
                            [
                                'label' => '',
                                'tooltip' => '编辑',
                                'action' => 'edit',
                                'target' => 'self',
                                'ui' => [
                                    ':underline' => 'false',
                                    'style' => 'font-size: 20px;',
                                    ':disabled' => 'scope.row.is_enable !== \'-1\'',
                                    'icon' => 'el-icon-edit',
                                ],
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
                                    'icon' => 'el-icon-delete',
                                ],
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
                            'name' => 'unique_key',
                            'label' => '唯一键',
                            'value' => function ($row) {
                                $sql = 'SELECT unique_key FROM video_collect WHERE video_id = ?';
                                return Be::getDb()->getValue($sql, [$row['id']]);
                            },
                        ],
                        [
                            'name' => 'image',
                            'label' => '封面图片',
                            'driver' => DetailItemImage::class,
                            'value' => function ($row) {
                                if ($row['image'] === '') {
                                    return Be::getProperty('App.Video')->getWwwUrl(). '/video/images//no-image.jpg';
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
                            'name' => 'author',
                            'label' => '作者',
                        ],
                        [
                            'name' => 'publish_time',
                            'label' => '发布时间',
                        ],
                        [
                            'name' => 'status',
                            'label' => '是否已发布',
                            'driver' => DetailItemToggleIcon::class,
                            'value' => function ($row) {
                                return $row['is_enable'] === '-1' ? '0' : '1';
                            },
                            'exportValue' => function ($row) {
                                return $row['is_enable'] === '-1' ? '未发布' : '已发布';
                            },
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
     * 编辑采集的视频
     *
     * @BePermission("编辑", ordering="2.12")
     */
    public function edit()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();
        if ($request->isAjax()) {
            try {
                $video = Be::getService('App.Video.Admin.Video')->edit($request->json('formData'));
                $response->set('success', true);
                $response->set('message', '编辑采集的视频成功！');
                $response->set('video', $video);
                $response->set('redirectUrl', beAdminUrl('Video.VideoCollect.collectVideos'));
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
                    $response->redirect(beAdminUrl('Video.VideoCollect.edit', ['id' => $postData['row']['id']]));
                }
            }
        } else {
            $videoId = $request->get('id', '');
            $video = Be::getService('App.Video.Admin.Video')->getVideo($videoId, [
                'categories' => 1,
                'tags' => 1,
            ]);
            $response->set('video', $video);

            if ($video->is_enable !== -1) {
                $response->error('已发布的博客禁止编辑！');
                return;
            }

            $response->set('video', $video);

            $categoryKeyValues = Be::getService('App.Video.Admin.Category')->getCategoryKeyValues();
            $response->set('categoryKeyValues', $categoryKeyValues);

            $configVideo = Be::getConfig('App.Video.Video');
            $response->set('configVideo', $configVideo);

            $response->set('backUrl', beAdminUrl('Video.VideoCollect.collectVideos'));
            $response->set('formActionUrl', beAdminUrl('Video.VideoCollect.edit'));

            $response->set('title', '编辑采集的视频');

            $response->display('App.Video.Admin.Video.edit');
        }
    }

    /**
     * 删除采集的视频
     *
     * @BePermission("删除", ordering="2.13")
     */
    public function delete()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $postData = $request->json();

            $videoIds = [];
            if (isset($postData['selectedRows'])) {
                foreach ($postData['selectedRows'] as $row) {
                    $videoIds[] = $row['id'];
                }
            } elseif (isset($postData['row'])) {
                $videoIds[] = $postData['row']['id'];
            }

            if (count($videoIds) > 0) {
                Be::getService('App.Video.Admin.VideoCollect')->delete($videoIds);
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
    public function preview()
    {
        $request = Be::getRequest();
        $data = $request->post('data', '', '');
        $data = json_decode($data, true);
        Be::getResponse()->redirect(beUrl('Video.Video.preview', ['id' => $data['row']['id']]));
    }

    /**
     * 发布
     *
     * @BePermission("发布", ordering="2.14")
     */
    public function publish()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $postData = $request->json();

        $videos = [];
        if (isset($postData['selectedRows'])) {
            $videos = $postData['selectedRows'];
        } elseif (isset($postData['row'])) {
            $videos[] = $postData['row'];
        }

        if (count($videos) === 0) {
            $response->error('您未选择视频！');
            return;
        }

        try {
            Be::getService('App.Video.Admin.VideoCollect')->publish($videos);
            $response->success('发布成功！');
        } catch (\Throwable $t) {
            $response->error($t->getMessage());
        }
    }


}

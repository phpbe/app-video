<?php
namespace Be\App\Video\Controller;

use Be\App\ControllerException;
use Be\App\ServiceException;
use Be\Be;

/**
 * 接口
 */
class Api
{

    /**
     * 采集接口
     *
     * @BeRoute("/video/collect/api")
     */
    public function collect()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $db = Be::getDb();
        $db->startTransaction();
        try {
            $configVideoCollectApi = Be::getConfig('App.Video.VideoCollectApi');
            if ($configVideoCollectApi->enable === 0) {
                throw new ControllerException('采集接口未启用！');
            }

            $token = $request->get('token', '');
            if ($configVideoCollectApi->token !== $token) {
                throw new ControllerException('密钥错误！');
            }

            $title = $request->post('title', '');
            if ($title === '') {
                $categoryKeyValues = Be::getService('App.Video.Admin.Category')->getCategoryKeyValues();
                $response->set('categories', $categoryKeyValues);
                $response->json();
                return;
            }

            $uniqueKey = $request->post('unique_key', '');

            $data = [];

            $tupleVideoCollect = Be::getTuple('video_collect');

            $collectVideoExist = false;
            if ($uniqueKey !== '') {
                if (mb_strlen($uniqueKey) > 200) {
                    throw new ServiceException('唯一值（unique_key）不得超过200个字符！');
                }

                try {
                    $tupleVideoCollect->loadBy([
                        'unique_key' => $uniqueKey,
                    ]);

                    $collectVideoExist = true;
                } catch (\Throwable $t) {
                }

                if ($collectVideoExist) {
                    $tupleVideo = Be::getTuple('video');
                    try {
                        $tupleVideo->load($tupleVideoCollect->video_id);

                        $data['id'] = $tupleVideoCollect->video_id;
                    } catch (\Throwable $t) {
                        throw new ServiceException('唯一键值（unique_key=' . $uniqueKey . '）对应的视频异常！');
                    }

                    if ($tupleVideo->is_enable !== -1) {
                        throw new ServiceException('唯一键值（unique_key=' . $uniqueKey . '）对应的视频已发布！');
                    }
                }
            }

            $now = date('Y-m-d H:i:s');
            $tupleVideoCollect->update_time = $now;
            if ($collectVideoExist) {
                $tupleVideoCollect->update();
            } else {
                $tupleVideoCollect->unique_key = $uniqueKey;
                $tupleVideoCollect->video_id = '';
                $tupleVideoCollect->create_time = $now;
                $tupleVideoCollect->insert();
            }

            $data['video_collect_id'] = $tupleVideoCollect->id;

            $data['image'] = $request->post('image', '');

            $data['title'] = $title;
            if (mb_strlen($data['title']) > 200) {
                throw new ServiceException('采集的视频标题（title）不得超过200个字符！');
            }

            $data['summary'] = $request->post('summary', '');
            if ($data['summary'] && mb_strlen($data['summary']) > 500) {
                throw new ServiceException('摘要（summary）不得超过500个字符！');
            }

            $data['description'] = $request->post('description', '', 'html');

            $data['author'] = $request->post('author', '');
            if ($data['author'] && mb_strlen($data['author']) > 50) {
                throw new ServiceException('作者（author）不得超过50个字符！');
            }

            $data['publish_time'] = $request->post('publish_time', '');
            if (!strtotime($data['publish_time'])) {
                $data['publish_time'] = date('Y-m-d H:i:s');
            }

            $tags = $request->post('tags', '');
            if ($tags) {
                $tags = explode('|', $tags);
                $tagsData = [];
                foreach ($tags as $tag) {
                    $tagsData[] = [
                        'id' => '',
                        'tag' => $tag,
                    ];
                }
                $data['tags'] = $tagsData;
            } else {
                $data['tags'] = [];
            }

            $data['is_enable'] = -1; // 采集的视频标记

            $video = Be::getService('App.Video.Admin.Video')->edit($data);

            if (!$collectVideoExist) {
                $tupleVideoCollect->video_id = $video->id;
                $tupleVideoCollect->update_time = date('Y-m-d H:i:s');
                $tupleVideoCollect->update();
            }

            $db->commit();

            $response->end('[OK] 数据已接收！');
        } catch (\Throwable $t) {
            $db->rollback();

            $response->end('[ERROR] ' . $t->getMessage());
        }

    }

}

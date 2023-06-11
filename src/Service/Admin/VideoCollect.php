<?php

namespace Be\App\Video\Service\Admin;

use Be\App\ServiceException;
use Be\Be;

class VideoCollect
{

    /**
     * 发布
     *
     * @param array $videos 要发布的视频数据
     */
    public function publish(array $videos)
    {
        if (count($videos) === 0) return;

        $db = Be::getDb();
        $db->startTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            foreach ($videos as $video) {
                $tupleVideo = Be::getTuple('video');
                try {
                    $tupleVideo->loadBy([
                        'id' => $video['id'],
                        'is_delete' => 0,
                    ]);
                } catch (\Throwable $t) {
                    throw new ServiceException('采集的视频（# ' . $video['id'] . '）不存在！');
                }

                $tupleVideo->is_enable = 1;
                $tupleVideo->update_time = $now;
                $tupleVideo->update();
            }

            $db->commit();

            Be::getService('App.System.Task')->trigger('Video.VideoSyncEsAndCache');

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException('发布采集的视频发生异常！');
        }
    }

    
    /**
     * 删除
     *
     * @param array $videoIds 要删除的视频ID
     */
    public function delete(array $videoIds)
    {
        if (count($videoIds) === 0) return;

        $db = Be::getDb();
        $db->startTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            foreach ($videoIds as $videoId) {
                $tupleVideo = Be::getTuple('video');
                try {
                    $tupleVideo->load($videoId);
                } catch (\Throwable $t) {
                    throw new ServiceException('采集的视频（# ' . $videoId . '）不存在！');
                }

                if ($tupleVideo->is_enable === -1) { // 未曾发布，直接物理删除

                    // 删除视频分类
                    Be::getTable('video_category')
                        ->where('video_id', $videoId)
                        ->delete();

                    // 删除商品款式
                    Be::getTable('video_tag')
                        ->where('video_id', $videoId)
                        ->delete();

                    if ($tupleVideo->video_collect_id !== '') {
                        Be::getTuple('video_collect')
                            ->delete($tupleVideo->video_collect_id);
                    }

                    // 最后删除视频主表
                    $tupleVideo->delete();

                } else {

                    if ($tupleVideo->video_collect_id !== '') {
                        Be::getTuple('video_collect')
                            ->delete($tupleVideo->video_collect_id);
                    }

                    $tupleVideo->video_collect_id = '';
                    $tupleVideo->update_time = $now;
                    $tupleVideo->update();
                }
            }

            $db->commit();

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);

            throw new ServiceException('删除采集的视频发生异常！');
        }
    }


}

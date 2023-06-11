<?php

namespace Be\App\Video\Service;

use Be\App\ServiceException;
use Be\Be;

class VideoCollect
{

    /**
     * 获取采集的视频
     *
     * @param string $videoId 采集的视频ID
     * @return \stdClass 视频对象
     * @throws ServiceException
     */
    public function getVideo(string $videoId): \stdClass
    {
        $tupleVideo = Be::getTuple('video_collect');
        try {
            $tupleVideo->load($videoId);
        } catch (\Throwable $t) {
            throw new ServiceException('采集的视频不存在！');
        }
        return $tupleVideo->toObject();
    }

}

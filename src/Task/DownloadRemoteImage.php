<?php
namespace Be\App\Video\Task;

use Be\Be;
use Be\Task\Task;

/**
 * @BeTask("自动下载远程图片", timeout="1800", schedule="40 * * * *")
 */
class DownloadRemoteImage extends Task
{

    protected $parallel = false;

    public function execute()
    {
        $timeout = $this->task->timeout;
        if ($timeout <= 0) {
            $timeout = 60;
        }

        $service = Be::getService('App.Video.Admin.TaskVideo');
        $t0 = time();
        do {
            $sql = 'SELECT * FROM video_video WHERE download_remote_image = 1';
            $video = Be::getDb()->getObject($sql);
            if (!$video) {
                break;
            }

            $service->downloadRemoteImages($video);

            $t1 = time();
        } while($t1 - $t0 < $timeout);
    }



}

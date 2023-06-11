<?php
namespace Be\App\Video\Task;

use Be\Be;
use Be\Task\Task;

/**
 * 视频全量量同步到ES和Cache
 *
 * @BeTask("视频全量量同步到ES和Cache")
 */
class AllVideoSyncEsAndCache extends Task
{

    public function execute()
    {
        $configEs = Be::getConfig('App.Video.Es');

        $service = Be::getService('App.Video.Admin.TaskVideo');

        $db = Be::getDb();
        $sql = 'SELECT * FROM video_video WHERE is_enable != -1';
        $objs = $db->getYieldObjects($sql);

        $batch = [];
        $i = 0;
        foreach ($objs as $obj) {
            $batch[] = $obj;

            $i++;
            if ($i >= 100) {
                if ($configEs->enable) {
                    $service->syncEs($batch);
                }

                $service->syncCache($batch);

                $batch = [];
                $i = 0;
            }
        }

        if ($i > 0) {
            if ($configEs->enable) {
                $service->syncEs($batch);
            }

            $service->syncCache($batch);
        }


        $service = Be::getService('App.Video.Admin.TaskVideoComment');

        $db = Be::getDb();
        $sql = 'SELECT * FROM video_video_comment WHERE is_enable != -1';
        $objs = $db->getYieldObjects($sql);

        $batch = [];
        $i = 0;
        foreach ($objs as $obj) {
            $batch[] = $obj;

            $i++;
            if ($i >= 100) {
                if ($configEs->enable) {
                    $service->syncEs($batch);
                }

                $service->syncCache($batch);

                $batch = [];
                $i = 0;
            }
        }

        if ($i > 0) {
            if ($configEs->enable) {
                $service->syncEs($batch);
            }

            $service->syncCache($batch);
        }

    }


}

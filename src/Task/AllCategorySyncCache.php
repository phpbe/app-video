<?php
namespace Be\App\Video\Task;

use Be\Be;
use Be\Task\Task;

/**
 * @BeTask("视频分类全量同步到Cache")
 */
class AllCategorySyncCache extends Task
{


    public function execute()
    {
        $service = Be::getService('App.Video.Admin.TaskCategory');

        $db = Be::getDb();
        $sql = 'SELECT * FROM video_category';
        $categories = $db->getYieldObjects($sql);

        $batch = [];
        $i = 0;
        foreach ($categories as $category) {
            $batch[] = $category;

            $i++;
            if ($i >= 100) {
                $service->syncCache($batch);

                $batch = [];
                $i = 0;
            }
        }

        if ($i > 0) {
            $service->syncCache($batch);
        }

    }

}

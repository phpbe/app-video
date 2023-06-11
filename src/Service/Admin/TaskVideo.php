<?php

namespace Be\App\Video\Service\Admin;

use Be\App\ServiceException;
use Be\Be;
use Be\Task\TaskException;
use Be\Util\File\FileSize;
use Be\Util\File\Mime;
use Be\Util\Net\Curl;

class TaskVideo
{

    /**
     * 同步到 ES
     *
     * @param array $videos
     */
    public function syncEs(array $videos)
    {
        if (count($videos) === 0) return;

        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Video.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return;
        }

        $es = Be::getEs();
        $db = Be::getDb();

        $batch = [];
        foreach ($videos as $video) {

            // 采集的视频，不处理
            if ($video->is_enable === '-1') {
                continue;
            }

            if ($video->is_delete !== '0' || $video->is_enable !== '1') {
                $params = [
                    'body' => [
                        'index' => $configEs->indexVideo,
                        'id' => $video->id,
                    ]
                ];

                $es->delete($params);

            } else {

                $categories = [];
                $sql = 'SELECT category_id FROM video_category WHERE video_id = ?';
                $categoryIds = $db->getValues($sql, [$video->id]);
                if (count($categoryIds) > 0) {
                    $sql = 'SELECT id, `name` FROM video_category WHERE is_delete=0 AND id IN (\'' . implode('\',\'', $categoryIds) . '\') ORDER BY ordering ASC';
                    $categories = $db->getObjects($sql);
                }

                $sql = 'SELECT tag FROM video_tag WHERE video_id = ?';
                $tags = $db->getValues($sql, [$video->id]);


                $batch[] = [
                    'index' => [
                        '_index' => $configEs->indexVideo,
                        '_id' => $video->id,
                    ]
                ];

                $batch[] = [
                    'id' => $video->id,
                    'image' => $video->image,
                    'title' => $video->title,
                    'summary' => $video->summary,
                    'description' => $video->description,
                    'url' => $video->url,
                    'author' => $video->author,
                    'publish_time' => $video->publish_time,
                    'ordering' => (int)$video->ordering,
                    'hits' => (int)$video->hits,
                    'is_push_home' => $video->is_push_home === '1',
                    'is_on_top' => $video->is_on_top === '1',
                    //'is_enable' => $video->is_enable === '1',
                    //'is_delete' => $video->is_delete === '1',
                    //'create_time' => $video->create_time,
                    //'update_time' => $video->update_time,
                    'categories' => $categories,
                    'tags' => $tags,
                ];
            }
        }

        if (count($batch) > 0) {
            $response = $es->bulk(['body' => $batch]);
            if ($response['errors'] > 0) {
                $reason = '';
                if (isset($response['items']) && count($response['items']) > 0) {
                    foreach ($response['items'] as $item) {
                        if (isset($item['index']['error']['reason'])) {
                            $reason = $item['index']['error']['reason'];
                            break;
                        }
                    }
                }
                throw new ServiceException('视频全量量同步到ES出错：' . $reason);
            }
        }
    }

    /**
     * 视频同步到缓存
     *
     * @param array $videos
     */
    public function syncCache(array $videos)
    {
        if (count($videos) === 0) return;

        $db = Be::getDb();
        $cache = Be::getCache();
        $keyValues = [];
        foreach ($videos as $video) {

            // 采集的商品，不处理
            if ($video->is_enable === '-1') {
                continue;
            }

            $key = 'App:Video:Video:' . $video->id;

            if ($video->is_delete !== '0' || $video->is_enable !== '1') {
                $cache->delete($key);
            } else {
                $categories = [];
                $sql = 'SELECT category_id FROM video_category WHERE video_id = ?';
                $categoryIds = $db->getValues($sql, [$video->id]);
                if (count($categoryIds) > 0) {
                    $sql = 'SELECT id, `name` FROM video_category WHERE is_delete=0 AND id IN (\'' . implode('\',\'', $categoryIds) . '\') ORDER BY ordering ASC';
                    $categories = $db->getObjects($sql);
                }
                $video->categories = $categories;
                $video->category_ids = array_column($categories, 'id');

                $sql = 'SELECT tag FROM video_tag WHERE video_id = ?';
                $video->tags = $db->getValues($sql, [$video->id]);

                $newVideo = new \stdClass();
                $newVideo->id = $video->id;
                $newVideo->image = $video->image;
                $newVideo->title = $video->title;
                $newVideo->summary = $video->summary;
                $newVideo->description = $video->description;
                $newVideo->url = $video->url;
                //$newVideo->url_custom = (int)$video->url_custom;
                $newVideo->author = $video->author;
                $newVideo->publish_time = $video->publish_time;
                $newVideo->seo_title = $video->seo_title;
                //$newVideo->seo_title_custom = (int)$video->seo_title_custom;
                $newVideo->seo_description = $video->seo_description;
                //$newVideo->seo_description_custom = (int)$video->seo_description_custom;
                $newVideo->seo_keywords = $video->seo_keywords;
                //$newVideo->ordering = (int)$video->ordering;
                $newVideo->hits = $video->hits;
                //$newVideo->is_push_home = (int)$video->is_push_home;
               // $newVideo->is_on_top = (int)$video->is_on_top;

                $newVideo->categories = $video->categories;
                $newVideo->category_ids = $video->category_ids;
                $newVideo->tags = $video->tags;

                $keyValues[$key] = $newVideo;
            }
        }

        if (count($keyValues) > 0) {
            $cache->setMany($keyValues);
        }
    }

    /**
     * 下载远程图片
     *
     * @param object $video 视频
     */
    public function downloadRemoteImages(object $video)
    {
        $storageRootUrl = Be::getStorage()->getRootUrl();
        $storageRootUrlLen = strlen($storageRootUrl);
        $imageKeyValues = [];

        $hasChange = false;
        $updateObj = new \stdClass();
        $updateObj->id = $video->id;
        $updateObj->download_remote_image = 2;
        $updateObj->update_time = date('Y-m-d H:i:s');
        Be::getDb()->update('video', $updateObj, 'id');


        $video->image = trim($video->image);
        if ($video->image !== '') {
            if (strlen($video->image) < $storageRootUrlLen || substr($video->image, 0, $storageRootUrlLen) !== $storageRootUrl) {
                $storageImage = false;
                try {
                    $storageImage = $this->downloadRemoteImage($video, $video->image);
                } catch (\Throwable $t) {
                    Be::getLog()->error($t);
                }

                if ($storageImage) {
                    $imageKeyValues[$video->image] = $storageImage;

                    $updateObj->image = $storageImage;
                    $hasChange = true;
                }
            }
        }

        $descriptionHasChange = false;
        $descriptionImages = [];

        $configSystem = Be::getConfig('App.System.System');
        $reg = '/ src=\"([^\"]*\.(' . implode('|', $configSystem->allowUploadImageTypes) . ')[^\"]*)\"/is';
        if (preg_match_all($reg, $video->description, $descriptionImages)) {
            $i = 0;
            foreach ($descriptionImages[1] as $descriptionImage) {
                $descriptionImage = trim($descriptionImage);
                if ($descriptionImage !== '') {
                    if (strlen($descriptionImage) < $storageRootUrlLen || substr($descriptionImage, 0, $storageRootUrlLen) !== $storageRootUrl) {
                        $storageImage = false;
                        if (isset($imageKeyValues[$descriptionImage])) {
                            $storageImage = $imageKeyValues[$descriptionImage];
                        } else {
                            try {
                                $storageImage = $this->downloadRemoteImage($video, $descriptionImage);
                            } catch (\Throwable $t) {
                                Be::getLog()->error($t);
                            }
                        }

                        if ($storageImage) {
                            $imageKeyValues[$descriptionImage] = $storageImage;

                            $replaceFrom = $descriptionImages[0][$i];
                            $replaceTo = str_replace($descriptionImage, $storageImage, $replaceFrom);
                            $video->description = str_replace($replaceFrom, $replaceTo, $video->description);
                            $descriptionHasChange = true;
                        }
                    }
                }

                $i++;
            }

            if ($descriptionHasChange) {
                $updateObj->description = $video->description;
                $hasChange = true;
            }
        }

        if ($hasChange) {
            $updateObj->download_remote_image = 10;
            $updateObj->update_time = date('Y-m-d H:i:s');
            Be::getDb()->update('video', $updateObj, 'id');
        }
    }

    /**
     * 下载远程图片
     *
     * @param object $video 视频
     */
    public function downloadRemoteImage(object $video, string $remoteImage)
    {
        $configDownloadRemoteImage = Be::getConfig('App.Video.DownloadRemoteImage');

        // 示例：https://cdn.shopify.com/s/files/1/0139/8942/products/Womens-Zamora-Jogger-Scrub-Pant_martiniolive-4.jpg
        $remoteImage = trim($remoteImage);

        $name = substr($remoteImage, strrpos($remoteImage, '/') + 1);
        $name = trim($name);

        $originalExt = strrchr($name, '.');
        if ($originalExt && strlen($originalExt) > 1) {
            $originalExt = substr($originalExt, 1);
            $originalExt = strtolower($originalExt);
            $originalExt = trim($originalExt);

            $originalName = substr($name, 0, strrpos($name, '.'));
        } else {
            $originalExt = '';
            $originalName = $name;
        }

        $tmpDir = Be::getRuntime()->getRootPath() . '/data/tmp/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
            chmod($tmpDir, 0777);
        }
        $tmpFile = $tmpDir . uniqid(date('Ymdhis') . '-' . rand(1, 999999) . '-', true);

        $fileData = null;
        $success = false;
        $n = 0;
        do {
            $n++;
            try {
                $fileData = Curl::get($remoteImage);
                $success = true;
            } catch (\Throwable $t) {
                if ($configDownloadRemoteImage->retryIntervalMin > 0 || $configDownloadRemoteImage->retryIntervalMax) {
                    if (Be::getRuntime()->isSwooleMode()) {
                        \Swoole\Coroutine::sleep(rand($configDownloadRemoteImage->retryIntervalMin, $configDownloadRemoteImage->retryIntervalMax));
                    } else {
                        sleep(rand($configDownloadRemoteImage->retryIntervalMin, $configDownloadRemoteImage->retryIntervalMax));
                    }
                }
            }
        } while ($success === false && $n < $configDownloadRemoteImage->retryTimes);

        if (!$success) {
            throw new TaskException('获取远程图片（' . $remoteImage . '）失败！');
        }

        file_put_contents($tmpFile, $fileData);

        try {
            $configSystem = Be::getConfig('App.System.System');
            $maxSize = $configSystem->uploadMaxSize;
            $maxSizeInt = FileSize::string2Int($maxSize);
            $size = filesize($tmpFile);
            if ($size > $maxSizeInt) {
                throw new ServiceException('您上传的文件尺寸已超过最大限制：' . $maxSize . '！');
            }

            $ext = Mime::detectExt($tmpFile, $originalExt);

            if (!in_array($ext, $configSystem->allowUploadImageTypes)) {
                throw new ServiceException('禁止上传的图像类型：' . $ext . '！');
            }

            $dirName = '';
            switch ($configDownloadRemoteImage->dirname) {
                case 'id':
                    $dirName = $video->id;
                    break;
                case 'url':
                    $dirName = $video->url;
                    break;
            };
            
            $fileName = '';
            switch ($configDownloadRemoteImage->fileName) {
                case 'original':
                    $fileName = $originalName . '.' . $ext;
                    break;
                case 'md5':
                    $fileName = md5_file($tmpFile) . '.' . $ext;
                    break;
                case 'sha1':
                    $fileName = sha1_file($tmpFile) . '.' . $ext;
                    break;
                case 'timestamp':
                    $fileName = uniqid(date('Ymdhis') . '-' . rand(1, 999999) . '-', true) . '.' . $ext;
                    break;
            };

            $storage = Be::getStorage();
            $object = $configDownloadRemoteImage->rootPath . $dirName . '/' . $fileName;
            if ($storage->isFileExist($object)) {
                $url = $storage->getFileUrl($object);
            } else {
                $url = $storage->uploadFile($object, $tmpFile);
            }

        } catch (\Throwable $t) {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }

            throw $t;
        }

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }

        if ($configDownloadRemoteImage->intervalMin > 0 || $configDownloadRemoteImage->intervalMax) {
            if (Be::getRuntime()->isSwooleMode()) {
                \Swoole\Coroutine::sleep(rand($configDownloadRemoteImage->intervalMin, $configDownloadRemoteImage->intervalMax));
            } else {
                sleep(rand($configDownloadRemoteImage->intervalMin, $configDownloadRemoteImage->intervalMax));
            }
        }

        return $url;
    }
}

<?php

namespace Be\App\Video\Controller;

use Be\App\ControllerException;
use Be\Be;

/**
 * 视频评论
 */
class VideoComment
{

    /**
     * 新增评论
     *
     * @BeRoute("/video/comment/create")
     */
    public function create()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $service = Be::getService('App.Video.VideoComment');
            $service->create($request->post());

            $response->success(beLang('App.Video', 'VIDEO.COMMENT.CREATE_SUCCESS'));
        } catch (\Throwable $t) {
            $response->error($t->getMessage());
        }
    }


}

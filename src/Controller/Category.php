<?php
namespace Be\App\Video\Controller;

use Be\App\ControllerException;
use Be\Be;

/**
 * 视频分类
 */
class Category
{

    /**
     * 分类视频列表
     *
     * @BeMenu("视频分类", picker="return \Be\Be::getService('App.Video.Admin.Category')->getCategoryMenuPicker()")
     * @BeRoute("\Be\Be::getService('App.Video.Category')->getCategoryUrl($params)")
     */
    public function videos()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        try {
            $id = $request->get('id', '');
            if ($id === '') {
                throw new ControllerException('视频分类不存在！');
            }

            $category = Be::getService('App.Video.Category')->getCategory($id);
            $response->set('category', $category);

            $response->set('title', $category->seo_title);
            $response->set('metaDescription', $category->seo_description);
            $response->set('metaKeywords', $category->seo_keywords);
            $response->set('pageTitle', $category->name);

            $response->display();

        } catch (\Throwable $t) {
            $response->error($t->getMessage());
        }
    }

}

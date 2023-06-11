<?php

namespace Be\App\Video\Section\Video\Detail\Comments;

use Be\Be;
use Be\Theme\Section;

class Template extends Section
{

    public array $positions = ['middle', 'center'];

    public array $routes = ['Video.Video.detail'];


    private function css()
    {
        echo '<style type="text/css">';
        echo $this->getCssBackgroundColor('app-video-comments');
        echo $this->getCssPadding('app-video-comments');
        echo $this->getCssMargin('app-video-comments');


        echo '#' . $this->id . ' .app-video-comments {';
        //echo 'box-shadow: 0 0 10px var(--font-color-9);';
        echo 'box-shadow: 0 0 10px #eaf0f6;';
        echo 'transition: all 0.3s ease;';
        echo '}';

        echo '#' . $this->id . ' .app-video-comments:hover {';
        //echo 'box-shadow: 0 0 15px var(--font-color-8);';
        echo 'box-shadow: 0 0 15px #dae0e6;';
        echo '}';
        
        echo '#' . $this->id . ' .app-video-comments .comment-name {';
        echo 'width: 10rem;';
        echo '}';

        echo '</style>';
    }

    public function display()
    {
        if ($this->config->enable === 0) {
            return;
        }

        $page = Be::getRequest()->get('comment_page', 1);
        $comments = Be::getService('App.Video.VideoComment')->getComments($this->page->video->id, [
            'page' => $page,
        ]);

        if ($comments['total'] === 0) {
            return;
        }

        $this->css();

        echo '<div class="app-video-comments">';

        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '<div class="be-container">';
        }

        echo $this->page->tag0('be-section-title');
        echo $this->config->title;
        echo $this->page->tag1('be-section-title');

        echo $this->page->tag0('be-section-content');

        $i = 0;
        foreach ($comments['rows'] as $comment) {
            ?>
            <div class="be-row<?php echo $i === 0 ? '': ' be-mt-300 be-bt-eee be-pt-50';?>">
                <div class="be-col-auto">
                    <div class="comment-name be-t-break">
                        <?php echo $comment->name; ?>
                    </div>
                </div>

                <div class="be-col">
                    <div class="be-pl-100">
                        <div class="be-lh-150 be-c-666">
                            <?php echo $comment->content; ?>
                        </div>
                        <div class="be-mt-100 be-c-999">
                            <span><?php echo date(beLang('App.Video', 'VIDEO.PUBLISH_TIME_YYYY_MM_DD'), strtotime($comment->create_time)); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $i++;
        }

        $total = $comments['total'];
        $pageSize = $comments['pageSize'];
        $pages = ceil($total / $pageSize);
        if ($pages > 1) {
            $page = $comments['page'];
            if ($page > $pages) $page = $pages;

            $paginationUrl = beUrl('Video.Video.detail', ['id'=> $this->page->video->id]);
            $paginationUrl .= strpos($paginationUrl, '?') === false ? '?' : '&';

            $html = '<nav class="be-mt-300">';
            $html .= '<ul class="be-pagination" style="justify-content: center;">';
            $html .= '<li>';
            if ($page > 1) {
                $url = $paginationUrl;
                $url .= http_build_query(['comment_page' => ($page - 1)]);
                $html .= '<a href="' . $url . '">' . beLang('App.Video', 'PAGINATION.PREVIOUS'). '</a>';
            } else {
                $html .= '<span>' . beLang('App.Video', 'PAGINATION.PREVIOUS'). '</span>';
            }
            $html .= '</li>';

            $from = null;
            $to = null;
            if ($pages < 9) {
                $from = 1;
                $to = $pages;
            } else {
                $from = $page - 4;
                if ($from < 1) {
                    $from = 1;
                }

                $to = $from + 8;
                if ($to > $pages) {
                    $to = $pages;
                }
            }

            if ($from > 1) {
                $html .= '<li><span>...</span></li>';
            }

            for ($i = $from; $i <= $to; $i++) {
                if ($i == $page) {
                    $html .= '<li class="active">';
                    $html .= '<span>' . $i . '</span>';
                    $html .= '</li>';
                } else {
                    $url = $paginationUrl;
                    $url .= http_build_query(['comment_page' => $i]);
                    $html .= '<li>';
                    $html .= '<a href="' . $url . '">' . $i . '</a>';
                    $html .= '</li>';
                }
            }

            if ($to < $pages) {
                $html .= '<li><span>...</span></li>';
            }

            $html .= '<li>';
            if ($page < $pages) {
                $url = $paginationUrl;
                $url .= http_build_query(['comment_page' => ($page + 1)]);
                $html .= '<a href="' . $url . '">' . beLang('App.Video', 'PAGINATION.NEXT'). '</a>';
            } else {
                $html .= '<span>' . beLang('App.Video', 'PAGINATION.NEXT'). '</span>';
            }
            $html .= '</li>';
            $html .= '</ul>';
            $html .= '</nav>';

            echo $html;
        }
        
        echo $this->page->tag1('be-section-content');

        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '</div>';
        }

        echo '</div>';
    }

}


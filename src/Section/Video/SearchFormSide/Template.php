<?php

namespace Be\App\Video\Section\Video\SearchFormSide;

use Be\Be;
use Be\Theme\Section;

class Template extends Section
{

    public array $positions = ['west', 'east'];


    private function css()
    {
        echo '<style type="text/css">';
        echo $this->getCssBackgroundColor('app-video-video-search-form-side');
        echo $this->getCssPadding('app-video-video-search-form-side');
        echo $this->getCssMargin('app-video-video-search-form-side');

        echo '#' . $this->id . ' .app-video-video-search-form-side {';
        //echo 'box-shadow: 0 0 10px var(--font-color-9);';
        echo 'box-shadow: 0 0 10px #eaf0f6;';
        echo 'transition: all 0.3s ease;';
        echo '}';

        echo '#' . $this->id . ' .app-video-video-search-form-side:hover {';
        //echo 'box-shadow: 0 0 15px var(--font-color-8);';
        echo 'box-shadow: 0 0 15px #dae0e6;';
        echo '}';

        echo '</style>';
    }


    public function display()
    {
        if ($this->config->enable === 0) {
            return;
        }

        $this->css();
        ?>
        <div class="app-video-video-search-form-side">
            <form action="<?php echo beUrl('Video.Video.search'); ?>" method="get">
                <div class="be-row">
                    <div class="be-col"><input type="text" name="keywords" class="be-input" placeholder="<?php echo beLang('App.Video', 'VIDEO.ENTRY_SEARCH_KEYWORDS'); ?>"></div>
                    <div class="be-col-auto"><input type="submit" class="be-btn be-btn-major be-lh-175" value="<?php echo beLang('App.Video', 'VIDEO.SEARCH'); ?>"></div>
                </div>
            </form>

            <?php
            if ($this->config->keywords > 0) {
                $topKeywords = Be::getService('App.Video.Video')->getHotSearchKeywords($this->config->keywords);
                if (count($topKeywords) > 0) {
                    echo '<div class="be-mt-100 be-lh-175">' . beLang('App.Video', 'VIDEO.TOP_SEARCH') . ': ';
                    foreach ($topKeywords as $topKeyword) {
                        echo '<a href="'. beUrl('Video.Video.search', ['keywords' => $topKeyword]) .'">' . $topKeyword . '</a> &nbsp;';
                    }
                    echo '</div>';
                }
            }
            ?>
        </div>
        <?php
    }

}


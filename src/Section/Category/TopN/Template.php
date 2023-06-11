<?php

namespace Be\App\Video\Section\Category\TopN;

use Be\Be;
use Be\Theme\Section;

class Template extends Section
{

    public array $positions = ['middle', 'center'];

    public function display()
    {
        if ($this->config->enable === 0) {
            return;
        }

        $categories = Be::getService('App.Video.Category')->getCategories($this->config->quantity);
        if (count($categories) === 0) {
            return;
        }

        $this->css();

        echo '<div class="app-video-category-top-n">';
        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '<div class="be-container">';
        }

        if ($this->config->title !== '') {
            echo '<div class="app-video-category-top-n-title">';
            echo '<h3 class="be-h3">' . $this->config->title . '</h3>';
            echo '</div>';
        }

        $isMobile = \Be\Be::getRequest()->isMobile();
        $noImage = Be::getProperty('App.Video')->getWwwUrl() . '/images/category/no-image.jpg';

        echo '<div class="app-video-category-top-n-items">';
        foreach ($categories as $category) {

            if ($category->image === '') {
                $category->image = $noImage;
            }

            echo '<div class="app-video-category-top-n-item">';

            echo '<div class="be-ta-center app-video-category-top-n-item-image">';
            echo '<a href="' . beUrl('Shop.Category.products', ['id' => $category->id]) . '"';
            if (!$isMobile) {
                echo ' target="_blank"';
            }
            echo '>';

            echo '<img src="' . $category->image . '" alt="' . htmlspecialchars($category->name) . '">';

            echo '</a>';
            echo '</div>';


            echo '<div class="be-mt-100 be-ta-center">';
            echo '<a class="be-d-block be-t-ellipsis" href="' . beUrl('Video.Category.video', ['id' => $category->id]) . '"';
            if (!$isMobile) {
                echo ' target="_blank"';
            }
            echo '>';
            echo $category->name;
            echo '</a>';
            echo '</div>';


            echo '</div>';
        }
        echo '</div>';

        if ($this->position === 'middle' && $this->config->width === 'default') {
            echo '</div>';
        }
        echo '</div>';
    }

    private function css()
    {
        echo '<style type="text/css">';
        echo $this->getCssBackgroundColor('app-video-category-top-n');
        echo $this->getCssPadding('app-video-category-top-n');
        echo $this->getCssMargin('app-video-category-top-n');

        echo '#' . $this->id . ' .app-video-category-top-n-title {';
        echo 'margin-bottom: 2rem;';
        echo '}';

        $itemWidthMobile = '100%';
        $itemWidthTablet = '50%';
        $itemWidthDesktop = '33.333333333333%';
        $itemWidthDesktopXl = '';
        $itemWidthDesktopXxl = '';
        $itemWidthDesktopX3l = '';
        $cols = 3;
        if (isset($section->config->cols)) {
            $cols = $section->config->cols;
        }
        if ($cols >= 4) {
            $itemWidthDesktopXl = '25%';
        }
        if ($cols >= 5) {
            $itemWidthDesktopXxl = '20%';
        }
        if ($cols >= 6) {
            $itemWidthDesktopX3l = '16.666666666666%';
        }
        echo $section->getCssSpacing('app-video-category-top-n-items', 'app-video-category-top-n-item', $itemWidthMobile, $itemWidthTablet, $itemWidthDesktop, $itemWidthDesktopXl, $itemWidthDesktopXxl, $itemWidthDesktopX3l);

        echo '</style>';
    }

}

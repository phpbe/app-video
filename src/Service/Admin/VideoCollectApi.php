<?php

namespace Be\App\Video\Service\Admin;

use Be\Be;
use Be\Config\ConfigHelper;
use Be\Util\Crypt\Random;

class VideoCollectApi
{

    public function getConfig()
    {
        $config = Be::getConfig('App.Video.VideoCollectApi');
        if ($config->token === '') {
            $config->token = Random::simple(32);
            ConfigHelper::update('App.Video.VideoCollectApi', $config);
            if (Be::getRuntime()->isSwooleMode()) {
                Be::getRuntime()->reload();
            }
        }
        return $config;
    }

    /**
     * 采集配置 - 切换启用状态
     *
     * @return int
     */
    public function toggleEnable(): int
    {
        $config = Be::getConfig('App.Video.VideoCollectApi');
        $config->enable = (int)(!$config->enable);
        ConfigHelper::update('App.Video.VideoCollectApi', $config);
        if (Be::getRuntime()->isSwooleMode()) {
            Be::getRuntime()->reload();
        }
        return $config->enable;
    }

    /**
     * 采集配置 - 重置 Token
     *
     * @return string
     */
    public function resetToken(): string
    {
        $config = Be::getConfig('App.Video.VideoCollectApi');
        $config->token = Random::simple(32);
        ConfigHelper::update('App.Video.VideoCollectApi', $config);

        if (Be::getRuntime()->isSwooleMode()) {
            Be::getRuntime()->reload();
        }

        return $config->token;
    }

}

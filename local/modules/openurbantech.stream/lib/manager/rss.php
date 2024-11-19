<?php

namespace Openurbantech\Stream\Manager;

use Bitrix\Main,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Rss extends \Openurbantech\Stream\Manager {

    function __construct($sourceObject, $elementManager = null, $streamManager = null) {
        parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
    }

    public function processContent() {
        $this->content = $this->request();
    }

    public function processItems($content) {
        $items = [];
        if ($content) {
            try {
                $rss = new \SimpleXMLElement($content);
                if (!empty($rss->channel->item)) {
                    foreach ($rss->channel->item as $item) {
                        $link = trim($item->link);
                        if (!preg_match('/^http(s)?:/', $link)) {
                            $link = $rss->channel->link . $link;
                        }
                        $items[] = [
                            'title' => $item->title,
                            'url' => $link,
                            'date' => $item->pubDate,
                        ];
                    }
                }
                $this->setItems($items);
            } catch (\Exception $e) {
                var_dump($this->getSourceUrl());
                var_dump($e->getMessage());
            }
        } else {
            var_dump('Content is');
            var_dump($content);
        }
    }

}

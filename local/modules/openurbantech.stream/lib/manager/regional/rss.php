<?php

namespace Openurbantech\Stream\Manager\Regional;

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
        $searchArray = [
            "велосипед",
            "самокат",
            "велопробег",
            "велогон",
            "триатлон",
            "кикшеринг",
            "кикшейринг",
            "велодорож",
            "велополос",
            "вело(\-)?план",
            "вело(\-)?стратег",
            "вело(\-)?инфра",
            "вело(\-)?маршрут",
        ];
        $searchString = '/('.join('|', $searchArray).')/iu';
        if ($content) {
            try {
                $rss = new \SimpleXMLElement($content);
                if (!empty($rss->channel->item)) {
                    foreach ($rss->channel->item as $item) {
                        if (
                            preg_match($searchString, $item->title) || preg_match($searchString, $item->description)
                        ) {
                            $link = trim($item->link);
                            if (!preg_match('/^http(s)?:/', $link)) {
                                $link = $rss->channel->link . $link;
                            }
                            $items[] = [
                                'title' => trim($item->title),
                                'url' => $link,
                                'date' => $item->pubDate,
                            ];
                        }
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

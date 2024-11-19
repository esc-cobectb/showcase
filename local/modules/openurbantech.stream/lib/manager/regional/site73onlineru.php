<?php

namespace Openurbantech\Stream\Manager\Regional;

use Bitrix\Main,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Site73onlineru extends \Openurbantech\Stream\Manager {

    function __construct($sourceObject, $elementManager = null, $streamManager = null) {
        parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
    }

    public function processContent() {
        $this->content = $this->request();
    }

    public function processItems($content) {
        $items = [];

        $doc = new \DOMDocument();
        $doc->loadHTML($content);

        $xpath = new \DOMXpath($doc);

        $sourceUrl = $this->getSourceUrl();
        $linkParts = parse_url($sourceUrl);
        $site = sprintf('%s://%s/', $linkParts['scheme'], $linkParts['host']);

        $elements = $xpath->query("//section[contains(@class, 'content')]//a");
        foreach ($elements as $index => $node) {
            $url = $node->getAttribute("href");
            $title = trim($node->nodeValue);
            if (!empty($url) && !empty($title) && strlen($title) > 6) {
                $items[$index]['title'] = $title;
                $items[$index]['url'] = $site . $url;
                $items[$index]['date'] = date('d.m.Y H:i:s');
            }
        }
        $this->setItems($items);
    }

}

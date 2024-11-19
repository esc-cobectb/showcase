<?php

namespace Openurbantech\Stream\Manager\Regional;

use Bitrix\Main,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Kubnewsru extends \Openurbantech\Stream\Manager {

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
        $site = sprintf('%s://%s', $linkParts['scheme'], $linkParts['host']);

        // .rubrik-list a.card
        $elements = $xpath->query("//div[contains(@class, 'rubrik-list')]//a[contains(@class, 'card')]");
        foreach ($elements as $index => $node) {
            $url = $node->getAttribute("href");
            if (!empty($url)) {
                $items[$index]['url'] = $site . $url;
                $items[$index]['date'] = date('d.m.Y H:i:s');
            }
        }
        // .rubrik-list div.card__description
        $elements = $xpath->query("//div[contains(@class, 'rubrik-list')]//div[contains(@class, 'card__description')]");
        foreach ($elements as $index => $node) {
            $title = trim($node->nodeValue);
            if (!empty($title)) {
                $items[$index]['title'] = $title;
            }
        }
        $this->setItems($items);
    }

}

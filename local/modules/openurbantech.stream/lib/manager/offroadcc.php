<?php
namespace Openurbantech\Stream\Manager;

use Bitrix\Main,
	Bitrix\Main\Web\HttpClient,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Offroadcc extends \Openurbantech\Stream\Manager
{

	function __construct($sourceObject, $elementManager = null, $streamManager = null) 
	{
    	parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
   	}

	public function processContent()
	{
		$this->content = $this->request();
	}

	public function processItems($content)
	{
		$items = [];

		$doc = new \DOMDocument();
		$doc->loadHTML($content);

		$xpath = new \DOMXpath($doc);

		$elements = $xpath->query("//div[@class='view-content']//div[@class='title-h3']//a");
		foreach ($elements as $index => $node) {
			$url = $node->getAttribute("href");
			$title = trim($node->nodeValue);
			if(!empty($url)){
				$items[$index]['url'] = 'https://off.road.cc'.$url;
				$items[$index]['date'] = date('d.m.Y H:i:s');
				$items[$index]['title'] = $title;
			}
			
		}
		$this->setItems($items);
	}
}
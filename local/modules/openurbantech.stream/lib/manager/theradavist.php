<?php
namespace Openurbantech\Stream\Manager;

use Bitrix\Main,
	Bitrix\Main\Web\HttpClient,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Theradavist extends \Openurbantech\Stream\Manager
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

		$elements = $xpath->query("//article//h2//a|//article//h3//a");
		foreach ($elements as $index => $node) {
			$url = $node->getAttribute("href");
			if(!empty($url)){
				$items[$index]['url'] = $url;
				$items[$index]['date'] = date('d.m.Y H:i:s');
			}
			$title = trim($node->nodeValue);
			if(!empty($title) && !empty($items[$index])){
				$items[$index]['title'] = $title;
			}
		}
		$this->setItems($items);
	}
}
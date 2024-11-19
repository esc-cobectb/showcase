<?

namespace Openurbantech\Mapmyride;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Api {

	const API_URL = 'https://api.ua.com';
	protected $url;
	protected $accessToken;
	protected $appID;
	protected $itemID;

	protected $httpTimeout = 10;

	function __construct($accessToken = false, $appID = false){
		$this->setAccessToken($accessToken);
		if ($appID === false){
			$appID = trim(\Bitrix\Main\Config\Option::get('openurbantech.mapmyride','client_id'));
		}
		$this->appID = $appID;
	}

	public function setItemID($itemID){
		$this->itemID = $itemID;
	}	

	public function setAccessToken($accessToken){
		$this->accessToken = $accessToken;
	}	

	public function setAppID($appID){
		$this->appID = $appID;
	}	

	public function setUrl($url){
		$this->url = $url;
	}

	public function prepareUrl($params = []){
		$suffix = '';
		if(!empty($params)){
			$suffix = '?'.http_build_query($params);
		}
		if(!empty($this->itemID)){
			$result = sprintf('%s%s%s/%s', self::API_URL, $this->url, $this->itemID, $suffix);
		} else {
			$result = sprintf('%s%s%s', self::API_URL, $this->url, $suffix);
		}
		return $result;
	}

	public function getOwnerCollection($ownerID, $params = []){
		$result = NULL;
		if(\Bitrix\Main\Loader::includeModule('socialservices')){
			$authManager = new \Openurbantech\Mapmyride\Auth();
			$userAuthData = $authManager->getAuthByOwnerID($ownerID);
			$token = $userAuthData['OATOKEN'];
			$this->setAccessToken($token);
			$params['user'] = $ownerID;
			$result = $this->get($params);
		}
		return $result;
	} 

	public function getItem($ownerID, $itemID, $params = []){
		$result = NULL;
		if(\Bitrix\Main\Loader::includeModule('socialservices')){
			$this->setItemID($itemID);
			$authManager = new \Openurbantech\Mapmyride\Auth();
			$userAuthData = $authManager->getAuthByOwnerID($ownerID);
			$token = $userAuthData['OATOKEN'];
			$this->setAccessToken($token);
			$result = $this->get($params);
		}
		return $result;
	} 

	public function get($params = []){
		if ($this->accessToken === false)
		{
			return false;
		}

		$headers = array(
	      'Authorization: Bearer ' . $this->accessToken,
	      'Api-Key: ' . $this->appID,
	    );

		$httpClient = new \Bitrix\Main\Web\HttpClient(array(
			"socketTimeout" => $this->httpTimeout,
			"streamTimeout" => $this->httpTimeout,
		));

		$httpClient->setHeader('Authorization', 'Bearer ' . $this->accessToken);
		$httpClient->setHeader('Api-Key', $this->appID);

		$url = $this->prepareUrl($params);
		$result = $httpClient->get($url);
		try {
			$result = \Bitrix\Main\Web\Json::decode($result);
		} catch (\Bitrix\Main\ArgumentException $e) {
			$result = [];
		}

		return $result;
	}

    public function getRawBody() {
        return trim(file_get_contents('php://input'));
    }

    public function getRequest() {
        $result = NULL;
        $raw = $this->getRawBody();
        if(!empty($raw)){
        	try {
        		$result = \Bitrix\Main\Web\Json::decode($raw);
        	} catch (\Exception $e){

        	}
        } else if(!empty($_REQUEST)) {
            $result = $_REQUEST;
        }
        return $result;
    }

}
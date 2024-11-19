<?php
namespace Openurbantech\Strava;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Api
{
    public $debug = false;
    public $proxyUnauthorized = false;

    protected $clientID = '';
    protected $clientSecret = '';
    protected $clientToken = '';
    protected $refreshToken = '';
    protected $baseURL = 'https://www.strava.com/api';
    protected $patterns = array(
        'segment_explore' => '/v3/segments/explore',
        'segment' => '/v3/segments/#id#',
        'segment_leaderboard' => '/v3/segments/#id#/leaderboard',
        'segment_efforts' => '/v3/segments/#id#/all_efforts',
        'activity' => '/v3/activities/#id#',
        'activity_streams' => '/v3/activities/#id#/streams',
        'athlete' => '/v3/athlete',
		'athlete_activities' => '/v3/athlete/activities',
        'token' => '/v3/oauth/token',
    );

    public $defaultPattern = '';
    public $defaultBounds = '47.056160,142.648541,46.863730,142.807032';
    public $defaultType = 'Ride';

    protected $segmentManager;
    protected $segmentStatsManager;
    protected $segmentStatsDailyManager;
    protected $ridersManager;
    protected $citiesManager;

    public $lastError = '';

    protected $requestLimit = 61;

	public $cityId = 0;
    protected $cities = [];

    function __construct() {
        $clientID = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'client_id', "");
        $clientToken = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'client_token', "");
        $clientSecret = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'client_secret', "");
        $refreshToken = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'refresh_token', "");
        $proxyUnauthorized = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_unauthorized', "");
        if(\Bitrix\Main\Loader::includeModule('openurbantech.server') && $proxyUnauthorized === 'Y'){
            $this->proxyUnauthorized = true;
        }

        $this->setClientID($clientID);
        $this->setClientToken($clientToken);
        $this->setClientSecret($clientSecret);
        $this->setRefreshToken($refreshToken);

        \Bitrix\Main\Loader::includeModule('socialservices');
    }

    public function getDefaultBoundsArray()
    {
        $boundsArray = explode(',', $this->defaultBounds);
        $boundsArray = array_map( 
            function($bound) { 
                return (float) trim($bound); 
            }, 
            $boundsArray 
        ); 
        return $boundsArray;
    }

    public function setClientID($clientID)
    {
        $this->clientID = $clientID;
    }

    public function setClientToken($clientToken, $save = false)
    {
        $this->clientToken = $clientToken;
        if($save) {
            \Bitrix\Main\Config\Option::set('openurbantech.strava', 'client_token', $clientToken);
        }
    }

    public function setRefreshToken($refreshToken, $save = false)
    {
        $this->refreshToken = $refreshToken;
        if($save) {
            \Bitrix\Main\Config\Option::set('openurbantech.strava', 'refresh_token', $refreshToken);
        }
    }

    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }


    public function getRequestLimit()
    {
        return $this->requestLimit;
    }

    public function canRequest()
    {
        return ($this->requestLimit > 1); 
    }

    public function getRawBody()
    {
        return trim(file_get_contents('php://input'));
    }

    public function jsonValidate($jsonString, $asArray)
    {
        if(!empty($jsonString)){ 
            // $json = json_decode($jsonString, $asArray);
            $json = \Bitrix\Main\Web\Json::decode($jsonString, $asArray);

            if (json_last_error() != JSON_ERROR_NONE) {
                $json = [];
            }
        } else {
            $json = [];
        }

        return $json;
    }

    public function getRequest()
    {
        $result = [];
        if ($data = $this->jsonValidate($this->getRawBody(), true)) {
            $result = $data;
        }
        if(empty($this->getRawBody()) && !empty($_REQUEST))
        {
            $result = $_REQUEST;
        }
        return $result;
    }

    public function getManager($name, $highloadID)
    {
        if(empty($this->$name)){
            if(\Bitrix\Main\Loader::IncludeModule('highloadblock')){
                $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($highloadID)->fetch();
                if (!empty($hlblock))
                {
                    $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
                    $entity_data_class = $entity->getDataClass();
                    $this->$name = new $entity_data_class;
                } 
            }
        }
        return $this->$name;
    }

    public function getSegmentManager()
    {
        return new \Openurbantech\Strava\Model\SegmentsTable();
    }

    public function getSegmentStatsManager()
    {
        return new \Openurbantech\Strava\Model\SegementsStatDailyTable();
    }

    public function getSegmentStatsDailyManager()
    {
        return new \Openurbantech\Strava\Model\SegementsStatDailyTable();
    }

    public function getRidersManager()
    {
        return new \Openurbantech\Strava\Model\RideTable();
    }

    public function getCitiesManager()
    {
        return new \Openurbantech\Strava\Model\CityTable();
    }

    public function countRider($effort)
    {
        $manager = $this->getRidersManager();
        $obDateTime = new \DateTime($effort->start_date);
        $obBitrixDateTime = \Bitrix\Main\Type\DateTime::createFromPhp($obDateTime);
        $obBitrixDateTime->setTimeZone(new \DateTimeZone('Europe/Kaliningrad'));
		$obBitrixDate = \Bitrix\Main\Type\Date::createFromPhp($obDateTime);
        $row = [
            'UF_DATE' => $obBitrixDate,
            'UF_RIDER_ID' => $effort->athlete->id,
        ];
        $obEffort = $manager->getList(['filter' => $row, 'limit' => 1]);
        if($obEffort->fetch())
        {
            // Do nothing
        }
        else
        {
            $manager->add($row);
        }
    }

    public function getCitiesArray()
    {
        if(empty($this->cities))
        {
            $cities = [];
            $manager = $this->getCitiesManager();
            $obCities = $manager->getList();
            while ($city = $obCities->fetch()) 
            {
                $cities[$city['ID']] = $city;
            }
            $this->cities = $cities;
        }
        return $this->cities;
    }

    public function getTimezoneByCityId($cityId)
    {
        $timezone = 'Europe/Kaliningrad';
        $cities = $this->getCitiesArray();
        if(isset($cities[$cityId]))
        {
            $timezone = $cities[$cityId]['UF_TIMEZONE'];
        }
        return $timezone;
    }

    function getCityIdBySegment($segment)
    {
        $cities = $this->getCitiesArray();
        foreach ($cities as $cityId => $city) 
        {
            if(\Openurbantech\Strava\Segment::isInsideBounds($segment, $city['UF_BOUNDS']))
            {
                return $cityId;
            }
        }
        return false;
    }

    function getCityIdByPoint($point)
    {
        $cities = $this->getCitiesArray();
        foreach ($cities as $cityId => $city) 
        {
            if(\Openurbantech\Strava\Segment::isInsideBounds($point, $city['UF_BOUNDS']))
            {
                return $cityId;
            }
        }
        return false;
    }

    function setCityId($cityId) {
        $citiesArray = $this->getCitiesArray();
        $city = $citiesArray[$cityId];
        $this->cityId = $cityId;
        $this->defaultBounds = $city['UF_BOUNDS'];
    }


    public function countEntry($entry, $segmentID = 0, $fromPoint = [], $toPoint = [], $way = [])
    {
        return true;
        $manager = $this->getSegmentStatsManager();
        $obDateTime = new \DateTime($entry->start_date);
        $obBitrixDateTime = \Bitrix\Main\Type\DateTime::createFromPhp($obDateTime);
		$timezone = $this->getTimezoneByCityId($this->cityId);
        $obBitrixDateTime->setTimeZone(new \DateTimeZone($timezone));
        $row = [
            'UF_DATETIME' => $obBitrixDateTime,
            'UF_RIDER' => $entry->athlete_name,
            'UF_STRAVA_ID' => $segmentID,
        ];
        $obStats = $manager->getList(['filter' => $row, 'limit' => 1]);
        if($obStats->fetch())
        {
            // Do nothing 
        }
        else
        {
            if(!empty($way))
            {
                $row['UF_POLYLINE'] = \Openurbantech\Strava\Activity::polylineEncode($way);
            }
            if(!empty($fromPoint))
            {
                $row['UF_FROM'] = join(',', $fromPoint);
            }
            if(!empty($toPoint))
            {
                $row['UF_TO'] = join(',', $toPoint);
            }
			$obDate = new \DateTime($entry->start_date);
			$obBitrixDate = \Bitrix\Main\Type\Date::createFromPhp($obDate);
            $row['UF_DATE'] = $obBitrixDate;
			$row['UF_CITY_ID'] = $this->cityId > 0 ? $this->cityId : false;
            $manager->add($row);
        }
    }

    public function getBaseUrl(){
        return $this->baseURL;
    }

    public function getUrl($sPatternName = '', $params = [], $useAccessToken = true){
        $result = false;
        if(empty($sPatternName)){
            $sPatternName = $this->defaultPattern;
        }
        if(!empty($sPatternName)){
            if(array_key_exists($sPatternName, $this->patterns)){
                $sPattern = $this->patterns[$sPatternName];
                if($useAccessToken){
                    $params['access_token'] = $this->clientToken;
                }
                if(!empty($params)) {
                    $query = http_build_query($params);
                    $sURL = sprintf('%s%s?%s',$this->baseURL,$sPattern, $query);
                } else {
                    $sURL = sprintf('%s%s',$this->baseURL,$sPattern);
                }
                $result = $sURL;
            }
        }
        return $result;
    }

    public function replaceParams($arParams = [], $sString = '') {
        if(!empty($arParams)) {     
            foreach ($arParams as $sKey => $sValue) {
                $sString = str_replace('#'.$sKey.'#', $sValue, $sString);
            }
        }
        return $sString;
    }

    public function exploreSegments($params = [])
    {
        $result = [];
        $url = $this->getUrl('segment_explore', $params);
        $response = $this->call($url, 1, true);
        foreach($response->segments as $segment)
        {
            $segment->name = $this->convert($segment->name);
            $result[] = $segment;
        }
        return $result;
    }

    public function getSegment($id)
    {
        return $this->getElementByID($id, 'segment');
    }

    public function getSegmentEfforts($id, $params = [], $all = false)
    {
        $result = [];
        $params['per_page'] = !empty($params['per_page']) ? $params['per_page'] : 100; 
        $params['page'] = !empty($params['page']) ? $params['page'] : 1;
        $active = true; 
        while($active)
        {
            $active = false;
            $url = $this->getUrl('segment_efforts', $params);
            $inputParams = array('id' => $id);
            $url = $this->replaceParams($inputParams, $url);
            $response = $this->call($url);
            if(empty($response->errors))
            {
                if(!empty($response))
                {
                    foreach($response as $entry)
                    {
                        $entry->name = $this->convert($entry->name);
                        $entry->segment->name = $this->convert($entry->segment->name);
                        $result[] = $entry;
                    }
                    if($all)
                    {
                        $active = (count($response) == $params['per_page']);
                        $params['page']++;
                    }
                }
            }
            else 
            {
                $this->lastError = $response->errors;
            }
        }
        return $result;
    }

    public function getElementByID($nID = 0, $sPatternName = ''){
        $result = false;
        if($nID > 0){
            $arParams = [
                'id' => $nID
            ];
            $sPatternName = empty($sPatternName) ? $this->defaultPattern : $sPatternName;
            if(!empty($sPatternName)){
                $sURL = $this->getUrl($sPatternName);
                $sURL = $this->replaceParams($arParams,$sURL);
                $result = $this->call($sURL, 2, true);
            }
        }
        return $result;
    }

    public function getByID($nID = 0){
        $obResult = new \CDBResult();
        $obJson = $this->getElementByID($nID);
        if(!empty($obJson)){
            $arJson = get_object_vars($obJson);
            $arJson['name'] = $this->convert($arJson['name']);
            $obResult->initFromArray(array($arJson));
        }
        return $obResult;
    }

    public function getNewToken($attempts = 1) {
        $refreshStatus = null;
        if($attempts > 0) {
            $httpClient = new \Bitrix\Main\Web\HttpClient(array(
                'socketTimeout' => 30,
                'streamTimeout' => 30,
            ));

            $query = array(
                "client_id" => $this->clientID,
                "client_secret" => $this->clientSecret,
                "grant_type" => "refresh_token",
                "refresh_token" => $this->refreshToken,
            );
            $url = $this->getUrl('token', [], false);
            $result = $httpClient->post($url, $query);
            try
            {
                $result = \Bitrix\Main\Web\Json::decode($result);
                if(!empty($result['access_token'])) {
                    $this->setClientToken($result['access_token'], true);
                }
                if(!empty($result['refresh_token'])) {
                    $this->setRefreshToken($result['refresh_token'], true);
                }
                $refreshStatus = true;
            }
            catch(\Bitrix\Main\ArgumentException $e)
            {
                $refreshStatus = false;
            }
        }
        return $refreshStatus;
    }

    public function call($sURL = '', $attempts = 2, $useAccessToken = false){
        $result = false;
        if(!empty($sURL) && $this->canRequest()){
            $c = curl_init();
            if($useAccessToken) {
                $authorization = sprintf("Authorization: Bearer %s", $this->clientToken);
                curl_setopt($c, CURLOPT_HTTPHEADER, [$authorization]);
            }
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c, CURLOPT_VERBOSE, 1);
            curl_setopt($c, CURLOPT_HEADER, 1);
            curl_setopt($c, CURLOPT_URL, $sURL);

            $useProxy = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_all');
            if($useProxy){
                $proxyIP =  \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_ip');
                $proxyPort =  \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_http_port');
                $proxyLogin =  \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_login');
                $proxyPass =  \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_pass');

                curl_setopt($c, CURLOPT_PROXY, $proxyIP.':'.$proxyPort);
                curl_setopt($c, CURLOPT_PROXYUSERPWD, $proxyLogin.':'.$proxyPass);
                curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
            }
            $response = curl_exec($c);
            if($useProxy){
                $headerSize = strlen($response) - curl_getinfo($c, CURLINFO_SIZE_DOWNLOAD);
            } else {
                $headerSize = curl_getinfo($c, CURLINFO_HEADER_SIZE);
            }
            $header = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);
            curl_close($c);
            $obJson = json_decode($body);
            
            if($this->debug) {
                var_dump($header);
                var_dump($body);
            }
            if(!empty($obJson)){
                $result = $obJson;
            }
            $this->requestLimit--;
            if($result->message === 'Authorization Error') {
                if($this->getNewToken($attempts--)) {
                    return $this->call($sURL, $attempts);
                }
            }
            if($result->message === 'Rate Limit Exceeded') {
                $this->requestLimit = 0;
            }
            
        }
        return $result;
    }


    public function convert($sValue){
        $bUTF = defined('BX_UTF');
        if(!$bUTF){
            $sValue = iconv("utf-8","windows-1251",$sValue);
        }
        return $sValue;
    }

    public static function agentExploreSegments($bounds = '', $type = '', $cityId = 0)
    {
        $stravaAPI = new self();
        if(empty($bounds))
        {
            $bounds = $stravaAPI->defaultBounds;
        }
        if(empty($type))
        {
            $type = $stravaAPI->defaultType == 'Ride' ? 'riding' : '';
        }
        $params = [
            'bounds' => $bounds,
        ];
        if(!empty($type))
        {
            $params['activity_type'] = $type;
        }
        $segments = $stravaAPI->exploreSegments($params);
        $segmentManager = $stravaAPI->getSegmentManager();
        $segmentsArray = [];
        $obSegments = $segmentManager->getList();
        while($existSegment = $obSegments->fetch())
        {
            $segmentsArray[$existSegment['UF_STRAVA_ID']] = [
                'ID' => $existSegment['ID'], 
                'UF_POLYLINE' => $existSegment['UF_POLYLINE'],
				'UF_CITY_ID' => $existSegment['UF_CITY_ID'],
                'UF_METERS' => $existSegment['UF_METERS'],
            ];
        }
        foreach($segments as $segment)
        {
            $segmentID = (string) $segment->id;
            if(!array_key_exists($segmentID, $segmentsArray))
            {
                $segmentData = [
                    'UF_STRAVA_ID' => $segmentID,
                    'UF_NAME' => $segment->name,
                    'UF_POLYLINE' => $segment->points,
					'UF_CITY_ID' => $cityId > 0 ? $cityId : false,
                    'UF_METERS' => $segment->distance,
                ];

                $segmentManager->add($segmentData);
                $segmentsArray[$segmentID] = $segmentData;
            } 
            else if(empty($segmentsArray[$segmentID]['UF_POLYLINE'])) 
            {
                $segmentData = [
                    'UF_POLYLINE' => $segment->points,
                    'UF_METERS' => $segment->distance,
                ];
                $segmentManager->update($segmentsArray[$segmentID]['ID'], $segmentData);
            }
        }
        return __METHOD__.'("'.$bounds.'","'.$type.'",'.$cityId.');';
    }

    public function explodeBySquares($squares = 5, $bounds = '')
    {
        $result = [];
        if(empty($bounds))
        {
            $bounds = $this->defaultBounds;
        }
        $boundsArray = explode(',', $bounds);
        $height = $boundsArray[2] - $boundsArray[0]; 
        $width = $boundsArray[3] - $boundsArray[1];
        $verticalStep = round($height / $squares, 4);
        $horizontalStep = round($width / $squares, 4);
        $bottomLeft = [$boundsArray[0], $boundsArray[1]];
        $stringArray = [];
        $string = '';
        for($v = 0; $v < $squares; $v++)
        {
            for($h = 0; $h < $squares; $h++)
            {
                $stringArray[0] = $bottomLeft[0] + $v*$verticalStep;
                $stringArray[1] = $bottomLeft[1] + $h*$horizontalStep;
                $stringArray[2] = $bottomLeft[0] + ($v + 1)*$verticalStep;
                $stringArray[3] = $bottomLeft[1] + ($h + 1)*$horizontalStep;
                $string = join(',', $stringArray);
                $result[] = $string;
            }
        }
        return $result;
    }

    public function exploreBySquares($squares = 5, $bounds = '', $type = '', $cityId = 0)
    {
        if(empty($bounds))
        {
            $bounds = $this->defaultBounds;
        }
        if(empty($type))
        {
            $type = $stravaAPI->defaultType == 'Ride' ? 'riding' : '';
        }
        $squaresArray = $this->explodeBySquares($squares, $bounds);
        foreach($squaresArray as $bound)
        {
            $this->agentExploreSegments($bound, $type, $cityId);
        }
    }

    public function getField($object, $field){
        if(isset($object->{$field})){
            return $object->{$field};
        } else if (is_array($object) && isset($object[$field])) {
            return $object[$field];
        }
        return null;
    }

    public function saveSegmentStatsDaily($stravaSegment, $cityID){
        $segmentStatsDailyManager = $this->getSegmentStatsDailyManager();
        $segmentID = $this->getField($stravaSegment, 'id');
        if($segmentID) {
            $effortCount = $this->getField($stravaSegment, 'effort_count');
            $athleteCount = $this->getField($stravaSegment, 'athlete_count');
            
            $obBitrixDate = new \Bitrix\Main\Type\DateTime();
            $timezone = $this->getTimezoneByCityId($cityID);
            $obBitrixDate->setTimeZone(new \DateTimeZone($timezone));
            //$obBitrixDate->add('-1 day');
            $params = [
                'filter' => [
                    'UF_STRAVA_ID' => $segmentID,
                    'UF_DATE' => $obBitrixDate,
                ]
            ];
            $stats = $segmentStatsDailyManager->getList($params);
            $dailyData = [
                'UF_EFFORTS' => $effortCount,
                'UF_ATHLETES' => $athleteCount,
            ];
            if($row = $stats->fetch()) {
                $segmentStatsDailyManager->update($row['ID'],$dailyData);
            } else {
                $dailyData['UF_STRAVA_ID'] = $segmentID;
                $dailyData['UF_DATE'] = $obBitrixDate;
                $segmentStatsDailyManager->add($dailyData);
            }
        }
    }

    public static function agentCountRiders()
    {
        $stravaAPI = new self();

        $segmentManager = $stravaAPI->getSegmentManager();
        $segmentsArray = [];
        $segmentsParams = [
            'filter' => [
                'UF_ACTIVE' => 1,
            ],
            'order' => [
                'UF_UPDATED' => 'ASC',
            ]
        ];
        $obSegments = $segmentManager->getList($segmentsParams);
        while($existSegment = $obSegments->fetch())
        {
            $segmentsArray[$existSegment['ID']] = [
				'SEGMENT_ID' => $existSegment['UF_STRAVA_ID'],
				'CITY_ID' => $existSegment['UF_CITY_ID'],
			];
        }
        foreach($segmentsArray as $databaseID => $segment)
        {
			$segmentID = $segment['SEGMENT_ID'];
			$stravaAPI->cityId = $segment['CITY_ID'];
        	if($stravaAPI->canRequest())
        	{
                if($stravaAPI->proxyUnauthorized){
                    $proxyClass = new \Openurbantech\Server\Proxy();
                    $module = 'openurbantech.strava';
                    $class = '\Openurbantech\Strava\Api';
                    $method = 'getSegment';
                    $args = [$segment['SEGMENT_ID']];
                    $stravaSegment = $proxyClass->send($module, $class, $method, $args);
                } else {
                    $stravaSegment = $stravaAPI->getSegment($segment['SEGMENT_ID']);
                }
				$stravaAPI->saveSegmentStatsDaily($stravaSegment, $segment['CITY_ID']);
                $segmentManager->update($databaseID, ['UF_UPDATED' => new \Bitrix\Main\Type\DateTime()]);
        	}
        }
        return sprintf('\%s();',__METHOD__);
    }
}
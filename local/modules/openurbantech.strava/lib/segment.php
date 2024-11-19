<?php
namespace Openurbantech\Strava;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Segment extends Api
{
	public $defaultPattern = 'segment';

	public function updatePolyline($segmentID = 0)
	{
		if($segmentID > 0)
		{
			$segment = $this->getElementByID($segmentID);
			$tableManager = $this->getSegmentManager();
			$obSegment = $tableManager->getList(['filter' => ['UF_STRAVA_ID' => $segmentID]]);
			$map = $this->getField($segment, 'map');
			if($existsSegment = $obSegment->fetch())
			{
				$segmentData = [
					'UF_POLYLINE' =>  $this->getField($map, 'polyline'),
					'UF_METERS' => $this->getField($segment, 'distance'),
					'UF_NAME' => $this->getField($segment, 'name'),
				];
				$tableManager->update($existsSegment['ID'], $segmentData);
			}
			else 
			{
				$segmentData = [
					'UF_STRAVA_ID' => $segmentID,
					'UF_NAME' => $this->getField($segment, 'name'),
					'UF_POLYLINE' => $this->getField($map, 'polyline'),
					'UF_METERS' => $this->getField($segment, 'distance'),
				];
				$tableManager->add($segmentData);
			}
		}
	}

	public function updateDistance($segmentID = 0)
	{
		if($segmentID > 0)
		{
			$segment = $this->getElementByID($segmentID);
			$tableManager = $this->getSegmentManager();
			$obSegment = $tableManager->getList(['filter' => ['UF_STRAVA_ID' => $segmentID]]);
			if($existsSegment = $obSegment->fetch())
			{
				$segmentData = [
					'UF_METERS' =>  $segment->distance,
				];
				$tableManager->update($existsSegment['ID'], $segmentData);
			}
			else 
			{
				$segmentData = [
					'UF_STRAVA_ID' => $segmentID,
					'UF_NAME' => $this->convert($segment->name),
					'UF_POLYLINE' => $segment->map->polyline,
					'UF_METERS' => $segment->distance,
				];
				$tableManager->add($segmentData);
			}
		}
	}

	public static function isPointInsideBounds($pointLatLng, $boundsArray)
	{
		$result = false;
		if($boundsArray[0] > $boundsArray[2]){
			$top = $boundsArray[0];
			$bottom = $boundsArray[2];
		} else {
			$top = $boundsArray[2];
			$bottom = $boundsArray[0];
		}
		if($boundsArray[1] > $boundsArray[3]){
			$ritgh = $boundsArray[1];
			$left = $boundsArray[3];
		} else {
			$ritgh = $boundsArray[3];
			$left = $boundsArray[1];
		}
		if($pointLatLng[0] >= $bottom && $pointLatLng[0] <= $top)
		{
			if($pointLatLng[1] >= $left && $pointLatLng[1] <= $ritgh)
			{
				$result = true;
			}
		}
		return $result;
	}

	public static function isInsideBounds($segment, $bounds)
	{
		$startLatLng = $segment->start_latlng;
		$endLatLng = $segment->end_latlng;
		$boundsArray = explode(',', $bounds);
		$boundsArray = array_map( 
	        function($bound) { 
	        	return (float) trim($bound); 
	        }, 
        	$boundsArray 
		); 
		return self::isPointInsideBounds($startLatLng, $boundsArray) || self::isPointInsideBounds($endLatLng, $boundsArray);

	}	

	public static function getPopularByCityID($cityID = null){
		$manager = new self();
		$result = [];
		$segmentsArray = [];
		$segmentsDistances = [];
		$segmentsAthletes = [];
		$segmentsEfforts = [];
		$tableManager = $manager->getSegmentManager();
		$dailyManager = $manager->getSegmentStatsDailyManager();
		$params = [
			'filter' => [
				'UF_ACTIVE' => 1,
			],
		];
		if(!empty($cityID)){
			$params['filter']['UF_CITY_ID'] = $cityID;
		}
		$segments = $tableManager->getList($params);
		while ($segment = $segments->fetch()) {
			$segmentsArray[$segment['UF_STRAVA_ID']] = $segment;
		}
		if(!empty($segmentsArray)){		
			$params = [
				'filter' => [
					'UF_STRAVA_ID' => array_keys($segmentsArray),
					'>UF_EFFORTS' => 0,
				],
				'order' => [
					'UF_DATE' => 'DESC',
				],
				'limit' => count($segmentsArray) * 3,
			];
			$daily = $dailyManager->getList($params);
			while ($day = $daily->fetch()) {
				$segmentID = $day['UF_STRAVA_ID'];
				$athletes = $day['UF_ATHLETES'];
				$efforts = $day['UF_EFFORTS'];
				$distance = $segmentsArray[$segmentID]['UF_METERS'];
				$km = $distance * $efforts;
				if(empty($segmentsDistances[$segmentID]) || $segmentsDistances[$segmentID] < $km){
					$segmentsDistances[$segmentID] = $km;
					$segmentsEfforts[$segmentID] = $efforts;
				}
				if(empty($segmentsAthletes[$segmentID]) || $segmentsAthletes[$segmentID] < $athletes){
					$segmentsAthletes[$segmentID] = $athletes;
				}
			}

			foreach ($segmentsDistances as $segmentID => $km) {
				$result[] = [
					'SEGMENT_ID' => $segmentsArray[$segmentID]['UF_STRAVA_ID'],
					'SEGMENT_NAME' => $segmentsArray[$segmentID]['UF_NAME'],
					'SEGMENT_DISTANCE' => $segmentsArray[$segmentID]['UF_METERS'],
					'CROSS_EFFORTS' => $segmentsEfforts[$segmentID],
					'CROSS_DISTANCE' => $km,
					'CROSS_ATHLETES' => $segmentsAthletes[$segmentID],
				];
			}

			usort($result, function ($a, $b) {
			    if ($a['CROSS_DISTANCE'] == $b['CROSS_DISTANCE']) {
			        return 0;
			    }
			    return ($a > $b) ? -1 : 1;
			});
		}
		return $result;
	}
}
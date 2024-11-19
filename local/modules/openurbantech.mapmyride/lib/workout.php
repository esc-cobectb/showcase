<?php

namespace Openurbantech\Mapmyride;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Workout extends Api {

    protected $url = '/v7.2/workout/';

    public function sync($objectID = 0, $ownerID = 0, $backbone = true, $forced = false) {

        $result = NULL;
        if (\Bitrix\Main\Loader::includeModule('socialservices')) {
            $authManager = new \Openurbantech\Mapmyride\Auth();
            $userAuthData = $authManager->getAuthByOwnerID($ownerID);
            if (empty($userAuthData)) {
                return false;
            }
            $userAuthData = $authManager->actualize($userAuthData);

            $name = trim(sprintf('%s %s', $userAuthData['NAME'], $userAuthData['LAST_NAME']));

            $params = ['field_set' => 'time_series'];
            $item = $this->getItem($ownerID, $objectID, $params);
            if (!empty($item)) {
                $result = true;
                $activityTypeManager = new \Openurbantech\Mapmyride\ActivityType();
                $type = $item['_links']['activity_type'][0]['id'];
                $cyclingType = $activityTypeManager->isCycling($type);
                if ($this->debug) {
                    var_dump($item);
                }
                if ($cyclingType !== false) {

                    $serverCityID = NULL;
                    $useServer = false;
                    if (\Bitrix\Main\Loader::includeModule('openurbantech.server')) {
                        $useServer = true;
                        $serverCityManager = new \Openurbantech\Server\City();
                        $serverRideManager = new \Openurbantech\Server\Ride();
                    }
                    if ($forced) {
                        $serverCityID = $forced;
                    }

                    $elevationProfile = [];
                    $coords = [];
                    $startLatLng = [];
                    $endLatLng = [];

                    foreach ($item['time_series']['position'] as $row) {
                        $point = [
                            'lat' => $row[1]['lat'],
                            'lng' => $row[1]['lng'],
                        ];

                        $coords[] = $point;
                        $elevationProfile[] = $point[1]['elevation'];

                        if (empty($startLatLng)) {
                            $startLatLng = $point;
                        }
                        $endLatLng = $point;
                        if ($useServer && empty($serverCityID)) {
                            $serverCityID = $serverCityManager->getByPoint($point);
                        }
                    }
                    if ($this->debug) {
                        var_dump($startLatLng);
                        var_dump($endLatLng);
                        var_dump($serverCityID);
                    }
                    if ($useServer && !empty($startLatLng) && !empty($endLatLng)) {
                        if($item['aggregates']['distance_total'] > 0){                           
                            if (empty($serverCityID)) {
                                $serverCityID = 0;
                                //return true;
                            }
                            $serverDatetime = $serverCityManager->getDateObject($item['start_datetime'], $serverCityID, $item['start_locale_timezone']);
                            $serverDatetimeLocal = $serverCityManager->getLocalDateObject($item['start_datetime'], $serverCityID);
                            $polyline = \Openurbantech\Server\polyline::encode($coords);

                            $device = $item['source'];
                            $gear = '';

                            $rideData = [
                                'APP_NAME' => 'Mapmyride',
                                'APP_RIDE_ID' => $objectID,
                                'APP_RIDER_ID' => $ownerID,
                                'APP_RIDER_NAME' => $name,
                                'BX_RIDER_ID' => $userAuthData['USER_ID'],
                                'APP_RIDE_DATETIME' => $serverDatetime,
                                'LOCAL_RIDE_DATETIME' => $serverDatetimeLocal,
                                'DISTANCE' => $item['aggregates']['distance_total'],
                                'RIDE_TIME' => $item['aggregates']['elapsed_time_total'],
                                'MOVING_TIME' => $item['aggregates']['active_time_total'],
                                'COORD_FROM' => \Openurbantech\Server\Point::toString($startLatLng),
                                'COORD_TO' => \Openurbantech\Server\Point::toString($endLatLng),
                                'POLYLINE' => $polyline,
                                'SPEED_AVG' => $item['aggregates']['speed_avg'],
                                'SPEED_MAX' => $item['aggregates']['speed_max'],
                                'CITY_ID' => $serverCityID,
                                'EXCLUDE_DISTANCE' => 0,
                                'RESULT_DISTANCE' => $item['aggregates']['distance_total'],
                                'PRIVACY' => $item['_links']['privacy'][0]['id'] == 0 ? 'private' : '',
                                'MANUAL' => 0,
                                'DEVICE' => $device,
                                'GEAR' => $gear,
                                'APP_RIDE_TYPE' => $cyclingType,
                                'TITLE' => $item['name'],
                                'EXTERNAL_ID' => $item['reference_key'],
                            ];
                            if ($this->debug) {
                                var_dump($rideData);
                            }
                            $serverRideManager->save($rideData);
                        } else {
                            $result = false;
                        }
                    }
                }
            }
        }
        return $result;
    }

}

<?php

namespace Openurbantech\Strava;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Activity extends Api {

    public $defaultPattern = 'activity';
    protected static $precision = 5;

    public function getStreams($activityID = 0, $ownerID = 0) {
        $result = [];
        $authManager = new \Openurbantech\Strava\Auth();
        $userAuthData = $authManager->getAuthByOwnerID($ownerID);
        $userAuthData = $authManager->actualize($userAuthData);
        if (!empty($userAuthData['OATOKEN']) && !empty($activityID)) {
            $this->setClientToken($userAuthData['OATOKEN']);
            $url = $this->getUrl('activity_streams', ['keys' => 'latlng,altitude,moving,heartrate,velocity_smooth,time,watts', 'key_by_type' => 'true']);
            $url = $this->replaceParams(['id' => $activityID], $url);
            $result = $this->call($url, 1, true);
        }
        return $result;
    }

    public function prepareStreams($streams) {
        $result = [];
        if (!empty($streams->latlng)) {
            foreach ($streams->latlng->data as $i => $v) {
                $result['lat'][$i] = $v[0];
                $result['lon'][$i] = $v[1];
            }
        }
        if (!empty($streams->time)) {
            foreach ($streams->time->data as $i => $v) {
                $result['time'][$i] = $v;
            }
        }
        if (!empty($streams->distance)) {
            foreach ($streams->distance->data as $i => $v) {
                $result['distance'][$i] = $v;
            }
        }
        if (!empty($streams->altitude)) {
            foreach ($streams->altitude->data as $i => $v) {
                $result['altitude'][$i] = $v;
            }
        }
        if (!empty($streams->velocity_smooth)) {
            foreach ($streams->velocity_smooth->data as $i => $v) {
                $result['velocity'][$i] = $v;
            }
        }
        if (!empty($streams->heartrate)) {
            foreach ($streams->heartrate->data as $i => $v) {
                $result['heartrate'][$i] = $v;
            }
        }
        if (!empty($streams->cadence)) {
            foreach ($streams->cadence->data as $i => $v) {
                $result['cadence'][$i] = $v;
            }
        }
        if (!empty($streams->watts)) {
            foreach ($streams->watts->data as $i => $v) {
                $result['watts'][$i] = $v;
            }
        }
        if (!empty($streams->moving)) {
            foreach ($streams->moving->data as $i => $v) {
                $result['moving'][$i] = $v ? 1 : 0;
            }
        }
        return $result;
    }

    public function getEfforts($nActivityID = 0) {
        $obResult = new \CDBResult();
        if ($nActivityID > 0) {
            $obActivity = $this->getByID($nActivityID);
            if ($arActivity = $obActivity->fetch()) {
                $arEfforts = array();
                foreach ($arActivity['segment_efforts'] as $nKey => $obValue) {
                    $arEffort = get_object_vars($obValue);
                    $arEffort['name'] = $this->convert($arEffort['name']);
                    // $arEffort['segment'] = get_object_vars($arEffort['segment']);
                    // $arEffort['segment']['name'] = $this->convert($arEffort['segment']['name']);
                    $arEfforts[] = $arEffort;
                }
                $obResult->initFromArray($arEfforts);
            }
        }
        return $obResult;
    }

    public function sync($objectID = 0, $ownerID = 0, $backbone = true, $forced = false) {
        $authManager = new \Openurbantech\Strava\Auth();
        $authManager->debug = $this->debug;
        $activityTypes = [
            'Ride',
            'EBikeRide',
            'Handcycle',
            'Velomobile',
        ];
        $result = NULL;
        $serverCityID = NULL;
        $segmentDistance = 0;
        $userAuthData = $authManager->getAuthByOwnerID($ownerID);
        if ($this->debug) {
            var_dump($ownerID);
            var_dump($objectID);
            var_dump($userAuthData);
        }
        if (empty($userAuthData)) {
            return false;
        }
        $userAuthData = $authManager->actualize($userAuthData);
        if ($this->debug) {
            var_dump($userAuthData);
        }
        if (!empty($userAuthData['OATOKEN']) && !empty($objectID)) {
            $this->setClientToken($userAuthData['OATOKEN']);
            $activity = $this->getElementByID($objectID);
            if (!empty($activity)) {
                if (empty($activity->errors)) {
                    $result = true;
                } else {
                    switch (true) {
                        case $activity->errors[0]->resource == 'Activity' && $activity->errors[0]->code == 'not found':
                        case $activity->errors[0]->resource == 'Activity' && $activity->errors[0]->field == 'id' && $activity->errors[0]->code == 'invalid':
                            $result = false;
                            break;
                        default:
                            # code...
                            break;
                    }
                }
            }
            if ($this->debug) {
                var_dump($activity);
            }

            $name = trim(sprintf('%s %s', $userAuthData['NAME'], $userAuthData['LAST_NAME']));
            if (in_array($activity->type, $activityTypes)) {

                $coords = $this->polylineDecode($activity->map->summary_polyline);

                $useServer = false;
                if (\Bitrix\Main\Loader::includeModule('openurbantech.server')) {
                    $useServer = true;
                    $serverCityManager = new \Openurbantech\Server\City();
                    $serverRideManager = new \Openurbantech\Server\Ride();
                }
                if (!empty($coords)) {
                    $startLatLng = [];
                    $endLatLng = [];

                    if ($forced) {
                        $serverCityID = $forced;
                    }

                    foreach ($coords as $index => $point) {
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
                    if (!empty($startLatLng) && !empty($endLatLng)) {

                        $activity->athlete_name = $name;
                        if ($useServer) {
                            if (empty($serverCityID)) {
                                $serverCityID = 0;
                            }
                            if(empty($activity->timezone)){
                                $mapTimezoneManager = new \Openurbantech\Server\Map\Timezone();
                                $timezone = $mapTimezoneManager->latLngToTimezoneString($startLatLng[0], $startLatLng[1]);
                                if(!empty($timezone)){
                                    $activity->timezone = $timezone;
                                } else {
                                    return NULL;
                                }
                            }
                            $serverDatetime = $serverCityManager->getDateObject($activity->start_date, $serverCityID, $activity->timezone);
                            $serverDatetimeLocal = $serverCityManager->getLocalDateObject($activity->start_date_local, $serverCityID);
                            $polyline = $activity->map->polyline;
                            // $polyline = $activity->map->polyline;
                            // $path = $this->polylineDecode($activity->map->polyline);
                            $distance = $activity->distance;
                            $device = $activity->device_name;
                            $gear = '';
                            if (!empty($activity->gear)) {
                                $gear = $activity->gear->name;
                            }
                            $rideData = [
                                'APP_NAME' => 'Strava',
                                'APP_RIDE_ID' => $activity->id,
                                'APP_RIDER_ID' => $activity->athlete->id,
                                'APP_RIDER_NAME' => $name,
                                'BX_RIDER_ID' => $userAuthData['USER_ID'],
                                'APP_RIDE_DATETIME' => $serverDatetime,
                                'LOCAL_RIDE_DATETIME' => $serverDatetimeLocal,
                                'DISTANCE' => $distance,
                                'RIDE_TIME' => $activity->elapsed_time,
                                'MOVING_TIME' => $activity->moving_time,
                                'COORD_FROM' => \Openurbantech\Server\Point::toString($startLatLng),
                                'COORD_TO' => \Openurbantech\Server\Point::toString($endLatLng),
                                'POLYLINE' => $polyline,
                                'SPEED_AVG' => $activity->average_speed,
                                'SPEED_MAX' => $activity->max_speed,
                                'CITY_ID' => $serverCityID,
                                'EXCLUDE_DISTANCE' => $segmentDistance,
                                'RESULT_DISTANCE' => ($distance - $segmentDistance),
                                'PRIVACY' => $activity->private ? 'private' : '',
                                'MANUAL' => $activity->manual ? 1 : 0,
                                'DEVICE' => $device,
                                'GEAR' => $gear,
                                'APP_RIDE_TYPE' => $activity->type,
                                'TITLE' => $activity->name,
                                'EXTERNAL_ID' => $activity->external_id,
                            ];
                            if ($this->debug) {
                                var_dump($rideData);
                            }
                            if ($rideID = $serverRideManager->save($rideData)) {
                                $streams = $this->getStreams($activity->id, $activity->athlete->id);
                                $outputData = $this->prepareStreams($streams);
                                if (!empty($outputData)) {
                                    $telemetryManager = new \Openurbantech\Server\Activity\Telemetry($rideID, $userAuthData['USER_ID'], $serverDatetimeLocal->format('Y'));
                                    if ($telemetryManager) {
                                        $r = $telemetryManager->saveData($outputData);
                                        if ($r) {
                                            $cols = array_keys($outputData);
                                            $update = [
                                                'TELEMETRY' => count($outputData[$cols[0]]),
                                            ];
                                            if (!empty($outputData['altitude'])) {
                                                $update['CLIMB'] = $telemetryManager->calculateClimb($outputData['altitude']);
                                            }
                                            if (!empty($outputData['velocity']) && !empty($outputData['time'])) {
                                                $velocities = $telemetryManager->calculateVelicities($outputData, 'km/h');
                                                if (!empty($velocities)) {
                                                    $update = array_merge($update, $velocities);
                                                }
                                            }
                                            foreach ($cols as $key) {
                                                $field = 'HAS_' . strtoupper($key);
                                                $update[$field] = 1;
                                            }
                                            $serverRideManager->update($rideID, $update);
                                            $pauses = $telemetryManager->calculatePauses($outputData);
                                            $telemetryManager->savePauses($pauses);
                                        } else {
                                            $update = [
                                                'TELEMETRY' => -1
                                            ];
                                            $serverRideManager->update($rideID, $update);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    public static function polylineDecode($value) {
        $index = 0;
        $points = array();
        $lat = 0;
        $lng = 0;
        while ($index < strlen($value)) {
            $b;
            $shift = 0;
            $result = 0;
            do {
                $b = ord(substr($value, $index++, 1)) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b > 31);
            $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lat += $dlat;
            $shift = 0;
            $result = 0;
            do {
                $b = ord(substr($value, $index++, 1)) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b > 31);
            $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lng += $dlng;
            $points[] = array(0 => $lat / 100000, 1 => $lng / 100000);
        }
        return $points;
    }

    public static function flatten($array = []) {
        $flatten = [];
        array_walk_recursive(
                $array, // @codeCoverageIgnore
                function ($current) use (&$flatten) {
                    $flatten[] = $current;
                }
        );
        return $flatten;
    }

    public static function polylineEncode($points = []) {
        $points = self::flatten($points);
        $encodedString = '';
        $index = 0;
        $previous = array(0, 0);
        foreach ($points as $number) {
            $number = (float) ($number);
            $number = (int) round($number * pow(10, static::$precision));
            $diff = $number - $previous[$index % 2];
            $previous[$index % 2] = $number;
            $number = $diff;
            $index++;
            $number = ($number < 0) ? ~($number << 1) : ($number << 1);
            $chunk = '';
            while ($number >= 0x20) {
                $chunk .= chr((0x20 | ($number & 0x1f)) + 63);
                $number >>= 5;
            }
            $chunk .= chr($number + 63);
            $encodedString .= $chunk;
        }
        return $encodedString;
    }

}

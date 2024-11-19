<?php

namespace Openurbantech\Mapmyride;

class OAuth extends \CSocServOAuthTransport
{
	const SERVICE_ID = "Mapmyride";

	const AUTH_URL = "https://www.mapmyfitness.com/v7.2/oauth2/uacf/authorize/";
	const TOKEN_URL = "https://api.ua.com/v7.2/oauth2/uacf/access_token/";
	const ATHLETE_URL = "https://api.ua.com/v7.2/user/";

	protected $userID = false;
	protected $userEmail = false;

	protected $scope = array(
		"activity:read_all",
	);

	public function __construct($appID = false, $appSecret = false, $code = false)
	{
		if ($appID === false)
		{
			$appID = trim(\Bitrix\Main\Config\Option::get('openurbantech.mapmyride','client_id'));
		}

		if ($appSecret === false)
		{
			$appSecret = trim(\Bitrix\Main\Config\Option::get('openurbantech.mapmyride','client_secret'));
		}

		parent::__construct($appID, $appSecret, $code);
	}

	public function GetAuthUrl($redirect_uri, $state = '')
	{
		return self::AUTH_URL.'?'.http_build_query([
			'client_id' => $this->appID,
			'redirect_uri' => $this->redirect_uri,
			'scope' => $this->getScopeEncode(),
			'response_type' => 'code',
			'state' => $state
		]);
	}

	public function GetAccessToken($redirect_uri)
	{
		$token = $this->getStorageTokens();
		if (is_array($token))
		{
            $this->access_token = $token["OATOKEN"];
            $this->accessTokenExpires = $token["OATOKEN_EXPIRES"];

            if(!$this->code)
            {
                if($this->checkAccessToken())
                {
                    return true;
                }
                elseif(isset($token["REFRESH_TOKEN"]))
                {
                    if($this->getNewAccessToken($token["REFRESH_TOKEN"]))
                    {
                        return true;
                    }
                }
            }

            $this->deleteStorageTokens();

			return true;
		}

		if ($this->code === false)
		{
			return false;
		}

		$query = array(
			"grant_type" => 'authorization_code',
			"client_id" => $this->appID,
			"client_secret" => $this->appSecret,
			"code" => $this->code,
			"redirect_uri" => $redirect_uri,
		);

		$httpClient = new \Bitrix\Main\Web\HttpClient(array(
			"socketTimeout" => $this->httpTimeout,
			"streamTimeout" => $this->httpTimeout,
		));

		$result = $httpClient->post(self::TOKEN_URL, $query);
		try
		{
			$arResult = \Bitrix\Main\Web\Json::decode($result);
		} 
		catch (\Bitrix\Main\ArgumentException $e)
		{
			$arResult = array();
		}
		if (!empty($arResult["access_token"]) && empty($arResult['expires_in']))
		{
			if($this->getNewAccessToken($arResult["access_token"]))
            {
                return true;
            }
		}
		if ((isset($arResult["access_token"]) && $arResult["access_token"] <> '') && isset($arResult["user_id"]) && $arResult["user_id"] <> '')
		{
			$refreshToken = !empty($arResult['refresh_token']) ? $arResult['refresh_token'] : $arResult["access_token"];
			$this->setToken($arResult["access_token"]);
			$this->setAccessTokenExpires($arResult['expires_in']);
			$this->setRefreshToken($refreshToken);
			$this->setUser($arResult["user_id"]);
			$this->userID = $arResult["user_id"];

			$_SESSION["OAUTH_DATA"] = array("OATOKEN" => $this->access_token);

			return true;
		}

		return false;
	}

	public function getNewAccessToken($refreshToken = false, $userId = 0, $save = false, $scope = array())
    {
        if($this->appID == false || $this->appSecret == false)
            return false;

        if($refreshToken == false)
            $refreshToken = $this->refresh_token;

        if($scope != null)
            $this->addScope($scope);

        if(empty($userId) && !empty($this->userId))
            $userId = $this->userId;

        $query = array(
            "client_id" => $this->appID,
            "client_secret" => $this->appSecret,
            "grant_type" => "refresh_token",
			"refresh_token" => $refreshToken,
		);
		
		$httpClient = new \Bitrix\Main\Web\HttpClient(array(
			"socketTimeout" => $this->httpTimeout,
			"streamTimeout" => $this->httpTimeout,
		));

        $result = $httpClient->post(self::TOKEN_URL, $query);
        var_dump($result);
        try
        {
            $arResult = \Bitrix\Main\Web\Json::decode($result);
        }
        catch(\Bitrix\Main\ArgumentException $e)
        {
            $arResult = array("error" => "ERROR_RESPONSE", "error_description" => "Wrong response from Network");
        }

        if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
        {
        	$refreshToken = !empty($arResult['refresh_token']) ? $arResult['refresh_token'] : $arResult["access_token"];
        	$expires = $arResult['expires_in'] ? (time() + $arResult['expires_in']) : $arResult['expires_at'];
			$this->setToken($arResult["access_token"]);
			$this->setAccessTokenExpires($expires);
			$this->setRefreshToken($refreshToken);

            if($save && intval($userId) > 0)
            {
                $dbSocservUser = \CSocServAuthDB::GetList(
                    array(),
                    array(
                        "USER_ID" => intval($userId),
                        "EXTERNAL_AUTH_ID" => self::SERVICE_ID
                    ), false, false, array("ID")
                );

                $arOauth = $dbSocservUser->Fetch();
                if($arOauth)
                {
                    \CSocServAuthDB::Update(
                        $arOauth["ID"], array(
                            "OATOKEN" => $this->access_token,
                            "OATOKEN_EXPIRES" => $this->accessTokenExpires,
                            "REFRESH_TOKEN" => $this->refresh_token,
                        )
                    );
                }
            }

            return true;
        }
        return false;
    }

	public function GetCurrentUser()
	{
		if ($this->access_token === false)
		{
			return false;
		}

		$headers = array(
	      'Authorization: Bearer ' . $this->access_token,
	      'Api-Key: ' . $this->appID,
	    );

		$httpClient = new \Bitrix\Main\Web\HttpClient(array(
			"socketTimeout" => $this->httpTimeout,
			"streamTimeout" => $this->httpTimeout,
		));

		$httpClient->setHeader('Authorization', 'Bearer ' . $this->access_token);
		$httpClient->setHeader('Api-Key', $this->appID);

		$url = self::ATHLETE_URL .'self/';
		$result = $httpClient->get($url);
		
		try {
			$result = \Bitrix\Main\Web\Json::decode($result);
		} catch (\Bitrix\Main\ArgumentException $e) {
			$result = array();
		}

		return $result;
	}

	public function GetCurrentUserEmail()
	{
		return $this->userEmail;
	}

}
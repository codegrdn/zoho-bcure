<?php

namespace App\Repositories;

use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;

class ZohoCrmApi
{
    public static function initialize($user)
    {
        $configuration = [
            'apiBaseUrl'                     => env('ZOHO_API_BASE_URL'),
            'apiVersion'                     => 'v2',
            'sandbox'                        => false,
            'client_id'                      => env('ZOHO_CLIENT_ID'),
            'client_secret'                  => env('ZOHO_SECRET'),
            'redirect_uri'                   => route('auth-zoho-callback'),
            'accounts_url'                   => env('ZOHO_ACCOUNT_URL'),
            'currentUserEmail'               => $user->email,
            'access_type'                    => 'offline',
            'persistence_handler_class_name' => '\App\Repositories\ZohoPersistence',
            'persistence_handler_class'      => base_path('app/Repositories/ZohoPersistenceInterface.php'),
        ];

        ZCRMRestClient::initialize($configuration);
    }

    public static function generateOauth($user, $grantToken)
    {
        self::initialize($user);

        ZohoOAuth::getClientInstance()->generateAccessToken($grantToken);

        return true;
    }

    public static function generateAuthURL(): string
    {
        $redirectUrl = route('auth-zoho-callback');
        $clientId = env('ZOHO_CLIENT_ID');
        $scopes = implode(',', array(
            'ZohoCRM.bulk.read',
            'ZohoCRM.modules.all',
            'ZohoSearch.securesearch.ALL',
            'ZohoCRM.users.all',
            'Aaaserver.profile.Read',
            'ZohoCRM.settings.ALL',
            'ZohoCRM.org.ALL'
        ));

        $params = array(
            'scope'         => $scopes,
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUrl,
            'response_type' => 'code',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        );

        return "https://accounts.zoho.com/oauth/v2/auth?" . http_build_query($params);
    }
}

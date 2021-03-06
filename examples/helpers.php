<?php

use HMRC\Oauth2\AccessToken;
use HMRC\Oauth2\Provider;

function baseURL()
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
            'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
}

function refreshAccessTokenIfNeeded()
{
    if (!isset($_SESSION['client_id'])) {
        return;
    }

    $provider = new Provider(
        $_SESSION['client_id'],
        $_SESSION['client_secret'],
        $_SESSION['callback_uri']
    );

    $existingAccessToken = AccessToken::get();

    if ($existingAccessToken->hasExpired()) {
        $newAccessToken = $provider->getAccessToken('refresh_token', [
            'refresh_token' => $existingAccessToken->getRefreshToken(),
        ]);
        AccessToken::set($newAccessToken);
    }
}

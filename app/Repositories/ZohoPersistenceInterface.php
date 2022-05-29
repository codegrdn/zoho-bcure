<?php

namespace App\Repositories;

interface ZohoPersistenceInterface
{
    public function saveOAuthData($zohoOAuthTokens);

    public function getOAuthTokens($userEmailId);

    public function deleteOAuthTokens($userEmailId);
}

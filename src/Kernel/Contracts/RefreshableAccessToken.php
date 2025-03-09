<?php

namespace OverNick\Easyxhs\Kernel\Contracts;

interface RefreshableAccessToken extends AccessToken
{
    public function refresh(): string;
}

<?php

namespace OverNick\Easyxhs\MiniApp;

use OverNick\Easyxhs\Kernel\Exceptions\DecryptException;

class Utils
{
    public function __construct(protected Application $app)
    {
    }

    /**
     * @throws DecryptException
     */
    public function decryptSession(string $sessionKey, string $iv, string $ciphertext): array
    {
        return Decryptor::decrypt($sessionKey, $iv, $ciphertext);
    }
}

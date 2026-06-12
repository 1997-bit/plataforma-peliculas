<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

use App\Models\User;
use App\Helpers\CryptoHelper;

// test cifrado
$enc = CryptoHelper::cifrar('test@test.com');
var_dump('encrypted', $enc);

$dec = CryptoHelper::descifrar($enc);
var_dump('decrypted', $dec);

// test create
$uuid = User::crear('test@test.com', 'password123', 'testuser');
var_dump('uuid', $uuid);

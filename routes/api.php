<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

require __DIR__ . '/Api/auth.php';
require __DIR__ . '/Api/client.php';
require __DIR__ . '/Api/vendeur.php';
require __DIR__ . '/Api/admin.php';
require __DIR__ . '/Api/payments.php';

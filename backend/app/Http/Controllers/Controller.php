<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Medium Clone API",
 *     version="1.0.0",
 *     description="API documentation for Medium Clone application",
 *     @OA\Contact(
 *         email="admin@mediumclone.com"
 *     )
 * )
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller
{
    //
}

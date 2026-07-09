<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Send generic success JSON response.
     */
    public function sendResponse($result, ?string $message = null, int $code = 200)
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        return response()->json($response, $code);
    }

    /**
     * Send generic error JSON response.
     */
    public function sendError(string $error, array $errorMessages = [], int $code = 400)
    {
        $response = [
            'success' => false,
            'data'    => !empty($errorMessages) ? $errorMessages : null,
            'message' => $error,
        ];

        return response()->json($response, $code);
    }
}


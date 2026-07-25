<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Get the authenticated user's shop.
     */
    protected function getAuthShop(?\Illuminate\Http\Request $request = null): ?\App\Models\Restaurant
    {
        $request = $request ?? request();
        return $request->user()?->restaurant;
    }

    /**
     * Get the authenticated user's shop ID.
     */
    protected function getAuthShopId(?\Illuminate\Http\Request $request = null): ?string
    {
        return $this->getAuthShop($request)?->id;
    }

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


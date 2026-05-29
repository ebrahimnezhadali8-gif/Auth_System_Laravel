<?php

if (!function_exists('successResponse')) { // If the function does not exist, create it

    function successResponse($data = null, int $code = 200, $message = null)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }
}

if (!function_exists('errorResponse')) {

    function errorResponse(int $code = 500, $message = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $code);
    }
}

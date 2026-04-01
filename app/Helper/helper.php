<?php

use App\Models\PlaceImages;
use App\Models\PropertityImages;

if (!function_exists('usernameAvatar')) {
    function usernameAvatar($name)
    {
        $nameSplit = explode(' ', $name);
        $firstLetter = substr($nameSplit[0], 0, 1);
        if (count($nameSplit) == 1) {
            $secondLetter = substr($nameSplit[0], 1, 1);
        } else {
            $secondLetter = substr($nameSplit[(count($nameSplit) - 1)], 0, 1);
        }
        return utf8_decode(strtoupper($firstLetter) . strtoupper($secondLetter));
    }
}
if (!function_exists('ImagePathName')) {
    function ImagePathName($file, $path)
    {
        $file_name = $path . time() . '.' . $file->getClientOriginalExtension();
        $file->move($path, $file_name);
        return $file_name;
    }
}

if (!function_exists('propertyImages')) {
    function propertyImages($path, $files, $id)
    {
        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension();
            $randomNumber = rand(100000, 999999);
            $filename = $path . $randomNumber . '_' . time() . '.' . $extension;
            $path = $path;
            $file->move($path, $filename);
            PlaceImages::create([
                'place_id' => $id,
                'original' => $filename,
                'extension' => $extension,
            ]);
        }
    }
}

if (!function_exists('sendResponse')) {
    function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];


        return response()->json($response, 200);
    }
}
if (!function_exists('sendError')) {
    function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];


        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }
}

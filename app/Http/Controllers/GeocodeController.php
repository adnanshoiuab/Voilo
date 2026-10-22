<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeocodeController extends Controller
{
    public function geocode(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'address' => ['required', 'string', 'max:255'],
        ]);
        $accessToken = config('services.mapbox.key');

        if (! $accessToken) {
            return response()->json(['message' => 'Geocoding is not configured.'], 503);
        }

        $address = rawurlencode($validatedData['address']);
        try {
            $response = Http::timeout(5)->get("https://api.mapbox.com/geocoding/v5/mapbox.places/{$address}.json", [
                'access_token' => $accessToken,
            ]);
        } catch (ConnectionException) {
            return response()->json(['message' => 'Geocoding service is unavailable.'], 502);
        }

        if (! $response->successful()) {
            return response()->json(['message' => 'Geocoding service is unavailable.'], 502);
        }

        $data = $response->json();
        if (! is_array($data) || ! is_array($data['features'] ?? null)) {
            return response()->json(['message' => 'Geocoding service returned an invalid response.'], 502);
        }

        return response()->json($data);
    }
}

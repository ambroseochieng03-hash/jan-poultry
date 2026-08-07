<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| JAN Poultry
| Location Service
|--------------------------------------------------------------------------
|
| Handles:
| - Google Maps locations
| - Geocoding
| - Directions
| - Future delivery tracking
|--------------------------------------------------------------------------
*/

class LocationService
{
    /**
     * Google Maps API Key.
     */
    private string $apiKey;

    /**
     * Geocoding Endpoint.
     */
    private string $geocodingEndpoint =
        'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct()
    {
        $this->apiKey =
            Env::get(
                'GOOGLE_MAPS_API_KEY',
                ''
            );
    }

    /**
     * Ensure API key exists.
     */
    private function ensureApiKey(): void
    {
        if (empty($this->apiKey)) {

            throw new RuntimeException(
                'Google Maps API key is not configured.'
            );

        }
    }

        /**
     * Send an HTTP GET request.
     */
    private function makeRequest(
        string $url
    ): array {

        $curl =
            curl_init();

        curl_setopt_array(

            $curl,

            [

                CURLOPT_URL => $url,

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_FOLLOWLOCATION => true,

                CURLOPT_CONNECTTIMEOUT => 10,

                CURLOPT_TIMEOUT => 20,

                CURLOPT_SSL_VERIFYPEER => true,

                CURLOPT_USERAGENT =>

                    'JAN Poultry Location Service'

            ]

        );

        $response =
            curl_exec($curl);

        if (

            $response === false

        ) {

            $error =
                curl_error($curl);

            curl_close($curl);

            return [

                'success' => false,

                'data' => null,

                'message' => $error

            ];

        }

        $statusCode =
            curl_getinfo(

                $curl,

                CURLINFO_HTTP_CODE

            );

        curl_close($curl);

        if (

            $statusCode !== 200

        ) {

            return [

                'success' => false,

                'data' => null,

                'message' =>

                    'HTTP Error '
                    .
                    $statusCode

            ];

        }

        return [

            'success' => true,

            'data' => json_decode(
                $response,
                true
            ),

            'message' => null

        ];

    }

        /**
     * Expand a shortened URL by following redirects.
     */
    private function expandUrl(
        string $url
    ): ?string {

        $curl =
            curl_init();

        curl_setopt_array(

            $curl,

            [

                CURLOPT_URL => $url,

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_NOBODY => true,

                CURLOPT_FOLLOWLOCATION => true,

                CURLOPT_MAXREDIRS => 10,

                CURLOPT_SSL_VERIFYPEER => true,

                CURLOPT_CONNECTTIMEOUT => 10,

                CURLOPT_TIMEOUT => 20

            ]

        );

        curl_exec($curl);

        if (

            curl_errno($curl)

        ) {

            curl_close($curl);

            return null;

        }

        $expandedUrl =
            curl_getinfo(

                $curl,

                CURLINFO_EFFECTIVE_URL

            );

        curl_close($curl);

        return
            $expandedUrl
            ?: null;

    }

        /**
     * Extract a location from a Google Maps URL.
     *
     * Returns:
     * - Place name
     * - Coordinates
     * - null if nothing useful is found
     */
    private function extractLocationFromUrl(
        string $url
    ): ?string {

        /*
        |--------------------------------------------------------------------------
        | Coordinates in ?q=
        |--------------------------------------------------------------------------
        */

        $parts =
            parse_url($url);

        if (

            !empty($parts['query'])

        ) {

            parse_str(
                $parts['query'],
                $query
            );

            if (

                !empty($query['q'])

            ) {

                return
                    trim(
                        $query['q']
                    );

            }

        }

        /*
        |--------------------------------------------------------------------------
        | /place/
        |--------------------------------------------------------------------------
        */

        if (

            preg_match(

                '#/place/([^/]+)#',

                $url,

                $matches

            )

        ) {

            return
                urldecode(

                    str_replace(

                        '+',

                        ' ',

                        $matches[1]

                    )

                );

        }

        /*
        |--------------------------------------------------------------------------
        | Nothing recognised
        |--------------------------------------------------------------------------
        */

        return null;

    }

        /**
     * Resolve a Google Maps URL into
     * a place name and coordinates.
     */
    private function resolveGoogleMapsUrl(
        string $url
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Expand shortened URLs
        |--------------------------------------------------------------------------
        */

        $expandedUrl =
            $this->expandUrl(
                $url
            );

        if (

            empty($expandedUrl)

        ) {

            return [

                'success'    => false,

                'place_name' => null,

                'lat'        => null,

                'lng'        => null,

                'message'    =>

                    'Unable to resolve the Google Maps link.'

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Extract location
        |--------------------------------------------------------------------------
        */

        $location =
            $this->extractLocationFromUrl(
                $expandedUrl
            );

        if (

            empty($location)

        ) {

            return [

                'success'    => false,

                'place_name' => null,

                'lat'        => null,

                'lng'        => null,

                'message'    =>

                    'Unable to determine the location from the provided Google Maps link.'

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Geocode extracted location
        |--------------------------------------------------------------------------
        */

        return
            $this->geocode(
                $location
            );

    }

        /**
     * Resolve a place name into coordinates.
     *
     * @return array{
     *     success: bool,
     *     place_name: ?string,
     *     lat: ?float,
     *     lng: ?float,
     *     message: ?string
     * }
     */
    public function geocode(
        string $location
    ): array {

        $this->ensureApiKey();

        $location =
            trim($location);

        if ($location === '') {

            return [

                'success'    => false,

                'place_name' => null,

                'lat'        => null,

                'lng'        => null,

                'message'    =>
                    'Location cannot be empty.'

            ];

        }

        $url =
            $this->geocodingEndpoint
            .
            '?address='
            .
            urlencode($location)
            .
            '&key='
            .
            $this->apiKey;

        $request =
            $this->makeRequest(
                $url
            );

        if (

            !$request['success']

        ) {

            return [

                'success'    => false,

                'place_name' => null,

                'lat'        => null,

                'lng'        => null,

                'message'    =>
                    $request['message']

            ];

        }

        $data =
            $request['data'];

        if (

            !isset($data['status'])

            ||

            $data['status'] !== 'OK'

        ) {

            return [

                'success'    => false,

                'place_name' => null,

                'lat'        => null,

                'lng'        => null,

                'message'    =>
                    'Location not found.'

            ];

        }

        $result =
            $data['results'][0];

        return [

            'success'    => true,

            'place_name' =>
                $result['formatted_address'],

            'lat' =>
                (float)
                $result['geometry']['location']['lat'],

            'lng' =>
                (float)
                $result['geometry']['location']['lng'],

            'message' => null

        ];

    }

        /**
     * Determine whether the given string
     * is a Google Maps URL.
     */
    public function isGoogleMapsUrl(
        string $value
    ): bool {

        $value =
            trim($value);

        return
            str_contains(
                $value,
                'google.com/maps'
            )

            ||

            str_contains(
                $value,
                'maps.app.goo.gl'
            )

            ||

            str_contains(
                $value,
                'goo.gl/maps'
            );

    }

        /**
     * Resolve a location.
     *
     * Accepts:
     * - Plain place names
     * - Google Maps URLs (future support)
     */
    public function resolveLocation(
        string $location
    ): array {

        $location =
            trim($location);

        if ($location === '') {

            return [

                'success'    => false,

                'place_name' => null,

                'lat'        => null,

                'lng'        => null,

                'message'    =>
                    'Location cannot be empty.'

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Google Maps URL
        |--------------------------------------------------------------------------
        */

        if (

            $this->isGoogleMapsUrl(
                $location
            )

        ) {

            return
                $this->resolveGoogleMapsUrl(
                    $location
                );

        }

        /*
        |--------------------------------------------------------------------------
        | Plain Location Name
        |--------------------------------------------------------------------------
        */

        return
            $this->geocode(
                $location
            );

    }
}
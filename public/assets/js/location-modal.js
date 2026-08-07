/*
|--------------------------------------------------------------------------
| JAN Poultry
| Reusable Location Modal
|--------------------------------------------------------------------------
*/

(function () {

    "use strict";

    const modal =
        document.getElementById(
            "jpLocationModal"
        );

    if (!modal) {

        return;

    }

    const title =
        document.getElementById(
            "jpLocationTitle"
        );

    const address =
        document.getElementById(
            "jpLocationAddress"
        );

    const distance =
        document.getElementById(
            "jpLocationDistance"
        );

    const duration =
        document.getElementById(
            "jpLocationDuration"
        );

    const closeButton =
        document.getElementById(
            "jpLocationClose"
        );

    const fullscreenButton =
        document.getElementById(
            "jpLocationFullscreen"
        );

    let currentMapsUrl = "";

    let map = null;

    let destinationMarker = null;

    let userMarker = null;

    let directionsService = null;

    let directionsRenderer = null;

    /*
    |--------------------------------------------------------------------------
    | Close
    |--------------------------------------------------------------------------
    */

    function closeModal() {

        modal.classList.remove(
            "jp-show"
        );

        document.body.style.overflow = "";

    }

    /*
    |--------------------------------------------------------------------------
    | Initialize Google Map
    |--------------------------------------------------------------------------
    */

    function initializeMap() {

        if (map) {

            return;

        }

        map =
            new google.maps.Map(

                document.getElementById(
                    "jpLocationMap"
                ),

                {

                    zoom: 14,

                    center: {

                        lat: -1.286389,

                        lng: 36.817223

                    },

                    mapTypeControl: false,

                    streetViewControl: false,

                    fullscreenControl: false

                }

            );

        directionsService =
            new google.maps.DirectionsService();

        directionsRenderer =
            new google.maps.DirectionsRenderer({

                suppressMarkers: true,

                polylineOptions: {

                    strokeColor: "#2F80ED",

                    strokeWeight: 8

                }

            });

        directionsRenderer.setMap(map);

    }

    /*
    |--------------------------------------------------------------------------
    | Public Function
    |--------------------------------------------------------------------------
    */

    window.showLocationModal =
        function (options = {}) {

            title.textContent =
                options.title ??
                "Location";

            address.textContent =
                options.address ??
                "";

            distance.textContent =
                "--";

            duration.textContent =
                "--";

            currentMapsUrl =
                options.mapsUrl ??
                "";

            initializeMap();    

            modal.classList.add(
                "jp-show"
            );

            document.body.style.overflow =
                "hidden";

        };

    /*
    |--------------------------------------------------------------------------
    | Close Button
    |--------------------------------------------------------------------------
    */

    closeButton.addEventListener(
        "click",
        closeModal
    );

    /*
    |--------------------------------------------------------------------------
    | Click Outside
    |--------------------------------------------------------------------------
    */

    modal.addEventListener(
        "click",
        function (event) {

            if (

                event.target === modal

            ) {

                closeModal();

            }

        }
    );

    /*
    |--------------------------------------------------------------------------
    | ESC
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        function (event) {

            if (

                event.key === "Escape"

            ) {

                closeModal();

            }

        }
    );

    /*
    |--------------------------------------------------------------------------
    | Fullscreen
    |--------------------------------------------------------------------------
    */

    fullscreenButton.addEventListener(
        "click",
        async function () {

            const card =
                modal.querySelector(
                    ".jp-location-card"
                );

            if (

                !document.fullscreenElement

            ) {

                await card.requestFullscreen();

            } else {

                await document.exitFullscreen();

            }

        }
    );

})();
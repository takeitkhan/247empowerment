"use strict";

let map, marker, infoWindow;

async function initProfileMap() {
    const center = { lat: 40.749933, lng: -73.98633 }; // Default center (NYC)

    const [{ Map }, { AdvancedMarkerElement }] = await Promise.all([
        google.maps.importLibrary("maps"),
        google.maps.importLibrary("marker")
    ]);

    map = new google.maps.Map(document.getElementById("map"), {
        center,
        zoom: 13,
        mapTypeControl: false,
    });

    // Create place autocomplete
    const placeAutocomplete = new google.maps.places.PlaceAutocompleteElement();
    placeAutocomplete.id = "place-autocomplete-input";
    placeAutocomplete.locationBias = center;

    const card = document.getElementById("place-autocomplete-card");
    card.appendChild(placeAutocomplete);

    marker = new google.maps.marker.AdvancedMarkerElement({ map });
    infoWindow = new google.maps.InfoWindow({});

    // Load saved values
    const lat = parseFloat(document.getElementById("latitude").value);
    const lng = parseFloat(document.getElementById("longitude").value);
    const displayName = document.getElementById("place_display_name").value || "Saved Location";
    const address = document.getElementById("place_address").value;

    if (!isNaN(lat) && !isNaN(lng)) {
        const savedLocation = { lat, lng };
        map.setCenter(savedLocation);
        map.setZoom(17);
        marker.position = savedLocation;
        updateInfoWindow(`<div><b>${displayName}</b><br>${address}</div>`, savedLocation);
    }

    // On place select
    placeAutocomplete.addEventListener("gmp-select", async ({ placePrediction }) => {
        const place = placePrediction.toPlace();
        await place.fetchFields({ fields: ["displayName", "formattedAddress", "location"] });

        const newLat = place.location.lat();
        const newLng = place.location.lng();

        // Fill your form fields automatically
        document.getElementById("latitude").value = newLat;
        document.getElementById("longitude").value = newLng;
        document.getElementById("place_display_name").value = place.displayName;
        document.getElementById("place_address").value = place.formattedAddress;

        map.setCenter(place.location);
        map.setZoom(17);
        marker.position = place.location;

        updateInfoWindow(
            `<div><b>${place.displayName}</b><br>${place.formattedAddress}</div>`,
            place.location
        );
    });
}

function updateInfoWindow(content, center) {
    infoWindow.setContent(content);
    infoWindow.setPosition(center);
    infoWindow.open({ map, anchor: marker, shouldFocus: false });
}

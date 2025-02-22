// public/js/google-maps.js
let map;
let marker;
let autocomplete;
let geocoder;
let isLocationSet = false;

// Format address components
function formatAddress(addressComponents) {
    let city = '';
    let state = '';
    let zip = '';

    if (addressComponents && Array.isArray(addressComponents)) {
        addressComponents.forEach(component => {
            const types = component.types;
            if (types.includes('locality')) {
                city = component.long_name;
            } 
            else if (types.includes('administrative_area_level_1')) {
                state = component.short_name;
            } 
            else if (types.includes('postal_code')) {
                zip = component.long_name;
            }
        });
    }

    return {
        city: city,
        state: state,
        zip: zip,
        formatted: city && state ? `${city}, ${state} ${zip}` : ''
    };
}

// Update form fields
function updateLocationFields(position, addressComponents = null) {
    if (position) {
        document.getElementById('tcm-latitude').value = typeof position.lat === 'function' ? position.lat() : position.lat;
        document.getElementById('tcm-longitude').value = typeof position.lng === 'function' ? position.lng() : position.lng;
    }

    if (addressComponents) {
        const address = formatAddress(addressComponents);
        document.getElementById('tcm-city').value = address.city;
        document.getElementById('tcm-state').value = address.state;
        document.getElementById('tcm-zip').value = address.zip;
        document.getElementById('tcm-location-input').value = address.formatted;
        isLocationSet = true;
        
        // Remove any error styling
        const input = document.getElementById('tcm-location-input');
        input.classList.remove('invalid-field');
    }
}

// Initialize map
function initializeMap() {
    const defaultLocation = { lat: 30.1543, lng: -95.7544 };
    
    // Initialize map components
    geocoder = new google.maps.Geocoder();
    
    map = new google.maps.Map(document.getElementById('tcm-map'), {
        center: defaultLocation,
        zoom: 11,
        mapTypeControl: true,
        streetViewControl: false,
        fullscreenControl: false
    });

    marker = new google.maps.Marker({
        position: defaultLocation,
        map: map,
        draggable: true
    });

    // Initialize autocomplete
    const input = document.getElementById('tcm-location-input');
    autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['address'],
        componentRestrictions: { country: 'us' }
    });

    // Prevent form submission on enter in location field
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            return false;
        }
    });

    // Reset location status when input is cleared
    input.addEventListener('input', function(e) {
        if (!this.value) {
            isLocationSet = false;
        }
    });

    // Bind autocomplete to map viewport
    autocomplete.bindTo('bounds', map);

    // Handle place selection
    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        
        if (!place.geometry || !place.geometry.location) {
            console.warn('No location found for input');
            return;
        }

        // Update map and marker
        map.setCenter(place.geometry.location);
        marker.setPosition(place.geometry.location);

        // Update form fields
        updateLocationFields(place.geometry.location, place.address_components);
    });

    // Handle marker drag
    marker.addListener('dragend', function() {
        const position = marker.getPosition();
        
        geocoder.geocode({
            location: { lat: position.lat(), lng: position.lng() }
        }, function(results, status) {
            if (status === 'OK' && results[0]) {
                updateLocationFields(position, results[0].address_components);
            } else {
                console.warn('Geocoder failed:', status);
            }
        });
    });
}

// Make location status check available globally
window.isLocationSet = function() {
    return isLocationSet;
};

// Initialize map when Google Maps API is loaded
window.initMap = initializeMap;
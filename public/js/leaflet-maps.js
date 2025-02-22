// public/js/leaflet-maps.js
let map;
let marker;
let isLocationSet = false;

// Initialize Leaflet map
function initializeMap() {
    // Default to Houston, TX
    const defaultLocation = [29.7604, -95.3698];

    // Create map
    map = L.map('tcm-map').setView(defaultLocation, 11);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add draggable marker
    marker = L.marker(defaultLocation, {
        draggable: true
    }).addTo(map);

    // Setup address search
    const searchInput = document.getElementById('tcm-location-input');
    
    // Use OpenStreetMap Nominatim for geocoding
    searchInput.addEventListener('input', debounce(function(e) {
        const query = e.target.value;
        if (query.length < 3) return;

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=us&state=texas`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    const result = data[0];
                    const lat = parseFloat(result.lat);
                    const lon = parseFloat(result.lon);
                    
                    marker.setLatLng([lat, lon]);
                    map.setView([lat, lon], 16);
                    
                    updateLocationFields({
                        lat: lat,
                        lng: lon,
                        display_name: result.display_name,
                        address: {
                            city: result.address.city || result.address.town || result.address.village,
                            state: result.address.state,
                            postcode: result.address.postcode
                        }
                    });
                }
            })
            .catch(error => console.error('Geocoding error:', error));
    }, 500));

    // Handle marker drag
    marker.on('dragend', function(e) {
        const position = marker.getLatLng();
        
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.lat}&lon=${position.lng}`)
            .then(response => response.json())
            .then(data => {
                updateLocationFields({
                    lat: position.lat,
                    lng: position.lng,
                    display_name: data.display_name,
                    address: {
                        city: data.address.city || data.address.town || data.address.village,
                        state: data.address.state,
                        postcode: data.address.postcode
                    }
                });
            })
            .catch(error => console.error('Reverse geocoding error:', error));
    });

    // Prevent form submission on enter in location field
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            return false;
        }
    });
}

// Update form fields with location data
function updateLocationFields(data) {
    if (!data.address.city || !data.address.state) {
        console.warn('Incomplete address data');
        return;
    }

    document.getElementById('tcm-latitude').value = data.lat;
    document.getElementById('tcm-longitude').value = data.lng;
    document.getElementById('tcm-city').value = data.address.city;
    document.getElementById('tcm-state').value = data.address.state;
    document.getElementById('tcm-zip').value = data.address.postcode;
    
    const formattedAddress = `${data.address.city}, ${data.address.state} ${data.address.postcode}`;
    document.getElementById('tcm-location-input').value = formattedAddress;
    
    isLocationSet = true;
    document.getElementById('tcm-location-input').classList.remove('invalid-field');
}

// Debounce function to limit API calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Make location status available globally
window.isLocationSet = function() {
    return isLocationSet;
};

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeMap();
});
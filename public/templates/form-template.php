<?php
if (!defined('ABSPATH')) exit;

$api_key = get_option('tcm_google_maps_api_key');
$default_location = get_option('tcm_default_location', array('lat' => '37.7749', 'lng' => '-122.4194'));
$technicians = tcm_get_technicians();
$services = tcm_get_services();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="<?php echo plugins_url('public/manifest.json', dirname(dirname(__FILE__))); ?>">
    <meta name="theme-color" content="#3498db">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="TechCheck">
    <link rel="apple-touch-icon" href="<?php echo plugins_url('public/images/icon-192.png', dirname(dirname(__FILE__))); ?>">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div id="pwa-install-button" class="pwa-install-button" style="display: none;">
    <button class="install-pwa-btn">
        <span class="icon">📱</span>
        Install Tech Check-in App
    </button>
</div>

<div class="tcm-checkin-container">
    <div id="pwa-install-container"></div>
    <form id="tcm-checkin-form" class="checkin-form" enctype="multipart/form-data">
        <?php wp_nonce_field('tcm-ajax-nonce', 'security'); ?>

        <!-- Technician Selection -->
        <div class="form-group">
            <label for="tcm-technician"><?php _e('Technician', 'tech-checkin-maps'); ?> *</label>
            <select id="tcm-technician" name="technician">
                <option value=""><?php _e('Select Technician', 'tech-checkin-maps'); ?></option>
                <?php foreach ($technicians as $tech) : ?>
                    <option value="<?php echo esc_attr($tech); ?>"><?php echo esc_html($tech); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Service Type -->
        <div class="form-group">
            <label for="tcm-service"><?php _e('Service Type', 'tech-checkin-maps'); ?> *</label>
            <select id="tcm-service" name="service">
                <option value=""><?php _e('Select Service', 'tech-checkin-maps'); ?></option>
                <?php foreach ($services as $service) : ?>
                    <option value="<?php echo esc_attr($service); ?>"><?php echo esc_html($service); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Location Section -->
        <div class="form-group">
            <label for="tcm-location-input"><?php _e('Service Location', 'tech-checkin-maps'); ?> *</label>
            <div class="tcm-location-input-container">
                <input type="text" id="tcm-location-input" name="location" class="location-autocomplete" placeholder="<?php _e('Enter address...', 'tech-checkin-maps'); ?>">
            </div>
            <div id="tcm-map" style="height: 300px; margin: 10px 0;"></div>

            <input type="hidden" name="street" id="tcm-street">
            <input type="hidden" name="city" id="tcm-city">
            <input type="hidden" name="state" id="tcm-state">
            <input type="hidden" name="zip" id="tcm-zip">
            <input type="hidden" name="latitude" id="tcm-latitude">
            <input type="hidden" name="longitude" id="tcm-longitude">
        </div>

        <!-- Service Details -->
        <div class="form-group">
            <label for="tcm-details"><?php _e('Service Details', 'tech-checkin-maps'); ?> *</label>
            <textarea id="tcm-details" name="details" rows="4"></textarea>
        </div>

        <!-- Photo Upload -->
        <div class="form-group">
            <label><?php _e('Service Photos', 'tech-checkin-maps'); ?></label>
            <div class="photo-upload-section">
                <div class="photo-upload">
                    <label for="tcm-photos-before">Before Photo *</label>
                    <input type="file" id="tcm-photos-before" name="photos_before" accept="image/*">
                    <div class="photo-preview" id="before-preview"></div>
                </div>

                <div class="photo-upload">
                    <label for="tcm-photos-after">After Photo *</label>
                    <input type="file" id="tcm-photos-after" name="photos_after" accept="image/*">
                    <div class="photo-preview" id="after-preview"></div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="tcm-submit-button">
            <?php _e('Submit Check-in', 'tech-checkin-maps'); ?>
        </button>
    </form>
</div>

<style>
/* Container styles */
.tcm-checkin-container {
   max-width: 800px;
   margin: 0 auto;
   padding: 20px;
}

.checkin-form {
   background: #fff;
   padding: 25px;
   border-radius: 8px;
   box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Form group styles */
.form-group {
   margin-bottom: 20px;
}

.form-group label {
   display: block;
   margin-bottom: 8px;
   font-weight: 500;
   color: #2c3e50;
}

.form-group label > span.required {
   color: #e74c3c;
}

/* Input styles */
input[type="text"],
select,
textarea {
   width: 100%;
   padding: 10px;
   border: 1px solid #ddd;
   border-radius: 4px;
   font-size: 16px;
}

/* Map container */
.checkin-map-container {
   width: 100%;
   height: 300px;
   margin: 10px 0;
}

#tcm-map {
   width: 100%;
   height: 100%;
   border-radius: 4px;
}

/* Photo upload section */
.photo-upload-section {
   display: flex;
   gap: 20px;
}

.photo-upload {
   margin-bottom: 20px;
   flex: 1;
}

.photo-preview {
   margin-top: 10px;
   min-height: 150px;
   background: #f8f9fa;
   border-radius: 4px;
   overflow: hidden;
}

.photo-preview img {
   width: 100%;
   height: 150px;
   object-fit: cover;
}

/* Submit button */
.tcm-submit-button {
   background: #2271b1;
   color: white;
   padding: 12px 24px;
   border: none;
   border-radius: 4px;
   cursor: pointer;
   font-weight: 500;
   width: 100%;
   font-size: 16px;
   margin-top: 20px;
}

.tcm-submit-button:hover {
   background: #135e96;
}

.tcm-submit-button:disabled {
   background: #ccc;
   cursor: not-allowed;
}

/* Invalid field styling */
.invalid-field {
   border-color: #e74c3c !important;
   background-color: #fff6f6 !important;
}

/* Success message */
.tcm-success-message {
   position: fixed;
   top: 50%;
   left: 50%;
   transform: translate(-50%, -50%);
   background: rgba(255, 255, 255, 0.95);
   padding: 20px;
   border-radius: 8px;
   box-shadow: 0 2px 10px rgba(0,0,0,0.1);
   z-index: 9999;
   text-align: center;
}

.tcm-success-message p {
   color: #2ecc71;
   margin-bottom: 15px;
   font-size: 16px;
}

.tcm-close-msg {
   background: #3498db;
   color: white;
   border: none;
   padding: 8px 20px;
   border-radius: 4px;
   cursor: pointer;
   font-size: 14px;
}

.tcm-close-msg:hover {
   background: #2980b9;
}

/* Mobile styles */
@media (max-width: 768px) {
   .tcm-checkin-container {
       max-width: 100%;
       padding: 0;
       margin: 0;
   }

   .checkin-form {
       padding: 15px;
       border-radius: 0;
       box-shadow: none;
   }

   .form-group {
       margin-bottom: 15px;
   }

   .form-group.photo-upload-section {
       margin-left: -15px;
       margin-right: -15px;
       background: #f8f9fa;
       padding: 15px;
   }

   .photo-upload {
       margin: 0 0 30px 0;
   }

   .photo-upload:last-child {
       margin-bottom: 0;
   }

   .photo-preview {
       margin: 10px 0 0 0;
       width: 100%;
       min-height: 200px;
       border-radius: 0;
       background: #f8f9fa;
   }

   .photo-preview img {
       width: 100%;
       height: 200px;
       object-fit: cover;
   }

   .checkin-map-container {
       margin-left: -15px;
       margin-right: -15px;
       width: calc(100% + 30px);
       height: 250px;
   }

   #tcm-map {
       border-radius: 0;
   }

   .tcm-submit-button {
       position: fixed;
       bottom: 0;
       left: 0;
       right: 0;
       margin: 0;
       border-radius: 0;
       padding: 15px;
       z-index: 100;
   }

   /* Add padding at bottom to account for fixed submit button */
   .checkin-form {
       padding-bottom: 70px;
   }

   /* Leaflet controls */
   .leaflet-control-container .leaflet-top {
       z-index: 800;
   }

   .leaflet-container {
       z-index: 1;
   }
}

/* Helper classes */
.required:after {
   content: " *";
   color: #e74c3c;
}

.tcm-location-input-container {
    position: relative;
}
</style>

<script>
// Initialize Google Maps
window.initTcmMap = function() {
    if (typeof google === 'undefined') {
        console.error('Google Maps not loaded');
        return;
    }

    const defaultLocation = <?php echo json_encode($default_location); ?>;
    const mapElement = document.getElementById('tcm-map');

    if (!mapElement) return;

    const map = new google.maps.Map(mapElement, {
        center: defaultLocation,
        zoom: 13
    });

    const marker = new google.maps.Marker({
        map: map,
        draggable: true,
        position: defaultLocation
    });

    const input = document.getElementById('tcm-location-input');
    const autocomplete = new google.maps.places.Autocomplete(input);

    autocomplete.bindTo('bounds', map);

    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();

        if (!place.geometry) {
            alert('<?php _e("No location found for that address.", "tech-checkin-maps"); ?>');
            return;
        }

        updateMap(place);
        updateLocationFields(place);
    });

    function updateMap(place) {
        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }

        marker.setPosition(place.geometry.location);
    }

    function updateLocationFields(place) {
        let street = '', city = '', state = '', zip = '';

        for (const component of place.address_components) {
            const type = component.types[0];

            switch(type) {
                case 'street_number':
                    street = component.long_name + ' ';
                    break;
                case 'route':
                    street += component.long_name;
                    break;
                case 'locality':
                    city = component.long_name;
                    break;
                case 'administrative_area_level_1':
                    state = component.short_name;
                    break;
                case 'postal_code':
                    zip = component.long_name;
                    break;
            }
        }

        document.getElementById('tcm-street').value = street;
        document.getElementById('tcm-city').value = city;
        document.getElementById('tcm-state').value = state;
        document.getElementById('tcm-zip').value = zip;
        document.getElementById('tcm-latitude').value = place.geometry.location.lat().toFixed(6);
        document.getElementById('tcm-longitude').value = place.geometry.location.lng().toFixed(6);
    }

    marker.addListener('dragend', function() {
        const position = marker.getPosition();
        const geocoder = new google.maps.Geocoder();

        geocoder.geocode({ location: position }, function(results, status) {
            if (status === 'OK' && results[0]) {
                updateLocationFields(results[0]);
            }
        });
    });

    window.tcmMap = map;
    window.tcmMarker = marker;
};

// Load Google Maps script
function loadGoogleMapsScript() {
    if (typeof google === 'undefined') {
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=<?php echo esc_js($api_key); ?>&libraries=places`;
        script.onload = initTcmMap;
        document.head.appendChild(script);
    } else {
        initTcmMap();
    }
}
loadGoogleMapsScript();

// Initialize form handlers
jQuery(document).ready(function($) {
    let isSubmitting = false;

    // Preview before photo
    $('#tcm-photos-before').on('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#before-preview').html(`<img src="${e.target.result}" alt="Before preview">`);
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Preview after photo
    $('#tcm-photos-after').on('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#after-preview').html(`<img src="${e.target.result}" alt="After preview">`);
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Form submission
    $('#tcm-checkin-form').on('submit', function(e) {
        e.preventDefault();

        if (isSubmitting) {
            return false;
        }

        // Basic validation
        let canSubmit = true;

        if (!$('#tcm-technician').val()) {
            canSubmit = false;
        }

        if (!$('#tcm-service').val()) {
            canSubmit = false;
        }

        if (!$('#tcm-location-input').val() || !$('#tcm-latitude').val() || !$('#tcm-longitude').val()) {
            canSubmit = false;
        }

        if (!canSubmit) {
            alert('Please fill in all required fields');
            return false;
        }
        // Form submission - continued
        isSubmitting = true;
        $('.tcm-submit-button').prop('disabled', true).text('Submitting...');

        const formData = new FormData(this);
        formData.append('action', 'tcm_submit_checkin');
        formData.append('security', $('#security').val());

        $.ajax({
            url: tcmAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log("Response received:", response);
                if (response.success) {
                    alert('Check-in submitted successfully!');
                    location.reload();
                } else {
                    alert(response.data || 'Error submitting check-in. Please try again.');
                    $('.tcm-submit-button').prop('disabled', false).text('Submit Check-in');
                    isSubmitting = false;
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                alert('Error submitting check-in. Please try again.');
                $('.tcm-submit-button').prop('disabled', false).text('Submit Check-in');
                isSubmitting = false;
            }
        });

        return false;
    });

    // Remove validation styling on input
    $('input, select').on('input change', function() {
        $(this).removeClass('invalid-field');
    });
});
</script>
<link rel="manifest" href="<?php echo TCM_PLUGIN_URL; ?>public/manifest.json">
<script src="<?php echo TCM_PLUGIN_URL; ?>public/js/pwa-installer.js"></script>
<?php wp_footer(); ?>
</body>
</html>
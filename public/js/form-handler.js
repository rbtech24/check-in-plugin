// Form handler initialization
jQuery(document).ready(function($) {
    let isSubmitting = false;
    let formProcessed = false;

    // Debug logging
    const DEBUG = true;
    function log(message, data = null) {
        if (DEBUG) {
            if (data) {
                console.log(`[TCM Debug] ${message}:`, data);
            } else {
                console.log(`[TCM Debug] ${message}`);
            }
        }
    }

    // Preview photos
    function handlePhotoPreview(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(`#${previewId}`).html(`<img src="${e.target.result}" alt="Preview">`);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $('#tcm-photos-before').on('change', function() {
        handlePhotoPreview(this, 'before-preview');
    });

    $('#tcm-photos-after').on('change', function() {
        handlePhotoPreview(this, 'after-preview');
    });

    // Reset form
    function resetForm() {
        $('#tcm-checkin-form')[0].reset();
        $('#before-preview').empty();
        $('#after-preview').empty();
        
        // Reset map if exists
        if (window.tcmMap && window.tcmMarker) {
            const defaultLocation = { lat: 30.1543, lng: -95.7544 };
            window.tcmMap.setCenter(defaultLocation);
            window.tcmMap.setZoom(13);
            window.tcmMarker.setPosition(defaultLocation);
        }

        // Clear fields
        $('#tcm-latitude').val('');
        $('#tcm-longitude').val('');
        $('#tcm-city').val('');
        $('#tcm-state').val('');
        $('#tcm-zip').val('');
        $('#tcm-location-input').val('');
        
        isSubmitting = false;
        formProcessed = false;
    }

    // Remove any existing submit handlers before setting up our handler
    $('#tcm-checkin-form').off('submit');

    // Handle form submission
    $('#tcm-checkin-form').on('submit', function(e) {
        e.preventDefault();
        
        if (isSubmitting || formProcessed) {
            log("Preventing duplicate submission");
            return false;
        }

        // Photo validation first
        const beforePhoto = $('#tcm-photos-before')[0].files;
        const afterPhoto = $('#tcm-photos-after')[0].files;

        if (!beforePhoto.length || !afterPhoto.length) {
            alert('Please select both before and after photos');
            return false;
        }

        // Field validation
        const required = ['tcm-technician', 'tcm-service', 'tcm-location-input'];
        let isValid = true;

        required.forEach(field => {
            const $field = $('#' + field);
            if (!$field.val()) {
                $field.addClass('invalid-field');
                isValid = false;
                log(`Invalid field: ${field}`);
            } else {
                $field.removeClass('invalid-field');
            }
        });

        if (!isValid) {
            alert('Please fill in all required fields');
            return false;
        }

        // Location validation
        if (!$('#tcm-latitude').val() || !$('#tcm-longitude').val()) {
            alert('Please select a valid location from the search results');
            return false;
        }

        log("All validations passed, proceeding with submission");
        isSubmitting = true;
        formProcessed = true;

        const $submitButton = $('.tcm-submit-button');
        $submitButton.prop('disabled', true).text('Submitting...');

        const formData = new FormData(this);
        formData.append('action', 'tcm_submit_checkin');
        formData.append('security', $('#security').val());

        log("Sending form data", {
            technician: formData.get('technician'),
            service: formData.get('service'),
            location: formData.get('location'),
            hasBeforePhoto: formData.has('photos_before'),
            hasAfterPhoto: formData.has('photos_after')
        });

        $.ajax({
            url: tcmAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                log("Response received", response);
                if (response.success) {
                    // Remove any existing messages
                    $('.tcm-success-message').remove();
                    
                    const successMsg = $('<div>', {
                        class: 'tcm-success-message',
                        css: {
                            position: 'fixed',
                            top: '50%',
                            left: '50%',
                            transform: 'translate(-50%, -50%)',
                            background: 'rgba(255, 255, 255, 0.95)',
                            padding: '20px',
                            borderRadius: '8px',
                            boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
                            zIndex: 9999,
                            textAlign: 'center'
                        }
                    }).html('<p>Check-in submitted successfully!</p><button class="tcm-close-msg">OK</button>');
                    
                    $('body').append(successMsg);
                    resetForm();
                } else {
                    log("Submission failed", response.data);
                    alert(response.data || 'Error submitting check-in. Please try again.');
                    $submitButton.prop('disabled', false).text('Submit Check-in');
                    isSubmitting = false;
                    formProcessed = false;
                }
            },
            error: function(xhr, status, error) {
                log("Ajax error", { status, error });
                alert('Error submitting check-in. Please try again.');
                $submitButton.prop('disabled', false).text('Submit Check-in');
                isSubmitting = false;
                formProcessed = false;
            }
        });

        return false;
    });

    // Handle success message close
    $('body').on('click', '.tcm-close-msg', function(e) {
        e.preventDefault();
        $(this).closest('.tcm-success-message').remove();
        location.reload();
    });

    // Remove validation styling on input
    $('input, select').on('input change', function() {
        $(this).removeClass('invalid-field');
    });

    // Add CSS
    $('<style>')
        .text(`
            .invalid-field {
                border-color: #e74c3c !important;
                background-color: #fff6f6 !important;
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
            #tcm-map {
                height: 300px;
                margin: 10px 0;
                border-radius: 4px;
            }
        `)
        .appendTo('head');
});
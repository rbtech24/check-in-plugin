
<?php
/**
 * Template Name: Single Check-in
 */

get_header(); 
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php
// Get the check-in data
$post_id = get_the_ID();
$service_type = get_post_meta($post_id, 'tcm_service', true);
$technician = get_post_meta($post_id, 'tcm_technician', true);
$city = get_post_meta($post_id, 'tcm_city', true);
$state = get_post_meta($post_id, 'tcm_state', true);
$street = get_post_meta($post_id, 'tcm_street', true);
$details = get_the_content();
$latitude = get_post_meta($post_id, 'tcm_latitude', true);
$longitude = get_post_meta($post_id, 'tcm_longitude', true);
$photos = get_post_meta($post_id, 'tcm_photos', true);

function get_street_without_number($street) {
    return trim(preg_replace('/^[\d-]+\s*/', '', $street));
}

$street_name = get_street_without_number($street);
$location = "$street_name, $city, $state";
?>

<div class="tcm-single-checkin">
    <div class="tcm-checkin-header">
        <h1 class="service-type"><?php echo esc_html($service_type); ?></h1>
        <div class="meta-info">
            <div class="technician">
                <i class="fas fa-user"></i> <span>Technician: <?php echo esc_html($technician); ?></span>
            </div>
            <div class="location">
                <i class="fas fa-map-marker-alt"></i> <span>Location: <?php echo esc_html($location); ?></span>
            </div>
            <div class="date">
                <i class="far fa-calendar"></i> <span>Date: <?php echo get_the_date('F j, Y'); ?></span>
            </div>
        </div>
    </div>

    <div class="content-section">
        <h2 class="section-title">Service Photos</h2>
        <div class="photo-grid">
            <div class="photo-container">
                <h3>Before Service</h3>
                <?php if (!empty($photos['before'])) : ?>
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($photos['before'], 'large')); ?>" 
                         alt="Before <?php echo esc_attr($service_type); ?>" 
                         class="service-photo">
                <?php endif; ?>
            </div>
            <div class="photo-container">
                <h3>After Service</h3>
                <?php if (!empty($photos['after'])) : ?>
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($photos['after'], 'large')); ?>" 
                         alt="After <?php echo esc_attr($service_type); ?>" 
                         class="service-photo">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content-section">
        <h2 class="section-title">Service Details</h2>
        <div class="details-content">
            <?php echo wpautop(wp_kses_post($details)); ?>
        </div>
    </div>

    <div class="content-section">
        <h2 class="section-title">Service Location</h2>
        <div id="service-map" class="service-map"></div>
    </div>
</div>

<style>
    body {
        margin: 0;
        padding: 0;
        font-family: system-ui, -apple-system, sans-serif;
        line-height: 1.6;
        color: #333;
        background: #f5f5f5;
    }

    .tcm-single-checkin {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .tcm-checkin-header {
        text-align: center;
        margin-bottom: 40px;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .service-type {
        font-size: 36px;
        color: #2c3e50;
        margin: 0 0 20px;
    }

    .meta-info {
        display: flex;
        justify-content: center;
        gap: 30px;
        flex-wrap: wrap;
        color: #666;
    }

    .meta-info > div {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .meta-info i {
        color: #39B54A;
    }

    .content-section {
        background: white;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .section-title {
        color: #2c3e50;
        margin: 0 0 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f5f5f5;
    }

    .details-content {
        line-height: 1.8;
        color: #444;
    }

    .photo-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    .photo-container {
        text-align: center;
    }

    .photo-container h3 {
        margin: 0 0 15px;
        color: #2c3e50;
    }

    .service-photo {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .service-photo:hover {
        transform: scale(1.02);
    }

    .service-map {
        height: 400px;
        border-radius: 8px;
    }

    .photo-lightbox {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        cursor: pointer;
    }

    .lightbox-content {
        max-width: 90%;
        max-height: 90vh;
    }

    .lightbox-content img {
        max-width: 100%;
        max-height: 90vh;
        object-fit: contain;
    }

    @media (max-width: 768px) {
        .tcm-single-checkin {
            margin: 20px auto;
            padding: 0 15px;
        }

        .service-type {
            font-size: 28px;
        }

        .meta-info {
            flex-direction: column;
            gap: 15px;
        }

        .photo-grid {
            grid-template-columns: 1fr;
        }

        .service-photo {
            height: 300px;
        }
    }
</style>

<?php if ($latitude && $longitude) : ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const map = L.map('service-map').setView([<?php echo floatval($latitude); ?>, <?php echo floatval($longitude); ?>], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        L.marker([<?php echo floatval($latitude); ?>, <?php echo floatval($longitude); ?>])
            .addTo(map)
            .bindPopup('<?php echo esc_js($location); ?>');
    });
</script>
<?php endif; ?>

<script>
    document.querySelectorAll('.service-photo').forEach(photo => {
        photo.addEventListener('click', function() {
            const lightbox = document.createElement('div');
            lightbox.className = 'photo-lightbox';
            lightbox.innerHTML = `
                <div class="lightbox-content">
                    <img src="${this.src}" alt="${this.alt}">
                </div>
            `;
            
            document.body.appendChild(lightbox);
            
            lightbox.addEventListener('click', function() {
                this.remove();
            });

            document.addEventListener('keyup', function(e) {
                if (e.key === 'Escape') {
                    lightbox.remove();
                }
            });
        });
    });
</script>

<?php get_footer(); ?>
<?php
/**
 * Template Name: Single Tech Check-in
 */

get_header(); 
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php
// Get the check-in data
$post_id = get_the_ID();
$service_type = get_post_meta($post_id, 'tcm_service', true);
$technician = get_post_meta($post_id, 'tcm_technician', true);
$city = get_post_meta($post_id, 'tcm_city', true);
$state = get_post_meta($post_id, 'tcm_state', true);
$street = get_post_meta($post_id, 'tcm_street', true);
$details = get_the_content();
$latitude = get_post_meta($post_id, 'tcm_latitude', true);
$longitude = get_post_meta($post_id, 'tcm_longitude', true);
$photos = get_post_meta($post_id, 'tcm_photos', true);

function get_street_without_number($street) {
    return trim(preg_replace('/^[\d-]+\s*/', '', $street));
}

$street_name = get_street_without_number($street);
$location = "$street_name, $city, $state";
?>

<div class="tcm-single-checkin">
    <div class="tcm-checkin-header">
        <h1 class="service-type"><?php echo esc_html($service_type); ?></h1>
        <div class="meta-info">
            <div class="technician">
                <i class="fas fa-user"></i> <span>Technician: <?php echo esc_html($technician); ?></span>
            </div>
            <div class="location">
                <i class="fas fa-map-marker-alt"></i> <span>Location: <?php echo esc_html($location); ?></span>
            </div>
            <div class="date">
                <i class="far fa-calendar"></i> <span>Date: <?php echo get_the_date('F j, Y'); ?></span>
            </div>
        </div>
    </div>

    <div class="content-section">
        <h2 class="section-title">Service Photos</h2>
        <div class="photo-grid">
            <div class="photo-container">
                <h3>Before Service</h3>
                <?php if (!empty($photos['before'])) : ?>
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($photos['before'], 'large')); ?>" 
                         alt="Before <?php echo esc_attr($service_type); ?>" 
                         class="service-photo">
                <?php endif; ?>
            </div>
            <div class="photo-container">
                <h3>After Service</h3>
                <?php if (!empty($photos['after'])) : ?>
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($photos['after'], 'large')); ?>" 
                         alt="After <?php echo esc_attr($service_type); ?>" 
                         class="service-photo">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content-section">
        <h2 class="section-title">Service Details</h2>
        <div class="details-content">
            <?php echo wpautop(wp_kses_post($details)); ?>
        </div>
    </div>

    <?php if ($latitude && $longitude) : ?>
    <div class="content-section">
        <h2 class="section-title">Service Location</h2>
        <div id="service-map" class="service-map"></div>
    </div>
    <?php endif; ?>
</div>

<style>
    .tcm-single-checkin {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .service-type {
        font-size: 36px;
        margin-bottom: 20px;
        color: #2c3e50;
    }

    .meta-info {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 30px;
    }

    .meta-info > div {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .content-section {
        background: white;
        border-radius: 8px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .section-title {
        margin: 0 0 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f5f5f5;
    }

    .photo-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .service-map {
        height: 400px;
        border-radius: 8px;
    }

    @media (max-width: 768px) {
        .photo-grid {
            grid-template-columns: 1fr;
        }
        .meta-info {
            flex-direction: column;
        }
    }
</style>

<?php if ($latitude && $longitude) : ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const map = L.map('service-map').setView([<?php echo floatval($latitude); ?>, <?php echo floatval($longitude); ?>], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        L.marker([<?php echo floatval($latitude); ?>, <?php echo floatval($longitude); ?>])
            .addTo(map)
            .bindPopup('<?php echo esc_js($location); ?>');
    });
</script>
<?php endif; ?>

<?php get_footer(); ?>

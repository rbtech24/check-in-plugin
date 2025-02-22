<?php
/**
 * Template Name: Single Check-in
 */

// Add custom meta title and description
$meta_title = sprintf('%s in %s, %s | %s', 
    esc_html($service_type),
    esc_html($city),
    esc_html($state),
    get_bloginfo('name')
);
add_filter('pre_get_document_title', function() use ($meta_title) {
    return $meta_title;
});

$meta_desc = sprintf('Professional %s service completed in %s, %s on %s. View before and after photos of our work.',
    esc_html($service_type),
    esc_html($city),
    esc_html($state),
    get_the_date()
);
add_action('wp_head', function() use ($meta_desc) {
    echo '<meta name="description" content="' . esc_attr($meta_desc) . '">';
}, 1);

get_header(); 
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="tcm-single-checkin-container">
    <?php while (have_posts()) : the_post(); 
        $post_id = get_the_ID();
        $service_type = get_post_meta($post_id, 'tcm_service', true);
        $technician = get_post_meta($post_id, 'tcm_technician', true);
        $city = get_post_meta($post_id, 'tcm_city', true);
        $state = get_post_meta($post_id, 'tcm_state', true);
        $street = get_post_meta($post_id, 'tcm_street', true);
        $latitude = get_post_meta($post_id, 'tcm_latitude', true);
        $longitude = get_post_meta($post_id, 'tcm_longitude', true);
        $photos = get_post_meta($post_id, 'tcm_photos', true);
    ?>
        <div class="tcm-single-header">
            <h1 class="tcm-single-title"><?php echo esc_html($service_type); ?></h1>
            <div class="tcm-meta-info">
                <span class="tcm-technician"><i class="fas fa-user"></i> <?php echo esc_html($technician); ?></span>
                <span class="tcm-date"><i class="fas fa-calendar"></i> <?php echo get_the_date('F j, Y'); ?></span>
            </div>
        </div>

        <div class="tcm-single-content">
            <div class="tcm-location-section">
                <h2><i class="fas fa-map-marker-alt"></i> Location</h2>
                <p><?php echo esc_html("$street, $city, $state"); ?></p>
                <?php if ($latitude && $longitude): ?>
                    <div id="tcm-map" class="tcm-single-map"></div>
                <?php endif; ?>
            </div>

            <div class="tcm-details-section">
                <h2><i class="fas fa-info-circle"></i> Service Details</h2>
                <?php the_content(); ?>
            </div>

            <?php if (!empty($photos)): ?>
            <div class="tcm-photos-section">
                <h2><i class="fas fa-images"></i> Service Photos</h2>
                <div class="tcm-photos-grid">
                    <?php foreach ($photos as $type => $photo_id): 
                        $img_url = wp_get_attachment_image_url($photo_id, 'large');
                        if ($img_url):
                    ?>
                        <div class="tcm-photo-item">
                            <span class="tcm-photo-label"><?php echo esc_html(ucfirst($type)); ?></span>
                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr("$type photo"); ?>" loading="lazy">
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php 
            // Add feedback form
            $checkin_id = get_the_ID();
            include(plugin_dir_path(__FILE__) . 'feedback-form.php');
        ?>
    </div>
<?php endwhile; ?>
</div>
<script src="<?php echo plugins_url('js/feedback.js', dirname(__FILE__)); ?>"></script>
<style>
            .tcm-single-checkin-container {
                max-width: 1200px;
                margin: 2rem auto;
                padding: 0 1rem;
            }
            .tcm-single-header {
                text-align: center;
                margin-bottom: 2rem;
            }
            .tcm-single-title {
                font-size: 2.5rem;
                color: #2c3e50;
                margin-bottom: 1rem;
            }
            .tcm-meta-info {
                display: flex;
                justify-content: center;
                gap: 2rem;
                color: #666;
            }
            .tcm-meta-info i {
                margin-right: 0.5rem;
            }
            .tcm-single-content {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .tcm-location-section,
            .tcm-details-section,
            .tcm-photos-section {
                padding: 2rem;
                border-bottom: 1px solid #eee;
            }
            .tcm-single-map {
                height: 400px;
                margin-top: 1rem;
                border-radius: 8px;
            }
            .tcm-photos-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 1.5rem;
                margin-top: 1.5rem;
            }
            .tcm-photo-item {
                position: relative;
                border-radius: 8px;
                overflow: hidden;
                aspect-ratio: 4/3;
            }
            .tcm-photo-label {
                position: absolute;
                top: 1rem;
                left: 1rem;
                background: rgba(0,0,0,0.7);
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 4px;
                font-size: 0.9rem;
            }
            .tcm-photo-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            @media (max-width: 768px) {
                .tcm-single-title {
                    font-size: 2rem;
                }
                .tcm-meta-info {
                    flex-direction: column;
                    gap: 1rem;
                }
                .tcm-photos-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    <?php get_footer(); ?>
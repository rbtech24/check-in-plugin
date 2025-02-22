<?php
if (!defined('ABSPATH')) exit;

$title = !empty($atts['title']) ? $atts['title'] : 'Recent Service Check-ins';

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$args = array(
    'post_type' => 'tech_checkin',
    'posts_per_page' => 8,
    'orderby' => 'date',
    'order' => 'DESC',
    'paged' => $paged
);

// Add service filter if set in shortcode
if (!empty($atts['service'])) {
    $args['meta_query'][] = array(
        'key' => 'tcm_service',
        'value' => $atts['service'],
        'compare' => '='
    );
}

// Add location filter if set in shortcode
if (!empty($atts['location'])) {
    $args['meta_query'][] = array(
        'key' => 'tcm_city',
        'value' => $atts['location'],
        'compare' => 'LIKE'
    );
}

$check_ins = new WP_Query($args);
$maps_data = array();
?>

<!-- Include Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Location-based meta tags -->
<?php if (!empty($atts['location'])): ?>
<meta name="geo.placename" content="<?php echo esc_attr($atts['location']); ?>">
<meta name="geo.region" content="<?php echo esc_attr($atts['location']); ?>">
<link rel="alternate" type="application/rss+xml" title="<?php echo esc_attr($service_text . ' in ' . $atts['location']); ?>" href="<?php echo esc_url(get_feed_link()); ?>">
<?php endif; ?>
<script src="<?php echo plugins_url('public/js/pwa-installer.js', dirname(dirname(__FILE__))); ?>"></script>

<?php
$location_text = !empty($atts['location']) ? " in " . esc_html($atts['location']) : "";
$service_text = !empty($atts['service']) ? esc_html($atts['service']) : "Service";
?>
<div class="tcm-checkins-container">
    <div id="pwa-install-button" class="pwa-install-button" style="display: none;">
        <button class="install-pwa-btn">
            <span class="icon">📱</span>
            Install Tech Check-in App
        </button>
    </div>
    <h1 class="tcm-section-title"><?php echo esc_html($title); ?></h1>
    <h2 class="tcm-section-subtitle"><?php echo esc_html($service_text . $location_text); ?> Reviews & Check-ins</h2>

    <!-- LocalBusiness Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "<?php echo esc_js(get_bloginfo('name')); ?>",
        "description": "<?php echo esc_js($service_text . ' services' . $location_text); ?>",
        "areaServed": "<?php echo esc_js($atts['location'] ?? ''); ?>",
        "serviceType": "<?php echo esc_js($service_text); ?>"
    }
    </script>

    <div class="tcm-checkins-grid">
        <?php if ($check_ins->have_posts()) : while ($check_ins->have_posts()) : $check_ins->the_post(); 
            $meta = get_post_meta(get_the_ID());
            $photos = get_post_meta(get_the_ID(), 'tcm_photos', true);
            $service_type = $meta['tcm_service'][0] ?? '';
            $location_city = $meta['tcm_city'][0] ?? '';
            $location_state = $meta['tcm_state'][0] ?? '';
            ?>
            <article class="tcm-checkin-card" itemscope itemtype="https://schema.org/ServiceEvent">
                <h2 class="service-title" itemprop="name"><?php echo esc_html($service_type); ?></h2>

                <div class="checkin-meta">
                    <div class="technician-info">
                        <span class="technician-label">Technician:</span>
                        <span class="technician-name" itemprop="performer"><?php echo esc_html($meta['tcm_technician'][0] ?? ''); ?></span>
                    </div>
                    <time class="date" datetime="<?php echo get_the_date('c'); ?>" itemprop="startDate">
                        <?php echo get_the_date('F j, Y'); ?>
                    </time>
                </div>
                <meta itemprop="location" content="<?php echo esc_attr("$location_city, $location_state"); ?>" />
                <meta itemprop="serviceType" content="<?php echo esc_attr($service_type); ?>" />

                <div class="checkin-location">
                    <span class="tcm-map-marker">📍</span>
                    <span><?php 
                        $street = $meta['tcm_street'][0] ?? '';
                        $zip = $meta['tcm_zip'][0] ?? '';
                        // Remove building number from street
                        $street = preg_replace('/^\d+\s*/', '', $street);
                        echo esc_html(trim("$street, $location_city, $location_state $zip")); 
                    ?></span>
                </div>

                <?php 
                $lat = $meta['tcm_latitude'][0] ?? '';
                $lng = $meta['tcm_longitude'][0] ?? '';
                if (!empty($lat) && !empty($lng)) : 
                    $map_id = 'map-' . get_the_ID();
                    $maps_data[] = array(
                        'id' => $map_id,
                        'lat' => floatval($lat),
                        'lng' => floatval($lng),
                        'url' => get_permalink(),
                        'title' => esc_js($service_type . ' in ' . $location_city . ', ' . $location_state)
                    );
                ?>
                    <div class="checkin-map-container">
                        <div id="<?php echo esc_attr($map_id); ?>" class="checkin-map"></div>
                    </div>
                <?php endif; ?>

                <div class="checkin-content">
                    <?php the_content(); ?>
                </div>

                <?php if (!empty($photos)) : ?>
                    <div class="checkin-photos">
                        <?php if (!empty($photos['before'])) : ?>
                            <div class="photo-container">
                                <span class="photo-label">Before</span>
                                <img src="<?php echo esc_url(wp_get_attachment_url($photos['before'])); ?>" 
                                     alt="Before <?php echo esc_attr($service_type); ?>" 
                                     onclick="openLightbox(this.src)">
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($photos['after'])) : ?>
                            <div class="photo-container">
                                <span class="photo-label">After</span>
                                <img src="<?php echo esc_url(wp_get_attachment_url($photos['after'])); ?>" 
                                     alt="After <?php echo esc_attr($service_type); ?>" 
                                     onclick="openLightbox(this.src)">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <footer class="checkin-footer">
                    <div class="checkin-tags">
                        <?php
                        $location_tag = str_replace(' ', '', $location_city) . $location_state;
                        $service_slug = sanitize_title($service_type);
                        $tags = array(
                            "#{$service_slug}",
                            "#{$location_tag}",
                            "#{$service_slug}{$location_state}",
                            "#{$location_state}Service",
                            "#{$service_slug}Service"
                        );
                        echo implode(' ', array_filter($tags));
                        ?>
                    </div>
                </footer>
            </article>
        <?php endwhile; ?>
        <?php else : ?>
            <p>No check-ins found.</p>
        <?php endif; ?>

        <div class="tcm-pagination">
            <?php 
            echo paginate_links(array(
                'total' => $check_ins->max_num_pages,
                'current' => $paged,
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;'
            ));
            ?>
        </div>
        <?php wp_reset_postdata(); ?>
    </div>
</div>

<style>
.tcm-pagination {
    text-align: center;
    margin: 30px 0;
    padding: 20px;
}

.tcm-pagination .page-numbers {
    padding: 8px 14px;
    margin: 0 5px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
}

.tcm-pagination .page-numbers.current {
    background: #39B54A;
    color: white;
    border-color: #39B54A;
}

.tcm-pagination .page-numbers:hover:not(.current) {
    background: #f5f5f5;
}
</style>

<!-- Lightbox -->
<div id="tcm-lightbox" class="tcm-lightbox">
    <span class="lightbox-close">&times;</span>
    <img class="lightbox-content" id="lightbox-img">
</div>

<style>
.tcm-section-title {
    text-align: center;
    margin-bottom: 2rem;
    color: #2c3e50;
    font-size: 32px;
    font-weight: 600;
    line-height: 1.2;
    padding: 0 1rem;
}

.tcm-checkins-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.tcm-checkins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 25px;
}

.tcm-checkin-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.tcm-checkin-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.service-title {
    margin: 0;
    padding: 20px;
    font-size: 28px;
    color: #2c3e50;
    font-weight: 600;
    line-height: 1.2;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.checkin-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.technician-info {
    display: flex;
    gap: 5px;
}

.technician-label {
    color: #4a5568;
    font-weight: 600;
}

.technician-name {
    color: #2c3e50;
}

.date {
    color: #7f8c8d;
}

.checkin-location {
    padding: 15px 20px;
    background: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1em;
    color: #2c3e50;
    border-bottom: 1px solid #eee;
}

.checkin-map-container {
    width: 100%;
    height: 250px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}

.checkin-map {
    width: 100%;
    height: 100%;
}

.checkin-content {
    padding: 20px;
    font-size: 1.1em;
    line-height: 1.6;
    color: #2c3e50;
    background: #fff;
    border-bottom: 1px solid #eee;
    margin: 0;
}

.checkin-content p {
    margin: 0 0 10px 0;
}

.checkin-content p:last-child {
    margin-bottom: 0;
}

.checkin-photos {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.photo-container {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.photo-label {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    z-index: 1;
}

.photo-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.photo-container:hover img {
    transform: scale(1.05);
}

.checkin-footer {
    padding: 15px 20px;
    background: #f8f9fa;
}

.checkin-tags {
    font-size: 0.9em;
    color: #3498db;
    word-spacing: 8px;
    line-height: 1.6;
}

/* Lightbox */
.tcm-lightbox {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    z-index: 1000;
}

.lightbox-content {
    max-width: 90%;
    max-height: 90vh;
    margin: auto;
    display: block;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.lightbox-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 30px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .tcm-checkins-grid {
        grid-template-columns: 1fr;
    }

    .service-title {
        font-size: 24px;
    }

    .checkin-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .checkin-content {
        font-size: 1em;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const maps = <?php echo json_encode($maps_data); ?>;

    maps.forEach(function(mapData) {
        const mapElement = document.getElementById(mapData.id);
        if (mapElement) {
            const map = L.map(mapData.id, {
                zoomControl: true,
                scrollWheelZoom: false
            }).setView([mapData.lat, mapData.lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            L.marker([mapData.lat, mapData.lng]).addTo(map).bindPopup(`<a href="${mapData.url}">${mapData.title}</a>`).openPopup();

            // Add zoom control to top-right
            L.control.zoom({
                position: 'topright'
            }).addTo(map);
        }
    });
});

// Lightbox functionality
function openLightbox(src) {
    const lightbox = document.getElementById('tcm-lightbox');
    const img = document.getElementById('lightbox-img');
    img.src = src;
    lightbox.style.display = 'block';
}

document.querySelector('.lightbox-close').onclick = function() {
    document.getElementById('tcm-lightbox').style.display = 'none';
}

document.getElementById('tcm-lightbox').onclick = function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
}
</script>
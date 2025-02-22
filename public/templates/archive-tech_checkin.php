<?php get_header(); ?>

<div class="tcm-archive-container">
    <h1 class="tcm-archive-title">Service Check-ins</h1>

    <div class="tcm-checkins-grid">
        <?php if (have_posts()) : while (have_posts()) : the_post(); 
            $service_type = get_post_meta(get_the_ID(), 'tcm_service', true);
            $technician = get_post_meta(get_the_ID(), 'tcm_technician', true);
            $city = get_post_meta(get_the_ID(), 'tcm_city', true);
            $state = get_post_meta(get_the_ID(), 'tcm_state', true);
            $details = get_the_content();
        ?>
            <article class="tcm-checkin-card">
                <div class="tcm-card-header">
                    <h2 class="tcm-card-title"><?php echo esc_html($service_type); ?></h2>
                    <div class="tcm-card-meta">
                        <span class="tcm-technician"><?php echo esc_html($technician); ?></span>
                        <span class="tcm-location"><?php echo esc_html("$city, $state"); ?></span>
                        <span class="tcm-date"><?php echo get_the_date(); ?></span>
                    </div>
                </div>

                <div class="tcm-card-content">
                    <div class="tcm-card-excerpt">
                        <?php echo wp_trim_words($details, 20); ?>
                    </div>

                    <a href="<?php the_permalink(); ?>" class="tcm-read-more">
                        View Service Details →
                    </a>
                </div>
            </article>
        <?php endwhile; ?>

        <?php else: ?>
            <div class="tcm-no-results">
                <h2>No service check-ins found</h2>
                <p>There are currently no service check-ins to display.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php echo paginate_links(); ?>
</div>

<style>
.tcm-archive-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.tcm-checkins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}

.tcm-checkin-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tcm-card-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.tcm-card-title {
    margin: 0 0 10px;
    font-size: 24px;
    color: #333;
}

.tcm-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 14px;
    color: #666;
}

.tcm-card-content {
    padding: 20px;
}

.tcm-card-excerpt {
    margin-bottom: 20px;
    line-height: 1.6;
}

.tcm-read-more {
    display: inline-block;
    padding: 8px 16px;
    background: #0066cc;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.2s;
}

.tcm-read-more:hover {
    background: #0052a3;
}

.tcm-no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
}
</style>

<?php get_footer(); ?>
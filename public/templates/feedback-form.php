
<div class="tcm-feedback-form" data-checkin-id="<?php echo esc_attr($checkin_id); ?>">
    <h3><?php _e('Rate Our Service', 'tech-checkin-maps'); ?></h3>
    <div class="rating-stars">
        <?php for($i = 1; $i <= 5; $i++): ?>
            <span class="star" data-rating="<?php echo $i; ?>" role="button" tabindex="0">★</span>
        <?php endfor; ?>
    </div>
    <textarea placeholder="<?php _e('Share your feedback...', 'tech-checkin-maps'); ?>" class="feedback-text" rows="4"></textarea>
    <button class="submit-feedback"><?php _e('Submit Feedback', 'tech-checkin-maps'); ?></button>
    <div class="feedback-message"></div>
</div>

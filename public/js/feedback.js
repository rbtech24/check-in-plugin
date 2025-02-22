document.addEventListener('DOMContentLoaded', () => {
    const ratingStars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('rating-input');
    const feedbackForm = document.getElementById('feedback-form');
    const submitButton = document.getElementById('submit-feedback');

    // Star rating interaction
    ratingStars.forEach((star, index) => {
        star.addEventListener('mouseover', () => {
            for (let i = 0; i <= index; i++) {
                ratingStars[i].classList.add('hover');
            }
        });

        star.addEventListener('mouseout', () => {
            ratingStars.forEach(s => s.classList.remove('hover'));
        });

        star.addEventListener('click', () => {
            ratingInput.value = index + 1;
            ratingStars.forEach((s, i) => {
                s.classList.toggle('active', i <= index);
            });
            submitButton.disabled = false;
        });
    });

    // Form submission with animation
    feedbackForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        submitButton.disabled = true;
        submitButton.textContent = 'Sending...';

        try {
            const response = await fetch(tcmAjax.ajaxurl, {
                method: 'POST',
                body: new FormData(feedbackForm)
            });

            if (response.ok) {
                showFeedbackSuccess();
            } else {
                throw new Error('Submission failed');
            }
        } catch (error) {
            showFeedbackError();
        }
    });
});

function showFeedbackSuccess() {
    const successMessage = document.createElement('div');
    successMessage.className = 'feedback-success';
    successMessage.textContent = 'Thank you for your feedback!';
    document.getElementById('feedback-form').replaceWith(successMessage);
}

function showFeedbackError() {
    const errorMessage = document.createElement('div');
    errorMessage.className = 'feedback-error';
    errorMessage.textContent = 'Something went wrong. Please try again.';
    document.getElementById('feedback-form').appendChild(errorMessage);
}
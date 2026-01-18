/**
 * LeadCrafter - Grand Slam Lead Magnets AJAX Submission
 */
document.addEventListener('DOMContentLoaded', function () {
    // Check if leadCrafterData is available
    if (typeof leadCrafterData === 'undefined') {
        console.error('LeadCrafter: leadCrafterData is not defined. Please check plugin configuration.');
        return;
    }

    const forms = document.querySelectorAll('.leadcrafter-form');

    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const wrap = this.closest('.leadcrafter-form-wrap');
            const messageEl = this.querySelector('.leadcrafter-message');
            const submitBtn = this.querySelector('button[type="submit"]');
            const emailInput = this.querySelector('input[type="email"]');
            
            const email = emailInput.value;
            const formId = wrap.dataset.formId;

            // Update UI to loading state
            submitBtn.disabled = true;
            const originalBtnText = submitBtn.textContent;
            submitBtn.textContent = '...';
            messageEl.textContent = '';
            messageEl.className = 'leadcrafter-message';

            const formData = new URLSearchParams();
            formData.append('action', 'leadcrafter_subscribe');
            formData.append('email', email);
            formData.append('form_id', formId);
            formData.append('nonce', leadCrafterData.nonce);

            fetch(leadCrafterData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageEl.textContent = data.data.message || 'Success!';
                    messageEl.classList.add('success');
                    emailInput.value = '';
                    
                    // Reset button after small delay
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalBtnText;
                    }, 2000);
                } else {
                    messageEl.textContent = data.data || 'An error occurred. Please try again.';
                    messageEl.classList.add('error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Grand Slam Lead Magnets Error:', error);
                messageEl.textContent = 'Connection error. Please try again.';
                messageEl.classList.add('error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            });
        });
    });
});

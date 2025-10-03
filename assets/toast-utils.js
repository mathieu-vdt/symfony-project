// Enhanced Toast Utilities for better UX
// This file can be imported in controllers or used globally

// Utility function to show different types of toasts
window.showToast = {
    success: (message, duration = 5000) => createToast(message, 'success', duration),
    error: (message, duration = 7000) => createToast(message, 'error', duration),
    warning: (message, duration = 6000) => createToast(message, 'warning', duration),
    info: (message, duration = 5000) => createToast(message, 'info', duration),
    
    // Quick presets for common actions
    saved: () => createToast('✓ Successfully saved!', 'success', 3000),
    deleted: () => createToast('🗑️ Successfully deleted!', 'success', 3000),
    updated: () => createToast('✏️ Successfully updated!', 'success', 3000),
    created: () => createToast('➕ Successfully created!', 'success', 3000),
    
    // Error presets
    networkError: () => createToast('🌐 Network error. Please check your connection.', 'error', 7000),
    validationError: () => createToast('📝 Please check your input and try again.', 'warning', 6000),
    permissionError: () => createToast('🔒 You don\'t have permission for this action.', 'error', 6000),
    
    // Clear all toasts
    clear: () => window.toastManager?.clear()
};

// Enhanced form submission handling with toasts
window.enhanceFormsWithToasts = function() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.textContent || submitBtn.value;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
                
                // Re-enable after 5 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }, 5000);
            }
        });
    });
};

// Auto-enhance forms when DOM is ready
document.addEventListener('DOMContentLoaded', window.enhanceFormsWithToasts);
document.addEventListener('turbo:load', window.enhanceFormsWithToasts);

// Demo function for testing toasts (remove in production)
window.demoToasts = function() {
    setTimeout(() => showToast.success('Welcome! This is a success message.'), 500);
    setTimeout(() => showToast.info('Here\'s some helpful information for you.'), 1000);
    setTimeout(() => showToast.warning('Please note this important warning.'), 1500);
    setTimeout(() => showToast.error('This is what an error looks like.'), 2000);
};
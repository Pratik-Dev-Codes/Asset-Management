// Initialize Laravel data
document.addEventListener('DOMContentLoaded', function() {
    // The window.laravelData will be set from the Blade template
    if (window.laravelData) {
        window.Laravel = window.laravelData;
        
        // Configure Axios if available
        if (window.axios) {
            var token = window.Laravel.csrfToken || '';
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
            window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            window.axios.defaults.withCredentials = true;
        }
    }
});

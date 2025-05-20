document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    // Initialize theme based on current class or system preference
    const initializeTheme = () => {
        const savedTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        const isDark = savedTheme === 'dark' || (!savedTheme && prefersDarkScheme.matches);
        
        // Apply theme
        document.documentElement.classList.toggle('dark', isDark);
        
        // Update toggle state if it exists
        if (darkModeToggle) {
            darkModeToggle.checked = isDark;
            updateToggleIcon(isDark);
        }
        
        return isDark;
    };
    
    // Initialize the theme
    let isDark = initializeTheme();
    
    // Handle toggle changes
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', async function() {
            try {
                isDark = this.checked;
                
                // Update UI immediately for better responsiveness
                document.documentElement.classList.toggle('dark', isDark);
                updateToggleIcon(isDark);
                
                // Save preference to server
                const response = await fetch('/theme/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ dark_mode: isDark })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to update theme preference');
                }
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to update theme preference');
                }
                
            } catch (error) {
                console.error('Error updating theme:', error);
                // Revert UI on error
                isDark = !isDark;
                document.documentElement.classList.toggle('dark', isDark);
                if (darkModeToggle) {
                    darkModeToggle.checked = isDark;
                    updateToggleIcon(isDark);
                }
                
                // Show error message to user
                showToast('Failed to update theme preference. Please try again.', 'error');
            }
        });
    }
    
    // Listen for system theme changes (only if no user preference is set)
    prefersDarkScheme.addEventListener('change', e => {
        if (!document.documentElement.hasAttribute('data-theme')) {
            const isDark = e.matches;
            document.documentElement.classList.toggle('dark', isDark);
            if (darkModeToggle) {
                darkModeToggle.checked = isDark;
                updateToggleIcon(isDark);
            }
        }
    });
    
    // Update toggle icon based on theme
    function updateToggleIcon(isDark) {
        const icon = darkModeToggle?.querySelector('i.fas');
        if (icon) {
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        // Check if toast container exists, if not create it
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'fixed bottom-4 right-4 z-50 space-y-2';
            document.body.appendChild(toastContainer);
        }
        
        const toast = document.createElement('div');
        const typeClasses = {
            success: 'bg-green-100 border-green-500 text-green-700',
            error: 'bg-red-100 border-red-500 text-red-700',
            warning: 'bg-yellow-100 border-yellow-500 text-yellow-700',
            info: 'bg-blue-100 border-blue-500 text-blue-700'
        };
        
        toast.className = `border-l-4 p-4 rounded shadow-lg ${typeClasses[type] || typeClasses.info} transition-all duration-300 transform translate-x-full opacity-0`;
        toast.role = 'alert';
        toast.innerHTML = `
            <div class="flex items-center">
                <div class="flex-1">${message}</div>
                <button type="button" class="ml-4 text-gray-500 hover:text-gray-700" onclick="this.parentElement.parentElement.remove()">
                    <span class="sr-only">Close</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Trigger reflow
        void toast.offsetWidth;
        
        // Show toast
        toast.classList.remove('translate-x-full', 'opacity-0');
        
        // Auto-remove after delay
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
});

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    const strengthBar = document.getElementById('password-strength');
    
    // Reset all indicators
    document.querySelectorAll('#length, #uppercase, #lowercase, #number').forEach(el => {
    });
    
    // Check password requirements
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    
    // Update indicators with icons
    const updateIndicator = (element, isValid) => {
        if (!element) return;
        const icon = element.querySelector('i');
        if (isValid) {
            element.classList.add('text-success');
            element.classList.remove('text-danger');
            if (icon) {
                icon.className = 'fas fa-check-circle me-1 text-success';
            }
        } else {
            element.classList.add('text-danger');
            element.classList.remove('text-success');
            if (icon) {
                icon.className = 'fas fa-circle-xmark me-1';
            }
        }
    };
    
    updateIndicator(lengthEl, hasLength);
    updateIndicator(uppercaseEl, hasUppercase);
    updateIndicator(lowercaseEl, hasLowercase);
    updateIndicator(numberEl, hasNumber);
    
    // Calculate strength score (0-4)
    const strength = [hasLength, hasUppercase, hasLowercase, hasNumber].filter(Boolean).length;
    const strengthText = ['Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong'][strength];
    const strengthClass = ['danger', 'warning', 'info', 'primary', 'success'][strength];
    
    // Update strength meter
    const strengthMeter = document.getElementById('password-strength');
    if (strengthMeter) {
        strengthMeter.innerHTML = `
            <div class="progress" style="height: 5px;">
                <div class="progress-bar bg-${strengthClass}" role="progressbar" 
                     style="width: ${strength * 25}%" 
                     aria-valuenow="${strength * 25}" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
            <small class="d-block mt-2 text-${strengthClass} fw-bold">
                <i class="fas fa-${strength > 2 ? 'check' : 'exclamation'}-circle me-1"></i>
                Password Strength: ${strengthText}
            </small>
        `;
    }
    
    // Enable/disable submit button based on strength
    const submitBtn = document.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = strength < 3;
    }
    
    return strength >= 3; // At least 3 out of 4 requirements met
}

// Check if passwords match
function checkPasswordMatch() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password-confirm');
    const matchMessage = document.getElementById('password-match');
    
    if (!password || !confirmPassword || !matchMessage) return false;
    
    const passwordValue = password.value;
    const confirmValue = confirmPassword.value;
    
    if (!confirmValue) {
        matchMessage.innerHTML = '';
        return false;
    }
    
    if (passwordValue === confirmValue) {
        matchMessage.innerHTML = `
            <div class="d-flex align-items-center text-success">
                <i class="fas fa-check-circle me-2"></i>
                <span>Passwords match</span>
            </div>
        `;
        confirmPassword.setCustomValidity('');
        return true;
    } else {
        matchMessage.innerHTML = `
            <div class="d-flex align-items-center text-danger">
                <i class="fas fa-times-circle me-2"></i>
                <span>Passwords do not match</span>
            </div>
        `;
        confirmPassword.setCustomValidity("Passwords do not match");
        return false;
    }
}

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    // Find the toggle button - it could be a sibling or parent element
    let toggleBtn = field.parentElement.querySelector('button[onclick*="togglePassword"]');
    
    if (!toggleBtn) {
        // Try to find the button in the next sibling (for input-group-append)
        toggleBtn = field.parentElement.nextElementSibling?.querySelector('button[onclick*="togglePassword"]');
    }
    
    const icon = toggleBtn?.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
        toggleBtn?.setAttribute('aria-label', 'Hide password');
    } else {
        field.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
        toggleBtn?.setAttribute('aria-label', 'Show password');
    }
    
    // Focus the password field after toggling
    field.focus();
}

// Initialize password validation
function initPasswordValidation() {
    // Add event listeners for password strength checking
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password-confirm');
    
    if (passwordInput) {
        // Check strength on input
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            
            // If confirm password has value, validate match
            if (confirmPasswordInput && confirmPasswordInput.value) {
                checkPasswordMatch();
            }
        });
        
        // Check strength on page load if password is pre-filled
        if (passwordInput.value) {
            checkPasswordStrength(passwordInput.value);
        }
    }
    
    // Add event listener for confirm password
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            checkPasswordMatch();
            
            // If password changes after confirm password, re-validate
            if (passwordInput && passwordInput.value) {
                checkPasswordMatch();
            }
        });
        
        // Check match on page load if confirm password is pre-filled
        if (confirmPasswordInput.value && passwordInput?.value) {
            checkPasswordMatch();
        }
    }
    
    // Initialize password visibility toggles
    document.querySelectorAll('[onclick^="togglePassword("]').forEach(button => {
        button.setAttribute('type', 'button');
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
        
        // Add keyboard support
        button.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                button.click();
            }
        });
    });
    
    // Add form validation
    const forms = document.querySelectorAll('form.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
            
            // Additional custom validation
            if (passwordInput && confirmPasswordInput) {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Scroll to the first error
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus();
                    }
                }
            }
        }, false);
    });
}

// Initialize when the DOM is fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordValidation);
} else {
    // DOMContentLoaded has already fired
    initPasswordValidation();
}

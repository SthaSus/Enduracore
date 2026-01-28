// Toggle password visibility
window.togglePassword = function (fieldId) {
    const field = document.getElementById(fieldId);

    // Find the button that triggered this
    const button = event.currentTarget;
    const icon = button.querySelector('i');

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
};

// Password strength checker
document.addEventListener('DOMContentLoaded', function () {
    const newPasswordInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('password-strength');
    const strengthText = document.getElementById('password-strength-text');
    const passwordForm = document.getElementById('passwordForm');

    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function () {
            const password = this.value;

            let strength = 0;
            let text = '';
            let color = '';

            if (password.length >= 6) strength += 25;
            if (password.length >= 10) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 12.5;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;

            if (strength <= 25) {
                text = 'Weak';
                color = 'bg-danger';
            } else if (strength <= 50) {
                text = 'Fair';
                color = 'bg-warning';
            } else if (strength <= 75) {
                text = 'Good';
                color = 'bg-info';
            } else {
                text = 'Strong';
                color = 'bg-success';
            }

            strengthBar.style.width = strength + '%';
            strengthBar.className = 'progress-bar ' + color;
            strengthText.textContent =
                password.length > 0 ? 'Password Strength: ' + text : '';
        });
    }

    // Confirm password match validation
    if (passwordForm) {
        passwordForm.addEventListener('submit', function (e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
    }
});

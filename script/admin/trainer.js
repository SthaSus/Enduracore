/**
 * Trainer Management JavaScript
 * EnduraCore Gym Management System
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Password validation
    const addTrainerForm = document.querySelector('#addTrainerModal form');
    if (addTrainerForm) {
        addTrainerForm.addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="password"]').value;
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    }
    
    // Phone number validation
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Remove non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 15 digits
            if (this.value.length > 15) {
                this.value = this.value.slice(0, 15);
            }
        });
    });
    
    // Username validation (no spaces, special chars)
    const usernameInputs = document.querySelectorAll('input[name="username"]');
    usernameInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Remove spaces and special characters, allow only alphanumeric and underscore
            this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '').toLowerCase();
        });
    });
    
    // Confirm delete with trainer name
    const deleteLinks = document.querySelectorAll('a.btn-danger[onclick*="confirm"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const row = this.closest('tr');
            const trainerName = row.querySelector('td:nth-child(2) strong').textContent;
            
            if (confirm(`Are you sure you want to delete trainer "${trainerName}"?\n\nThis will:\n• Delete their account\n• Remove all their workout plans\n• Unassign all members\n\nThis action cannot be undone!`)) {
                window.location.href = this.getAttribute('href');
            }
        });
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Search/Filter functionality
    addSearchFilter();
    
    // Form validation styling
    addFormValidation();
});

/**
 * Add search/filter functionality to trainer table
 */
function addSearchFilter() {
    const cardBody = document.querySelector('.card-body');
    if (!cardBody) return;
    
    // Create search input
    const searchDiv = document.createElement('div');
    searchDiv.className = 'mb-3';
    searchDiv.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="trainerSearch" placeholder="Search trainers by name, username, or specialization...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="specializationFilter">
                    <option value="">All Specializations</option>
                    <option value="Strength">Strength</option>
                    <option value="Cardio">Cardio</option>
                    <option value="Yoga">Yoga</option>
                    <option value="CrossFit">CrossFit</option>
                    <option value="General">General</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="accountStatusFilter">
                    <option value="">All Statuses</option>
                    <option value="has-account">Has Account</option>
                    <option value="no-account">No Account</option>
                </select>
            </div>
        </div>
    `;
    
    const tableResponsive = cardBody.querySelector('.table-responsive');
    cardBody.insertBefore(searchDiv, tableResponsive);
    
    // Add event listeners
    const searchInput = document.getElementById('trainerSearch');
    const specFilter = document.getElementById('specializationFilter');
    const statusFilter = document.getElementById('accountStatusFilter');
    
    searchInput.addEventListener('keyup', filterTrainers);
    specFilter.addEventListener('change', filterTrainers);
    statusFilter.addEventListener('change', filterTrainers);
}

/**
 * Filter trainers based on search criteria
 */
function filterTrainers() {
    const searchTerm = document.getElementById('trainerSearch').value.toLowerCase();
    const specFilter = document.getElementById('specializationFilter').value;
    const statusFilter = document.getElementById('accountStatusFilter').value;
    
    const rows = document.querySelectorAll('.table tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const username = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        const specialization = row.querySelector('td:nth-child(4)').textContent;
        const hasAccount = row.querySelector('td:nth-child(9) .badge-success') !== null;
        
        // Check search term
        const matchesSearch = name.includes(searchTerm) || username.includes(searchTerm);
        
        // Check specialization filter
        const matchesSpec = specFilter === '' || specialization === specFilter;
        
        // Check account status filter
        let matchesStatus = true;
        if (statusFilter === 'has-account') {
            matchesStatus = hasAccount;
        } else if (statusFilter === 'no-account') {
            matchesStatus = !hasAccount;
        }
        
        // Show/hide row
        if (matchesSearch && matchesSpec && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show "no results" message if needed
    showNoResultsMessage(visibleCount);
}

/**
 * Show/hide "no results" message
 */
function showNoResultsMessage(visibleCount) {
    let noResultsRow = document.getElementById('noResultsRow');
    
    if (visibleCount === 0) {
        if (!noResultsRow) {
            const tbody = document.querySelector('.table tbody');
            const colspan = document.querySelectorAll('.table thead th').length;
            
            noResultsRow = document.createElement('tr');
            noResultsRow.id = 'noResultsRow';
            noResultsRow.innerHTML = `
                <td colspan="${colspan}" class="text-center text-muted py-4">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p>No trainers found matching your criteria</p>
                </td>
            `;
            tbody.appendChild(noResultsRow);
        }
    } else {
        if (noResultsRow) {
            noResultsRow.remove();
        }
    }
}

/**
 * Add Bootstrap validation styling to forms
 */
function addFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
        
        // Real-time validation feedback
        const inputs = form.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                }
            });
        });
    });
}

/**
 * Password strength indicator
 */
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    return strength;
}

/**
 * Add password strength indicator to add trainer modal
 */
const passwordInput = document.querySelector('#addTrainerModal input[name="password"]');
if (passwordInput) {
    const strengthDiv = document.createElement('div');
    strengthDiv.className = 'mt-1';
    strengthDiv.innerHTML = '<small id="passwordStrength"></small>';
    passwordInput.parentNode.appendChild(strengthDiv);
    
    passwordInput.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        const strengthText = document.getElementById('passwordStrength');
        
        if (this.value.length === 0) {
            strengthText.textContent = '';
            return;
        }
        
        const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const colors = ['text-danger', 'text-warning', 'text-info', 'text-success', 'text-success'];
        
        strengthText.textContent = `Password Strength: ${labels[strength]}`;
        strengthText.className = `small ${colors[strength]} fw-bold`;
    });
}
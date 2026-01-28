let paymentModal;

document.addEventListener('DOMContentLoaded', function() {
    paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
});

function showPaymentModal(paymentId, amount, membership) {
    document.getElementById('modal_payment_id').value = paymentId;
    document.getElementById('modal_amount').textContent = amount.toFixed(2);
    document.getElementById('modal_membership').textContent = membership;
    paymentModal.show();
}

function selectMethod(method) {
    // Remove active class from all
    document.querySelectorAll('.payment-method-option').forEach(el => {
        el.classList.remove('active');
    });
    
    // Add active class to selected
    event.currentTarget.classList.add('active');
    
    // Check the radio button
    const methodKey = method.toLowerCase().replace(' ', '');
    document.getElementById('method_' + methodKey).checked = true;
    
    // Hide all detail sections
    document.getElementById('cardDetails').style.display = 'none';
    document.getElementById('esewaDetails').style.display = 'none';
    document.getElementById('khaltiDetails').style.display = 'none';
    document.getElementById('onlineDetails').style.display = 'none';
    
    // Show relevant section
    if (method === 'Card') {
        document.getElementById('cardDetails').style.display = 'block';
    } else if (method === 'eSewa') {
        document.getElementById('esewaDetails').style.display = 'block';
    } else if (method === 'Khalti') {
        document.getElementById('khaltiDetails').style.display = 'block';
    } else if (method === 'Online Banking') {
        document.getElementById('onlineDetails').style.display = 'block';
    }
}

// Show processing overlay on form submit
document.getElementById('paymentForm').addEventListener('submit', function() {
    document.getElementById('processingOverlay').style.display = 'flex';
    paymentModal.hide();
});
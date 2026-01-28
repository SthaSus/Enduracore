// Make functions GLOBAL
window.printReceipt = function (modalId) {
    window.print();
};

window.updatePaymentStatus = function (paymentId, newStatus) {
    if (confirm('Are you sure you want to mark this payment as ' + newStatus + '?')) {
        document.getElementById('statusPaymentId').value = paymentId;
        document.getElementById('statusNewStatus').value = newStatus;
        document.getElementById('updateStatusForm').submit();
    }
};

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});

let paymentModal;
let selectedPlanData = {};

document.addEventListener('DOMContentLoaded', function () {
    paymentModal = new bootstrap.Modal(
        document.getElementById('paymentModal')
    );
});

function selectPlan(type, price, oldMembershipId, isRenewal) {
    selectedPlanData = {
        type,
        price,
        oldMembershipId,
        isRenewal
    };

    document.getElementById('selected_type').value = type;
    document.getElementById('is_renewal').value = isRenewal;
    document.getElementById('old_membership_id').value = oldMembershipId;

    document.getElementById('summary_plan_name').textContent =
        type + ' Membership';
    document.getElementById('summary_price').textContent =
        price.toFixed(2);

    // ✅ Read PHP data safely
    const body = document.body;
    const startDate = new Date(body.dataset.startDate);

    const endDate = new Date(startDate);
    const durations = {
        Monthly: 1,
        Quarterly: 3,
        'Half-Yearly': 6,
        Yearly: 12
    };
    endDate.setMonth(endDate.getMonth() + durations[type]);

    document.getElementById('summary_dates').textContent =
        `${startDate.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        })} - ${endDate.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        })}`;

    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('selected');
        opt.querySelector('.fa-check-circle').style.display = 'none';
    });

    document.getElementById('confirmPaymentBtn').disabled = true;
    paymentModal.show();
}

function selectPayment(method, element) {
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('selected');
        opt.querySelector('.fa-check-circle').style.display = 'none';
    });

    element.classList.add('selected');
    element.querySelector('.fa-check-circle').style.display = 'block';

    document.getElementById('selected_payment_method').value = method;
    document.getElementById('confirmPaymentBtn').disabled = false;
}

    function updateFee() {
        const select = document.getElementById('membershipType');
        const feeInput = document.getElementById('membershipFee');
        const selectedOption = select.options[select.selectedIndex];
        const fee = selectedOption.getAttribute('data-fee');
        if (fee) {
            feeInput.value = fee;
        }
    }
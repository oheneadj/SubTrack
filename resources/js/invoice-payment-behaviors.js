/**
 * Alpine component for the public invoice payment page. Reads the
 * admin-adjustable "amount to pay" input at submit time and copies it into
 * the hidden field of whichever gateway form was submitted, so a partial
 * payment amount travels with the checkout POST.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('invoicePaymentForm', () => ({
        submit(event) {
            const amount = document.getElementById('payment-amount').value;
            event.target.querySelector('input[name=amount]').value = amount;
        },
    }));
});

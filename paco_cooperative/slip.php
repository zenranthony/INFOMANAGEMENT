<?php
require_once 'pascco_layout.php';
pascco_header('Electronic Slip');
?>
<main class="page-shell">
    <div class="eyebrow">Member service</div><h1>Generate Electronic Slip</h1>
    <form class="slip-form no-print" onsubmit="generateSlip(event)">
        <label for="slip-type">Slip Type</label><select id="slip-type"><option>Deposit</option><option>Withdrawal</option></select>
        <label for="account-name">Account Name</label><input id="account-name" required>
        <label for="account-number">Account Number</label><input id="account-number" required>
        <label for="amount">Amount (PHP)</label><input id="amount" type="number" min="0.01" step="0.01" required>
        <label for="details">Additional Details</label><textarea id="details" rows="3"></textarea>
        <button class="button" type="submit">Create Slip</button>
    </form>
    <section id="slip-preview" class="slip-preview" hidden>
        <img src="LOGO.png" alt="PASCCO logo"><h2 id="slip-heading"></h2><p><strong>Date:</strong> <span id="slip-date"></span></p><p><strong>Account Name:</strong> <span id="slip-name"></span></p><p><strong>Account Number:</strong> <span id="slip-number"></span></p><p><strong>Amount:</strong> PHP <span id="slip-amount"></span></p><p><strong>Details:</strong> <span id="slip-details"></span></p><button class="button no-print" type="button" onclick="window.print()">Print Slip</button>
    </section>
</main>
<script>
function generateSlip(event) {
    event.preventDefault();
    const type = document.getElementById('slip-type').value;
    document.getElementById('slip-heading').textContent = type.toUpperCase() + ' SLIP';
    document.getElementById('slip-date').textContent = new Date().toLocaleDateString();
    document.getElementById('slip-name').textContent = document.getElementById('account-name').value;
    document.getElementById('slip-number').textContent = document.getElementById('account-number').value;
    document.getElementById('slip-amount').textContent = Number(document.getElementById('amount').value).toFixed(2);
    document.getElementById('slip-details').textContent = document.getElementById('details').value || '-';
    document.getElementById('slip-preview').hidden = false;
}
</script>
<?php pascco_footer(); ?>

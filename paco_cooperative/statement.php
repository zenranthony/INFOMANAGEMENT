<?php
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'member') {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$account_id = (int) ($_GET['account_id'] ?? 0);
$from_date = trim($_GET['from'] ?? '');
$to_date = trim($_GET['to'] ?? '');
$type = trim($_GET['type'] ?? '');
$account_stmt = $pdo->prepare("SELECT a.id, a.account_number, a.account_type, a.balance, m.first_name, m.last_name, m.member_number FROM accounts a JOIN members m ON m.id = a.member_id WHERE a.id = ? AND m.user_id = ? AND a.status = 'active'");
$account_stmt->execute([$account_id, $user_id]);
$account = $account_stmt->fetch();
if (!$account) {
    http_response_code(404);
    exit('Savings account not found.');
}
$conditions = ['account_id = ?'];
$parameters = [$account_id];
if ($from_date !== '' && DateTime::createFromFormat('Y-m-d', $from_date)) { $conditions[] = 'FROM_UNIXTIME(created_at) >= ?'; $parameters[] = $from_date . ' 00:00:00'; }
if ($to_date !== '' && DateTime::createFromFormat('Y-m-d', $to_date)) { $conditions[] = 'FROM_UNIXTIME(created_at) <= ?'; $parameters[] = $to_date . ' 23:59:59'; }
if (in_array($type, ['deposit', 'withdrawal', 'transfer'], true)) { $conditions[] = 'transaction_type = ?'; $parameters[] = $type; }
$stmt = $pdo->prepare('SELECT transaction_type, amount, description, reference_number, created_at FROM transactions WHERE ' . implode(' AND ', $conditions) . ' ORDER BY id ASC LIMIT 500');
$stmt->execute($parameters);
$transactions = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Savings Statement | PASCCO</title><style>
body{background:#eef1f5;color:#182a45;font-family:Arial,sans-serif;margin:0}.document{background:white;margin:24px auto;max-width:900px;padding:42px;box-shadow:0 2px 12px rgba(0,0,0,.12)}.document-header{align-items:start;border-bottom:3px solid #071d49;display:flex;justify-content:space-between;padding-bottom:18px}.brand{color:#071d49;font-size:1.4rem;font-weight:bold}.muted{color:#5d6b7e;font-size:.9rem}.actions{display:flex;gap:10px;margin:22px 0}.actions button,.actions a{background:#1456a0;border:0;color:white;cursor:pointer;font-weight:bold;padding:10px 14px;text-decoration:none}.summary{display:grid;gap:10px;grid-template-columns:repeat(4,1fr);margin:22px 0}.summary div{border:1px solid #d8e0ee;padding:12px}.summary small{color:#5d6b7e;display:block}.summary strong{display:block;margin-top:5px}table{border-collapse:collapse;width:100%}th,td{border-bottom:1px solid #d8e0ee;padding:10px;text-align:left}th{background:#e6edf8;color:#071d49}.deposit{color:#1456a0}.withdrawal,.transfer{color:#9b3d3d}.footer{border-top:1px solid #d8e0ee;color:#5d6b7e;font-size:.8rem;margin-top:28px;padding-top:12px}@media(max-width:650px){.document{margin:0;padding:22px}.document-header{display:block}.summary{grid-template-columns:repeat(2,1fr)}table{font-size:.82rem}}@media print{body{background:white}.document{box-shadow:none;margin:0;max-width:none;padding:0}.actions{display:none}}
</style></head><body><main class="document"><header class="document-header"><div><div class="brand">PACO SAVINGS &amp; CREDIT COOPERATIVE</div><div class="muted">Official Member Savings Statement</div></div><div class="muted">Generated <?php echo date('F j, Y g:i A'); ?></div></header><div class="actions"><button type="button" onclick="window.print()">Print / Save as PDF</button><a href="member_savings.php?account_id=<?php echo (int) $account['id']; ?>">Back to History</a></div><section class="summary"><div><small>Member</small><strong><?php echo htmlspecialchars($account['first_name'] . ' ' . $account['last_name'], ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Member Number</small><strong><?php echo htmlspecialchars($account['member_number'], ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Account</small><strong><?php echo htmlspecialchars($account['account_number'], ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Current Balance</small><strong>PHP <?php echo number_format($account['balance'], 2); ?></strong></div></section><p class="muted">Period: <?php echo htmlspecialchars($from_date ?: 'Beginning', ENT_QUOTES, 'UTF-8'); ?> to <?php echo htmlspecialchars($to_date ?: 'Present', ENT_QUOTES, 'UTF-8'); ?><?php echo $type ? ' · Type: ' . htmlspecialchars(strtoupper($type), ENT_QUOTES, 'UTF-8') : ''; ?></p><table><thead><tr><th>Date</th><th>Reference</th><th>Type</th><th>Description</th><th>Amount</th></tr></thead><tbody><?php if ($transactions): ?><?php foreach ($transactions as $transaction): ?><tr><td><?php echo date('M j, Y H:i', (int) $transaction['created_at']); ?></td><td><?php echo htmlspecialchars($transaction['reference_number'], ENT_QUOTES, 'UTF-8'); ?></td><td class="<?php echo htmlspecialchars($transaction['transaction_type'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo strtoupper(htmlspecialchars($transaction['transaction_type'], ENT_QUOTES, 'UTF-8')); ?></td><td><?php echo htmlspecialchars($transaction['description'], ENT_QUOTES, 'UTF-8'); ?></td><td>PHP <?php echo number_format($transaction['amount'], 2); ?></td></tr><?php endforeach; ?><?php else: ?><tr><td colspan="5">No transactions found for this period.</td></tr><?php endif; ?></tbody></table><div class="footer">This statement is generated from the PASCCO member portal. Contact the cooperative office for corrections or official certification.</div></main></body></html>

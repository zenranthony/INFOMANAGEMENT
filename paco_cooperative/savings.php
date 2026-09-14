<?php
require_once 'pascco_layout.php';
require_once 'pascco_products.php';
pascco_header('Savings Products', 'savings');
?>
<main class="page-shell">
    <div class="eyebrow">Save with purpose</div><h1>Deposit Products</h1>
    <p class="intro">Build a stronger financial future with savings options for children, regular members, and specific life goals.</p>
    <section class="grid">
        <?php foreach (pascco_savings_products() as $category => $products): ?>
            <article class="panel"><h2><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></h2><?php foreach ($products as $product): ?><h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3><p><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></p><?php endforeach; ?><?php if ($category === 'Time Deposit'): ?><table class="data-table"><tr><th>Amount</th><th>Interest</th></tr><tr><td>PHP 10,000 - 99,000</td><td>1.5%</td></tr><tr><td>PHP 100,000 - 499,000</td><td>2%</td></tr><tr><td>PHP 500,000 and above</td><td>2.5%</td></tr></table><?php endif; ?></article>
        <?php endforeach; ?>
    </section>
</main>
<?php pascco_footer(); ?>

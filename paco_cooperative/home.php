<?php
require_once 'pascco_layout.php';
pascco_header('Home', 'home');
?>
<main class="page-shell">
    <section class="hero-panel">
        <div class="eyebrow">Paco Savings &amp; Credit Cooperative</div>
        <h1>Helping People<br>Help Themselves</h1>
        <p class="intro">A member-focused cooperative building financial confidence through savings, accessible credit, and shared responsibility.</p>
        <a class="button" href="register.php">Become a Member</a>
        <a class="button secondary" href="about.php">Learn About PASCCO</a>
    </section>
    <section class="grid">
        <article class="panel"><h2>Savings</h2><p>Explore regular, programmed, and time deposit products designed for everyday goals.</p><a href="savings.php">View savings products</a></article>
        <article class="panel"><h2>Credit</h2><p>Find productive, providential, and special loan products for members.</p><a href="credit.php">Explore loan products</a></article>
        <article class="panel"><h2>Member Portal</h2><p>Manage your balance, transactions, transfers, and loan applications online.</p><a href="login.php">Sign in to your account</a></article>
    </section>
</main>
<?php pascco_footer(); ?>
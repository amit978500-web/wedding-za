<?php
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/components.php';
wz_require_role('host');
$user=wz_user();
$pageTitle='My Wedding Za';
$pageDescription='Your Wedding Za account workspace.';
$pageKey='account';
require __DIR__.'/includes/header.php';
?>
<main>
<section class="account-v2-hero">
  <div class="container account-v2-hero-grid">
    <div><span class="eyebrow">YOUR ACCOUNT</span><h1>Welcome back,<br><em><?=h(explode(' ',(string)$user['name'])[0])?>.</em></h1><p>Your saved vendors, event brief and planning tools live here. The current build uses browser storage for planning data and PHP session state for the signed-in account shell.</p></div>
    <div class="account-v2-card"><span>ACCOUNT TYPE</span><strong>Host / Planner</strong><p><?=h((string)$user['email'])?></p><a href="logout.php">Log out ↗</a></div>
  </div>
</section>

<section class="section paper-2">
  <div class="container account-v2-grid">
    <a href="planner.php"><span>01</span><h3>Planning Studio</h3><p>Event brief, checklist, budget and progress.</p><b>Open workspace ↗</b></a>
    <a href="shortlist.php"><span>02</span><h3>Shortlist</h3><p>Review the vendors worth contacting.</p><b>Review saves ↗</b></a>
    <a href="vendors.php"><span>03</span><h3>Discover</h3><p>Find venues and teams by occasion and city.</p><b>Find vendors ↗</b></a>
    <a href="invites.php"><span>04</span><h3>Invitations</h3><p>Shape the first guest-facing impression.</p><b>Open invites ↗</b></a>
  </div>
</section>

<section class="section">
  <div class="container account-state-grid">
    <div><span class="eyebrow">ACCOUNT FOUNDATION</span><h2>Ready for the<br><em>database layer.</em></h2></div>
    <div><p>This account shell already separates authentication state from planning state. The next backend phase can move users, event briefs, shortlists and enquiries into MySQL while preserving the current UX.</p><div class="account-tech-list"><span>PHP sessions</span><span>CSRF protection</span><span>Role-aware routes</span><span>MySQL-ready separation</span></div></div>
  </div>
</section>
</main>
<?php require __DIR__.'/includes/footer.php';?>

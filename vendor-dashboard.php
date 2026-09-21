<?php
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
wz_require_role('vendor');
$user=wz_user();
$pageTitle='Vendor Business Workspace';
$pageDescription='Manage Wedding Za business profile and enquiries.';
$pageKey='vendor-dashboard';
require __DIR__.'/includes/header.php';
?>
<main>
<section class="business-dashboard-v2">
  <aside class="business-sidebar">
    <a class="business-sidebar-brand" href="index.php">Wedding Za<br><em>Business</em></a>
    <nav><a class="active" href="#overview">Overview</a><a href="#enquiries">Enquiries</a><a href="#profile">Profile quality</a><a href="vendor.php?id=amber-courtyard">Public profile</a><a href="register-vendor.php">Business support</a></nav>
    <div class="business-sidebar-user"><span><?=h((string)$user['name'])?></span><small><?=h((string)$user['email'])?></small><a href="logout.php">Log out ↗</a></div>
  </aside>

  <div class="business-dashboard-main">
    <header class="business-dashboard-head" id="overview"><div><span class="eyebrow">BUSINESS WORKSPACE</span><h1>Good morning,<br><em><?=h(explode(' ',(string)$user['name'])[0])?>.</em></h1></div><a class="pill-btn wine" href="vendor.php?id=amber-courtyard">View public profile ↗</a></header>

    <div class="business-kpis">
      <div><span>Profile views</span><strong>1,284</strong><small>Last 30 days</small></div>
      <div><span>Qualified enquiries</span><strong>26</strong><small>Across 5 event types</small></div>
      <div><span>Shortlists</span><strong>91</strong><small>Saved by hosts</small></div>
      <div><span>Response rate</span><strong>94%</strong><small>Target ≥ 90%</small></div>
    </div>

    <section class="business-panel" id="enquiries">
      <div class="business-panel-head"><div><span class="eyebrow">ENQUIRIES</span><h2>Know the brief<br><em>before you reply.</em></h2></div><button type="button" class="filter-reset">Export later</button></div>
      <div class="business-enquiry-table">
        <div class="business-enquiry-row head"><span>Client</span><span>Event</span><span>City</span><span>Budget context</span><span>Status</span></div>
        <div class="business-enquiry-row"><strong>Aarushi & Kunal</strong><span>Wedding · Dec 2026</span><span>Jaipur</span><span>₹30–60 lakh</span><b class="new">New</b></div>
        <div class="business-enquiry-row"><strong>Aurora Pvt Ltd</strong><span>Corporate · Feb 2027</span><span>Delhi NCR</span><span>₹15–30 lakh</span><b>Replied</b></div>
        <div class="business-enquiry-row"><strong>Meera Sharma</strong><span>Birthday · Jan 2027</span><span>Mumbai</span><span>₹5–15 lakh</span><b class="new">New</b></div>
      </div>
    </section>

    <section class="business-panel" id="profile">
      <div class="business-panel-head"><div><span class="eyebrow">PROFILE QUALITY</span><h2>Better context,<br><em>better leads.</em></h2></div><strong class="profile-score">82%</strong></div>
      <div class="profile-quality-grid">
        <div class="done"><span>✓</span><strong>Business basics</strong><small>Name, city, category, starting price</small></div>
        <div class="done"><span>✓</span><strong>Portfolio</strong><small>Strong image coverage</small></div>
        <div><span>03</span><strong>Event fit</strong><small>Add more occasion-specific proof</small></div>
        <div><span>04</span><strong>Policies</strong><small>Add cancellation and travel terms</small></div>
      </div>
    </section>

    <section class="business-panel">
      <div class="business-panel-head"><div><span class="eyebrow">ACCOUNT ARCHITECTURE</span><h2>Ready for<br><em>real data.</em></h2></div></div>
      <p class="business-tech-note">This workspace now has role-aware PHP session access. The next backend phase can replace demo KPIs and enquiry rows with MySQL records without changing the page structure.</p>
    </section>
  </div>
</section>
</main>
<?php require __DIR__.'/includes/footer.php';?>

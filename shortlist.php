<?php
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/components.php';
$pageTitle='My Shortlist';$pageDescription='Compare the event vendors you saved across Wedding Za and keep them connected to your event brief.';$pageKey='shortlist';
require __DIR__.'/includes/header.php';
?>
<main>
<section class="shortlist-v2-hero">
  <div class="container shortlist-v2-head">
    <div><span class="eyebrow">YOUR SAVED EDIT</span><h1>Keep the serious<br><em>contenders.</em></h1><p>Shortlisting is where browsing becomes decision-making. Keep only the vendors you would actually contact.</p></div>
    <div class="shortlist-summary-card">
      <span>SAVED VENDORS</span><strong id="shortlistHeroCount">0</strong><p id="shortlistHeroText">Your shortlist is empty.</p>
      <a href="planner.php">Open planning studio ↗</a>
    </div>
  </div>
</section>

<section class="section shortlist-brief-strip">
  <div class="container shortlist-brief-inner">
    <div><span class="eyebrow">EVENT CONTEXT</span><strong id="shortlistBriefTitle">No event brief yet</strong><p id="shortlistBriefMeta">Add occasion, city, date and guest count so your shortlist has context.</p></div>
    <a class="pill-btn outline" href="planner.php">Edit event brief ↗</a>
  </div>
</section>

<section class="section paper-2">
  <div class="container shortlist-layout">
    <aside class="shortlist-decision-guide">
      <span class="eyebrow">CUT THE LIST</span>
      <h3>Would you actually enquire?</h3>
      <p>If the answer is no, remove it. A useful shortlist is intentionally small.</p>
      <div><span>01</span><strong>Compare fit</strong><small>Occasion, city and scale.</small></div>
      <div><span>02</span><strong>Compare proof</strong><small>Portfolio consistency and reviews.</small></div>
      <div><span>03</span><strong>Compare friction</strong><small>Price context, policy and logistics.</small></div>
    </aside>
    <div>
      <div class="shortlist-results-head"><div><span class="eyebrow">SAVED PROFILES</span><h2>Your working list.</h2></div><a class="text-link" href="vendors.php">Add more vendors ↗</a></div>
      <div class="vendor-grid shortlist-grid-v2" id="shortlistGrid"><?php foreach(wz_data('vendors') as $v)wz_vendor_card($v);?></div>
      <div class="empty-state shortlist-empty-v2" id="shortlistEmpty">
        <span class="eyebrow">NOTHING SAVED YET</span><h3>Start with three,<br>not thirty.</h3><p class="muted">Browse vendors and save only the profiles you would genuinely consider contacting.</p><a class="pill-btn wine" href="vendors.php">Discover vendors ↗</a>
      </div>
    </div>
  </div>
</section>

<section class="section shortlist-action-section" id="shortlistAction">
  <div class="container shortlist-action-grid">
    <div><span class="eyebrow light">READY TO MOVE?</span><h2>Good shortlist.<br><em>Better enquiry.</em></h2></div>
    <div><p>Open a vendor profile and send your occasion, city, date and guest context. Specific enquiries usually lead to more useful replies.</p><a class="pill-btn light" href="planner.php">Check planning brief ↗</a></div>
  </div>
</section>
</main>
<?php require __DIR__.'/includes/footer.php';?>

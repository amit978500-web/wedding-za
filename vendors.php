<?php
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/components.php';
$pageTitle='Event Vendors';$pageDescription='Browse venues, photographers, planners, decorators, caterers, artists and event specialists across India.';$pageKey='vendors';
$selectedEvent=(string)($_GET['event']??'');$selectedCity=(string)($_GET['city']??'');$selectedCategory=(string)($_GET['category']??'');
require __DIR__.'/includes/header.php';
?>
<main>
<?php wz_page_intro('DISCOVER YOUR TEAM','Event vendors,<br><em>edited with taste.</em>','Choose the occasion, city and category, compare starting prices and save the profiles you want to come back to.'); ?>
<div class="filter-bar"><div class="container filter-inner filter-inner-events">
  <div class="filter-field"><input id="filterSearch" type="search" placeholder="Search name or locality…" aria-label="Search vendors"></div>
  <div class="filter-field"><select id="filterEvent"><option value="">All celebrations</option><?php foreach(wz_data('event_types') as $event):?><option value="<?=h($event['name'])?>" <?=$selectedEvent===$event['name']?'selected':''?>><?=h($event['name'])?></option><?php endforeach;?></select></div>
  <div class="filter-field"><select id="filterCity"><option value="">All cities</option><?php foreach(wz_data('cities') as $city):?><option value="<?=h($city)?>" <?=$selectedCity===$city?'selected':''?>><?=h($city)?></option><?php endforeach;?></select></div>
  <div class="filter-field"><select id="filterCategory"><option value="">All categories</option><?php foreach(wz_data('categories') as $c):?><option value="<?=h($c['name'])?>" <?=$selectedCategory===$c['name']?'selected':''?>><?=h($c['name'])?></option><?php endforeach;?></select></div>
  <div class="filter-field"><select id="filterSort"><option value="featured">Featured</option><option value="rating">Highest rated</option><option value="price">Starting price</option></select></div>
  <div class="filter-count" id="filterCount"></div>
</div></div>
<section class="section"><div class="container"><div class="listing-head reveal"><div><span class="eyebrow">THE DIRECTORY</span><h2>Profiles worth opening.</h2></div><p>Save any vendor with the heart button. Most teams here can work across multiple event types.</p></div><div class="vendor-grid" id="vendorListing"><?php foreach(wz_data('vendors') as $v)wz_vendor_card($v);?></div><div class="empty-state" id="vendorEmpty" hidden><h3>No exact match yet.</h3><p class="muted">Try another celebration, city, category or search term.</p></div></div></section>
<section class="section paper-2"><div class="container"><div class="section-head reveal"><div><span class="eyebrow">DON’T KNOW WHERE TO START?</span><h2>Start with the<br>big decisions.</h2></div><p>Venue, planning, food and photography usually shape the event faster than anything else — whatever the occasion.</p></div><div class="category-scroller"><?php foreach(array_slice(wz_data('categories'),0,4) as $c):?><a class="category-luxe" href="vendors.php?category=<?=urlencode($c['name'])?>"><img src="<?=h($c['image'])?>" alt="<?=h($c['name'])?>"><div class="category-luxe-copy"><small>POPULAR</small><h3><?=h($c['name'])?></h3><p><?=h($c['sub'])?></p><b>Browse ↗</b></div></a><?php endforeach;?></div></div></section>
</main>
<?php require __DIR__.'/includes/footer.php'; ?>

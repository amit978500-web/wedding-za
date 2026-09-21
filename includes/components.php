<?php
function wz_breadcrumbs(array $items): void { ?>
<nav class="breadcrumbs" aria-label="Breadcrumb"><a href="index.php">Home</a><span>/</span><?php foreach ($items as $i => $item): $last = $i === array_key_last($items); ?><?php if (!$last && !empty($item[1])): ?><a href="<?=h($item[1])?>"><?=h($item[0])?></a><span>/</span><?php else: ?><span><?=h($item[0])?></span><?php endif; ?><?php endforeach; ?></nav><?php }

function wz_vendor_card(array $v, string $class=''): void { ?>
<article class="vendor-card vision-vendor-card <?=h($class)?>" data-vendor-id="<?=h($v['id'])?>" data-city="<?=h($v['city']??'')?>" data-category="<?=h($v['category']??'')?>" data-events="<?=h(implode('|',$v['events']??[]))?>" data-rating="<?=h((string)($v['rating']??0))?>" data-price="<?=h((string)wz_money_number((string)($v['price']??'0')))?>" data-search="<?=h(($v['name']??'').' '.($v['city']??'').' '.($v['category']??'').' '.($v['locality']??''))?>">
  <a class="vendor-media" href="vendor.php?id=<?=urlencode((string)$v['id'])?>" aria-label="Open <?=h($v['name'])?>"><img src="<?=h($v['image'])?>" alt="<?=h($v['name'])?>" loading="lazy" decoding="async"><span class="vendor-image-wash"></span><span class="vendor-open">View<br>profile ↗</span></a>
  <button class="heart-btn" type="button" data-shortlist="<?=h($v['id'])?>" aria-label="Save <?=h($v['name'])?>">♡</button>
  <div class="vendor-card-body">
    <div class="vendor-meta"><span><?=h($v['category'])?> · <?=h($v['city'])?></span><span>★ <?=h((string)$v['rating'])?></span></div>
    <h3><a href="vendor.php?id=<?=urlencode((string)$v['id'])?>"><?=h($v['name'])?></a></h3>
    <div class="vendor-card-foot"><span><?=h($v['locality'])?></span><strong><?=h($v['price'])?></strong></div>
  </div>
</article><?php }

function wz_wedding_card(array $w, string $class=''): void { $eventType=(string)($w['event_type']??'Wedding'); ?>
<a class="story-card vision-story-card <?=h($class)?>" href="wedding-story.php?id=<?=urlencode((string)$w['id'])?>"><div class="story-media"><img src="<?=h($w['image'])?>" alt="<?=h($w['couple'])?> <?=h($eventType)?>" loading="lazy" decoding="async"><span class="story-open">Open story ↗</span></div><div class="story-copy"><span><?=h($eventType)?> · <?=h($w['city'])?> · <?=h($w['theme'])?></span><h3><?=h($w['couple'])?></h3><p><?=h($w['title'])?></p></div></a><?php }

function wz_article_card(array $a, string $class=''): void { ?>
<a class="journal-card vision-journal-card <?=h($class)?>" href="article.php?id=<?=urlencode((string)$a['id'])?>"><div class="journal-media"><img src="<?=h($a['image'])?>" alt="<?=h($a['title'])?>" loading="lazy" decoding="async"><span>READ ↗</span></div><div class="journal-copy"><small><?=h($a['category'])?> · <?=h($a['read'])?></small><h3><?=h($a['title'])?></h3><p><?=h($a['excerpt'])?></p></div></a><?php }

function wz_page_intro(string $kicker, string $title, string $text, ?string $image=null): void { ?>
<section class="page-intro vision-page-intro <?= $image ? 'has-image':'' ?>"><?php if ($image): ?><div class="page-intro-bg" style="--bg:url('<?=h($image)?>')"></div><?php endif; ?><div class="container page-intro-inner"><span class="vision-index">WZ / <?=h($kicker)?></span><h1 data-split-title><?= $title ?></h1><p><?=h($text)?></p></div><span class="vision-page-orbit" aria-hidden="true"></span></section><?php }

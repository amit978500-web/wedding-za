<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $pageTitle='Real Celebrations';
    $pageDescription='Explore real celebrations, locations, themes and the vendors behind them.';
    $pageKey='real-weddings';
    require __DIR__.'/includes/header.php';
?>
<main>
    <?php
        wz_page_intro('REAL CELEBRATIONS','Real events with<br><em>useful credits.</em>','Browse real events for the mood, then follow the venue and vendor trail when something feels right.','https://images.unsplash.com/photo-1744805624952-dab790f6b3bd?auto=format&fit=crop&w=1900&q=92');
    ?>
    <section class="section dark-editorial">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow light">
                    THE CELEBRATION ARCHIVE
                    </span>
                    <h2>
                    Start with a
                    <br>
                    celebration.
                    </h2>
                </div>
                <p>
                Different cities, cultures and visual directions — with vendor tags built into every story.
                </p>
            </div>
            <div class="story-grid-premium">
                <?php
                    foreach(wz_data('weddings') as $w)wz_wedding_card($w);
                ?>
            </div>
        </div>
    </section>
    <section class="section paper-2">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">
                    DISCOVER BY CITY
                    </span>
                    <h2>
                    See what works
                    <br>
                    where.
                    </h2>
                </div>
            </div>
            <div class="city-feature-grid">
                <?php
                    $imgs=['Jaipur'=>'https://images.unsplash.com/photo-1767158597961-bd58dccc16bd?auto=format&fit=crop&w=1200&q=92','Udaipur'=>'https://images.unsplash.com/photo-1770665567877-72ee8a7c9051?auto=format&fit=crop&w=1200&q=92','Goa'=>'https://images.unsplash.com/photo-1710952356679-1eff1cb5ba64?auto=format&fit=crop&w=1200&q=92'];
                    $i=0;
                    foreach($imgs as $city=>$im):
                ?>
                <a class="city-tile" href="city.php?city=<?=urlencode($city)?>
                ">
                <img src="<?=h($im)?>
                " alt="
                <?= h($city) ?>
                ">
                <span class="city-index">
                0
                <?= ++$i ?>
                </span>
                <div class="city-tile-copy">
                    <small>
                    REAL CELEBRATIONS
                    </small>
                    <h3>
                    <?= h($city) ?>
                    </h3>
                    <span>
                    Explore city ↗
                    </span>
                </div>
                </a>
            <?php
                endforeach;
            ?>
        </div>
    </div>
</section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>

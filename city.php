<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $city=(string)($_GET['city']??'Jaipur');
    $allowed=wz_data('cities');
    if(!in_array($city,$allowed,true)) $city='Jaipur';
    $heroMap=[
    'Jaipur'=>'https://images.unsplash.com/photo-1767158597961-bd58dccc16bd?auto=format&fit=crop&w=1900&q=92',
    'Udaipur'=>'https://images.unsplash.com/photo-1770665567877-72ee8a7c9051?auto=format&fit=crop&w=1900&q=92',
    'Goa'=>'https://images.unsplash.com/photo-1710952356679-1eff1cb5ba64?auto=format&fit=crop&w=1900&q=92',
    'Delhi NCR'=>'https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=1900&q=92',
    'Mumbai'=>'https://images.unsplash.com/photo-1751608734207-1c68d53554b4?auto=format&fit=crop&w=1900&q=92',
    'Bengaluru'=>'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=1900&q=92',
    'Hyderabad'=>'https://images.unsplash.com/photo-1578662996442-48f60103fc96?auto=format&fit=crop&w=1900&q=92',
    'Chandigarh'=>'https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=1900&q=92'
    ];
    $hero=$heroMap[$city]??reset($heroMap);
    $pageTitle=$city.' Event Vendors, Venues & Ideas';
    $pageDescription='Discover venues, vendors, real celebrations and planning ideas for weddings, birthdays, corporate events and private functions in '.$city.'.';
    $pageKey='city';
    $localVendors=array_values(array_filter(wz_data('vendors'),fn($v)=>($v['city']??'')===$city));
    $localCelebrations=array_values(array_filter(wz_data('weddings'),fn($w)=>($w['city']??'')===$city));
    $events=wz_data('event_types');
    $cityNotes=[
    'Jaipur'=>['Heritage scale, destination hospitality and strong visual identity.','Palace logistics','Guest transfers','Heat-aware timings'],
    'Udaipur'=>['Lakefront venues and destination-weekend energy reward tighter guest logistics.','Room blocks','Boat / road access','Sound cut-offs'],
    'Goa'=>['Outdoor functions need weather, sound and transport planning from day one.','Weather backup','After-party rules','Guest transport'],
    'Delhi NCR'=>['Huge vendor depth and venue variety make disciplined shortlisting especially valuable.','Travel time','Venue access','Production scale'],
    'Mumbai'=>['City events benefit from smart timing, compact guest flow and realistic logistics.','Traffic windows','Loading access','Indoor backup']
    ];
    $note=$cityNotes[$city]??['A strong local plan makes every kind of event easier to execute.','Guest flow','Venue access','Local vendor fit'];
    require __DIR__.'/includes/header.php';
?>
<main>
    <section class="landing-hero-v2 city-landing-v2">
        <div class="landing-hero-image">
            <img src="<?=h($hero)?>
            " alt="
            <?= h($city) ?>
            events">
        </div>
        <div class="landing-hero-overlay">
        </div>
        <div class="container landing-hero-content">
            <span class="eyebrow light">
            WEDDING ZA CITY EDIT /
            <?= h(strtoupper($city)) ?>
            </span>
            <h1>
            <?= h($city) ?>
            <br>
            <em>
            for every occasion.
            </em>
            </h1>
            <p>
            <?= h($note[0]) ?>
            </p>
            <div class="landing-hero-actions">
                <a class="vision-primary" href="vendors.php?city=<?=urlencode($city)?>
                ">Browse
                <?= h($city) ?>
                vendors
                <span>
                ↗
                </span>
                </a>
                <a class="vision-secondary" href="#occasions">
                Choose an occasion
                </a>
            </div>
            <div class="landing-stats">
                <div>
                    <strong>
                    <?= count($localVendors)?:'Growing' ?>
                    </strong>
                    <span>
                    local vendor picks
                    </span>
                </div>
                <div>
                    <strong>
                    <?= count($localCelebrations) ?>
                    </strong>
                    <span>
                    real celebration stories
                    </span>
                </div>
                <div>
                    <strong>
                    <?= count($events) ?>
                    </strong>
                    <span>
                    occasion types
                    </span>
                </div>
            </div>
        </div>
    </section>
    <section class="section landing-occasion-section" id="occasions">
        <div class="container">
            <div class="vision-section-head">
                <div>
                    <span>
                    01 / START HERE
                    </span>
                    <small>
                    CHOOSE THE FUNCTION
                    </small>
                </div>
                <h2>
                What are you
                <br>
                <em>
                planning in
                <?= h($city) ?>
                ?
                </em>
                </h2>
                <p>
                Occasion first, then team. Each event type changes the right venue, vendor mix and planning priorities.
                </p>
            </div>
            <div class="landing-event-grid">
                <?php
                    foreach($events as $event):
                ?>
                    <a href="event.php?type=<?=urlencode($event['name'])?>
                    &city=
                    <?= urlencode($city) ?>
                    " class="landing-event-card">
                    <img src="<?=h($event['image'])?>
                    " alt="
                    <?= h($event['name']) ?>
                    in
                    <?= h($city) ?>
                    " loading="lazy">
                    <div>
                        <span>
                        <?= h($city) ?>
                        </span>
                        <h3>
                        <?= h($event['name']) ?>
                        </h3>
                        <p>
                        <?= h($event['sub']) ?>
                        </p>
                        <b>
                        Explore ↗
                        </b>
                    </div>
                    </a>
                <?php
                    endforeach;
                ?>
            </div>
        </div>
    </section>
    <section class="section paper-2">
        <div class="container city-planning-grid">
            <div>
                <span class="eyebrow">
                CITY REALITY CHECK
                </span>
                <h2>
                Plan
                <?= h($city) ?>
                like
                <br>
                <em>
                a local operator.
                </em>
                </h2>
            </div>
            <div class="city-planning-points">
                <?php
                    foreach(array_slice($note,1) as $i=>$point):
                ?>
                    <div>
                        <span>
                        0
                        <?= $i+1 ?>
                        </span>
                        <strong>
                        <?= h($point) ?>
                        </strong>
                        <p>
                        Confirm this early so the event does not become more expensive or stressful later.
                        </p>
                    </div>
                <?php
                    endforeach;
                ?>
            </div>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">
                    LOCAL SHORTLIST
                    </span>
                    <h2>
                    Teams to open
                    <br>
                    <em>
                    first.
                    </em>
                    </h2>
                </div>
                <a class="text-link" href="vendors.php?city=<?=urlencode($city)?>
                ">All
                <?= h($city) ?>
                vendors ↗
                </a>
            </div>
            <div class="vendor-grid">
                <?php
                    $display=$localVendors?:array_slice(wz_data('vendors'),0,6);
                    foreach(array_slice($display,0,6) as $v) wz_vendor_card($v);
                ?>
            </div>
        </div>
    </section>
    <?php
        if($localCelebrations):
    ?>
        <section class="section dark-editorial">
            <div class="container">
                <div class="section-head">
                    <div>
                        <span class="eyebrow light">
                        REAL CELEBRATIONS
                        </span>
                        <h2>
                        See
                        <?= h($city) ?>
                        <br>
                        <em>
                        in motion.
                        </em>
                        </h2>
                    </div>
                    <p>
                    Use real events to understand scale, mood and which teams have already worked in the city.
                    </p>
                </div>
                <div class="story-grid-premium">
                    <?php
                        foreach(array_slice($localCelebrations,0,4) as $w) wz_wedding_card($w);
                    ?>
                </div>
            </div>
        </section>
    <?php
        endif;
    ?>
    <section class="section city-cross-links">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">
                    KEEP EXPLORING
                    </span>
                    <h2>
                    Compare another
                    <br>
                    <em>
                    city.
                    </em>
                    </h2>
                </div>
            </div>
            <div class="city-link-row">
                <?php
                    foreach(array_filter($allowed,fn($x)=>$x!==$city) as $other):
                ?>
                    <a href="city.php?city=<?=urlencode($other)?>
                    ">
                    <?= h($other) ?>
                    <span>
                    ↗
                    </span>
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

<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $type=(string)($_GET['type']??'Wedding');
    $city=(string)($_GET['city']??'');
    $event=null;
    foreach(wz_data('event_types') as $candidate) {
    if(strcasecmp((string)$candidate['name'],$type)===0 || strcasecmp((string)$candidate['id'],$type)===0) {
    $event=$candidate;
    break;
    }
    }
    if(!$event) {
    $all=wz_data('event_types');
    $event=$all[0]??['name'=>'Wedding','sub'=>'Celebrations','image'=>''];
    }
    $type=(string)$event['name'];
    if($city!=='' && !in_array($city,wz_data('cities'),true)) $city='';
    $vendors=array_values(array_filter(
    wz_data('vendors'),
    fn($v)=>in_array($type,$v['events']??[],true) && ($city==='' || ($v['city']??'')===$city)
    ));
    $stories=array_values(array_filter(
    wz_data('weddings'),
    fn($w)=>(($w['event_type']??'Wedding')===$type) && ($city==='' || ($w['city']??'')===$city)
    ));
    $guides=[
    'Wedding'=>['Guest experience before decoration','Lock venue + hospitality early','Build function-by-function vendor scope'],
    'Engagement'=>['Keep the guest journey intimate','Choose a venue that fits one strong function','Prioritise food, photography and atmosphere'],
    'Birthday'=>['Design around the person, not a theme pack','Protect entertainment and food flow','Plan an easy arrival and exit'],
    'Anniversary'=>['Make it personal, not performative','Use story, music and food as anchors','Keep production proportional to the room'],
    'Baby Shower'=>['Comfort and timing matter most','Choose soft hospitality and flexible seating','Plan photography around family moments'],
    'Corporate'=>['Run-of-show is the backbone','AV, stage and guest flow come before decor','Assign cue ownership to one production lead'],
    'Festive'=>['Build around ritual, food and gathering','Plan crowd movement and service capacity','Use decor to support, not overwhelm'],
    'Private Party'=>['Protect energy from arrival to last song','Plan bar, food and sound as one system','Keep logistics invisible to guests']
    ];
    $guide=$guides[$type]??$guides['Wedding'];
    $pageTitle=$type.($city?' in '.$city:'').' — Vendors, Venues & Ideas';
    $pageDescription='Plan a '.$type.($city?' in '.$city:'').' with curated vendors, venues, real event inspiration and practical planning guidance.';
    $pageKey='event';
    require __DIR__.'/includes/header.php';
?>
<main>
    <section class="landing-hero-v2 event-landing-v2">
        <div class="landing-hero-image">
            <img src="<?=h($event['image'])?>
            " alt="
            <?= h($type) ?>
            celebration" fetchpriority="high">
        </div>
        <div class="landing-hero-overlay">
        </div>
        <div class="container landing-hero-content">
            <span class="eyebrow light">
            WEDDING ZA OCCASION EDIT
            <?= $city?' / '.h(strtoupper($city)):'' ?>
            </span>
            <h1>
            <?= h($type) ?>
            <br>
            <em>
            done with intent.
            </em>
            </h1>
            <p>
            <?= h($event['sub']) ?>
            . Start with the decisions that shape the whole experience, then build the right local team around them.
            </p>
            <div class="landing-hero-actions">
                <a class="vision-primary" href="vendors.php?event=<?=urlencode($type)?>
                <?= $city?'&city='.urlencode($city):'' ?>
                ">Find
                <?= h($type) ?>
                vendors
                <span>
                ↗
                </span>
                </a>
                <a class="vision-secondary" href="#guide">
                Planning priorities
                </a>
            </div>
        </div>
    </section>
    <section class="section event-guide-section" id="guide">
        <div class="container event-guide-grid">
            <div>
                <span class="eyebrow">
                THE FIRST THREE DECISIONS
                </span>
                <h2>
                Plan the function
                <br>
                <em>
                before the feed.
                </em>
                </h2>
                <p>
                Inspiration helps, but operational choices are what make a celebration actually feel effortless.
                </p>
            </div>
            <div class="event-guide-cards">
                <?php
                    foreach($guide as $i=>$item):
                ?>
                    <article>
                        <span>
                        0
                        <?= $i+1 ?>
                        </span>
                        <h3>
                        <?= h($item) ?>
                        </h3>
                        <p>
                        Resolve this before expanding the shortlist. It will make every later vendor conversation more specific.
                        </p>
                    </article>
                <?php
                    endforeach;
                ?>
            </div>
        </div>
    </section>
    <section class="section paper-2">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">
                    <?= h(strtoupper($type)) ?>
                    VENDORS
                    <?= $city?' · '.h(strtoupper($city)):'' ?>
                    </span>
                    <h2>
                    Teams that already
                    <br>
                    <em>
                    fit the occasion.
                    </em>
                    </h2>
                </div>
                <a class="text-link" href="vendors.php?event=<?=urlencode($type)?>
                <?= $city?'&city='.urlencode($city):'' ?>
                ">View all matches ↗
                </a>
            </div>
            <div class="vendor-grid">
                <?php
                    $display=$vendors?:array_filter(wz_data('vendors'),fn($v)=>in_array($type,$v['events']??[],true));
                    foreach(array_slice(array_values($display),0,6) as $v) wz_vendor_card($v);
                ?>
            </div>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">
                    CHOOSE THE PLACE
                    </span>
                    <h2>
                    <?= h($type) ?>
                    ideas,
                    <br>
                    <em>
                    city by city.
                    </em>
                    </h2>
                </div>
                <p>
                Different cities change venue inventory, budgets, logistics and the mood of the entire function.
                </p>
            </div>
            <div class="city-link-grid">
                <?php
                    foreach(array_slice(wz_data('cities'),0,6) as $c):
                ?>
                    <a href="event.php?type=<?=urlencode($type)?>
                    &city=
                    <?= urlencode($c) ?>
                    ">
                    <span>
                    <?= h($c) ?>
                    </span>
                    <b>
                    <?= h($type) ?>
                    guide ↗
                    </b>
                    </a>
                <?php
                    endforeach;
                ?>
            </div>
        </div>
    </section>
    <?php
        if($stories):
    ?>
        <section class="section dark-editorial">
            <div class="container">
                <div class="section-head">
                    <div>
                        <span class="eyebrow light">
                        REAL
                        <?= h(strtoupper($type)) ?>
                        STORIES
                        </span>
                        <h2>
                        See the idea
                        <br>
                        <em>
                        fully realised.
                        </em>
                        </h2>
                    </div>
                </div>
                <div class="story-grid-premium">
                    <?php
                        foreach(array_slice($stories,0,4) as $w) wz_wedding_card($w);
                    ?>
                </div>
            </div>
        </section>
    <?php
        endif;
    ?>
    <section class="section event-cross-links">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">
                    OTHER OCCASIONS
                    </span>
                    <h2>
                    Planning something
                    <br>
                    <em>
                    different?
                    </em>
                    </h2>
                </div>
            </div>
            <div class="landing-event-grid compact">
                <?php
                    foreach(array_filter(wz_data('event_types'),fn($x)=>$x['name']!==$type) as $other):
                ?>
                    <a href="event.php?type=<?=urlencode($other['name'])?>
                    " class="landing-event-card">
                    <img src="<?=h($other['image'])?>
                    " alt="
                    <?= h($other['name']) ?>
                    " loading="lazy">
                    <div>
                        <span>
                        OCCASION
                        </span>
                        <h3>
                        <?= h($other['name']) ?>
                        </h3>
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
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>

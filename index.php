<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $pageTitle='Celebrations, Reimagined';
    $pageDescription='Discover remarkable venues, vendors, ideas and planning tools for weddings, birthdays, engagements, corporate events and every celebration across India.';
    $pageKey='home';
    $vendors=wz_data('vendors');
    $weddings=wz_data('weddings');
    $articles=wz_data('articles');
    $ideas=wz_data('inspiration');
    $categories=wz_data('categories');
    $events=wz_data('event_types');
    $featuredCelebrations=[];
    foreach(['aanya-veer','ria-roka','aurora-summit'] as $fid) {
    $item=wz_wedding($fid);
    if($item)$featuredCelebrations[]=$item;
    }
    $cities=[
    ['Jaipur','Palaces · courtyards · colour','https://images.unsplash.com/photo-1767158597961-bd58dccc16bd?auto=format&fit=crop&w=1800&q=94'],
    ['Udaipur','Lakes · light · old-world romance','https://images.unsplash.com/photo-1770665567877-72ee8a7c9051?auto=format&fit=crop&w=1800&q=94'],
    ['Goa','Salt air · sunset · after-parties','https://images.unsplash.com/photo-1710952356679-1eff1cb5ba64?auto=format&fit=crop&w=1800&q=94'],
    ['Delhi NCR','Grandeur · scale · everything close','https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=1800&q=94']
    ];
    require __DIR__.'/includes/header.php';
?>
<main class="vision-home">
    <section class="vision-hero celebration-hero" id="home">
        <div class="vision-hero-bg">
            <img src="https://images.unsplash.com/photo-1744805624952-dab790f6b3bd?auto=format&fit=crop&w=2200&q=95" alt="Elegant celebration decor in Mumbai, India" fetchpriority="high" decoding="async">
        </div>
        <div class="vision-hero-vignette">
        </div>
        <div class="container vision-hero-stage">
            <div class="vision-hero-eyebrow">
                INDIA’S CELEBRATION DISCOVERY PLATFORM / 2026
            </div>
            <div class="hero-occasion-line" aria-hidden="true">
                <span>
                Weddings
                </span>
                <i>
                </i>
                <span>
                Milestones
                </span>
                <i>
                </i>
                <span>
                Brand moments
                </span>
                <i>
                </i>
                <span>
                Private celebrations
                </span>
            </div>
            <h1 aria-label="Whatever the occasion. Make it unforgettable.">
            <span class="hero-line">
            Whatever the occasion.
            </span>
            <span class="hero-line hero-line-italic">
            Make it
            <em>
            unforgettable.
            </em>
            </span>
            </h1>
            <p class="vision-hero-copy">
            From a 40-person dinner to a 4,000-guest celebration — discover remarkable venues, creators and event teams across India without drowning in options.
            </p>
            <div class="vision-hero-actions">
                <button type="button" class="vision-primary hero-mobile-plan" data-discovery-open>
                Plan your event
                <span>
                ↗
                </span>
                </button>
                <a href="real-weddings.php" class="vision-secondary">
                Explore real celebrations
                </a>
            </div>
            <div class="celebration-types" aria-label="Celebration types">
                <?php
                    foreach(array_slice($events,0,6) as $event):
                ?>
                    <a href="event.php?type=<?=urlencode($event['name'])?>
                    ">
                    <?= h($event['name']) ?>
                    </a>
                <?php
                    endforeach;
                ?>
            </div>
            <form class="hero-plan-dock" id="heroPlanDock" action="vendors.php" method="get">
                <div class="hero-plan-preview">
                    <img id="heroPlanPreview" src="<?=h($events[0]['image'] ?? '')?>
                    " alt="" aria-hidden="true">
                    <span>
                    <small>
                    QUICK START
                    </small>
                    <strong id="heroPlanLabel">
                    Build your event team
                    </strong>
                    </span>
                </div>
                <label>
                    <span>
                    Occasion
                    </span>
                    <select name="event" id="heroEvent">
                        <option value="">
                        Any celebration
                        </option>
                        <?php
                            foreach($events as $event):
                        ?>
                            <option value="<?=h($event['name'])?>
                            " data-image="
                            <?= h($event['image']) ?>
                            ">
                            <?= h($event['name']) ?>
                            </option>
                        <?php
                            endforeach;
                        ?>
                    </select>
                </label>
                <label>
                    <span>
                    City
                    </span>
                    <select name="city" id="heroCity">
                        <option value="">
                        Anywhere in India
                        </option>
                        <?php
                            foreach(wz_data('cities') as $city):
                        ?>
                            <option value="<?=h($city)?>
                            ">
                            <?= h($city) ?>
                            </option>
                        <?php
                            endforeach;
                        ?>
                    </select>
                </label>
                <label>
                    <span>
                    Need
                    </span>
                    <select name="category" id="heroCategory">
                        <option value="">
                        Any vendor
                        </option>
                        <?php
                            foreach($categories as $category):
                        ?>
                            <option value="<?=h($category['name'])?>
                            ">
                            <?= h($category['name']) ?>
                            </option>
                        <?php
                            endforeach;
                        ?>
                    </select>
                </label>
                <button type="submit">
                Explore
                <span>
                ↗
                </span>
                </button>
            </form>
        </div>
        <figure class="vhero-float vhero-float-a">
            <img src="https://images.unsplash.com/photo-1776078171101-0776fd3a953f?auto=format&fit=crop&w=1200&q=94" alt="Indian couple celebrating an engagement">
            <figcaption>
            01 / ENGAGEMENT
            </figcaption>
        </figure>
        <figure class="vhero-float vhero-float-b">
            <img src="https://images.unsplash.com/photo-1695277789188-ec9ef58d3bc3?auto=format&fit=crop&w=1200&q=94" alt="Corporate event stage in India">
            <figcaption>
            02 / CORPORATE
            </figcaption>
        </figure>
        <div class="vision-scroll-note">
            <span>
            SCROLL
            </span>
            <i>
            </i>
            <span>
            DISCOVER
            </span>
        </div>
    </section>
    <section class="vision-statement section-bleed">
        <div class="container vision-statement-grid">
            <div class="vision-statement-index">
                WZ / 01
            </div>
            <div class="vision-statement-copy">
                <span>
                THE IDEA
                </span>
                <h2 data-split-title>
                Every function deserves more than a directory. It deserves
                <em>
                the right people, place and feeling.
                </em>
                </h2>
            </div>
            <p>
            Wedding Za brings venue discovery, vendors, ideas and planning into one curated experience — whether you are hosting fifty people or five thousand.
            </p>
        </div>
    </section>
    <section class="vision-experience" id="visionExperience">
        <div class="vision-experience-sticky">
            <div class="container vision-experience-head">
                <span>
                WZ / 02
                </span>
                <div>
                    <small>
                    START WITH THE OCCASION
                    </small>
                    <h2>
                    What are we
                    <br>
                    celebrating?
                    </h2>
                </div>
                <p>
                Choose the function first. We will shape the vendor mix, venue direction and planning journey around the kind of event you are creating.
                </p>
            </div>
            <div class="vision-category-track" id="visionCategoryTrack">
                <?php
                    foreach($events as $i=>$event):
                ?>
                    <a class="vision-category-panel" href="event.php?type=<?=urlencode($event['name'])?>
                    ">
                    <div class="vision-category-image">
                        <img src="<?=h($event['image'])?>
                        " alt="
                        <?= h($event['name']) ?>
                        celebration" loading="lazy" decoding="async">
                    </div>
                    <div class="vision-category-number">
                        <?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?>
                    </div>
                    <div class="vision-category-copy">
                        <span>
                        CELEBRATION
                        </span>
                        <h3>
                        <?= h($event['name']) ?>
                        </h3>
                        <p>
                        <?= h($event['sub']) ?>
                        </p>
                        <b>
                        Plan this event ↗
                        </b>
                    </div>
                    </a>
                <?php
                    endforeach;
                ?>
            </div>
        </div>
    </section>
    <section class="vision-cities section-bleed">
        <div class="container vision-section-head">
            <div>
                <span>
                WZ / 03
                </span>
                <small>
                PLACE CHANGES EVERYTHING
                </small>
            </div>
            <h2>
            Choose the city.
            <br>
            <em>
            Then build the mood.
            </em>
            </h2>
            <p>
            Venue scale, logistics, light, weather, access and energy — the city quietly shapes every kind of function.
            </p>
        </div>
        <div class="vision-city-rail-wrap">
            <div class="vision-city-rail" id="visionCityRail">
                <?php
                    foreach($cities as $i=>$city):
                ?>
                    <a class="vision-city-card" href="city.php?city=<?=urlencode($city[0])?>
                    ">
                    <figure>
                        <img src="<?=h($city[2])?>
                        " alt="
                        <?= h($city[0]) ?>
                        event destination" loading="lazy" decoding="async">
                    </figure>
                    <div>
                        <span>
                        0
                        <?= $i+1 ?>
                        </span>
                        <h3>
                        <?= h($city[0]) ?>
                        </h3>
                        <p>
                        <?= h($city[1]) ?>
                        </p>
                        <b>
                        Open city guide ↗
                        </b>
                    </div>
                    </a>
                <?php
                    endforeach;
                ?>
            </div>
        </div>
    </section>
    <section class="vision-vendors section-bleed">
        <div class="container vision-section-head light">
            <div>
                <span>
                WZ / 04
                </span>
                <small>
                THE WEDDING ZA EDIT
                </small>
            </div>
            <h2>
            Teams worth
            <br>
            <em>
            meeting first.
            </em>
            </h2>
            <p>
            Venues, planners, photographers, caterers, artists and specialists selected for strong work and clear context — across every kind of celebration.
            </p>
        </div>
        <div class="container vision-featured-vendors">
            <div class="vision-featured-large">
                <?php
                    if(!empty($vendors[0]))wz_vendor_card($vendors[0],'vision-vendor-featured');
                ?>
            </div>
            <div class="vision-featured-stack">
                <?php
                    foreach(array_slice($vendors,1,4) as $v)wz_vendor_card($v);
                ?>
            </div>
        </div>
        <div class="container vision-section-action">
            <a href="vendors.php">
            See the full vendor edit
            <span>
            ↗
            </span>
            </a>
        </div>
    </section>
    <section class="vision-real section-bleed">
        <div class="container vision-real-head">
            <span>
            WZ / 05 · REAL CELEBRATIONS
            </span>
            <h2>
            See how a function
            <br>
            <em>
            becomes a feeling.
            </em>
            </h2>
        </div>
        <div class="vision-real-stage">
            <?php
                foreach($featuredCelebrations as $i=>$w):
            ?>
                <article class="vision-real-panel">
                    <a href="wedding-story.php?id=<?=urlencode($w['id'])?>
                    ">
                    <img src="<?=h($w['image'])?>
                    " alt="
                    <?= h($w['couple']) ?>
                    celebration" loading="lazy" decoding="async">
                    <div class="vision-real-overlay">
                        <span>
                        <?= h($w['event_type']??'Celebration') ?>
                        ·
                        <?= h($w['city']) ?>
                        ·
                        <?= h($w['theme']) ?>
                        </span>
                        <h3>
                        <?= h($w['couple']) ?>
                        </h3>
                        <p>
                        <?= h($w['title']) ?>
                        </p>
                        <b>
                        View the story ↗
                        </b>
                    </div>
                    </a>
                </article>
            <?php
                endforeach;
            ?>
        </div>
    </section>
    <section class="vision-ideas section-bleed">
        <div class="container vision-section-head">
            <div>
                <span>
                WZ / 06
                </span>
                <small>
                THE VISUAL EDIT
                </small>
            </div>
            <h2>
            Save what stops
            <br>
            <em>
            your scroll.
            </em>
            </h2>
            <p>
            Decor, fashion, food, stages, flowers, portraits, entrances, lighting and details for celebrations of every scale.
            </p>
        </div>
        <div class="vision-idea-collage">
            <?php
                foreach(array_slice($ideas,0,7) as $i=>$item):
            ?>
                <a class="vision-idea vision-idea-<?=$i+1?>
                " href="inspiration.php">
                <img src="<?=h($item['image'])?>
                " alt="
                <?= h($item['title']) ?>
                " loading="lazy" decoding="async">
                <span>
                <?= h($item['category']) ?>
                </span>
                <h3>
                <?= h($item['title']) ?>
                </h3>
                </a>
            <?php
                endforeach;
            ?>
        </div>
        <div class="container vision-section-action dark">
            <a href="inspiration.php">
            Enter the inspiration edit
            <span>
            ↗
            </span>
            </a>
        </div>
    </section>
    <section class="vision-concierge section-bleed">
        <div class="vision-concierge-image">
            <img src="https://images.unsplash.com/photo-1744805624952-dab790f6b3bd?auto=format&fit=crop&w=1800&q=94" alt="Premium event decor in India" loading="lazy" decoding="async">
        </div>
        <div class="container vision-concierge-grid">
            <div>
                <span>
                ZA ASSIST / COMING NEXT
                </span>
                <h2>
                When planning becomes
                <br>
                <em>
                decision fatigue.
                </em>
                </h2>
            </div>
            <div>
                <p>
                Tell us the occasion, city, guest count, budget and feeling you are after. Wedding Za Assist is designed to turn a hundred options into a thoughtful event shortlist.
                </p>
                <a href="contact.php">
                Talk to Wedding Za
                <span>
                ↗
                </span>
                </a>
            </div>
        </div>
    </section>
    <section class="vision-journal section-bleed">
        <div class="container vision-section-head">
            <div>
                <span>
                WZ / 07
                </span>
                <small>
                THE JOURNAL
                </small>
            </div>
            <h2>
            Read before
            <br>
            <em>
            you decide.
            </em>
            </h2>
            <p>
            Useful context on venues, budgets, decor, guest experience and event planning — written to make every function easier to shape.
            </p>
        </div>
        <div class="container vision-journal-grid">
            <?php
                foreach(array_slice($articles,0,4) as $a)wz_article_card($a);
            ?>
        </div>
    </section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>

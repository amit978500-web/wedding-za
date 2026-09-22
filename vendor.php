<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $id=(string)($_GET['id']??'amber-courtyard');
    $v=wz_vendor($id)??(wz_data('vendors')[0]??null);
    if(!$v) {
    http_response_code(404);
    header('Location:404.php');
    exit;
    }
    $pageTitle=$v['name'];
    $pageDescription=$v['about'];
    $pageKey='vendor';
    require __DIR__.'/includes/header.php';
    $images=$v['images']??[$v['image']];
    while(count($images)<3)$images[]=$v['image'];
    $eventFit=$v['events']??[];
?>
<main>
    <section class="vendor-profile-hero">
        <div class="container">
            <?php
                wz_breadcrumbs([['Vendors','vendors.php'],[$v['category'],'vendors.php?category='.urlencode($v['category'])],[$v['name'],null]]);
            ?>
            <div class="vendor-profile-top">
                <div class="vendor-profile-title">
                    <span class="eyebrow">
                    <?= h($v['category']) ?>
                    ·
                    <?= h($v['city']) ?>
                    </span>
                    <h1>
                    <?= h($v['name']) ?>
                    </h1>
                    <p>
                    <?= h($v['locality']) ?>
                    ,
                    <?= h($v['city']) ?>
                    ·
                    <?= !empty($v['verified'])?'✓ Verified business':'' ?>
                    </p>
                </div>
                <div class="vendor-profile-actions">
                    <div class="rating-pill">
                        ★
                        <?= h((string)$v['rating']) ?>
                        ·
                        <?= h((string)$v['reviews']) ?>
                        reviews
                    </div>
                    <button class="pill-btn outline heart-btn-static" type="button" data-shortlist="<?=h($v['id'])?>
                    ">♡ Save to shortlist
                    </button>
                </div>
            </div>
            <div class="vendor-profile-gallery">
                <figure class="vendor-profile-gallery-main">
                    <img src="<?=h($images[0])?>
                    " alt="
                    <?= h($v['name']) ?>
                    portfolio">
                </figure>
                <figure>
                    <img src="<?=h($images[1])?>
                    " alt="
                    <?= h($v['name']) ?>
                    portfolio">
                </figure>
                <figure>
                    <img src="<?=h($images[2])?>
                    " alt="
                    <?= h($v['name']) ?>
                    portfolio">
                </figure>
                <div class="vendor-gallery-label">
                    <small>
                    SELECTED WORK
                    </small>
                    <strong>
                    <?= count($images) ?>
                    +
                    </strong>
                    <span>
                    portfolio frames
                    </span>
                </div>
            </div>
        </div>
    </section>
    <section class="vendor-profile-content section-sm">
        <div class="container vendor-profile-layout">
            <div class="vendor-profile-main">
                <nav class="profile-tabs profile-tabs-v2">
                    <a href="#fit">
                    Event fit
                    </a>
                    <a href="#about">
                    About
                    </a>
                    <a href="#services">
                    Services
                    </a>
                    <a href="#details">
                    Details
                    </a>
                    <a href="#portfolio">
                    Portfolio
                    </a>
                </nav>
                <section class="profile-block event-fit-block" id="fit">
                    <div class="profile-block-head">
                        <span class="eyebrow">
                        BEST FOR
                        </span>
                        <h2>
                        Where this team fits.
                        </h2>
                    </div>
                    <div class="event-fit-list">
                        <?php
                            foreach($eventFit as $event):
                        ?>
                            <a href="vendors.php?event=<?=urlencode($event)?>
                            &category=
                            <?= urlencode($v['category']) ?>
                            ">
                            <?= h($event) ?>
                            </a>
                        <?php
                            endforeach;
                        ?>
                    </div>
                </section>
                <section class="profile-block" id="about">
                    <div class="profile-block-head">
                        <span class="eyebrow">
                        ABOUT
                        </span>
                        <h2>
                        A little about
                        <?= h($v['name']) ?>
                        </h2>
                    </div>
                    <p class="profile-lead">
                    <?= h($v['about']) ?>
                    </p>
                </section>
                <section class="profile-block" id="services">
                    <div class="profile-block-head">
                        <span class="eyebrow">
                        WHAT THEY DO
                        </span>
                        <h2>
                        Services
                        </h2>
                    </div>
                    <div class="service-grid-v2">
                        <?php
                            foreach($v['services']??[] as $i=>$s):
                        ?>
                            <div>
                                <span>
                                <?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?>
                                </span>
                                <strong>
                                <?= h($s) ?>
                                </strong>
                            </div>
                        <?php
                            endforeach;
                        ?>
                    </div>
                </section>
                <section class="profile-block" id="details">
                    <div class="profile-block-head">
                        <span class="eyebrow">
                        GOOD TO KNOW
                        </span>
                        <h2>
                        Before you enquire
                        </h2>
                    </div>
                    <div class="vendor-facts-v2">
                        <div>
                            <span>
                            Capacity / team
                            </span>
                            <strong>
                            <?= h($v['capacity']??'Ask vendor') ?>
                            </strong>
                        </div>
                        <div>
                            <span>
                            Experience
                            </span>
                            <strong>
                            <?= h($v['experience']??'Ask vendor') ?>
                            </strong>
                        </div>
                        <div>
                            <span>
                            Policy
                            </span>
                            <strong>
                            <?= h($v['policy']??'Ask vendor') ?>
                            </strong>
                        </div>
                    </div>
                </section>
                <section class="profile-block" id="portfolio">
                    <div class="profile-block-head">
                        <span class="eyebrow">
                        PORTFOLIO
                        </span>
                        <h2>
                        Selected work
                        </h2>
                    </div>
                    <div class="story-gallery vendor-story-gallery">
                        <?php
                            foreach($images as $im):
                        ?>
                            <figure>
                                <img src="<?=h($im)?>
                                " alt="
                                <?= h($v['name']) ?>
                                work" loading="lazy">
                            </figure>
                        <?php
                            endforeach;
                        ?>
                    </div>
                </section>
            </div>
            <aside class="enquiry-card enquiry-card-v2">
                <div class="enquiry-card-head">
                    <small>
                    STARTING FROM
                    </small>
                    <h3>
                    <?= h($v['price']) ?>
                    </h3>
                    <span>
                    <?= h($v['tag']) ?>
                    </span>
                </div>
                <div class="enquiry-proof">
                    <div>
                        <strong>
                        <?= h((string)$v['rating']) ?>
                        </strong>
                        <span>
                        rating
                        </span>
                    </div>
                    <div>
                        <strong>
                        <?= h((string)$v['reviews']) ?>
                        </strong>
                        <span>
                        reviews
                        </span>
                    </div>
                    <div>
                        <strong>
                        <?= count($eventFit) ?>
                        </strong>
                        <span>
                        event types
                        </span>
                    </div>
                </div>
                <form class="form-stack" data-async action="api/lead.php" method="post">
                    <input type="hidden" name="type" value="vendor-enquiry">
                    <input type="hidden" name="vendor" value="<?=h($v['name'])?>
                    ">
                    <input class="hp-field" name="company_website" tabindex="-1" autocomplete="off">
                    <div class="field">
                        <label>
                            Your name
                        </label>
                        <input name="name" required>
                    </div>
                    <div class="field">
                        <label>
                            Phone / WhatsApp
                        </label>
                        <input name="phone" inputmode="tel" required>
                    </div>
                    <div class="field">
                        <label>
                            What are you planning?
                        </label>
                        <select name="topic">
                            <option value="">
                            Choose occasion
                            </option>
                            <?php
                                foreach($eventFit as $event):
                            ?>
                                <option>
                                <?= h($event) ?>
                                </option>
                            <?php
                                endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>
                                Event city
                            </label>
                            <input name="city" value="<?=h($v['city'])?>
                            ">
                        </div>
                        <div class="field">
                            <label>
                                Event date
                            </label>
                            <input type="date" name="event_date">
                        </div>
                    </div>
                    <div class="field">
                        <label>
                            Tell them a little
                        </label>
                        <textarea name="message" placeholder="Guest count, function details, timings and what you need from them…">
                        </textarea>
                    </div>
                    <button class="pill-btn wine wide" type="submit">
                    Request pricing & availability ↗
                    </button>
                    <div class="success-box">
                    </div>
                    <p class="form-note">
                    Demo enquiries are saved locally to
                    <code>
                    storage/leads.csv
                    </code>
                    . Connect your CRM/email before production launch.
                    </p>
                </form>
            </aside>
        </div>
    </section>
    <section class="section paper-2">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">
                    KEEP COMPARING
                    </span>
                    <h2>
                    Similar teams,
                    <br>
                    <em>
                    same decision.
                    </em>
                    </h2>
                </div>
                <a class="text-link" href="vendors.php?category=<?=urlencode($v['category'])?>
                ">More
                <?= h($v['category']) ?>
                ↗
                </a>
            </div>
            <div class="vendor-grid">
                <?php
                    $n=0;
                    foreach(wz_data('vendors') as $x) {
                    if($x['id']!==$v['id']&&$x['category']===$v['category']) {
                    wz_vendor_card($x);
                    if(++$n===3)break;
                    }
                    }
                ?>
            </div>
        </div>
    </section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>

<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    require __DIR__.'/includes/vendors.php';
    require __DIR__.'/includes/marketplace.php';
    require __DIR__.'/includes/media.php';
    $id=(string)($_GET['id']??'amber-courtyard');
    $v=wz_public_vendor($id)??(wz_public_vendors()[0]??null);
    if(!$v) {
    http_response_code(404);
    header('Location:404.php');
    exit;
    }
    $pageTitle=$v['name'];
    $pageDescription=$v['about'];
    $pageKey='vendor';
    $pageImage=(string)($v['image']??'');
    $businessUserId=(int)($v['database_user_id']??0);
    $businessType=(string)($v['business_type']??(
        ($v['category']??'')==='Venues'?'venue':'vendor'
    ));
    $reviewMessage='';
    $reviewSuccess=false;

    if (
        $_SERVER['REQUEST_METHOD']==='POST'
        && ($_POST['action']??'')==='review'
    ) {
        if (!wz_is_logged_in() || wz_role()!=='host') {
            $reviewMessage='Sign in as a customer to write a review.';
        } elseif (!wz_csrf_valid($_POST['csrf']??null)) {
            $reviewMessage='Session expired. Refresh and try again.';
        } elseif ($businessUserId<=0) {
            $reviewMessage='Reviews are not available for this profile yet.';
        } else {
            $reviewPhotos=[];

            if (
                isset($_FILES['review_photo'])
                && (int)($_FILES['review_photo']['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_OK
            ) {
                $upload=wz_media_upload(
                    $_FILES['review_photo'],
                    'Customer review photo for '.(string)$v['name']
                );

                if(empty($upload['ok'])){
                    $reviewMessage=(string)$upload['message'];
                }else{
                    $reviewPhotos[]=(string)$upload['path'];
                }
            }

            if($reviewMessage===''){
            $result=wz_marketplace_create_review([
                'reviewer_user_id'=>(int)(wz_user()['id']??0),
                'business_user_id'=>$businessUserId,
                'business_type'=>$businessType,
                'rating'=>(int)($_POST['rating']??0),
                'title'=>(string)($_POST['title']??''),
                'body'=>(string)($_POST['body']??''),
                'photos'=>$reviewPhotos,
            ]);
            $reviewMessage=(string)$result['message'];
            $reviewSuccess=!empty($result['ok']);
            }
        }
    }

    $reviews=$businessUserId>0
        ?wz_marketplace_reviews_for_business(
            $businessUserId,
            $businessType
        )
        :[];

    $availability=$businessUserId>0
        ?wz_marketplace_availability(
            $businessUserId,
            $businessType,
            12
        )
        :[];

    if (wz_is_logged_in()) {
        $viewerId=(int)(wz_user()['id']??0);

        if ($viewerId>0) {
            wz_marketplace_record_view(
                $viewerId,
                $businessType,
                (string)$v['id']
            );

            if ($businessUserId>0) {
                wz_marketplace_record_business_event(
                    $businessUserId,
                    $businessType,
                    'profile_view',
                    wz_role()==='host'?$viewerId:null
                );
            }
        }
    }

    $structuredData=[
        [
            '@context'=>'https://schema.org',
            '@type'=>'LocalBusiness',
            'name'=>(string)$v['name'],
            'description'=>(string)$v['about'],
            'image'=>$pageImage,
            'areaServed'=>(string)($v['city']??'India'),
            'url'=>wz_app_url(
                'vendor.php?id='.
                urlencode((string)$v['id'])
            ),
            'aggregateRating'=>
                (int)($v['reviews']??0)>0
                    ?[
                        '@type'=>'AggregateRating',
                        'ratingValue'=>(float)($v['rating']??0),
                        'reviewCount'=>(int)($v['reviews']??0),
                        'bestRating'=>5,
                        'worstRating'=>1,
                    ]
                    :null,
        ],
    ];

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
                    <?php if ($businessType==='venue'): ?>
                        <button
                            class="pill-btn outline"
                            type="button"
                            data-compare-venue="<?=h($v['id'])?>"
                            data-compare-name="<?=h($v['name'])?>"
                        >
                            + Compare venue
                        </button>
                    <?php endif; ?>
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
                    <?php if($businessUserId>0): ?>
                        <a href="#availability">
                        Availability
                        </a>
                    <?php endif; ?>
                    <a href="#reviews">
                    Reviews
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

                    <?php if ($businessType==='venue'): ?>
                        <div class="vendor-facts-v2 marketplace-venue-facts">
                            <div>
                                <span>Rooms</span>
                                <strong><?= h((string)($v['rooms']??0)) ?></strong>
                            </div>
                            <div>
                                <span>Parking</span>
                                <strong>
                                    <?= !empty($v['parking_capacity'])
                                        ? h((string)$v['parking_capacity']).' cars'
                                        : 'Ask venue' ?>
                                </strong>
                            </div>
                            <div>
                                <span>Venue type</span>
                                <strong><?= h((string)($v['venue_type']??'Venue')) ?></strong>
                            </div>
                            <div>
                                <span>Veg price / plate</span>
                                <strong>
                                    <?= !empty($v['price_per_plate_veg'])
                                        ? '₹'.number_format((float)$v['price_per_plate_veg'])
                                        : 'Ask venue' ?>
                                </strong>
                            </div>
                            <div>
                                <span>Non-veg price / plate</span>
                                <strong>
                                    <?= !empty($v['price_per_plate_nonveg'])
                                        ? '₹'.number_format((float)$v['price_per_plate_nonveg'])
                                        : 'Ask venue' ?>
                                </strong>
                            </div>
                            <div>
                                <span>Rental</span>
                                <strong>
                                    <?= !empty($v['rental_price'])
                                        ? '₹'.number_format((float)$v['rental_price'])
                                        : 'Ask venue' ?>
                                </strong>
                            </div>
                        </div>

                        <?php if (!empty($v['spaces'])): ?>
                            <div class="profile-block-head" style="margin-top:28px;">
                                <span class="eyebrow">EVENT SPACES</span>
                                <h2>Spaces at this venue</h2>
                            </div>

                            <div class="marketplace-chip-list">
                                <?php foreach ($v['spaces'] as $space): ?>
                                    <span><?= h((string)$space) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($v['amenities'])): ?>
                            <div class="profile-block-head" style="margin-top:28px;">
                                <span class="eyebrow">AMENITIES</span>
                                <h2>What is available</h2>
                            </div>

                            <div class="marketplace-chip-list">
                                <?php foreach ($v['amenities'] as $amenity): ?>
                                    <span><?= h((string)$amenity) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty(array_filter($v['policies']??[]))): ?>
                            <div class="profile-block-head" style="margin-top:28px;">
                                <span class="eyebrow">POLICIES</span>
                                <h2>Before you book</h2>
                            </div>

                            <div class="vendor-facts-v2">
                                <?php foreach(($v['policies']??[]) as $policyName=>$policyValue): ?>
                                    <?php if(trim((string)$policyValue)!==''): ?>
                                        <div>
                                            <span><?= h(ucfirst((string)$policyName)) ?></span>
                                            <strong><?= h((string)$policyValue) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($v['video_url'])): ?>
                            <p style="margin-top:22px;">
                                <a
                                    class="text-link"
                                    href="<?=h((string)$v['video_url'])?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Watch venue video ↗
                                </a>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (!empty($v['service_areas'])): ?>
                            <div class="profile-block-head" style="margin-top:28px;">
                                <span class="eyebrow">SERVICE AREAS</span>
                                <h2>Where this team works</h2>
                            </div>

                            <div class="marketplace-chip-list">
                                <?php foreach($v['service_areas'] as $area): ?>
                                    <span><?=h((string)$area)?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($v['packages'])): ?>
                            <div class="profile-block-head" style="margin-top:28px;">
                                <span class="eyebrow">PACKAGES</span>
                                <h2>Starting package options</h2>
                            </div>

                            <div class="service-grid-v2">
                                <?php foreach($v['packages'] as $i=>$package): ?>
                                    <div>
                                        <span><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span>
                                        <strong><?=h((string)$package)?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
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

                <?php if($businessUserId>0): ?>
                    <section class="profile-block" id="availability">
                        <div class="profile-block-head">
                            <span class="eyebrow">AVAILABILITY</span>
                            <h2>Upcoming calendar status</h2>
                        </div>

                        <?php if($availability): ?>
                            <div class="marketplace-availability-grid">
                                <?php foreach($availability as $slot): ?>
                                    <div class="marketplace-availability-card">
                                        <strong>
                                            <?=h(date('d M Y',strtotime((string)$slot['availability_date'])))?>
                                        </strong>
                                        <span class="crm-badge <?=h((string)$slot['status'])?>">
                                            <?=h(ucfirst((string)$slot['status']))?>
                                        </span>
                                        <?php if(!empty($slot['note'])): ?>
                                            <small><?=h((string)$slot['note'])?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="profile-lead">
                                No public availability dates have been added yet. Send an enquiry to confirm your date.
                            </p>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <section class="profile-block marketplace-reviews" id="reviews">
                    <div class="profile-block-head">
                        <span class="eyebrow">CUSTOMER REVIEWS</span>
                        <h2>
                            <?= h((string)$v['reviews']) ?> review<?= (int)$v['reviews']===1?'':'s' ?>
                            · <?= h((string)$v['rating']) ?> ★
                        </h2>
                    </div>

                    <?php if ($reviewMessage!==''): ?>
                        <div class="marketplace-review-notice <?= $reviewSuccess?'success':'' ?>">
                            <?= h($reviewMessage) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($businessUserId>0 && wz_is_logged_in() && wz_role()==='host'): ?>
                        <form method="post" enctype="multipart/form-data" class="marketplace-review-form">
                            <input type="hidden" name="action" value="review">
                            <input type="hidden" name="csrf" value="<?=h(wz_csrf_token())?>">

                            <label>
                                <span>Rating</span>
                                <select name="rating" required>
                                    <option value="">Choose rating</option>
                                    <option value="5">5 — Excellent</option>
                                    <option value="4">4 — Very good</option>
                                    <option value="3">3 — Good</option>
                                    <option value="2">2 — Could be better</option>
                                    <option value="1">1 — Poor</option>
                                </select>
                            </label>

                            <label>
                                <span>Review title</span>
                                <input name="title" maxlength="180" placeholder="What stood out?">
                            </label>

                            <label class="full">
                                <span>Your experience</span>
                                <textarea name="body" required placeholder="Share useful details about communication, quality, value and the event experience."></textarea>
                            </label>

                            <label class="full">
                                <span>Review photo (optional)</span>
                                <input
                                    type="file"
                                    name="review_photo"
                                    accept="image/jpeg,image/png,image/webp"
                                >
                            </label>

                            <button class="pill-btn wine" type="submit">
                                Submit review ↗
                            </button>
                        </form>
                    <?php elseif ($businessUserId>0 && !wz_is_logged_in()): ?>
                        <p class="profile-lead">
                            <a href="login.php?role=host">Sign in as a customer</a>
                            to write a review.
                        </p>
                    <?php endif; ?>

                    <div class="marketplace-review-list">
                        <?php foreach ($reviews as $review): ?>
                            <article class="marketplace-review-card">
                                <div>
                                    <strong>
                                        <?= str_repeat('★',(int)$review['rating']) ?>
                                        <?= str_repeat('☆',5-(int)$review['rating']) ?>
                                    </strong>
                                    <?php if (!empty($review['is_verified_booking'])): ?>
                                        <span>✓ Verified booking</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($review['title'])): ?>
                                    <h3><?= h((string)$review['title']) ?></h3>
                                <?php endif; ?>
                                <p><?= h((string)$review['body']) ?></p>

                                <?php
                                $reviewPhotos=json_decode(
                                    (string)($review['photos_json']??'[]'),
                                    true
                                )?:[];
                                ?>

                                <?php if($reviewPhotos): ?>
                                    <div class="marketplace-review-photos">
                                        <?php foreach($reviewPhotos as $photo): ?>
                                            <img
                                                src="<?=h(
                                                    str_starts_with((string)$photo,'http')
                                                        ?(string)$photo
                                                        :wz_app_url((string)$photo)
                                                )?>"
                                                alt="Customer review photo"
                                                loading="lazy"
                                            >
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <small>
                                    <?= h((string)$review['reviewer_name']) ?>
                                    · <?= h(date('d M Y',strtotime((string)$review['created_at']))) ?>
                                </small>

                                <?php if (!empty($review['business_reply'])): ?>
                                    <div class="marketplace-business-reply">
                                        <strong>Business reply</strong>
                                        <p><?= h((string)$review['business_reply']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>

                        <?php if (!$reviews): ?>
                            <div class="empty-state">
                                <h3>No published reviews yet.</h3>
                                <p class="muted">Be the first customer to share a useful experience.</p>
                            </div>
                        <?php endif; ?>
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
                    Enquiries are stored securely in the Wedding Za lead system.
                    
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
                    foreach(wz_public_vendors() as $x) {
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

<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $id=(string)($_GET['id']??'aanya-veer');
    $w=wz_wedding($id)??(wz_data('weddings')[0]??null);
    if(!$w) {
    header('Location:404.php');
    exit;
    }
    $eventType=(string)($w['event_type']??'Wedding');
    $pageTitle=$w['couple'].' · '.$eventType;
    $pageDescription=$w['summary'];
    $pageKey='wedding-story';
    require __DIR__.'/includes/header.php';
?>
<main>
    <section class="story-hero">
        <img src="<?=h($w['image'])?>
        " alt="
        <?= h($w['couple']) ?>
        <?= h($eventType) ?>
        ">
        <div class="container story-hero-inner reveal">
            <span class="eyebrow light">
            <?= h($eventType) ?>
            ·
            <?= h($w['city']) ?>
            ·
            <?= h($w['date']) ?>
            </span>
            <h1>
            <?= h($w['couple']) ?>
            </h1>
            <p>
            <?= h($w['title']) ?>
            —
            <?= h($w['summary']) ?>
            </p>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <div class="section-head reveal">
                <div>
                    <span class="eyebrow">
                    THE STORY
                    </span>
                    <h2>
                    <?= h($w['theme']) ?>
                    ,
                    <br>
                    without trying too hard.
                    </h2>
                </div>
                <p>
                <?= h($w['summary']) ?>
                The result feels personal because the visual decisions stay consistent across the whole function instead of competing for attention.
                </p>
            </div>
            <div class="story-gallery">
                <?php
                    foreach($w['gallery']??[] as $g):
                ?>
                    <figure class="reveal">
                        <img src="<?=h($g)?>
                        " alt="
                        <?= h($w['couple']) ?>
                        event photo" loading="lazy">
                    </figure>
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
                    THE EVENT TEAM
                    </span>
                    <h2>
                    Meet the people
                    <br>
                    behind it.
                    </h2>
                </div>
            </div>
            <div class="vendor-grid">
                <?php
                    foreach($w['vendors']??[] as $vid) {
                    $v=wz_vendor($vid);
                    if($v)wz_vendor_card($v);
                    }
                ?>
            </div>
        </div>
    </section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>

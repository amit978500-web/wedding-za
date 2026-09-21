<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $pageTitle='Event Vendors';
    $pageDescription='Browse venues, photographers, planners, decorators, caterers, artists and event specialists across India.';
    $pageKey='vendors';
    $selectedEvent=(string)($_GET['event']??'');
    $selectedCity=(string)($_GET['city']??'');
    $selectedCategory=(string)($_GET['category']??'');
    require __DIR__.'/includes/header.php';
?>
<main>
    <section class="vendor-discovery-hero">
        <div class="container vendor-discovery-hero-grid">
            <div class="vendor-discovery-copy">
                <span class="eyebrow">
                CURATED FOR THE OCCASION
                </span>
                <h1>
                Find people who
                <br>
                <em>
                fit the function.
                </em>
                </h1>
                <p>
                Start with the occasion, then narrow by city and category. Every profile shows event fit, starting price and the details that matter before you enquire.
                </p>
                <div class="vendor-discovery-pills">
                    <?php
                        foreach(array_slice(wz_data('event_types'),0,6) as $event):
                    ?>
                        <a href="vendors.php?event=<?=urlencode($event['name'])?>
                        " class="
                        <?= $selectedEvent===$event['name']?'active':'' ?>
                        ">
                        <?= h($event['name']) ?>
                        </a>
                    <?php
                        endforeach;
                    ?>
                </div>
            </div>
            <div class="vendor-discovery-visual">
                <figure class="vendor-discovery-main">
                    <img src="https://images.unsplash.com/photo-1744805624952-dab790f6b3bd?auto=format&fit=crop&w=1400&q=92" alt="Indian event setup">
                </figure>
                <figure class="vendor-discovery-mini">
                    <img src="https://images.unsplash.com/photo-1770387688476-d7072fb2ca2e?auto=format&fit=crop&w=900&q=92" alt="Indian event photography">
                </figure>
                <div class="vendor-discovery-note">
                    <small>
                    THE SHORTCUT
                    </small>
                    <strong>
                    Occasion → City → Team
                    </strong>
                    <span>
                    Filter less. Decide faster.
                    </span>
                </div>
            </div>
        </div>
    </section>
    <section class="vendor-discovery-shell">
        <div class="container">
            <div class="vendor-filter-panel" id="vendorFilterPanel">
                <div class="vendor-filter-top">
                    <div>
                        <span class="eyebrow">
                        DISCOVERY FILTERS
                        </span>
                        <strong id="filterCount">
                        0 vendors found
                        </strong>
                    </div>
                    <button type="button" class="filter-reset" id="filterReset">
                    Reset filters
                    </button>
                </div>
                <div class="vendor-filter-grid">
                    <label class="vendor-filter-search">
                        <span>
                        Search
                        </span>
                        <input id="filterSearch" type="search" placeholder="Name, locality or service…">
                    </label>
                    <label>
                        <span>
                        Occasion
                        </span>
                        <select id="filterEvent">
                            <option value="">
                            All celebrations
                            </option>
                            <?php
                                foreach(wz_data('event_types') as $event):
                            ?>
                                <option value="<?=h($event['name'])?>
                                "
                                <?= $selectedEvent===$event['name']?'selected':'' ?>
                                >
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
                        <select id="filterCity">
                            <option value="">
                            All cities
                            </option>
                            <?php
                                foreach(wz_data('cities') as $city):
                            ?>
                                <option value="<?=h($city)?>
                                "
                                <?= $selectedCity===$city?'selected':'' ?>
                                >
                                <?= h($city) ?>
                                </option>
                            <?php
                                endforeach;
                            ?>
                        </select>
                    </label>
                    <label>
                        <span>
                        Category
                        </span>
                        <select id="filterCategory">
                            <option value="">
                            All categories
                            </option>
                            <?php
                                foreach(wz_data('categories') as $c):
                            ?>
                                <option value="<?=h($c['name'])?>
                                "
                                <?= $selectedCategory===$c['name']?'selected':'' ?>
                                >
                                <?= h($c['name']) ?>
                                </option>
                            <?php
                                endforeach;
                            ?>
                        </select>
                    </label>
                    <label>
                        <span>
                        Sort
                        </span>
                        <select id="filterSort">
                            <option value="featured">
                            Wedding Za edit
                            </option>
                            <option value="rating">
                            Highest rated
                            </option>
                            <option value="price">
                            Lowest starting price
                            </option>
                        </select>
                    </label>
                </div>
                <div class="active-filter-bar" id="activeFilterBar">
                </div>
            </div>
            <div class="vendor-listing-layout">
                <aside class="vendor-guide-panel">
                    <span class="eyebrow">
                    BEFORE YOU SHORTLIST
                    </span>
                    <h3>
                    Four signals worth checking.
                    </h3>
                    <div class="vendor-guide-list">
                        <div>
                            <b>
                            01
                            </b>
                            <span>
                            <strong>
                            Event fit
                            </strong>
                            <small>
                            Have they actually worked on your type of function?
                            </small>
                            </span>
                        </div>
                        <div>
                            <b>
                            02
                            </b>
                            <span>
                            <strong>
                            Starting price
                            </strong>
                            <small>
                            Use it as context, not the final quote.
                            </small>
                            </span>
                        </div>
                        <div>
                            <b>
                            03
                            </b>
                            <span>
                            <strong>
                            Portfolio consistency
                            </strong>
                            <small>
                            Look for repeatable quality, not one hero image.
                            </small>
                            </span>
                        </div>
                        <div>
                            <b>
                            04
                            </b>
                            <span>
                            <strong>
                            Operational clarity
                            </strong>
                            <small>
                            Policies, capacity and logistics matter.
                            </small>
                            </span>
                        </div>
                    </div>
                </aside>
                <div class="vendor-results">
                    <div class="vendor-results-head">
                        <div>
                            <span class="eyebrow">
                            THE WEDDING ZA EDIT
                            </span>
                            <h2>
                            Profiles worth opening.
                            </h2>
                        </div>
                        <p>
                        Save anyone worth a second look. Your shortlist stays on this device while we build the account layer.
                        </p>
                    </div>
                    <div class="vendor-grid vendor-grid-v2" id="vendorListing">
                        <?php
                            foreach(wz_data('vendors') as $v)wz_vendor_card($v);
                        ?>
                    </div>
                    <div class="empty-state" id="vendorEmpty" hidden>
                        <h3>
                        No exact match yet.
                        </h3>
                        <p class="muted">
                        Try another occasion, city, category or search term.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="section paper-2 vendor-next-step">
        <div class="container vendor-next-grid">
            <div>
                <span class="eyebrow">
                STILL TOO MANY OPTIONS?
                </span>
                <h2>
                Start with the
                <br>
                <em>
                decision that shapes the rest.
                </em>
                </h2>
            </div>
            <div>
                <p>
                Venue, planning, food and photography usually influence the rest of the function fastest. Begin there, then fill in specialist categories.
                </p>
                <a class="pill-btn wine" href="planner.php">
                Open planning studio ↗
                </a>
            </div>
        </div>
    </section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>

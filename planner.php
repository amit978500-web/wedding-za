<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $pageTitle='Planning Studio';
    $pageDescription='Build your event brief, manage tasks, track budget and keep your shortlist connected in one Wedding Za planning workspace.';
    $pageKey='planner';
    $budgetCats=['Venue & space','Food & beverage','Photography & film','Decor & production','Styling & artists','Entertainment','Hospitality & transport','Invites & gifting'];
    require __DIR__.'/includes/header.php';
?>
<main>
    <section class="planner-v2-hero">
        <div class="container planner-v2-hero-grid">
            <div>
                <span class="eyebrow">
                YOUR WEDDING ZA WORKSPACE
                </span>
                <h1>
                Plan with
                <br>
                <em>
                less noise.
                </em>
                </h1>
                <p>
                One event brief, one shortlist, one working budget and the tasks that actually move the function forward.
                </p>
            </div>
            <div class="planner-v2-journey">
                <a href="vendors.php">
                <span>
                01
                </span>
                <strong>
                Discover
                </strong>
                <small>
                Find the right teams
                </small>
                </a>
                <a href="shortlist.php">
                <span>
                02
                </span>
                <strong>
                Shortlist
                </strong>
                <small>
                Keep the serious options
                </small>
                </a>
                <a class="active" href="planner.php">
                <span>
                03
                </span>
                <strong>
                Plan
                </strong>
                <small>
                Turn choices into a brief
                </small>
                </a>
                <a href="contact.php">
                <span>
                04
                </span>
                <strong>
                Act
                </strong>
                <small>
                Enquire with context
                </small>
                </a>
            </div>
        </div>
    </section>
    <section class="section planner-brief-section">
        <div class="container planner-brief-grid">
            <div class="planner-brief-copy">
                <span class="eyebrow">
                EVENT BRIEF
                </span>
                <h2>
                Give the plan
                <br>
                <em>
                a centre.
                </em>
                </h2>
                <p>
                This context is saved on this device and can later become the foundation of a real user account, vendor enquiry and concierge brief.
                </p>
                <div class="brief-status" id="briefStatus">
                    <span>
                    LOCAL WORKSPACE
                    </span>
                    <strong>
                    Your brief saves automatically.
                    </strong>
                </div>
            </div>
            <form class="planner-brief-card" id="eventBriefForm">
                <label>
                    <span>
                    Occasion
                    </span>
                    <select data-brief="event">
                        <option value="">
                        Choose occasion
                        </option>
                        <?php
                            foreach(wz_data('event_types') as $event):
                        ?>
                            <option value="<?=h($event['name'])?>
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
                    <select data-brief="city">
                        <option value="">
                        Choose city
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
                    Event date
                    </span>
                    <input data-brief="date" type="date">
                </label>
                <label>
                    <span>
                    Guest count
                    </span>
                    <input data-brief="guests" type="number" min="1" placeholder="e.g. 250">
                </label>
                <label>
                    <span>
                    Budget range
                    </span>
                    <select data-brief="budget">
                        <option value="">
                        Choose range
                        </option>
                        <option>
                        Under ₹5 lakh
                        </option>
                        <option>
                        ₹5–15 lakh
                        </option>
                        <option>
                        ₹15–30 lakh
                        </option>
                        <option>
                        ₹30–60 lakh
                        </option>
                        <option>
                        ₹60 lakh+
                        </option>
                    </select>
                </label>
                <label>
                    <span>
                    One-line direction
                    </span>
                    <input data-brief="direction" placeholder="e.g. Modern Indian, warm, dinner-led">
                </label>
            </form>
        </div>
    </section>
    <section class="section paper-2">
        <div class="container planner-dashboard-grid">
            <aside class="planner-progress-card">
                <span class="eyebrow">
                PROGRESS
                </span>
                <div class="progress-ring" id="progressRing" style="--p:0">
                    <div>
                        <strong id="progressPct">
                        0%
                        </strong>
                        <span id="progressDone">
                        0/0
                        </span>
                    </div>
                </div>
                <h3>
                Keep momentum,
                <br>
                not pressure.
                </h3>
                <p>
                Tick what is genuinely decided. The percentage is a planning signal, not a score.
                </p>
                <div class="planner-shortlist-snapshot">
                    <span>
                    Saved vendors
                    </span>
                    <strong id="plannerShortlistCount">
                    0
                    </strong>
                    <a href="shortlist.php">
                    Open shortlist ↗
                    </a>
                </div>
            </aside>
            <div class="planner-main planner-main-v2" id="plannerChecklist">
                <?php
                    $n=0;
                    foreach(wz_data('checklist') as $group):
                ?>
                <section class="planner-group-v2">
                    <div class="planner-group-head">
                        <div>
                            <span class="eyebrow">
                            PLANNING PHASE
                            </span>
                            <h3>
                            <?= h($group['group']) ?>
                            </h3>
                        </div>
                        <small>
                        <?= count($group['items']) ?>
                        TASKS
                        </small>
                    </div>
                    <?php
                        foreach($group['items'] as $task):$key='t'.(++$n);
                    ?>
                    <label class="task-row task-row-v2">
                        <input type="checkbox" data-task="<?=h($key)?>
                        ">
                        <span>
                        <?= h($task) ?>
                        </span>
                        <i>
                        Done
                        </i>
                    </label>
                <?php
                    endforeach;
                ?>
            </section>
        <?php
            endforeach;
        ?>
    </div>
</div>
</section>
<section class="section budget-v2-section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">
                WORKING BUDGET
                </span>
                <h2>
                Know what is
                <br>
                <em>
                committed.
                </em>
                </h2>
            </div>
            <p>
            Track planned versus paid. This is intentionally simple: enough to keep the event financially visible without becoming accounting software.
            </p>
        </div>
        <div class="budget-board budget-board-v2">
            <div class="budget-card">
                <span>
                Planned total
                </span>
                <strong id="budgetPlanned">
                ₹0
                </strong>
            </div>
            <div class="budget-card">
                <span>
                Spent / paid
                </span>
                <strong id="budgetSpent">
                ₹0
                </strong>
            </div>
            <div class="budget-card">
                <span>
                Remaining
                </span>
                <strong id="budgetLeft">
                ₹0
                </strong>
            </div>
        </div>
        <div class="budget-table budget-table-v2" id="budgetTable">
            <div class="budget-row">
                <span>
                Category
                </span>
                <span>
                Planned
                </span>
                <span>
                Spent
                </span>
                <span>
                Status
                </span>
            </div>
            <?php
                foreach($budgetCats as $i=>$c):
            ?>
                <div class="budget-row">
                    <strong>
                    <?= h($c) ?>
                    </strong>
                    <input inputmode="numeric" type="number" min="0" placeholder="0" data-budget="b<?=$i?>
                    :planned">
                    <input inputmode="numeric" type="number" min="0" placeholder="0" data-budget="b<?=$i?>
                    :spent">
                    <span data-budget-status>
                    Track
                    </span>
                </div>
            <?php
                endforeach;
            ?>
        </div>
    </div>
</section>
<section class="section planner-action-section">
    <div class="container planner-action-grid">
        <div>
            <span class="eyebrow light">
            NEXT ACTION
            </span>
            <h2>
            Turn the plan
            <br>
            <em>
            into conversations.
            </em>
            </h2>
        </div>
        <div>
            <p>
            Your event brief and shortlist should make vendor enquiries more specific. Open your saved teams, compare them, then send context instead of a generic “price please”.
            </p>
            <a class="pill-btn light" href="shortlist.php">
            Review saved vendors ↗
            </a>
        </div>
    </div>
</section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>

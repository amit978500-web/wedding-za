        </main>
    </div>


    <?php if (!empty($wzMemberCard)): ?>
        <div
            class="wz-member-modal"
            data-wz-member-modal
            data-auto-open="<?= !empty($wzMemberCardAutoOpen) ? '1' : '0' ?>"
            aria-hidden="true"
        >
            <div
                class="wz-member-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="wzMemberDialogTitle"
            >
                <div class="wz-member-dialog-head">
                    <div>
                        <span>WELCOME TO YOUR WORKSPACE</span>
                        <strong id="wzMemberDialogTitle">
                            Your Wedding Za card
                        </strong>
                    </div>

                    <button
                        class="wz-member-close"
                        type="button"
                        data-wz-card-close
                        aria-label="Close Wedding Za card"
                    >
                        ×
                    </button>
                </div>

                <?= wz_crm_member_card_html(
                    $wzMemberCard,
                    'modal'
                ) ?>

                <div class="wz-member-dialog-actions">
                    <a href="<?= h(wz_app_url((string)$wzMemberCard['profile_path'])) ?>">
                        Update profile ↗
                    </a>

                    <button
                        type="button"
                        data-wz-card-close
                    >
                        Enter workspace
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script
        src="<?= h(wz_app_url('assets/js/crm-ui.js?v=1.2.0')) ?>"
        defer
    ></script>
</body>
</html>

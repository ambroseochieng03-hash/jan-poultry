
<?php

require_once
__DIR__ .
'/../app/config/database.php';

$db =
    (new Database())
    ->getConnection();

$stmt =
    $db->query(
        "
        SELECT
            setting_key,
            setting_value
        FROM settings
        "
    );

$settings = [];

foreach (
    $stmt->fetchAll(PDO::FETCH_ASSOC)
    as $row
) {

    $settings[
        $row['setting_key']
    ] =
    $row['setting_value'];

}
?>

<footer class="footer">

    <div class="footer-inner">

        <div class="footer-brand">

            <h2>

                <?= htmlspecialchars(
                    $settings['site_name']
                    ?? 'JAN POULTRY'
                ) ?>

            </h2>

            <p>

                <?= nl2br(
                    htmlspecialchars(
                        $settings['about_description']
                        ?? 'Fresh Poultry. Trusted Farmers. Delivered With Care.'
                    )
                ) ?>

            </p>

        </div>

        <!-- =====================================
             CONTACT
        ====================================== -->

        <div class="footer-contact">

            <a
                href="#"
                class="footer-contact-item">

                <i class="fa-solid fa-phone"></i>

                <span>

                    <?= htmlspecialchars(
                        $settings['contact_phone']
                        ?? '+254 XXX XXX XXX'
                    ) ?>

                </span>

            </a>

            <a
                href="#"
                class="footer-contact-item">

                <i class="fa-solid fa-envelope"></i>

                <span>

                    <?= htmlspecialchars(
                        $settings['contact_email']
                        ?? 'info@janpoultry.com'
                    ) ?>

                </span>

            </a>

            <a
                href="#"
                class="footer-contact-item">

                <i class="fa-solid fa-location-dot"></i>

                <span>

                    <?= htmlspecialchars(
                        $settings['contact_address']
                        ?? 'Kenya'
                    ) ?>

                </span>

            </a>

            <a
                href="#"
                class="footer-contact-item">

                <i class="fa-solid fa-clock"></i>

                <span>

                    <?= htmlspecialchars(
                        $settings['business_hours']
                        ?? 'Mon - Sat'
                    ) ?>

                </span>

            </a>

        </div>

        <!-- =====================================
             QUICK ACTIONS
        ====================================== -->

        <div class="footer-actions">

            <a href="/jan-poultry/">

                <i class="fa-solid fa-house"></i>

                <span>

                    Home

                </span>

            </a>

            <a href="/jan-poultry/auth/guest-login.php">

                <i class="fa-solid fa-user-secret"></i>

                <span>

                    Guest

                </span>

            </a>

            <a href="/jan-poultry/auth/login.php">

                <i class="fa-solid fa-right-to-bracket"></i>

                <span>

                    Sign In

                </span>

            </a>

            <a
                href="#"
                id="jpFooterRecoverAccount">

                <i class="fa-solid fa-key"></i>

                <span>

                    Recover

                </span>

            </a>

        </div>

        <!-- =====================================
             SOCIALS
        ====================================== -->

        <div class="footer-socials">

            <a

                href="<?= htmlspecialchars(
                    $settings['facebook_url']
                    ?? '#'
                ) ?>"

                target="_blank"

                rel="noopener">

                <i class="fab fa-facebook-f"></i>

            </a>

            <a

                href="<?= htmlspecialchars(
                    $settings['instagram_url']
                    ?? '#'
                ) ?>"

                target="_blank"

                rel="noopener">

                <i class="fab fa-instagram"></i>

            </a>

            <a

                href="<?= htmlspecialchars(
                    $settings['x_url']
                    ?? '#'
                ) ?>"

                target="_blank"

                rel="noopener">

                <i class="fab fa-x-twitter"></i>

            </a>

            <a

                href="https://wa.me/<?= preg_replace(
                    '/\D/',
                    '',
                    $settings['whatsapp_number']
                    ?? ''
                ) ?>"

                target="_blank"

                rel="noopener">

                <i class="fab fa-whatsapp"></i>

            </a>

            <a

                href="<?= htmlspecialchars(
                    $settings['youtube_url']
                    ?? '#'
                ) ?>"

                target="_blank"

                rel="noopener">

                <i class="fab fa-youtube"></i>

            </a>

        </div>

        <div class="footer-bottom">

            <p>

                © <?= date('Y') ?>

                JAN POULTRY

            </p>

            <span>

                Freshness • Quality • Trust

            </span>

        </div>

    </div>

</footer>

<script src="/jan-poultry/public/assets/js/home.js"></script>

<script src="/jan-poultry/public/assets/js/footer.js"></script>

<script
src="/jan-poultry/public/assets/js/navbar.js">
</script>

<script src="/jan-poultry/public/assets/js/auth-response-modal.js"></script>

<script src="/jan-poultry/public/assets/js/admin-forgot-password.js"></script>


</body>
</html>
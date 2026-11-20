<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/services/LocationService.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

Auth::requireAdmin();

$db =
(new Database())
->getConnection();

/*

$locationService =
    new LocationService(); */

/* fetch settings */
$stmt =
$db->query("
    SELECT setting_key, setting_value
    FROM settings
");

$rows = $stmt->fetchAll();

$settings = [];

foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

/* update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        /*
    |--------------------------------------------------------------------------
    | Resolve Google Maps Location
    |--------------------------------------------------------------------------
    */

    /*
    if (

        !empty(
            $_POST['google_maps_embed']
        )

    ) {

        $location =
            $locationService
                ->resolveLocation(

                    trim(
                        $_POST['google_maps_embed']
                    )

                );

        if (

            $location['success']

        ) {

            $_POST['google_place_name'] =

                $location['place_name'];

            $_POST['google_maps_lat'] =

                (string)
                $location['lat'];

            $_POST['google_maps_lng'] =

                (string)
                $location['lng'];

        }

    } */

    foreach ($_POST as $key => $value) {

        $update =
        $db->prepare("
            UPDATE settings
            SET setting_value = ?
            WHERE setting_key = ?
        ");

        $update->execute([
            $value,
            $key
        ]);
    }

    header("Location: settings");
    exit;
}

$pageCss = 'settings.css';

include
__DIR__ .
'/../includes/admin-header.php';

?>

<div class="jp-settings-page">

    <div class="jp-settings-header">

        <div>

            <h1>

                System Settings

            </h1>

            <p>

                Configure your website, business information and customer contact details.

            </p>

        </div>

    </div>

    <form
        method="POST"
        class="jp-settings-form">

        <!-- =========================
             GENERAL
        ========================== -->

        <section class="jp-settings-card">

            <div class="jp-settings-card-header">

                <h2>

                    General

                </h2>

                <p>

                    Basic system configuration.

                </p>

            </div>

            <div class="jp-settings-grid">

                <div class="jp-settings-field">

                    <label>

                        Site Name

                    </label>

                    <input
                        type="text"
                        name="site_name"
                        value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">

                </div>

                <div class="jp-settings-field">

                    <label>

                        Contact Email

                    </label>

                    <input
                        type="email"
                        name="contact_email"
                        value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">

                </div>

                <div class="jp-settings-field">

                    <label>

                        Currency Mode

                    </label>

                    <select name="currency_mode">

                        <option
                            value="KES"
                            <?= ($settings['currency_mode'] ?? '') === 'KES' ? 'selected' : '' ?>>

                            KES Only

                        </option>

                        <option
                            value="USD"
                            <?= ($settings['currency_mode'] ?? '') === 'USD' ? 'selected' : '' ?>>

                            USD Only

                        </option>

                        <option
                            value="BOTH"
                            <?= ($settings['currency_mode'] ?? '') === 'BOTH' ? 'selected' : '' ?>>

                            Both (KES + USD)

                        </option>

                    </select>

                </div>

                <div class="jp-settings-field">

                    <label>

                        Maintenance Mode

                    </label>

                    <select
                        name="maintenance_mode">

                        <option
                            value="0"
                            <?= ($settings['maintenance_mode'] ?? '') === '0' ? 'selected' : '' ?>>

                            OFF

                        </option>

                        <option
                            value="1"
                            <?= ($settings['maintenance_mode'] ?? '') === '1' ? 'selected' : '' ?>>

                            ON

                        </option>

                    </select>

                </div>

            </div>

        </section>

        <!-- =========================
             ABOUT
        ========================== -->

        <section class="jp-settings-card">

            <div class="jp-settings-card-header">

                <h2>

                    About

                </h2>

                <p>

                    Information displayed on the About page.

                </p>

            </div>

            <div class="jp-settings-grid">

                <div class="jp-settings-field jp-settings-full">

                    <label>

                        About Title

                    </label>

                    <input
                        type="text"
                        name="about_title"
                        value="<?= htmlspecialchars($settings['about_title'] ?? '') ?>">

                </div>

                <div class="jp-settings-field jp-settings-full">

                    <label>

                        About Description

                    </label>

                    <textarea
                        name="about_description"
                        rows="5"><?= htmlspecialchars($settings['about_description'] ?? '') ?></textarea>

                </div>

                <div class="jp-settings-field">

                    <label>

                        Mission

                    </label>

                    <textarea
                        name="mission"
                        rows="4"><?= htmlspecialchars($settings['mission'] ?? '') ?></textarea>

                </div>

                <div class="jp-settings-field">

                    <label>

                        Vision

                    </label>

                    <textarea
                        name="vision"
                        rows="4"><?= htmlspecialchars($settings['vision'] ?? '') ?></textarea>

                </div>

            </div>

        </section>
                <!-- =========================
             CONTACT
        ========================== -->

        <section class="jp-settings-card">

            <div class="jp-settings-card-header">

                <h2>

                    Contact

                </h2>

                <p>

                    Customer contact information displayed throughout the website.

                </p>

            </div>

            <div class="jp-settings-grid">

                <div class="jp-settings-field">

                    <label>

                        Contact Phone

                    </label>

                    <input
                        type="text"
                        name="contact_phone"
                        value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">

                </div>

                <div class="jp-settings-field">

                    <label>

                        Business Hours

                    </label>

                    <input
                        type="text"
                        name="business_hours"
                        value="<?= htmlspecialchars($settings['business_hours'] ?? '') ?>">

                </div>

                <div class="jp-settings-field jp-settings-full">

                    <label>

                        Contact Address

                    </label>

                    <textarea
                        name="contact_address"
                        rows="3"><?= htmlspecialchars($settings['contact_address'] ?? '') ?></textarea>

                </div>

                <div class="jp-settings-field jp-settings-full">

                    <label>

                        Google Maps Embed Code / URL

                    </label>

                    <textarea
                        name="google_maps_embed"
                        rows="4"><?= htmlspecialchars($settings['google_maps_embed'] ?? '') ?></textarea>

                </div>

            </div>

        </section>

        <!-- =========================
             SOCIAL MEDIA
        ========================== -->

        <section class="jp-settings-card">

            <div class="jp-settings-card-header">

                <h2>

                    Social Media

                </h2>

                <p>

                    Links to your official social media pages.

                </p>

            </div>

            <div class="jp-settings-grid">

                <div class="jp-settings-field">

                    <label>

                        Facebook URL

                    </label>

                    <input
                        type="url"
                        name="facebook_url"
                        value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>">

                </div>

                <div class="jp-settings-field">

                    <label>

                        Instagram URL

                    </label>

                    <input
                        type="url"
                        name="instagram_url"
                        value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>">

                </div>

                <div class="jp-settings-field">

                    <label>

                        X (Twitter) URL

                    </label>

                    <input
                        type="url"
                        name="x_url"
                        value="<?= htmlspecialchars($settings['x_url'] ?? '') ?>">

                </div>

                <div class="jp-settings-field">

                    <label>

                        WhatsApp Number

                    </label>

                    <input
                        type="text"
                        name="whatsapp_number"
                        value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '') ?>">

                </div>

                <div class="jp-settings-field jp-settings-full">

                    <label>

                        YouTube URL

                    </label>

                    <input
                        type="url"
                        name="youtube_url"
                        value="<?= htmlspecialchars($settings['youtube_url'] ?? '') ?>">

                </div>

            </div>

        </section>

        <div class="jp-settings-actions">

            <button
                type="submit"
                class="jp-settings-save">

                Save Settings

            </button>

        </div>

    </form>

</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>

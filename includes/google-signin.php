<?php
/* "Continue with Google" button. Renders nothing until GOOGLE_CLIENT_ID is set in .env. */
$jpGoogleClientId = (string) Env::get('GOOGLE_CLIENT_ID', '');
if ($jpGoogleClientId === '') {
    return;
}
?>
<div class="jp-google" data-csrf="<?= htmlspecialchars(Security::generateCsrfToken()) ?>">
    <div class="jp-google-or"><span>or</span></div>
    <div id="g_id_onload"
         data-client_id="<?= htmlspecialchars($jpGoogleClientId) ?>"
         data-callback="jpGoogleCallback"
         data-auto_prompt="false"
         data-ux_mode="popup"></div>
    <div class="g_id_signin"
         data-type="standard" data-size="large" data-theme="outline"
         data-text="continue_with" data-shape="rectangular" data-logo_alignment="left"
         data-width="300"></div>
    <p class="jp-google-msg" id="jpGoogleMsg" role="alert" hidden></p>
</div>
<script src="/assets/js/google-signin.js" defer></script>
<script src="https://accounts.google.com/gsi/client" async defer></script>

/* Receives the Google ID token and signs the customer in through /google-auth. */
window.jpGoogleCallback = async function (response) {
    var msg = document.getElementById('jpGoogleMsg');
    function show(text) {
        if (msg) { msg.textContent = text; msg.hidden = false; } else { window.alert(text); }
    }
    var holder = document.querySelector('[data-csrf]');
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = (meta && meta.content) || (holder && holder.dataset.csrf) || '';
    if (msg) { msg.hidden = true; }

    try {
        var body = new URLSearchParams({ credential: response.credential, csrf_token: token });
        var res = await fetch('/google-auth', {
            method: 'POST',
            headers: { 'X-CSRF-Token': token, 'Accept': 'application/json' },
            body: body,
            credentials: 'same-origin'
        });
        var data = await res.json();
        if (data.success) {
            window.location.href = data.redirect || '/products';
        } else {
            show(data.message || 'Google sign-in failed. Please try again.');
        }
    } catch (e) {
        show('Network problem. Please try again.');
    }
};

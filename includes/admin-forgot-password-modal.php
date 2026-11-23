<div
    id="forgotPasswordModal"
    class="jp-modal">

    <div
        class="jp-modal-card forgot-password-card">

        <button
            type="button"
            class="jp-modal-close"
            id="closeForgotPasswordModal">

            &times;

        </button>

        <div class="forgot-password-header">

            <div class="forgot-icon">

                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>

            </div>

            <h2>

                Forgot Password?

            </h2>

            <p>

                Enter your email address.

                If an account exists, we'll send you a secure password reset link.

            </p>

        </div>

        <form
            id="forgotPasswordForm"
            action="/admin/forgot-password-ajax">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= Security::escape(Security::generateCsrfToken()) ?>">

            <div class="input-group">

                <label>

                    Account Email

                </label>

                <input

                    type="email"

                    name="email"

                    id="forgotEmail"

                    placeholder="admin@example.com"

                    required

                    autocomplete="email">

            </div>

            <div class="forgot-actions">

                <button

                    type="button"

                    class="btn-secondary"

                    id="cancelForgotPassword">

                    Cancel

                </button>

                <button

                    type="submit"

                    class="btn-primary"

                    id="sendResetButton">

                    Send Reset Link

                </button>

            </div>

        </form>

    </div>

</div>

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

                🔒

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

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const btnLogin = document.getElementById('btnLogin');
    const loginText = document.getElementById('loginText');
    const loginSpinner = document.getElementById('loginSpinner');
    const alertContainer = document.getElementById('alertContainer');

    const urlParams = new URLSearchParams(window.location.search);
    const redirectUrl = urlParams.get('redirect') || 'dashboard.html';

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        setLoading(true);

        const result = await login(username, password);

        setLoading(false);

        if (result.success) {
            showAlert('success', '<i class="bi bi-check-circle-fill me-2"></i>Inicio de sesion exitoso! Redirigiendo...');
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 1000);
        } else if (result.locked) {
            const message = result.remainingMinutes
                ? `${result.error}. Intente nuevamente en ${result.remainingMinutes} minuto(s).`
                : result.error;
            showAlert('warning', `<i class="bi bi-lock-fill me-2"></i>${message}`);
        } else if (result.attemptsRemaining !== undefined) {
            const attemptsMsg = result.attemptsRemaining > 0
                ? `<br><small style="opacity: 0.8;">Intentos restantes: ${result.attemptsRemaining}</small>`
                : '<br><small style="color: #ef4444;">Ultimo intento antes del bloqueo</small>';
            showAlert('danger', `<i class="bi bi-exclamation-triangle-fill me-2"></i>${result.error}${attemptsMsg}`);
        } else {
            showAlert('danger', `<i class="bi bi-exclamation-triangle-fill me-2"></i>${result.error}`);
        }
    });

    function setLoading(isLoading) {
        btnLogin.disabled = isLoading;
        if (isLoading) {
            loginText.innerHTML = '<i class="bi bi-hourglass-split"></i> Iniciando...';
            loginSpinner.classList.remove('d-none');
        } else {
            loginText.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Iniciar Sesion';
            loginSpinner.classList.add('d-none');
        }
    }

    function showAlert(type, message) {
        alertContainer.innerHTML = `
            <div class="alert alert-${type}" style="position: relative; animation: slideIn 0.3s ease;">
                ${message}
                <button type="button" class="alert-close" onclick="this.parentElement.remove()" style="
                    position: absolute;
                    top: 50%;
                    right: 12px;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    opacity: 0.7;
                    color: inherit;
                ">&times;</button>
            </div>
        `;

        // Auto-hide after 5 seconds for success
        if (type === 'success') {
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }
    }
});

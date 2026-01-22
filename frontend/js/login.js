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

        console.log('Intentando login con:', username);
        console.log('URL API:', API_URL);

        setLoading(true);

        const result = await login(username, password);

        setLoading(false);

        console.log('Resultado del login:', result);

        if (result.success) {
            showAlert('success', '¡Inicio de sesion exitoso! Redirigiendo...');
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 1000);
        } else if (result.locked) {
            // Cuenta bloqueada
            const message = result.remainingMinutes
                ? `${result.error}. Intente nuevamente en ${result.remainingMinutes} minuto(s).`
                : result.error;
            showAlert('warning', `<i class="bi bi-lock-fill me-2"></i>${message}`);
        } else if (result.attemptsRemaining !== undefined) {
            // Login fallido con intentos restantes
            const attemptsMsg = result.attemptsRemaining > 0
                ? `<br><small>Intentos restantes: ${result.attemptsRemaining}</small>`
                : '<br><small class="text-danger">Ultimo intento antes del bloqueo</small>';
            showAlert('danger', `${result.error}${attemptsMsg}`);
        } else {
            showAlert('danger', result.error);
        }
    });

    function setLoading(isLoading) {
        btnLogin.disabled = isLoading;
        if (isLoading) {
            loginText.textContent = 'Iniciando...';
            loginSpinner.classList.remove('d-none');
        } else {
            loginText.textContent = 'Iniciar Sesion';
            loginSpinner.classList.add('d-none');
        }
    }

    function showAlert(type, message) {
        alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
});

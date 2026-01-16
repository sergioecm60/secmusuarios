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
            showAlert('success', '¡Inicio de sesión exitoso!');
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 1000);
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
            loginText.textContent = 'Iniciar Sesión';
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
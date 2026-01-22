const API_URL = '/api';
let refreshTimer = null;

async function login(username, password) {
    try {
        console.log('Enviando peticion a:', `${API_URL}/login`);
        const response = await fetch(`${API_URL}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });

        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);

        // Cuenta bloqueada (HTTP 423)
        if (response.status === 423) {
            return {
                success: false,
                error: data.error,
                locked: true,
                lockedUntil: data.locked_until,
                remainingMinutes: data.remaining_minutes
            };
        }

        if (data.success) {
            // Guardar tokens y datos de usuario
            localStorage.setItem('token', data.token || data.access_token);
            if (data.refresh_token) {
                localStorage.setItem('refreshToken', data.refresh_token);
            }
            if (data.expires_in) {
                localStorage.setItem('tokenExpiresIn', data.expires_in);
                scheduleTokenRefresh(data.expires_in);
            }
            localStorage.setItem('user', JSON.stringify(data.user));
            return { success: true };
        } else {
            return {
                success: false,
                error: data.error || 'Error al iniciar sesion',
                attemptsRemaining: data.attempts_remaining
            };
        }
    } catch (error) {
        console.error('Error:', error);
        return { success: false, error: 'Error de conexion' };
    }
}

function scheduleTokenRefresh(expiresIn) {
    // Refrescar 1 minuto antes de expirar
    const refreshTime = (expiresIn - 60) * 1000;

    if (refreshTimer) {
        clearTimeout(refreshTimer);
    }

    if (refreshTime > 0) {
        refreshTimer = setTimeout(async () => {
            const success = await refreshAccessToken();
            if (!success) {
                // Si no se puede refrescar, forzar logout
                console.warn('No se pudo refrescar el token, cerrando sesion');
                logout();
            }
        }, refreshTime);
    }
}

async function refreshAccessToken() {
    const refreshToken = localStorage.getItem('refreshToken');
    if (!refreshToken) {
        return false;
    }

    try {
        const response = await fetch(`${API_URL}/refresh`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ refresh_token: refreshToken })
        });

        const data = await response.json();

        if (data.success) {
            localStorage.setItem('token', data.access_token);
            if (data.refresh_token) {
                localStorage.setItem('refreshToken', data.refresh_token);
            }
            if (data.expires_in) {
                localStorage.setItem('tokenExpiresIn', data.expires_in);
                scheduleTokenRefresh(data.expires_in);
            }
            console.log('Token refrescado exitosamente');
            return true;
        }
        return false;
    } catch (error) {
        console.error('Error refreshing token:', error);
        return false;
    }
}

async function logout() {
    const refreshToken = localStorage.getItem('refreshToken');
    const token = getToken();

    if (refreshTimer) {
        clearTimeout(refreshTimer);
        refreshTimer = null;
    }

    // Notificar al servidor si hay tokens
    if (token && refreshToken) {
        try {
            await fetch(`${API_URL}/logout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ refresh_token: refreshToken })
            });
        } catch (error) {
            console.error('Error during logout:', error);
        }
    }

    // Limpiar storage local
    localStorage.removeItem('token');
    localStorage.removeItem('refreshToken');
    localStorage.removeItem('tokenExpiresIn');
    localStorage.removeItem('user');
    window.location.href = 'login.html';
}

function getToken() {
    return localStorage.getItem('token');
}

function getUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
}

async function apiCall(endpoint, options = {}) {
    let token = getToken();

    const headers = {
        'Content-Type': 'application/json'
    };

    if (options && options.headers) {
        Object.assign(headers, options.headers);
    }

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    try {
        let response = await fetch(`${API_URL}${endpoint}`, {
            ...options,
            headers
        });

        // Si el token expiro, intentar refrescar
        if (response.status === 401) {
            const refreshed = await refreshAccessToken();
            if (refreshed) {
                token = getToken();
                headers['Authorization'] = `Bearer ${token}`;
                response = await fetch(`${API_URL}${endpoint}`, {
                    ...options,
                    headers
                });
            } else {
                logout();
                throw new Error('Sesion expirada');
            }
        }

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Error en la peticion');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

function checkAuth() {
    const token = getToken();
    if (!token) {
        window.location.href = 'login.html';
        return false;
    }
    return true;
}

// Inicializar auto-refresh al cargar la pagina si hay token
function initAuth() {
    const expiresIn = localStorage.getItem('tokenExpiresIn');
    if (expiresIn && getToken()) {
        scheduleTokenRefresh(parseInt(expiresIn));
    }
}

// Llamar al cargar (si no es la pagina de login)
if (!window.location.pathname.includes('login.html')) {
    document.addEventListener('DOMContentLoaded', initAuth);
}

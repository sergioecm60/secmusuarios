document.addEventListener('DOMContentLoaded', async () => {
    if (!checkAuth()) return;

    const user = getUser();
    updateUserInfo(user);

    document.getElementById('btnLogout').addEventListener('click', logout);
    document.getElementById('btnRefreshUsers').addEventListener('click', loadUsers);

    await loadUserData();
    await loadUsers();
});

async function loadUserData() {
    try {
        const data = await apiCall('/me');
        
        if (data.success) {
            updateUserInfo(data.user);
            document.getElementById('userCard').innerHTML = `
                <p class="mb-1"><strong>Nombre:</strong> ${data.user.nombre_completo}</p>
                <p class="mb-1"><strong>Usuario:</strong> ${data.user.username}</p>
                <p class="mb-1"><strong>Email:</strong> ${data.user.email}</p>
                <p class="mb-0"><strong>Rol:</strong> 
                    <span class="badge ${getRolBadgeClass(data.user.rol)}">${data.user.rol}</span>
                </p>
            `;
        }
    } catch (error) {
        console.error('Error loading user data:', error);
    }
}

async function loadUsers() {
    const container = document.getElementById('usersTableContainer');
    
    try {
        const data = await apiCall('/users/all');
        
        if (data.success) {
            updateStats(data.totals);
            renderUsersTable(data.data);
        }
    } catch (error) {
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> Error al cargar usuarios: ${error.message}
            </div>
        `;
    }
}

function updateStats(totals) {
    document.getElementById('statTotalUsers').textContent = totals.total || 0;
    
    const activeCount = (totals.secmalquileres || 0) + (totals.secmti || 0) + (totals.secmautos || 0) + (totals.secmrrhh || 0) + (totals.Psitios || 0) + (totals.secmagencias || 0) + (totals.secusuarios || 0);
    document.getElementById('statActiveUsers').textContent = activeCount;
    
    const adminCount = (totals.secmalquileres || 0) + (totals.secmti || 0) + (totals.secmautos || 0) + (totals.secmrrhh || 0) + (totals.Psitios || 0) + (totals.secmagencias || 0) + (totals.secusuarios || 0);
    document.getElementById('statAdmins').textContent = adminCount;

    if (document.getElementById('statSecmusuarios')) {
        document.getElementById('statSecmusuarios').textContent = totals.secusuarios || 0;
    }
    if (document.getElementById('statSecmalquileres')) {
        document.getElementById('statSecmalquileres').textContent = totals.secmalquileres || 0;
    }
    if (document.getElementById('statSecmti')) {
        document.getElementById('statSecmti').textContent = totals.secmti || 0;
    }
    if (document.getElementById('statSecmautos')) {
        document.getElementById('statSecmautos').textContent = totals.secmautos || 0;
    }
    if (document.getElementById('statSecmrrhh')) {
        document.getElementById('statSecmrrhh').textContent = totals.secmrrhh || 0;
    }
    if (document.getElementById('statPsitios')) {
        document.getElementById('statPsitios').textContent = totals.Psitios || 0;
    }
    if (document.getElementById('statSecmagencias')) {
        document.getElementById('statSecmagencias').textContent = totals.secmagencias || 0;
    }
}

function renderUsersTable(data) {
    const container = document.getElementById('usersTableContainer');

    const systems = ['secmusuarios', 'secmalquileres', 'secmti', 'secmautos', 'secmrrhh', 'Psitios', 'secmagencias'];
    const systemNames = {
        'secmusuarios': 'SECM Usuarios',
        'secmalquileres': 'Alquileres',
        'secmti': 'TI Portal',
        'secmautos': 'Autos',
        'secmrrhh': 'Recursos Humanos',
        'Psitios': 'Sitios Seguros',
        'secmagencias': 'Agencias'
    };

    let html = '<div class="accordion" id="usersAccordion">';
    
    systems.forEach((system, index) => {
        const users = data[system] || [];
        const active = 'collapse' + index;
        
        html += `
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading${index}">
                    <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#${active}" aria-expanded="${index === 0 ? 'true' : 'false'}" aria-controls="${active}">
                        <strong>${systemNames[system] || system}</strong>
                        <span class="badge bg-primary ms-2">${users.length} usuarios</span>
                    </button>
                </h2>
                <div id="${active}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#usersAccordion" aria-labelledby="heading${index}">
                    <div class="accordion-body">
                        ${users.length === 0 ? '<p class="text-muted">No hay usuarios en este sistema</p>' : `
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Usuario</th>
                                            <th>Nombre Completo</th>
                                            <th>Email</th>
                                            <th>Rol</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${users.map(user => `
                                            <tr>
                                                <td>${user.id}</td>
                                                <td><strong>${user.username}</strong></td>
                                                <td>${user.nombre_completo || '-'}</td>
                                                <td>${user.email || '-'}</td>
                                                <td><span class="badge ${getRolBadgeClass(user.rol)}">${user.rol}</span></td>
                                                <td>
                                                    <span class="badge ${user.activo ? 'bg-success' : 'bg-danger'}">
                                                        ${user.activo ? 'Activo' : 'Inactivo'}
                                                    </span>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function updateUserInfo(user) {
    document.getElementById('userInfo').textContent = user ? `${user.nombre_completo} (${user.username})` : '';
}

function getRolBadgeClass(rol) {
    const classes = {
        'superadmin': 'bg-danger',
        'admin': 'bg-warning text-dark',
        'user': 'bg-primary'
    };
    return classes[rol] || 'bg-secondary';
}
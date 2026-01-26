document.addEventListener('DOMContentLoaded', async () => {
    if (!checkAuth()) return;

    const user = getUser();
    updateUserInfo(user);

    document.getElementById('btnLogout').addEventListener('click', logout);
    document.getElementById('btnRefreshUsers').addEventListener('click', loadMasterUsers);
    document.getElementById('btnCreateUser').addEventListener('click', () => openCreateUserModal('secmusuarios'));

    await loadUserData();
    await loadMasterUsers();
});

async function loadUserData() {
    try {
        const data = await apiCall('/me');

        if (data.success) {
            updateUserInfo(data.user);
            const initials = getInitials(data.user.nombre_completo || data.user.username);
            document.getElementById('userCard').innerHTML = `
                <div class="user-avatar">${initials}</div>
                <div class="user-details">
                    <p class="mb-1"><strong style="font-size: 18px;">${data.user.nombre_completo || data.user.username}</strong></p>
                    <p class="mb-1"><i class="bi bi-person me-1"></i> ${data.user.username}</p>
                    <p class="mb-1"><i class="bi bi-envelope me-1"></i> ${data.user.email || '-'}</p>
                    <p class="mb-0">
                        <span class="badge ${getRolBadgeClass(data.user.rol)}">${data.user.rol}</span>
                    </p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading user data:', error);
    }
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.split(' ').filter(p => p.length > 0);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

async function loadMasterUsers() {
    const container = document.getElementById('usersTableContainer');

    try {
        const allSystemsData = await apiCall('/users/all');

        if (allSystemsData.success) {
            updateStats(allSystemsData.totals);
            renderUsersTable(allSystemsData.data);
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

    const activeCount = (totals.secusuarios || 0) + (totals.secmalquileres || 0) + (totals.secmti || 0) + (totals.secmautos || 0) + (totals.secmrrhh || 0) + (totals.Psitios || 0) + (totals.secmagencias || 0);
    document.getElementById('statActiveUsers').textContent = activeCount;

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
    const systemIcons = {
        'secmusuarios': 'bi-shield-lock-fill',
        'secmalquileres': 'bi-building',
        'secmti': 'bi-hdd-network-fill',
        'secmautos': 'bi-car-front-fill',
        'secmrrhh': 'bi-person-badge-fill',
        'Psitios': 'bi-key-fill',
        'secmagencias': 'bi-ticket-perforated-fill'
    };

    let html = '<div class="accordion">';

    systems.forEach((system, index) => {
        const users = data[system] || [];
        const isOpen = index === 0;

        html += `
            <div class="accordion-item">
                <div class="accordion-header">
                    <button class="accordion-button ${isOpen ? '' : 'collapsed'}" onclick="toggleAccordion(this)">
                        <i class="bi ${systemIcons[system]} me-2"></i>
                        <strong>${systemNames[system] || system}</strong>
                        <span class="badge badge-primary ms-2">${users.length}</span>
                    </button>
                </div>
                <div class="accordion-body" style="display: ${isOpen ? 'block' : 'none'};">
                    <div style="margin-bottom: 16px; text-align: right;">
                        <button class="btn btn-success btn-sm" onclick="openCreateUserModal('${system}')">
                            <i class="bi bi-plus-lg"></i> Nuevo en ${systemNames[system]}
                        </button>
                    </div>
                    ${users.length === 0 ? '<p class="text-muted text-center" style="padding: 40px;">No hay usuarios en este sistema</p>' : `
                        <div class="table-container">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th style="text-align: center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${users.map(user => `
                                        <tr>
                                            <td style="color: var(--text-muted);">#${user.id}</td>
                                            <td><strong>${user.username}</strong></td>
                                            <td>${user.nombre_completo || '-'}</td>
                                            <td>${user.email || '-'}</td>
                                            <td><span class="badge ${getRolBadgeClass(user.rol)}">${user.rol}</span></td>
                                            <td>
                                                <span class="badge ${user.activo ? 'badge-success' : 'badge-danger'}">
                                                    ${user.activo ? 'Activo' : 'Inactivo'}
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <button class="btn btn-primary btn-sm me-1" onclick="openEditUserModal('${system}', ${user.id}, ${JSON.stringify(user).replace(/"/g, '&quot;')})" title="Editar">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteUser('${system}', ${user.id}, '${user.username}')" title="Eliminar">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `}
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

function toggleAccordion(button) {
    const accordionItem = button.closest('.accordion-item');
    const body = accordionItem.querySelector('.accordion-body');
    const isOpen = body.style.display !== 'none';

    // Close all
    document.querySelectorAll('.accordion-body').forEach(b => b.style.display = 'none');
    document.querySelectorAll('.accordion-button').forEach(b => b.classList.add('collapsed'));

    // Toggle current
    if (!isOpen) {
        body.style.display = 'block';
        button.classList.remove('collapsed');
    }
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

async function openCreateUserModal(sistema) {
    // Si se pasa un evento en lugar de un string, usar 'secmusuarios' por defecto
    if (!sistema || typeof sistema !== 'string') {
        sistema = 'secmusuarios';
    }

    const user = getUser();
    if (!user || !user.rol || (user.rol !== 'superadmin' && user.rol !== 'admin')) {
        alert('No tienes permisos para crear usuarios');
        return;
    }

    const systemNames = {
        'secmusuarios': 'SECM Usuarios',
        'secmalquileres': 'Alquileres',
        'secmti': 'TI Portal',
        'secmautos': 'Autos',
        'secmrrhh': 'Recursos Humanos',
        'Psitios': 'Sitios Seguros',
        'secmagencias': 'Agencias'
    };

    const isSuperAdminOrAdmin = user.rol === 'superadmin' || user.rol === 'admin';
    const showSystemSelection = isSuperAdminOrAdmin && sistema === 'secmusuarios';

    const systemsCheckboxes = showSystemSelection ? `
        <div class="mb-3">
            <label class="form-label fw-bold">Crear usuario en los siguientes sistemas:</label>
            <div class="row">
                <div class="col-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input system-checkbox" id="chkSecmusuarios" value="secmusuarios" checked disabled>
                        <label class="form-check-label" for="chkSecmusuarios">SECM Usuarios (Master)</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input system-checkbox" id="chkSecmalquileres" value="secmalquileres">
                        <label class="form-check-label" for="chkSecmalquileres">Alquileres</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input system-checkbox" id="chkSecmti" value="secmti">
                        <label class="form-check-label" for="chkSecmti">TI Portal</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input system-checkbox" id="chkSecmautos" value="secmautos">
                        <label class="form-check-label" for="chkSecmautos">Autos</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input system-checkbox" id="chkSecmrrhh" value="secmrrhh">
                        <label class="form-check-label" for="chkSecmrrhh">Recursos Humanos</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input system-checkbox" id="chkPsitios" value="Psitios">
                        <label class="form-check-label" for="chkPsitios">Sitios Seguros</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input system-checkbox" id="chkSecmagencias" value="secmagencias">
                        <label class="form-check-label" for="chkSecmagencias">Agencias</label>
                    </div>
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAllSystems()">Seleccionar Todos</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllSystems()">Deseleccionar Todos</button>
            </div>
        </div>
    ` : '';

    const modalHtml = `
        <div class="modal show" id="userModal" style="display: flex;">
            <div class="modal-dialog ${showSystemSelection ? 'modal-lg' : ''}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Usuario ${showSystemSelection ? '' : 'en ' + (systemNames[sistema] || sistema)}</h5>
                        <button type="button" class="btn-close" onclick="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form id="userForm">
                            <input type="hidden" id="modalSistema" value="${sistema}">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group">
                                    <label>Usuario *</label>
                                    <input type="text" id="modalUsername" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" id="modalEmail">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Contrasena *</label>
                                <input type="password" id="modalPassword" required>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group" id="nombreContainer">
                                    <label>Nombre</label>
                                    <input type="text" id="modalNombre">
                                </div>
                                <div class="form-group" id="apellidoContainer">
                                    <label>Apellido</label>
                                    <input type="text" id="modalApellido">
                                </div>
                            </div>
                            <div class="form-group" id="nombreCompletoContainer" style="display:none;">
                                <label>Nombre Completo</label>
                                <input type="text" id="modalNombreCompleto">
                            </div>
                            <div class="form-group">
                                <label>Rol</label>
                                <select id="modalRol">
                                    <option value="user">Usuario</option>
                                    <option value="admin">Admin</option>
                                    <option value="superadmin">Superadmin</option>
                                </select>
                            </div>
                            ${systemsCheckboxes}
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="saveUser('${sistema}', false)">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.body.style.overflow = 'hidden';

    // Cerrar al hacer clic fuera del modal
    document.getElementById('userModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    adjustFormFieldsForSystem(sistema);
}

function closeModal() {
    const modal = document.getElementById('userModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

async function openEditUserModal(sistema, userId, userData) {
    const user = getUser();
    if (!user || !user.rol || (user.rol !== 'superadmin' && user.rol !== 'admin')) {
        alert('No tienes permisos para editar usuarios');
        return;
    }

    const systemNames = {
        'secmusuarios': 'SECM Usuarios',
        'secmalquileres': 'Alquileres',
        'secmti': 'TI Portal',
        'secmautos': 'Autos',
        'secmrrhh': 'Recursos Humanos',
        'Psitios': 'Sitios Seguros',
        'secmagencias': 'Agencias'
    };

    const modalHtml = `
        <div class="modal show" id="userModal" style="display: flex;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Usuario #${userId} - ${systemNames[sistema] || sistema}</h5>
                        <button type="button" class="btn-close" onclick="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form id="userForm">
                            <input type="hidden" id="modalUserId" value="${userId}">
                            <input type="hidden" id="modalSistema" value="${sistema}">
                            <div class="form-group">
                                <label>Usuario</label>
                                <input type="text" id="modalUsername" value="${userData.username}">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" id="modalEmail" value="${userData.email || ''}">
                            </div>
                            <div class="form-group">
                                <label>Nueva Contrasena (dejar vacio para no cambiar)</label>
                                <input type="password" id="modalPassword">
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group" id="nombreContainer">
                                    <label>Nombre</label>
                                    <input type="text" id="modalNombre" value="${userData.nombre || ''}">
                                </div>
                                <div class="form-group" id="apellidoContainer">
                                    <label>Apellido</label>
                                    <input type="text" id="modalApellido" value="${userData.apellido || ''}">
                                </div>
                            </div>
                            <div class="form-group" id="nombreCompletoContainer" style="display:none;">
                                <label>Nombre Completo</label>
                                <input type="text" id="modalNombreCompleto" value="${userData.nombre_completo || ''}">
                            </div>
                            <div class="form-group" id="fullNameContainer" style="display:none;">
                                <label>Full Name</label>
                                <input type="text" id="modalFullName" value="${userData.full_name || ''}">
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group">
                                    <label>Rol</label>
                                    <select id="modalRol">
                                        <option value="user" ${userData.rol === 'user' ? 'selected' : ''}>Usuario</option>
                                        <option value="admin" ${userData.rol === 'admin' ? 'selected' : ''}>Admin</option>
                                        <option value="superadmin" ${userData.rol === 'superadmin' ? 'selected' : ''}>Superadmin</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select id="modalActivo">
                                        <option value="1" ${userData.activo ? 'selected' : ''}>Activo</option>
                                        <option value="0" ${!userData.activo ? 'selected' : ''}>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="saveUser('${sistema}', true)">Guardar Cambios</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.body.style.overflow = 'hidden';

    // Cerrar al hacer clic fuera del modal
    document.getElementById('userModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    adjustFormFieldsForSystem(sistema);
}

async function saveUser(sistema, isEdit) {
    const userId = document.getElementById('modalUserId')?.value;
    const username = document.getElementById('modalUsername').value;
    const email = document.getElementById('modalEmail').value;
    const password = document.getElementById('modalPassword').value;
    const nombre = document.getElementById('modalNombre').value || '';
    const apellido = document.getElementById('modalApellido').value || '';
    const nombreCompleto = document.getElementById('modalNombreCompleto')?.value || '';
    const rol = document.getElementById('modalRol').value;
    const activo = document.getElementById('modalActivo')?.value || '1';

    // Recoger sistemas seleccionados (checkboxes)
    const systemCheckboxes = document.querySelectorAll('.system-checkbox:checked');
    const selectedSystems = Array.from(systemCheckboxes).map(cb => cb.value);

    if (!username) {
        alert('El nombre de usuario es obligatorio');
        return;
    }

    if (!isEdit && !password) {
        alert('La contrasena es obligatoria para usuarios nuevos');
        return;
    }

    const data = {
        sistema: sistema,
        username: username,
        email: email || '',
        rol: rol
    };

    if (password) {
        data.password = password;
    }

    // Si hay sistemas seleccionados (modo multi-sistema), enviar el array
    if (selectedSystems.length > 0 && !isEdit) {
        data.sistemas = selectedSystems;
    }

    if (nombre) data.nombre = nombre;
    if (apellido) data.apellido = apellido;
    if (nombreCompleto) data.nombre_completo = nombreCompleto;
    if (isEdit) {
        data.id = userId;
        data.activo = parseInt(activo);
    }

    try {
        const token = getToken();
        const url = 'http://secmusuarios.test:8081/api/users';
        const method = isEdit ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            // Si hay resultados por sistema, mostrar detalle
            if (result.results) {
                const systemNames = {
                    'secmusuarios': 'SECM Usuarios',
                    'secmalquileres': 'Alquileres',
                    'secmti': 'TI Portal',
                    'secmautos': 'Autos',
                    'secmrrhh': 'Recursos Humanos',
                    'Psitios': 'Sitios Seguros',
                    'secmagencias': 'Agencias'
                };

                let detailMsg = result.message + '\n\nDetalle:\n';
                for (const [sys, res] of Object.entries(result.results)) {
                    const status = res.success ? 'OK' : 'Error: ' + (res.error || 'desconocido');
                    detailMsg += `- ${systemNames[sys] || sys}: ${status}\n`;
                }
                alert(detailMsg);
            } else {
                alert(isEdit ? 'Usuario actualizado exitosamente' : 'Usuario creado exitosamente');
            }

            closeModal();
            await loadMasterUsers();
        } else {
            alert('Error: ' + (result.error || 'Error desconocido'));
        }
    } catch (error) {
        alert('Error al guardar usuario: ' + error.message);
    }
}

async function deleteUser(sistema, userId, username) {
    const user = getUser();
    if (!user || !user.rol || user.rol !== 'superadmin') {
        alert('Solo los superadministradores pueden eliminar usuarios');
        return;
    }

    if (!confirm('¿Estás seguro de que deseas eliminar este usuario?\n\nEsta acción no se puede deshacer.')) {
        return;
    }

    try {
        const token = getToken();
        const response = await fetch('http://secmusuarios.test:8081/api/users', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                sistema: sistema,
                id: userId,
                username: username
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Usuario eliminado exitosamente');
            await loadMasterUsers();
        } else {
            alert('Error: ' + (result.error || 'Error desconocido'));
        }
    } catch (error) {
        alert('Error al eliminar usuario: ' + error.message);
    }
}

async function deleteUserFromAllSystems(userId, username) {
    const user = getUser();
    if (!user || !user.rol || user.rol !== 'superadmin') {
        alert('Solo los superadministradores pueden eliminar usuarios');
        return;
    }

    if (!confirm(`¿Estás seguro de que deseas eliminar el usuario "${username}" de TODOS los sistemas?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }

    try {
        const token = getToken();
        const response = await fetch('http://secmusuarios.test:8081/api/users', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                id: userId,
                systems: ['secmusuarios', 'secmalquileres', 'secmti', 'secmautos', 'secmrrhh', 'Psitios', 'secmagencias']
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Usuario eliminado exitosamente de todos los sistemas');
            await loadMasterUsers();
        } else {
            alert('Error: ' + (result.error || 'Error desconocido'));
        }
    } catch (error) {
        alert('Error al eliminar usuario: ' + error.message);
    }
}

// Funciones auxiliares para seleccion de sistemas
function selectAllSystems() {
    document.querySelectorAll('.system-checkbox:not(:disabled)').forEach(cb => {
        cb.checked = true;
    });
}

function deselectAllSystems() {
    document.querySelectorAll('.system-checkbox:not(:disabled)').forEach(cb => {
        cb.checked = false;
    });
}
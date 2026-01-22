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
                        <button class="btn btn-sm btn-outline-light ms-auto" onclick="event.stopPropagation(); openCreateUserModal('${system}')">
                            <i class="bi bi-plus-lg"></i> Nuevo Usuario
                        </button>
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
                                            <th>Acciones</th>
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
                                                <td>
                                                    <button class="btn btn-sm btn-primary me-1" onclick="openEditUserModal('${system}', ${user.id}, ${JSON.stringify(user).replace(/"/g, '&quot;')})">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser('${system}', ${user.id}, '${user.username}')">
                                                        <i class="bi bi-trash"></i>
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
        <div class="modal fade" id="userModal" tabindex="-1">
            <div class="modal-dialog ${showSystemSelection ? 'modal-lg' : ''}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Usuario ${showSystemSelection ? '' : 'en ' + (systemNames[sistema] || sistema)}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="userForm">
                            <input type="hidden" id="modalSistema" value="${sistema}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Usuario *</label>
                                        <input type="text" class="form-control" id="modalUsername" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" id="modalEmail">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contrasena *</label>
                                <input type="password" class="form-control" id="modalPassword" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3" id="nombreContainer">
                                        <label class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="modalNombre">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3" id="apellidoContainer">
                                        <label class="form-label">Apellido</label>
                                        <input type="text" class="form-control" id="modalApellido">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3" id="nombreCompletoContainer" style="display:none;">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="modalNombreCompleto">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" id="modalRol">
                                    <option value="user">Usuario</option>
                                    <option value="admin">Admin</option>
                                    <option value="superadmin">Superadmin</option>
                                </select>
                            </div>
                            ${systemsCheckboxes}
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="saveUser('${sistema}', false)">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();

    document.getElementById('userModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });

    adjustFormFieldsForSystem(sistema);
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
        <div class="modal fade" id="userModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Usuario #${userId} - ${systemNames[sistema] || sistema}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="userForm">
                            <input type="hidden" id="modalUserId" value="${userId}">
                            <input type="hidden" id="modalSistema" value="${sistema}">
                            <div class="mb-3">
                                <label class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="modalUsername" value="${userData.username}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="modalEmail" value="${userData.email || ''}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nueva Contraseña (dejar vacío para no cambiar)</label>
                                <input type="password" class="form-control" id="modalPassword">
                            </div>
                            <div class="mb-3" id="nombreContainer">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="modalNombre" value="${userData.nombre || ''}">
                            </div>
                            <div class="mb-3" id="apellidoContainer">
                                <label class="form-label">Apellido</label>
                                <input type="text" class="form-control" id="modalApellido" value="${userData.apellido || ''}">
                            </div>
                            <div class="mb-3" id="nombreCompletoContainer" style="display:none;">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="modalNombreCompleto" value="${userData.nombre_completo || ''}">
                            </div>
                            <div class="mb-3" id="fullNameContainer" style="display:none;">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="modalFullName" value="${userData.full_name || ''}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" id="modalRol">
                                    <option value="user" ${userData.rol === 'user' ? 'selected' : ''}>Usuario</option>
                                    <option value="admin" ${userData.rol === 'admin' ? 'selected' : ''}>Admin</option>
                                    <option value="superadmin" ${userData.rol === 'superadmin' ? 'selected' : ''}>Superadmin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" id="modalActivo">
                                    <option value="1" ${userData.activo ? 'selected' : ''}>Activo</option>
                                    <option value="0" ${!userData.activo ? 'selected' : ''}>Inactivo</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="saveUser('${sistema}', true)">Guardar Cambios</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();

    document.getElementById('userModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
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

            const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
            modal.hide();
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
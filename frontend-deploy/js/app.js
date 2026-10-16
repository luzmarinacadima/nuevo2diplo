(function () {
    'use strict';

    var apiBaseUrl = String(window.APP_CONFIG.API_BASE_URL || '').replace(/\/+$/, '');
    var app = document.getElementById('app');
    var toastStack = document.getElementById('toast-stack');
    var state = { user: null, action: 'dashboard' };
    var roles = { admin: 'Administrador', profesor: 'Profesor', estudiante: 'Estudiante' };
    var cardsByRole = {
        admin: [
            ['listar', '👥', 'Grupos', 'Gestion de grupos y horarios'],
            ['materias', '📚', 'Materias', 'Catalogo de asignaturas'],
            ['clases', '🗓️', 'Clases', 'Programacion de clases y toma de asistencia'],
            ['estudiantes', '👨‍🎓', 'Estudiantes', 'Registro y control de alumnos'],
            ['registrar_estudiantes_grupo', '🧾', 'Asignar a un Grupo estudiantes', 'Asignar a un Grupo estudiantes'],
            ['profesores', '👨‍🏫', 'Profesores', 'Administracion de docentes'],
            ['usuarios', '🔐', 'Usuarios', 'Administracion de cuentas']
        ],
        profesor: [['clases', '📝', 'Mis Clases', 'Clases creadas para tus grupos'], ['registrar_clase', '➕', 'Crear Clase', 'Registrar una clase para tus grupos']],
        estudiante: [['marcar_asistencia_estudiante', '✅', 'Mi asistencia', 'Marca asistencia y consulta tu historial']]
    };
    var resources = {
        listar: { title: 'Gestion de Grupos', path: '/groups', singular: 'grupo', columns: [['id', 'ID'], ['nombre', 'Nombre'], ['capacidad_maxima', 'Capacidad'], ['capacidad_actual', 'Inscritos']] },
        materias: { title: 'Gestion de Materias', path: '/subjects', singular: 'materia', columns: [['id', 'ID'], ['nombre', 'Nombre']] },
        estudiantes: { title: 'Gestion de Estudiantes', path: '/students', singular: 'estudiante', columns: [['codigo_estudiante', 'Codigo'], ['ci', 'CI'], ['estado', 'Estado']] },
        profesores: { title: 'Gestion de Profesores', path: '/teachers', singular: 'profesor', columns: [['usuario_id', 'Usuario ID'], ['codigo_profesor', 'Codigo']] },
        usuarios: { title: 'Gestion de Usuarios', path: '/users', singular: 'usuario', columns: [['id', 'ID'], ['nombres', 'Nombres'], ['apellidos', 'Apellidos'], ['nombre_usuario', 'Usuario'], ['tipo_usuario', 'Tipo']] },
        clases: { title: 'Gestion de Clases', path: '/classes', singular: 'clase', columns: [['id', 'ID'], ['nombre', 'Grupo'], ['materia_nombre', 'Materia'], ['fecha', 'Fecha'], ['hora', 'Hora'], ['estado', 'Estado']] }
    };

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character];
        });
    }

    function notify(message, type) {
        if (!message) return;
        var toast = document.createElement('div');
        toast.className = 'toast toast--' + (['success', 'danger', 'warning', 'info'].indexOf(type) >= 0 ? type : 'info');
        toast.innerHTML = '<span class="toast-dot"></span><span class="toast-text"></span><button type="button" class="toast-close" aria-label="Cerrar">x</button>';
        toast.querySelector('.toast-text').textContent = message;
        toast.querySelector('.toast-close').addEventListener('click', function () { toast.remove(); });
        toastStack.appendChild(toast);
        window.setTimeout(function () { if (toast.parentNode) toast.remove(); }, 5200);
    }

    async function request(path, options) {
        var config = Object.assign({ credentials: 'include', headers: { Accept: 'application/json', 'Content-Type': 'application/json' } }, options || {});
        var response;
        try { response = await fetch(apiBaseUrl + path, config); } catch (error) { throw new Error('No se pudo conectar con el backend en ' + apiBaseUrl + '.'); }
        var contentType = response.headers.get('content-type') || '';
        var result = contentType.indexOf('application/json') >= 0 ? await response.json().catch(function () { return {}; }) : await response.blob();
        if (!response.ok) throw new Error(result.message || 'El backend devolvió HTTP ' + response.status + '.');
        return result;
    }

    function role() { return String((state.user || {}).tipo_usuario || '').toLowerCase(); }
    function isAdmin() { return role() === 'admin'; }
    function value(row, key) { return row[key] != null ? row[key] : ''; }
    function setContent(html) { var content = document.getElementById('content'); if (content) content.innerHTML = html; }

    function shell() {
        var user = state.user || {};
        var cards = cardsByRole[role()] || [];
        app.innerHTML = '<div class="user-strip"><div class="user-strip-left"><div class="user-avatar">' +
            escapeHtml((user.nombres || 'U').substring(0, 1).toUpperCase()) + '</div><div><div class="user-name">' +
            escapeHtml((user.nombres || 'Usuario') + ' ' + (user.apellidos || '')) + '</div><div class="user-role">' +
            escapeHtml(roles[role()] || role()) + '</div></div></div><div class="user-strip-actions"><button type="button" id="theme-toggle" class="btn btn-outline btn-sm theme-toggle">Modo oscuro</button><button type="button" id="logout" class="btn btn-danger btn-sm">Cerrar Sesion</button></div></div>' +
            '<div class="dashboard-grid">' + cards.map(function (card) { return '<a href="#' + card[0] + '" class="stat-card"><span class="icon">' + card[1] + '</span><h3>' + escapeHtml(card[2]) + '</h3><p>' + escapeHtml(card[3]) + '</p></a>'; }).join('') + '</div><div class="main-content" id="content"></div>';
        document.getElementById('logout').addEventListener('click', logout);
        document.getElementById('theme-toggle').addEventListener('click', toggleTheme);
    }

    function toggleTheme() {
        var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('asistenciau-theme', next);
    }

    async function logout() {
        try { await request('/auth/logout', { method: 'POST' }); state.user = null; renderLogin(); } catch (error) { notify(error.message, 'danger'); }
    }

    function renderLogin() {
        app.innerHTML = '<div class="card card-sm"><h2>Iniciar Sesion</h2><form id="login-form"><div class="form-group"><label for="username">Usuario:</label><input id="username" name="username" class="form-control" required></div><div class="form-group"><label for="password">Contraseña:</label><input id="password" name="password" type="password" class="form-control" required></div><button class="btn" type="submit">Ingresar</button></form></div>';
        document.getElementById('login-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = new FormData(event.target);
            try {
                var result = await request('/auth/login', { method: 'POST', body: JSON.stringify({ username: form.get('username'), password: form.get('password') }) });
                state.user = result.user;
                if (!state.user) throw new Error('La respuesta de login no contiene el usuario autenticado.');
                render();
            } catch (error) { notify(error.message, 'danger'); }
        });
    }

    function button(label, action, id, kind) {
        return '<button type="button" class="btn btn-' + (kind || 'outline') + ' btn-sm" data-action="' + action + '" data-id="' + escapeHtml(id) + '">' + label + '</button>';
    }

    function renderTable(config, rows) {
        var create = isAdmin() && config.singular !== 'clase' ? '<button type="button" class="btn" data-action="create" data-resource="' + config.singular + '">➕ Nuevo</button>' : '';
        var headers = config.columns.map(function (column) { return '<th>' + column[1] + '</th>'; }).join('');
        var body = rows.map(function (row) {
            var id = value(row, 'id') || value(row, 'usuario_id') || value(row, 'id_clase');
            var cells = config.columns.map(function (column) { return '<td>' + escapeHtml(value(row, column[0])) + '</td>'; }).join('');
            var actions = '';
            if (isAdmin() || (config.singular === 'clase' && role() === 'profesor')) {
                actions += button('Editar', 'edit', id, 'success') + ' ' + button('Eliminar', 'delete', id, 'danger');
            }
            if (config.singular === 'clase') {
                actions += ' ' + button('Asistencia', 'attendance', id, 'outline') + ' ' + button('PDF', 'report-pdf', id, 'outline') + ' ' + button('Excel', 'report-excel', id, 'outline');
            }
            return '<tr>' + cells + '<td class="actions-cell">' + actions + '</td></tr>';
        }).join('');
        return '<div class="card"><div class="panel-header"><h2 class="panel-title">' + config.title + '</h2>' + create + '</div><div class="table-container"><table><thead><tr>' + headers + '<th class="actions-cell">Acciones</th></tr></thead><tbody>' + (body || '<tr><td colspan="' + (config.columns.length + 1) + '">No hay registros.</td></tr>') + '</tbody></table></div></div>';
    }

    function field(name, label, type, required, valueText, extra) {
        var tag = type === 'textarea' ? 'textarea' : 'input';
        var attributes = ' name="' + name + '" class="form-control" ' + (required ? 'required ' : '') + (extra || '');
        return '<div class="form-group"><label>' + label + ':</label><' + tag + attributes + '>' + (tag === 'textarea' ? escapeHtml(valueText || '') + '</textarea>' : '') + (tag === 'input' ? ' value="' + escapeHtml(valueText || '') + '">' : '') + '</div>';
    }

    function selectField(name, label, options, selected, required) {
        return '<div class="form-group"><label>' + label + ':</label><select name="' + name + '" class="form-control" ' + (required ? 'required' : '') + '><option value="">Seleccione...</option>' + options.map(function (item) { return '<option value="' + escapeHtml(item.value) + '"' + (String(item.value) === String(selected) ? ' selected' : '') + '>' + escapeHtml(item.label) + '</option>'; }).join('') + '</select></div>';
    }

    async function loadOptions(path, labelKey, valueKey) {
        var result = await request(path);
        return (result.data || []).map(function (item) { return { value: item[valueKey], label: item[labelKey] || item.nombre_usuario || item.codigo_profesor || item.nombres }; });
    }

    async function renderForm(resource, id, existing) {
        var editing = Boolean(id);
        var data = existing || {};
        var html = '<div class="card card-md"><h2>' + (editing ? 'Editar ' : 'Registrar ') + resource + '</h2><form id="entity-form" data-resource="' + resource + '" data-id="' + escapeHtml(id || '') + '">';
        if (resource === 'materia') html += field('nombre', 'Nombre', 'text', true, data.nombre);
        if (resource === 'usuario') {
            html += field('nombres', 'Nombres', 'text', true, data.nombres) + field('apellidos', 'Apellidos', 'text', true, data.apellidos) + field('genero', 'Género', 'text', false, data.genero) + field('nombre_usuario', 'Nombre de Usuario', 'text', true, data.nombre_usuario) + field('contrasena', editing ? 'Contraseña (obligatoria en el backend actual)' : 'Contraseña', 'password', true, '') + selectField('tipo_usuario', 'Tipo de Usuario', [{ value: 'profesor', label: 'Profesor' }, { value: 'estudiante', label: 'Estudiante' }, { value: 'admin', label: 'Admin' }], data.tipo_usuario, true);
        }
        if (resource === 'estudiante') {
            var users = await loadOptions('/students/available', 'nombre_usuario', 'id');
            html += selectField('usuario_id', 'Usuario Base', users, data.usuario_id, !editing) + field('ci', 'Cédula de Identidad', 'text', true, data.ci) + field('codigo_estudiante', 'Código de Estudiante', 'text', true, data.codigo_estudiante) + selectField('estado', 'Estado', [{ value: 'activo', label: 'Activo' }, { value: 'inactivo', label: 'Inactivo' }], data.estado || 'activo', true);
        }
        if (resource === 'profesor') {
            var professorUsers = await request('/users').then(function (result) { return (result.data || []).filter(function (item) { return item.tipo_usuario === 'profesor'; }).map(function (item) { return { value: item.id, label: item.nombre_usuario + ' (' + item.nombres + ')' }; }); });
            html += selectField('usuario_id', 'Usuario Base', professorUsers, data.usuario_id, !editing) + field('codigo_profesor', 'Código de Profesor', 'text', true, data.codigo_profesor);
        }
        if (resource === 'grupo') {
            var subjects = await loadOptions('/subjects', 'nombre', 'id');
            var teachers = await loadOptions('/teachers', 'codigo_profesor', 'profesor_id');
            html += field('nombre', 'Nombre', 'text', true, data.nombre) + field('capacidad_maxima', 'Capacidad Máxima', 'number', true, data.capacidad_maxima || 30, 'min="1"') + selectField('materia_id', 'Materia', subjects, data.materia_id, true) + selectField('profesor_id', 'Profesor', teachers, data.profesor_id, true);
        }
        if (resource === 'clase') {
            var groups = await loadOptions('/groups', 'nombre', 'id');
            html += selectField('grupo_id', 'Grupo', groups, data.grupo_id, true) + field('dia', 'Día', 'date', true, data.dia || data.fecha) + field('hora_inicio', 'Hora Inicio', 'time', true, data.hora_inicio) + field('hora_fin', 'Hora Fin', 'time', true, data.hora_fin) + field('codigo', 'Código', 'text', true, data.codigo);
        }
        html += '<div class="form-actions"><button type="submit" class="btn">' + (editing ? 'Actualizar' : 'Guardar') + '</button><a href="#' + (resource === 'grupo' ? 'listar' : resource + 's') + '" class="btn btn-danger">Cancelar</a></div></form></div>';
        setContent(html);
        document.getElementById('entity-form').addEventListener('submit', submitEntity);
    }

    function payloadFromForm(form, resource) {
        var data = Object.fromEntries(new FormData(form).entries());
        if (resource === 'estudiante') { data.codigo_estudiante = data.codigo_estudiante; }
        if (resource === 'grupo') { data.capacidad_maxima = Number(data.capacidad_maxima); data.materia_id = Number(data.materia_id); data.profesor_id = Number(data.profesor_id); }
        if (resource === 'clase') { data.grupo_id = Number(data.grupo_id); }
        return data;
    }

    async function submitEntity(event) {
        event.preventDefault();
        var form = event.target;
        var resource = form.dataset.resource;
        var id = form.dataset.id;
        var pathMap = { grupo: '/groups', materia: '/subjects', estudiante: '/students', profesor: '/teachers', usuario: '/users', clase: '/classes' };
        var path = pathMap[resource] + (id ? '/' + encodeURIComponent(id) : '');
        try {
            await request(path, { method: id ? 'PATCH' : 'POST', body: JSON.stringify(payloadFromForm(form, resource)) });
            notify('Operacion realizada correctamente.', 'success');
            location.hash = resource === 'grupo' ? 'listar' : resource + 's';
        } catch (error) { notify(error.message, 'danger'); }
    }

    async function loadResource(action) {
        var config = resources[action];
        if (!config) return renderSpecial(action);
        setContent('<div class="card"><p>Cargando...</p></div>');
        try {
            var result = await request(config.path);
            setContent(renderTable(config, result.data || []));
        } catch (error) { setContent('<div class="alert alert-danger">' + escapeHtml(error.message) + '</div>'); }
    }

    async function renderSpecial(action) {
        if (action === 'registrar_clase') return renderForm('clase');
        if (action === 'crear_grupo') return renderForm('grupo');
        if (action === 'registrar_estudiante') return renderForm('estudiante');
        if (action === 'registrar_usuario') return renderForm('usuario');
        if (action === 'registrar_profesor') return renderForm('profesor');
        if (action === 'registrar_estudiantes_grupo') return renderAssignments();
        if (action === 'marcar_asistencia_estudiante') return renderStudentDashboard();
        if (action === 'attendance') return renderAttendance(state.pendingId);
        renderDashboard(action);
    }

    async function renderAssignments() {
        try {
            var groups = await loadOptions('/groups', 'nombre', 'id');
            var students = await loadOptions('/students', 'codigo_estudiante', 'codigo_estudiante');
            setContent('<div class="card card-md"><h2>Asignar Estudiante a Grupo</h2><form id="assignment-form">' + selectField('group_id', 'Grupo', groups, '', true) + selectField('code', 'Estudiante', students, '', true) + '<div class="form-actions"><button class="btn" type="submit">Asignar</button></div></form></div><div id="assignment-list"></div>');
            document.getElementById('assignment-form').addEventListener('submit', async function (event) {
                event.preventDefault();
                var data = Object.fromEntries(new FormData(event.target).entries());
                try { await request('/assignments', { method: 'POST', body: JSON.stringify({ group_id: Number(data.group_id), code: data.code }) }); notify('Estudiante asignado correctamente.', 'success'); } catch (error) { notify(error.message, 'danger'); }
            });
        } catch (error) { setContent('<div class="alert alert-danger">' + escapeHtml(error.message) + '</div>'); }
    }

    async function renderAttendance(classId) {
        try {
            var result = await request('/attendance/classes/' + encodeURIComponent(classId));
            var rows = result.data || [];
            setContent('<div class="card"><div class="panel-header"><h2 class="panel-title">Asistencia de la Clase ' + escapeHtml(classId) + '</h2><div>' + button('Actualizar', 'attendance', classId, 'outline') + ' ' + button('Finalizar', 'finish', classId, 'danger') + '</div></div><div class="table-container"><table><thead><tr><th>Código</th><th>Nombres</th><th>Apellidos</th><th>Estado</th><th>Acción</th></tr></thead><tbody>' + rows.map(function (row) { return '<tr><td>' + escapeHtml(row.codigo_estudiante) + '</td><td>' + escapeHtml(row.nombres) + '</td><td>' + escapeHtml(row.apellidos) + '</td><td>' + escapeHtml(row.tipo || 'Pendiente') + '</td><td>' + button('Presente', 'mark-present', classId + '|' + row.codigo_estudiante, 'success') + ' ' + button('Tarde', 'mark-late', classId + '|' + row.codigo_estudiante, 'outline') + '</td></tr>'; }).join('') + '</tbody></table></div></div>');
        } catch (error) { setContent('<div class="alert alert-danger">' + escapeHtml(error.message) + '</div>'); }
    }

    async function renderStudentDashboard() {
        try {
            var result = await request('/students/dashboard');
            var data = result.data || {};
            setContent('<div class="card"><h2>Mi asistencia</h2><p><strong>Estudiante:</strong> ' + escapeHtml((data.estudiante || {}).codigo_estudiante || '') + '</p><p>Presente: ' + escapeHtml((data.resumen || {}).presente || 0) + ' | Tarde: ' + escapeHtml((data.resumen || {}).tarde || 0) + ' | Falta: ' + escapeHtml((data.resumen || {}).falta || 0) + '</p></div><div class="card"><h2>Clases y asistencia</h2><div class="table-container"><table><thead><tr><th>Materia</th><th>Grupo</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr></thead><tbody>' + (data.clases || []).map(function (item) { return '<tr><td>' + escapeHtml(item.materia_nombre) + '</td><td>' + escapeHtml(item.grupo_nombre) + '</td><td>' + escapeHtml(item.dia) + '</td><td>' + escapeHtml(item.estado_asistencia || 'Pendiente') + '</td><td>' + button('Marcar', 'student-mark', item.id_clase, 'success') + '</td></tr>'; }).join('') + '</tbody></table></div></div>');
        } catch (error) { setContent('<div class="alert alert-danger">' + escapeHtml(error.message) + '</div>'); }
    }

    async function handleAction(action, id, resource) {
        var config = resources[resource];
        if (action === 'create') return renderForm(resource);
        if (action === 'edit') {
            var result = await request(config.path + '/' + encodeURIComponent(id));
            return renderForm(resource, id, result.data || result);
        }
        if (action === 'delete') {
            if (!window.confirm('¿Está seguro de eliminar este registro?')) return;
            await request(config.path + '/' + encodeURIComponent(id), { method: 'DELETE' });
            notify('Registro eliminado correctamente.', 'success');
            return loadResource(state.action);
        }
        if (action === 'attendance') { state.pendingId = id; return renderAttendance(id); }
        if (action === 'report-pdf' || action === 'report-excel') {
            var blob = await request('/attendance/classes/' + encodeURIComponent(id) + '/report?format=' + (action === 'report-pdf' ? 'pdf' : 'excel'));
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a'); link.href = url; link.download = 'reporte_asistencia_c' + id + (action === 'report-pdf' ? '.pdf' : '.xls'); link.click(); URL.revokeObjectURL(url); return;
        }
        if (action === 'finish') { await request('/attendance/classes/' + id + '/finish', { method: 'POST', body: JSON.stringify({ estrategia: 'manual' }) }); notify('Asistencia finalizada.', 'success'); return renderAttendance(id); }
        if (action === 'mark-present' || action === 'mark-late') {
            var parts = String(id).split('|'); await request('/attendance/classes/' + parts[0], { method: 'POST', body: JSON.stringify({ codigo_estudiante: parts[1], tipo: action === 'mark-present' ? 'presente' : 'tarde', estrategia: 'manual' }) }); notify('Asistencia registrada.', 'success'); return renderAttendance(parts[0]);
        }
        if (action === 'student-mark') { await request('/students/attendance', { method: 'POST', body: JSON.stringify({ clase_id: Number(id) }) }); notify('Asistencia registrada.', 'success'); return renderStudentDashboard(); }
    }

    function renderDashboard() {
        if (role() === 'estudiante') return renderStudentDashboard();
        setContent('<div class="card empty-state"><span class="empty-state-icon">🎯</span><h2>Bienvenido al sistema</h2><p>Seleccione una opcion del panel para continuar.</p></div>');
    }

    async function render() {
        shell();
        var savedTheme = localStorage.getItem('asistenciau-theme');
        document.documentElement.setAttribute('data-theme', savedTheme === 'dark' ? 'dark' : 'light');
        state.action = location.hash.substring(1) || 'dashboard';
        if (resources[state.action]) await loadResource(state.action); else await renderSpecial(state.action);
    }

    app.addEventListener('click', async function (event) {
        var target = event.target.closest('[data-action]');
        if (!target) return;
        try { await handleAction(target.dataset.action, target.dataset.id, target.dataset.resource || (state.action === 'listar' ? 'listar' : state.action)); } catch (error) { notify(error.message, 'danger'); }
    });
    window.addEventListener('hashchange', render);
    (async function () {
        try { var result = await request('/auth/me'); state.user = result.user; if (!state.user) throw new Error('Sin sesión'); render(); } catch (error) { renderLogin(); }
    }());
}());

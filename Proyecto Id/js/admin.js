const API_BASE = '../api';
let allStudents = [];
let currentViewStudent = null;
let searchTimeout = null;

function showToast(msg, type = 'info') {
    const c = document.getElementById('toastContainer');
    if (!c) return;
    const t = document.createElement('div');
    t.className = 'toast toast-' + type;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => { t.classList.add('hide'); setTimeout(() => t.remove(), 300); }, 3500);
}

function initDashboard() {
    const admin = JSON.parse(localStorage.getItem('admin') || 'null');
    if (!admin) {
        window.location.href = 'login.html';
        return;
    }
    document.getElementById('adminNameNav').textContent = admin.nombre || admin.usuario;
    loadCarreras();
    loadStudents();
    loadStats();
}

async function loadCarreras() {
    try {
        const res = await fetch(API_BASE + '/carreras.php');
        const data = await res.json();
        const sel = document.getElementById('fCarrera');
        if (!sel) return;
        const carreras = data.success && data.carreras ? data.carreras : [];
        sel.innerHTML = '<option value="">Selecciona una carrera</option>' +
            carreras.map(c => `<option value="${esc(c.nombre)}">${esc(c.nombre)}</option>`).join('');
    } catch (e) { console.warn('Error cargando carreras', e); }
}

function adminLogout() {
    fetch(API_BASE + '/admin_logout.php').then(() => {
        localStorage.removeItem('admin');
        window.location.href = 'login.html';
    });
}

async function loadStudents(search = '') {
    try {
        let url = API_BASE + '/alumnos.php';
        if (search) url += '?buscar=' + encodeURIComponent(search);

        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            allStudents = data.alumnos;
            renderStudentsTable(data.alumnos);
            updateStats(data.alumnos);
        }
    } catch (err) {
        showToast('Error al cargar alumnos', 'error');
    }
}

async function loadStats() {
    try {
        const docsRes = await fetch(API_BASE + '/documentos.php');
        const docsData = await docsRes.json();
        if (docsData.success) {
            document.getElementById('statDocs').textContent = docsData.documentos.length;
        }
    } catch { }
}

function updateStats(students) {
    document.getElementById('statTotal').textContent = students.length;
    document.getElementById('statActivos').textContent = students.filter(s => s.estado === 'activo').length;
    const carreras = new Set(students.map(s => s.carrera));
    document.getElementById('statCarreras').textContent = carreras.size;
}

function renderStudentsTable(students) {
    const tbody = document.getElementById('studentsTable');
    if (!tbody) return;

    if (students.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="padding:40px;color:var(--text-muted);"><div class="empty-state" style="padding:20px;"><h4>No se encontraron alumnos</h4><p>Agrega un nuevo alumno para comenzar</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = students.map((s, i) => `
        <tr style="animation:fadeIn 0.3s ease ${i * 0.05}s both;">
            <td><span style="font-family:monospace;font-weight:600;color:var(--accent);">${esc(s.matricula)}</span></td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-gradient);display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:white;flex-shrink:0;">
                        ${(s.nombre[0] || '') + (s.apellido[0] || '')}
                    </div>
                    <div>
                        <div style="font-weight:600;color:var(--text-primary);">${esc(s.nombre)} ${esc(s.apellido)}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted);">${esc(s.email || '')}</div>
                    </div>
                </div>
            </td>
            <td>${esc(s.carrera)}</td>
            <td>${s.semestre}°</td>
            <td><span class="badge ${getBadgeClass(s.estado)}">${capitalizar(s.estado)}</span></td>
            <td class="text-sm" style="color:var(--text-muted);">${formatDate(s.fecha_registro)}</td>
            <td style="text-align:right;">
                <div style="display:flex;gap:6px;justify-content:flex-end;">
                    <button class="btn btn-secondary btn-sm" onclick="viewStudent(${s.id})" title="Ver detalles">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="openEditModal(${s.id})" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                    </button>
                    <button class="btn btn-sm" style="background:rgba(239,68,68,0.06);color:#dc2626;border:1px solid rgba(239,68,68,0.15);" onclick="openDeleteModal(${s.id}, '${esc(s.nombre)} ${esc(s.apellido)}')" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const term = document.getElementById('searchInput').value.trim();
        loadStudents(term);
    }, 400);
}

function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Nuevo Alumno';
    document.getElementById('editStudentId').value = '';
    document.getElementById('studentForm').reset();
    document.getElementById('fMatricula').disabled = false;
    openModal('studentModal');
}

function openEditModal(id) {
    const student = allStudents.find(s => s.id == id);
    if (!student) return;

    document.getElementById('modalTitle').textContent = 'Editar Alumno';
    document.getElementById('editStudentId').value = student.id;
    document.getElementById('fMatricula').value = student.matricula;
    document.getElementById('fMatricula').disabled = true;
    document.getElementById('fNombre').value = student.nombre;
    document.getElementById('fApellido').value = student.apellido;
    document.getElementById('fCarrera').value = student.carrera;
    document.getElementById('fEmail').value = student.email || '';
    document.getElementById('fTelefono').value = student.telefono || '';
    document.getElementById('fSemestre').value = student.semestre || 1;
    document.getElementById('fGrupo').value = student.grupo || '';
    document.getElementById('fEstado').value = student.estado || 'activo';
    document.getElementById('fPassword').value = '';
    openModal('studentModal');
}

async function handleSaveStudent(e) {
    e.preventDefault();

    const editId = document.getElementById('editStudentId').value;
    const isEdit = !!editId;

    const studentData = {
        matricula: document.getElementById('fMatricula').value.trim(),
        nombre: document.getElementById('fNombre').value.trim(),
        apellido: document.getElementById('fApellido').value.trim(),
        carrera: document.getElementById('fCarrera').value.trim(),
        email: document.getElementById('fEmail').value.trim(),
        telefono: document.getElementById('fTelefono').value.trim(),
        semestre: parseInt(document.getElementById('fSemestre').value),
        grupo: document.getElementById('fGrupo').value.trim(),
        estado: document.getElementById('fEstado').value,
    };
    if (!isEdit) studentData.password = document.getElementById('fPassword').value;
    else if (document.getElementById('fPassword').value) studentData.password = document.getElementById('fPassword').value;
    if (!isEdit && (!studentData.password || studentData.password.length < 6)) {
        showToast('La contraseña debe tener al menos 6 caracteres', 'warning');
        return;
    }
    const btn = document.getElementById('btnSaveStudent');
    btn.disabled = true;
    try {
        const body = isEdit ? { ...studentData, id: editId } : studentData;
        if (isEdit && !body.password) delete body.password;
        const res = await fetch(API_BASE + '/alumnos.php', { method: isEdit ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        const data = await res.json();
        if (data.success) {
            showToast(isEdit ? 'Alumno actualizado' : 'Alumno registrado', 'success');
            closeModal('studentModal');
            loadStudents();
            loadStats();
        } else {
            showToast(data.error || 'Error al guardar', 'error');
        }
    } catch (err) {
        showToast('Error de conexión', 'error');
    }

    btn.disabled = false;
}

async function viewStudent(id) {
    const student = allStudents.find(s => s.id == id);
    if (!student) return;

    currentViewStudent = student;

    document.getElementById('viewAvatar').textContent = (student.nombre[0] || '') + (student.apellido[0] || '');
    document.getElementById('viewName').textContent = student.nombre + ' ' + student.apellido;
    document.getElementById('viewCarrera').textContent = student.carrera;
    document.getElementById('viewMatricula').textContent = student.matricula;

    const estadoEl = document.getElementById('viewEstado');
    estadoEl.textContent = capitalizar(student.estado);
    estadoEl.className = 'badge ' + getBadgeClass(student.estado);

    document.getElementById('viewEmail').textContent = student.email || 'No registrado';
    document.getElementById('viewTelefono').textContent = student.telefono || 'No registrado';
    document.getElementById('viewSemestre').textContent = student.semestre ? student.semestre + '° Semestre' : '--';
    document.getElementById('viewGrupo').textContent = student.grupo || '--';
    try {
        const res = await fetch(API_BASE + '/documentos.php?alumno_id=' + student.id);
        const data = await res.json();
        if (data.success) renderViewDocs(data.documentos);
    } catch { }

    openModal('viewModal');
}

function renderViewDocs(docs) {
    const container = document.getElementById('viewDocsList');
    if (!container) return;

    if (!docs || docs.length === 0) {
        container.innerHTML = '<p class="text-muted text-sm" style="text-align:center;padding:20px;">Sin documentos subidos</p>';
        return;
    }

    container.innerHTML = docs.map(doc => `
        <div class="doc-item">
            <div class="doc-icon">${getDocIcon(doc.tipo || doc.nombre_archivo)}</div>
            <div class="doc-info">
                <div class="name">${esc(doc.nombre_archivo)}</div>
                <div class="meta">${formatFileSize(doc.tamano)} &middot; ${formatDate(doc.fecha_subida)}</div>
            </div>
            <div style="display:flex;gap:6px;">
                <a href="../${doc.ruta}" target="_blank" class="btn btn-secondary btn-sm" title="Ver">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </a>
                <button class="btn btn-sm" style="background:rgba(239,68,68,0.06);color:#dc2626;border:1px solid rgba(239,68,68,0.15);" onclick="deleteDoc(${doc.id})" title="Eliminar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </div>
    `).join('');
}

async function adminUploadDoc(input) {
    if (!input.files || !input.files[0] || !currentViewStudent) return;

    const file = input.files[0];
    if (file.size > 10 * 1024 * 1024) {
        showToast('El archivo excede 10MB', 'error');
        input.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('alumno_id', currentViewStudent.id);
    formData.append('documento', file);
    formData.append('descripcion', 'Subido por admin');

    showToast('Subiendo documento...', 'info');

    try {
        const res = await fetch(API_BASE + '/documentos.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Documento subido correctamente', 'success');
            viewStudent(currentViewStudent.id);
            loadStats();
        } else {
            showToast(data.error || 'Error al subir', 'error');
        }
    } catch {
        showToast('Error de conexión', 'error');
    }

    input.value = '';
}

async function deleteDoc(docId) {
    if (!confirm('¿Eliminar este documento?')) return;

    try {
        const res = await fetch(API_BASE + '/documentos.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: docId })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Documento eliminado', 'success');
            if (currentViewStudent) viewStudent(currentViewStudent.id);
            loadStats();
        } else {
            showToast(data.error, 'error');
        }
    } catch {
        showToast('Error de conexión', 'error');
    }
}

function openDeleteModal(id, name) {
    document.getElementById('deleteStudentId').value = id;
    document.getElementById('deleteStudentName').textContent = name;
    openModal('deleteModal');
}

async function confirmDelete() {
    const id = document.getElementById('deleteStudentId').value;
    if (!id) return;

    try {
        const res = await fetch(API_BASE + '/alumnos.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const data = await res.json();

        if (data.success) {
            showToast('Alumno eliminado', 'success');
            closeModal('deleteModal');
            loadStudents();
            loadStats();
        } else {
            showToast(data.error, 'error');
        }
    } catch {
        showToast('Error de conexión', 'error');
    }
}

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function capitalizar(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getBadgeClass(estado) {
    switch (estado) {
        case 'activo': return 'badge-success';
        case 'inactivo': return 'badge-warning';
        case 'baja': return 'badge-danger';
        default: return 'badge-info';
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '--';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch {
        return dateStr;
    }
}

function formatFileSize(bytes) {
    if (!bytes) return '--';
    bytes = parseInt(bytes);
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function getDocIcon(tipo) {
    if (!tipo) return '📄';
    tipo = tipo.toLowerCase();
    if (tipo.includes('pdf')) return '📕';
    if (tipo.includes('image') || tipo.includes('jpg') || tipo.includes('png')) return '🖼️';
    if (tipo.includes('word') || tipo.includes('doc')) return '📘';
    if (tipo.includes('excel') || tipo.includes('xls')) return '📗';
    return '📄';
}

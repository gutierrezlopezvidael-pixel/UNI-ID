const API_BASE = (() => { const p = window.location.pathname; return p.substring(0, p.lastIndexOf('/')) + '/api'; })();

function showToast(msg, type = 'info') {
    const c = document.getElementById('toastContainer');
    if (!c) return;
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => { t.classList.add('hide'); setTimeout(() => t.remove(), 300); }, 3500);
}

function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('emailInput').value.trim();
    const password = document.getElementById('passwordInput').value;
    const btn = document.getElementById('btnLogin');
    if (!email || !password) { showToast('Ingresa email y contraseña', 'warning'); return; }
    btn.disabled = true;
    btn.innerHTML = '<span>Verificando...</span>';
    fetch(API_BASE + '/login_alumno.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            sessionStorage.setItem('alumno', JSON.stringify(data.alumno));
            sessionStorage.setItem('documentos', JSON.stringify(data.documentos || []));
            showToast('¡Bienvenido, ' + data.alumno.nombre + '!', 'success');
            setTimeout(() => window.location.href = 'alumno.html', 800);
        } else {
            showToast(data.error || 'Credenciales incorrectas', 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg> Iniciar Sesión';
        }
    }).catch(() => {
        showToast('Error de conexión', 'error');
        btn.disabled = false;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg> Iniciar Sesión';
    });
}

function loadStudentProfile() {
    const alumnoStr = sessionStorage.getItem('alumno');
    if (!alumnoStr) { window.location.href = 'index.html'; return; }
    const alumno = JSON.parse(alumnoStr);
    const docs = JSON.parse(sessionStorage.getItem('documentos') || '[]');
    const set = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
    set('navStudentName', alumno.nombre + ' ' + alumno.apellido);
    set('profileName', alumno.nombre + ' ' + alumno.apellido);
    set('profileCarrera', alumno.carrera);
    set('profileMatricula', alumno.matricula);
    set('detMatricula', alumno.matricula);
    set('detEmail', alumno.email || 'No registrado');
    set('detTelefono', alumno.telefono || 'No registrado');
    set('detSemestre', alumno.semestre ? alumno.semestre + '° Semestre' : '—');
    set('detGrupo', alumno.grupo || '—');
    set('detFecha', alumno.fecha_registro ? new Date(alumno.fecha_registro).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }) : '—');
    const avatar = document.getElementById('profileAvatar');
    if (avatar) avatar.textContent = (alumno.nombre[0] || '') + (alumno.apellido[0] || '');
    const estadoEl = document.getElementById('profileEstado');
    if (estadoEl) { estadoEl.textContent = (alumno.estado || 'activo').charAt(0).toUpperCase() + (alumno.estado || '').slice(1); estadoEl.className = 'badge ' + (alumno.estado === 'activo' ? 'badge-success' : alumno.estado === 'inactivo' ? 'badge-warning' : 'badge-danger'); }
    renderStudentDocuments(docs);
    loadBoletaAndKardex(alumno.id);
}

async function loadBoletaAndKardex(alumnoId) {
    const boletaEl = document.getElementById('boletaSection');
    const kardexEl = document.getElementById('kardexList');
    if (!boletaEl) return;
    try {
        const [calRes, docRes] = await Promise.all([fetch(API_BASE + '/calificaciones.php?alumno_id=' + alumnoId), fetch(API_BASE + '/documentos.php?alumno_id=' + alumnoId + '&tipo_documento=kardex')]);
        const calData = await calRes.json();
        const docData = await docRes.json();
        if (calData.success && calData.calificaciones?.length) {
            const byCuatri = {};
            calData.calificaciones.forEach(c => { if (!byCuatri[c.cuatrimestre]) byCuatri[c.cuatrimestre] = []; byCuatri[c.cuatrimestre].push(c); });
            boletaEl.innerHTML = Object.keys(byCuatri).sort((a,b)=>a-b).map(cuatri => {
                const items = byCuatri[cuatri];
                const prom = (items.reduce((s,x)=>s+parseFloat(x.calificacion),0)/items.length).toFixed(1);
                return `<div style="margin-bottom:20px;"><h4 style="font-size:0.95rem;margin-bottom:8px;color:var(--text-secondary);">Cuatrimestre ${cuatri}</h4><table style="width:100%;font-size:0.9rem;"><thead><tr><th style="text-align:left;padding:8px;border-bottom:1px solid var(--border);">Materia</th><th style="text-align:right;padding:8px;border-bottom:1px solid var(--border);">Calificación</th></tr></thead><tbody>${items.map(c=>'<tr><td style="padding:8px;border-bottom:1px solid #f1f5f9;">'+esc(c.materia_nombre)+'</td><td style="padding:8px;text-align:right;font-weight:600;">'+c.calificacion+'</td></tr>').join('')}<tr><td style="padding:8px;font-weight:600;">Promedio</td><td style="padding:8px;text-align:right;font-weight:700;">${prom}</td></tr></tbody></table></div>`;
            }).join('');
        } else boletaEl.innerHTML = '<p class="text-muted text-sm">Sin calificaciones.</p>';
        if (kardexEl && docData.success && docData.documentos?.length) kardexEl.innerHTML = docData.documentos.map(d => `<div class="doc-item"><div class="doc-icon">📄</div><div class="doc-info"><div class="name">${esc(d.nombre_archivo)}</div><div class="meta">${d.tamano>=1024*1024?(d.tamano/1024/1024).toFixed(1)+' MB':(d.tamano/1024).toFixed(1)+' KB'} • ${d.fecha_subida?new Date(d.fecha_subida).toLocaleDateString('es-MX'):'—'}</div></div><a href="${d.ruta}" target="_blank" class="btn btn-primary btn-sm">Ver</a></div>`).join('');
        else if (kardexEl) kardexEl.innerHTML = '<p class="text-muted text-sm">Sin documento Kardex.</p>';
    } catch (e) {
        boletaEl.innerHTML = '<p class="text-muted text-sm">No se pudieron cargar.</p>';
        if (kardexEl) kardexEl.innerHTML = '';
    }
}

function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function renderStudentDocuments(docs) {
    const c = document.getElementById('documentsList');
    if (!c) return;
    if (!docs?.length) { c.innerHTML = '<div class="empty-state"><h4>Sin documentos</h4></div>'; return; }
    c.innerHTML = docs.map(d => `<div class="doc-item"><div class="doc-icon">${d.tipo?.includes('pdf')?'📕':'🖼️'}</div><div class="doc-info"><div class="name">${esc(d.nombre_archivo)}</div></div><a href="${d.ruta}" target="_blank" class="btn btn-secondary btn-sm">Ver</a></div>`).join('');
}

async function uploadDocument(input) {
    if (!input.files?.[0]) return;
    const alumno = JSON.parse(sessionStorage.getItem('alumno')||'{}');
    if (!alumno.id) return;
    const fd = new FormData();
    fd.append('alumno_id', alumno.id);
    fd.append('documento', input.files[0]);
    showToast('Subiendo...', 'info');
    try {
        const r = await fetch(API_BASE + '/documentos.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) { showToast('Subido', 'success'); const res = await fetch(API_BASE + '/documentos.php?alumno_id=' + alumno.id); const data = await res.json(); if (data.success) renderStudentDocuments(data.documentos); }
        else showToast(d.error || 'Error', 'error');
    } catch { showToast('Error', 'error'); }
    input.value = '';
}

function logout() { sessionStorage.clear(); window.location.href = 'index.html'; }

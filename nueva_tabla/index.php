<?php
// index.php
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestión de Animales</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <span class="navbar-brand fw-semibold">Gestión de Animales</span>
  </div>
</nav>

<main class="container py-4">
  <div id="alertBox" class="mb-3"></div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between">
        <div class="flex-grow-1">
          <label class="form-label mb-1">Buscar</label>
          <input id="searchInput" type="text" class="form-control"
                 placeholder="Filtra por nombre, especie, fecha (YYYY-MM-DD) o edad (calculada)">
          <div class="form-text">Filtrado en cliente (sin pedir a la API).</div>
        </div>

        <div class="mt-2 mt-md-4">
          <button id="btnOpenAdd" class="btn btn-primary">+ Agregar Animal</button>
        </div>
      </div>

      <hr class="my-4">

      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead class="table-dark">
            <tr>
              <th style="width:90px;">No.</th>
              <th>Nombre</th>
              <th>Especie</th>
              <th style="width:170px;">Fecha Nacimiento</th>
              <th style="width:110px;">Edad</th>
              <th style="width:260px;">Acciones</th>
            </tr>
          </thead>
          <tbody id="animalsBody">
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Cargando...</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</main>

<!-- Modal Agregar -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agregar Animal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input type="text" class="form-control" id="a_nombre" maxlength="100" required>
          <div class="invalid-feedback">Nombre requerido (máx 100).</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Especie</label>
          <input type="text" class="form-control" id="a_especie" maxlength="100" required>
          <div class="invalid-feedback">Especie requerida (máx 100).</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Fecha de Nacimiento</label>
          <input type="date" class="form-control" id="a_fechanacimiento" required>
          <div class="invalid-feedback">Fecha inválida (no futura).</div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btnAddSave">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Ver -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle del Animal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-5">ID real</dt><dd class="col-7" id="v_id"></dd>
          <dt class="col-5">Nombre</dt><dd class="col-7" id="v_nombre"></dd>
          <dt class="col-5">Especie</dt><dd class="col-7" id="v_especie"></dd>
          <dt class="col-5">Fecha Nacimiento</dt><dd class="col-7" id="v_fn"></dd>
          <dt class="col-5">Edad (calculada)</dt><dd class="col-7" id="v_edad"></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Animal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="e_id">

        <div class="mb-2 small text-muted">
          ID real: <span class="fw-semibold" id="e_id_text"></span>
        </div>

        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input type="text" class="form-control" id="e_nombre" maxlength="100" required>
          <div class="invalid-feedback">Nombre requerido (máx 100).</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Especie</label>
          <input type="text" class="form-control" id="e_especie" maxlength="100" required>
          <div class="invalid-feedback">Especie requerida (máx 100).</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Fecha de Nacimiento</label>
          <input type="date" class="form-control" id="e_fechanacimiento" required>
          <div class="invalid-feedback">Fecha inválida (no futura).</div>
        </div>

        <div class="small text-muted">
          Edad calculada: <span class="fw-semibold" id="e_edad_calc"></span>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-warning" id="btnEditSave">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="deleteForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">Eliminar Animal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="d_id">
        <p class="mb-2">¿Seguro que quieres eliminar este animal?</p>
        <ul class="mb-0">
          <li><span class="text-muted">ID real:</span> <span class="fw-semibold" id="d_id_text"></span></li>
          <li><span class="text-muted">Nombre:</span> <span class="fw-semibold" id="d_nombre"></span></li>
          <li><span class="text-muted">Especie:</span> <span class="fw-semibold" id="d_especie"></span></li>
        </ul>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger" id="btnDeleteConfirm">Sí, eliminar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(() => {
  const API_URL = 'api/algo.php';

  let animals = [];   // data completa (con id real)
  let filtered = [];  // data filtrada

  const tbody = document.getElementById('animalsBody');
  const searchInput = document.getElementById('searchInput');
  const alertBox = document.getElementById('alertBox');

  const addModal = new bootstrap.Modal(document.getElementById('addModal'));
  const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
  const editModal = new bootstrap.Modal(document.getElementById('editModal'));
  const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

  const btnOpenAdd = document.getElementById('btnOpenAdd');
  const addForm = document.getElementById('addForm');
  const editForm = document.getElementById('editForm');
  const deleteForm = document.getElementById('deleteForm');

  // -------- Helpers ----------
  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function showAlert(message, type = 'success') {
    alertBox.innerHTML = `
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    `;
  }

  function todayYmd() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  function calcEdad(fechaYmd) {
    if (!fechaYmd || !/^\d{4}-\d{2}-\d{2}$/.test(fechaYmd)) return '';
    const [y, m, d] = fechaYmd.split('-').map(Number);
    const born = new Date(y, m - 1, d);
    if (isNaN(born.getTime())) return '';

    const today = new Date();
    let age = today.getFullYear() - born.getFullYear();
    const mm = today.getMonth() - born.getMonth();
    if (mm < 0 || (mm === 0 && today.getDate() < born.getDate())) age--;
    return (age < 0) ? '' : String(age);
  }

  function setLoading() {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Cargando...</td></tr>`;
  }

  function setEmpty(msg = 'Sin resultados') {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">${escapeHtml(msg)}</td></tr>`;
  }

  function findById(id) {
    return animals.find(a => String(a.id) === String(id)) || null;
  }

  // -------- Render ----------
  function renderTable(list) {
    tbody.innerHTML = '';

    if (!Array.isArray(list) || list.length === 0) {
      setEmpty('No hay animales para mostrar');
      return;
    }

    // No. = 1..N (sin mostrar id real)
    list.forEach((a, idx) => {
      const tr = document.createElement('tr');

      const tdNo = document.createElement('td');
      tdNo.textContent = String(idx + 1);

      const tdNombre = document.createElement('td');
      tdNombre.textContent = a.nombre ?? '';

      const tdEspecie = document.createElement('td');
      tdEspecie.textContent = a.especie ?? '';

      const tdFN = document.createElement('td');
      tdFN.textContent = a.fechanacimiento ?? '';

      const tdEdad = document.createElement('td');
      tdEdad.textContent = calcEdad(a.fechanacimiento);

      const tdAcc = document.createElement('td');

      const btnVer = document.createElement('button');
      btnVer.className = 'btn btn-sm btn-outline-primary me-2';
      btnVer.textContent = 'Ver';
      btnVer.addEventListener('click', () => openView(a.id));

      const btnEdit = document.createElement('button');
      btnEdit.className = 'btn btn-sm btn-outline-warning me-2';
      btnEdit.textContent = 'Editar';
      btnEdit.addEventListener('click', () => openEdit(a.id));

      const btnDel = document.createElement('button');
      btnDel.className = 'btn btn-sm btn-outline-danger';
      btnDel.textContent = 'Eliminar';
      btnDel.addEventListener('click', () => openDelete(a.id));

      tdAcc.appendChild(btnVer);
      tdAcc.appendChild(btnEdit);
      tdAcc.appendChild(btnDel);

      tr.appendChild(tdNo);
      tr.appendChild(tdNombre);
      tr.appendChild(tdEspecie);
      tr.appendChild(tdFN);
      tr.appendChild(tdEdad);
      tr.appendChild(tdAcc);

      tbody.appendChild(tr);
    });
  }

  function applyFilter() {
    const q = (searchInput.value || '').trim().toLowerCase();
    if (!q) {
      filtered = [...animals];
      renderTable(filtered);
      return;
    }

    filtered = animals.filter(a => {
      const nombre = String(a.nombre ?? '').toLowerCase();
      const especie = String(a.especie ?? '').toLowerCase();
      const fn = String(a.fechanacimiento ?? '').toLowerCase();
      const edad = calcEdad(a.fechanacimiento).toLowerCase();

      return nombre.includes(q) || especie.includes(q) || fn.includes(q) || edad.includes(q);
    });

    if (filtered.length === 0) {
      setEmpty('No hay coincidencias');
      return;
    }
    renderTable(filtered);
  }

  // -------- API calls ----------
  async function apiGet(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    const json = await res.json();
    if (!res.ok || !json.ok) throw new Error(json.message || 'Error');
    return json.data;
  }

  async function apiPost(action, payload) {
    const res = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (!res.ok || !json.ok) throw new Error(json.message || 'Error');
    return json.data;
  }

  async function listarAnimales() {
    try {
      setLoading();
      const data = await apiGet(`${API_URL}?action=listar`);
      animals = Array.isArray(data) ? data : [];
      filtered = [...animals];
      renderTable(filtered);
    } catch (err) {
      setEmpty('Error cargando datos');
      showAlert(err.message || 'Error desconocido', 'danger');
    }
  }

  // -------- Modales ----------
  async function openView(id) {
    try {
      // si quieres siempre fresco, puedes descomentar esto:
      // const a = await apiGet(`${API_URL}?action=obtener&id=${encodeURIComponent(id)}`);
      const a = findById(id) || await apiGet(`${API_URL}?action=obtener&id=${encodeURIComponent(id)}`);

      document.getElementById('v_id').textContent = a.id ?? '';
      document.getElementById('v_nombre').textContent = a.nombre ?? '';
      document.getElementById('v_especie').textContent = a.especie ?? '';
      document.getElementById('v_fn').textContent = a.fechanacimiento ?? '';
      document.getElementById('v_edad').textContent = calcEdad(a.fechanacimiento); // ✅ edad en ver

      viewModal.show();
    } catch (err) {
      showAlert(err.message || 'Error abriendo detalle', 'danger');
    }
  }

  function openEdit(id) {
    const a = findById(id);
    if (!a) {
      showAlert('No se encontró el animal en memoria, recargando...', 'warning');
      listarAnimales();
      return;
    }

    // set max date = hoy
    document.getElementById('e_fechanacimiento').max = todayYmd();

    document.getElementById('e_id').value = a.id;
    document.getElementById('e_id_text').textContent = a.id;
    document.getElementById('e_nombre').value = a.nombre ?? '';
    document.getElementById('e_especie').value = a.especie ?? '';
    document.getElementById('e_fechanacimiento').value = a.fechanacimiento ?? '';
    document.getElementById('e_edad_calc').textContent = calcEdad(a.fechanacimiento);

    // limpia invalids
    editForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    editModal.show();
  }

  function openDelete(id) {
    const a = findById(id);
    if (!a) {
      showAlert('No se encontró el animal en memoria, recargando...', 'warning');
      listarAnimales();
      return;
    }

    document.getElementById('d_id').value = a.id;
    document.getElementById('d_id_text').textContent = a.id;
    document.getElementById('d_nombre').textContent = a.nombre ?? '';
    document.getElementById('d_especie').textContent = a.especie ?? '';

    deleteModal.show();
  }

  // -------- Validación ----------
  function validateText(input, maxLen=100) {
    input.classList.remove('is-invalid');
    const v = (input.value || '').trim();
    if (!v || v.length > maxLen) {
      input.classList.add('is-invalid');
      return null;
    }
    return v;
  }

  function validateDate(input) {
    input.classList.remove('is-invalid');
    const v = input.value;
    if (!v) {
      input.classList.add('is-invalid');
      return null;
    }
    const chosen = new Date(v + 'T00:00:00');
    const today = new Date();
    const today0 = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    if (isNaN(chosen.getTime()) || chosen > today0) {
      input.classList.add('is-invalid');
      return null;
    }
    return v;
  }

  // -------- Eventos ----------
  searchInput.addEventListener('input', applyFilter);

  btnOpenAdd.addEventListener('click', () => {
    addForm.reset();
    addForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    document.getElementById('a_fechanacimiento').max = todayYmd();

    addModal.show();
  });

  addForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const nombre = validateText(document.getElementById('a_nombre'));
    const especie = validateText(document.getElementById('a_especie'));
    const fn = validateDate(document.getElementById('a_fechanacimiento'));
    if (!nombre || !especie || !fn) return;

    const btn = document.getElementById('btnAddSave');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
      const inserted = await apiPost('insertar', { nombre, especie, fechanacimiento: fn });

      // Como listamos ORDER BY id DESC, lo metemos al inicio
      animals.unshift(inserted);

      addModal.hide();
      showAlert('Animal agregado ✅', 'success');

      searchInput.value = '';
      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error insertando', 'danger');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Guardar';
    }
  });

  // edad dinámica en edit al cambiar fecha
  document.getElementById('e_fechanacimiento').addEventListener('input', (e) => {
    document.getElementById('e_edad_calc').textContent = calcEdad(e.target.value);
  });

  editForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('e_id').value;
    const nombre = validateText(document.getElementById('e_nombre'));
    const especie = validateText(document.getElementById('e_especie'));
    const fn = validateDate(document.getElementById('e_fechanacimiento'));
    if (!id || !nombre || !especie || !fn) return;

    const btn = document.getElementById('btnEditSave');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
      const updated = await apiPost('editar', { id, nombre, especie, fechanacimiento: fn });

      // update local
      animals = animals.map(a => String(a.id) === String(updated.id) ? updated : a);

      editModal.hide();
      showAlert('Animal editado ✅', 'success');

      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error editando', 'danger');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Guardar cambios';
    }
  });

  deleteForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('d_id').value;
    if (!id) return;

    const btn = document.getElementById('btnDeleteConfirm');
    btn.disabled = true;
    btn.textContent = 'Eliminando...';

    try {
      await apiPost('eliminar', { id });

      animals = animals.filter(a => String(a.id) !== String(id));

      deleteModal.hide();
      showAlert('Animal eliminado ✅', 'success');

      applyFilter(); // renumera el No. 1..N automáticamente
    } catch (err) {
      showAlert(err.message || 'Error eliminando', 'danger');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Sí, eliminar';
    }
  });

  // ✅ Cargar cuando todo está listo (readyState complete)
  window.addEventListener('load', () => {
    listarAnimales();
  });

})();
</script>

</body>
</html>
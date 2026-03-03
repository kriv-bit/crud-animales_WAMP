(() => {
  // Endpoint de la API.
  const API_URL = 'api/algo.php';

  // Lista completa en memoria y lista filtrada (búsqueda cliente).
  let especies = [];
  let filtered = [];

  // Referencias DOM.
  const tbody = document.getElementById('speciesBody');
  const searchInput = document.getElementById('searchSpecies');
  const alertBox = document.getElementById('alertBox');

  // Modales Bootstrap.
  const addModal = new bootstrap.Modal(document.getElementById('addModal'));
  const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
  const editModal = new bootstrap.Modal(document.getElementById('editModal'));
  const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

  // Formularios.
  const btnOpenAdd = document.getElementById('btnOpenAdd');
  const addForm = document.getElementById('addForm');
  const editForm = document.getElementById('editForm');
  const deleteForm = document.getElementById('deleteForm');

  // ---------------- Helpers ----------------

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

  function setLoading() {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">Cargando...</td></tr>`;
  }

  function setEmpty(msg = 'Sin resultados') {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">${escapeHtml(msg)}</td></tr>`;
  }

  function findById(id) {
    return especies.find(e => String(e.id) === String(id)) || null;
  }

  // ---------------- Render ----------------

  function renderTable(list) {
    tbody.innerHTML = '';

    if (!Array.isArray(list) || list.length === 0) {
      setEmpty('No hay especies para mostrar');
      return;
    }

    list.forEach((e, idx) => {
      const tr = document.createElement('tr');

      const tdNo = document.createElement('td');
      tdNo.textContent = String(idx + 1);

      const tdNombre = document.createElement('td');
      tdNombre.textContent = e.nombre ?? '';

      const tdDesc = document.createElement('td');
      tdDesc.textContent = e.descripcion ?? '';

      const tdAcc = document.createElement('td');

      const btnVer = document.createElement('button');
      btnVer.className = 'btn btn-sm btn-outline-primary me-2';
      btnVer.textContent = 'Ver';
      btnVer.addEventListener('click', () => openView(e.id));

      const btnEdit = document.createElement('button');
      btnEdit.className = 'btn btn-sm btn-outline-warning me-2';
      btnEdit.textContent = 'Editar';
      btnEdit.addEventListener('click', () => openEdit(e.id));

      const btnDel = document.createElement('button');
      btnDel.className = 'btn btn-sm btn-outline-danger';
      btnDel.textContent = 'Eliminar';
      btnDel.addEventListener('click', () => openDelete(e.id));

      tdAcc.appendChild(btnVer);
      tdAcc.appendChild(btnEdit);
      tdAcc.appendChild(btnDel);

      tr.appendChild(tdNo);
      tr.appendChild(tdNombre);
      tr.appendChild(tdDesc);
      tr.appendChild(tdAcc);

      tbody.appendChild(tr);
    });
  }

  function applyFilter() {
    const q = (searchInput.value || '').trim().toLowerCase();

    if (!q) {
      filtered = [...especies];
      renderTable(filtered);
      return;
    }

    filtered = especies.filter(e => {
      const nombre = String(e.nombre ?? '').toLowerCase();
      const desc = String(e.descripcion ?? '').toLowerCase();
      return nombre.includes(q) || desc.includes(q);
    });

    if (filtered.length === 0) {
      setEmpty('No hay coincidencias');
      return;
    }

    renderTable(filtered);
  }

  // ---------------- API ----------------

  async function apiGet(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
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

  async function listarEspecies() {
    try {
      setLoading();
      const data = await apiGet(`${API_URL}?action=listar_especies`);
      especies = Array.isArray(data) ? data : [];
      filtered = [...especies];
      renderTable(filtered);
    } catch (err) {
      setEmpty('Error cargando datos');
      showAlert(err.message || 'Error desconocido', 'danger');
    }
  }

  // ---------------- Modales ----------------

  async function openView(id) {
    try {
      const e = findById(id) || await apiGet(`${API_URL}?action=obtener_especie&id=${encodeURIComponent(id)}`);

      document.getElementById('v_id').textContent = e.id ?? '';
      document.getElementById('v_nombre').textContent = e.nombre ?? '';
      document.getElementById('v_desc').textContent = e.descripcion ?? '';

      viewModal.show();
    } catch (err) {
      showAlert(err.message || 'Error abriendo detalle', 'danger');
    }
  }

  async function openEdit(id) {
    const e = findById(id);
    if (!e) {
      showAlert('No se encontró la especie en memoria; se recargará el listado.', 'warning');
      await listarEspecies();
      return;
    }

    document.getElementById('e_id').value = e.id;
    document.getElementById('e_id_text').textContent = e.id;
    document.getElementById('e_nombre').value = e.nombre ?? '';
    document.getElementById('e_desc').value = e.descripcion ?? '';

    editForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    editModal.show();
  }

  function openDelete(id) {
    const e = findById(id);
    if (!e) {
      showAlert('No se encontró la especie en memoria; se recargará el listado.', 'warning');
      listarEspecies();
      return;
    }

    document.getElementById('d_id').value = e.id;
    document.getElementById('d_id_text').textContent = e.id;
    document.getElementById('d_nombre').textContent = e.nombre ?? '';

    deleteModal.show();
  }

  // ---------------- Validación ----------------

  function validateText(input, maxLen = 100) {
    input.classList.remove('is-invalid');
    const v = (input.value || '').trim();
    if (!v || v.length > maxLen) {
      input.classList.add('is-invalid');
      return null;
    }
    return v;
  }

  function validateTextOptional(input, maxLen = 255) {
    input.classList.remove('is-invalid');
    const v = (input.value || '').trim();
    if (v.length > maxLen) {
      input.classList.add('is-invalid');
      return null;
    }
    return v;
  }

  // ---------------- Eventos ----------------

  searchInput.addEventListener('input', applyFilter);

  btnOpenAdd.addEventListener('click', () => {
    addForm.reset();
    addForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    addModal.show();
  });

  addForm.addEventListener('submit', async (ev) => {
    ev.preventDefault();

    const nombre = validateText(document.getElementById('a_nombre'), 100);
    const desc = validateTextOptional(document.getElementById('a_desc'), 255);
    if (!nombre || desc === null) return;

    const btn = document.getElementById('btnAddSave');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
      const created = await apiPost('insertar_especie', { nombre, descripcion: desc });
      especies.unshift(created);

      addModal.hide();
      showAlert('Especie agregada correctamente.', 'success');

      searchInput.value = '';
      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error insertando.', 'danger');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Guardar';
    }
  });

  editForm.addEventListener('submit', async (ev) => {
    ev.preventDefault();

    const id = document.getElementById('e_id').value;
    const nombre = validateText(document.getElementById('e_nombre'), 100);
    const desc = validateTextOptional(document.getElementById('e_desc'), 255);
    if (!id || !nombre || desc === null) return;

    const btn = document.getElementById('btnEditSave');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
      const updated = await apiPost('editar_especie', { id, nombre, descripcion: desc });
      especies = especies.map(x => String(x.id) === String(updated.id) ? updated : x);

      editModal.hide();
      showAlert('Especie editada correctamente.', 'success');
      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error editando.', 'danger');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Guardar cambios';
    }
  });

  deleteForm.addEventListener('submit', async (ev) => {
    ev.preventDefault();

    const id = document.getElementById('d_id').value;
    if (!id) return;

    const btn = document.getElementById('btnDeleteConfirm');
    btn.disabled = true;
    btn.textContent = 'Eliminando...';

    try {
      await apiPost('eliminar_especie', { id });
      especies = especies.filter(x => String(x.id) !== String(id));

      deleteModal.hide();
      showAlert('Especie eliminada correctamente.', 'success');
      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error eliminando.', 'danger');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Sí, eliminar';
    }
  });

  window.addEventListener('load', () => {
    listarEspecies();
  });

})();
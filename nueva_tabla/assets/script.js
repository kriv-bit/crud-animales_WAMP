(() => {
  // URL de la API (backend PHP) que responde JSON.
  // Nota: esta API ya trabaja con:
  // - animales.especie_id (FK)
  // - JOIN para devolver especies.nombre como "especie"
  const API_URL = 'api/algo.php';

  // Arreglo principal con TODOS los animales (incluye el id real de la BD).
  let animals = [];

  // Arreglo que se muestra en pantalla (puede ser filtrado por el buscador).
  let filtered = [];

  // Catálogo de especies (id, nombre, descripcion).
  // Se usa para llenar los <select> del modal Agregar y Editar.
  let especies = [];

  // Referencias a elementos del DOM (HTML).
  const tbody = document.getElementById('animalsBody');          // <tbody> donde se pintan filas
  const searchInput = document.getElementById('searchInput');    // input de búsqueda
  const alertBox = document.getElementById('alertBox');          // contenedor de alertas

  // Selects de especie (FK) en los modales.
  // En el HTML corregido ya no existe a_especie/e_especie (texto),
  // ahora son a_especie_id / e_especie_id (select con id de especie).
  const addEspecieSelect = document.getElementById('a_especie_id');
  const editEspecieSelect = document.getElementById('e_especie_id');

  // Instancias de los modales de Bootstrap (para abrir/cerrar).
  const addModal = new bootstrap.Modal(document.getElementById('addModal'));
  const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
  const editModal = new bootstrap.Modal(document.getElementById('editModal'));
  const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

  // Botón "Agregar Animal" y formularios de los modales.
  const btnOpenAdd = document.getElementById('btnOpenAdd');
  const addForm = document.getElementById('addForm');
  const editForm = document.getElementById('editForm');
  const deleteForm = document.getElementById('deleteForm');

  // -------- Helpers (funciones de apoyo) ----------

  // Escapa texto para evitar inyección de HTML/JS en mensajes (protección XSS).
  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  // Muestra una alerta Bootstrap (success, danger, warning, etc.).
  function showAlert(message, type = 'success') {
    alertBox.innerHTML = `
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    `;
  }

  // Devuelve la fecha de hoy en formato YYYY-MM-DD (para input type="date").
  function todayYmd() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  // Calcula edad (años) a partir de fechanacimiento "YYYY-MM-DD".
  // Nota: la edad NO está almacenada en la BD; se calcula en el navegador.
  function calcEdad(fechaYmd) {
    // Valida formato básico.
    if (!fechaYmd || !/^\d{4}-\d{2}-\d{2}$/.test(fechaYmd)) return '';

    // Convierte string a números.
    const [y, m, d] = fechaYmd.split('-').map(Number);

    // Crea fecha de nacimiento.
    const born = new Date(y, m - 1, d);
    if (isNaN(born.getTime())) return '';

    // Calcula diferencia de años y ajusta si aún no ha cumplido este año.
    const today = new Date();
    let age = today.getFullYear() - born.getFullYear();
    const mm = today.getMonth() - born.getMonth();
    if (mm < 0 || (mm === 0 && today.getDate() < born.getDate())) age--;

    // Si por alguna razón queda negativa (fecha futura), devuelve vacío.
    return (age < 0) ? '' : String(age);
  }

  // Pone mensaje "Cargando..." en la tabla.
  function setLoading() {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Cargando...</td></tr>`;
  }

  // Pone mensaje "vacío" en la tabla con texto personalizado.
  function setEmpty(msg = 'Sin resultados') {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">${escapeHtml(msg)}</td></tr>`;
  }

  // Busca un animal por id real dentro del array animals.
  function findById(id) {
    return animals.find(a => String(a.id) === String(id)) || null;
  }

  // -------- Render (pintar tabla) ----------

  // Renderiza la tabla con la lista que le pases (filtered o animals).
  function renderTable(list) {
    tbody.innerHTML = '';

    // Si no hay datos, muestra mensaje.
    if (!Array.isArray(list) || list.length === 0) {
      setEmpty('No hay animales para mostrar');
      return;
    }

    // Recorre la lista y crea una fila por animal.
    // "No." es un número de fila: 1..N, NO es el id real de la BD.
    list.forEach((a, idx) => {
      const tr = document.createElement('tr');

      // Columna No. (se recalcula cada vez que renderizas/filtras).
      const tdNo = document.createElement('td');
      tdNo.textContent = String(idx + 1);

      // Nombre.
      const tdNombre = document.createElement('td');
      tdNombre.textContent = a.nombre ?? '';

      // Especie (nombre). Nota: el backend devuelve "especie" por JOIN.
      const tdEspecie = document.createElement('td');
      tdEspecie.textContent = a.especie ?? '';

      // Fecha nacimiento.
      const tdFN = document.createElement('td');
      tdFN.textContent = a.fechanacimiento ?? '';

      // Edad calculada (no viene de la BD).
      const tdEdad = document.createElement('td');
      tdEdad.textContent = calcEdad(a.fechanacimiento);

      // Acciones (botones).
      const tdAcc = document.createElement('td');

      // Botón Ver → abre modal ver.
      const btnVer = document.createElement('button');
      btnVer.className = 'btn btn-sm btn-outline-primary me-2';
      btnVer.textContent = 'Ver';
      btnVer.addEventListener('click', () => openView(a.id));

      // Botón Editar → abre modal editar.
      const btnEdit = document.createElement('button');
      btnEdit.className = 'btn btn-sm btn-outline-warning me-2';
      btnEdit.textContent = 'Editar';
      btnEdit.addEventListener('click', () => openEdit(a.id));

      // Botón Eliminar → abre modal eliminar.
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

  // Aplica filtro en cliente (sin pedir a la API).
  function applyFilter() {
    const q = (searchInput.value || '').trim().toLowerCase();

    // Si no hay texto, se muestra todo.
    if (!q) {
      filtered = [...animals];
      renderTable(filtered);
      return;
    }

    // Filtra por nombre / especie (nombre) / fecha / edad (calculada).
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

  // -------- API calls (comunicación con el backend) ----------

  // GET para acciones que solo consultan (listar / obtener / listar_especies).
  async function apiGet(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const json = await res.json();

    // Si falla HTTP o la API responde ok:false, se lanza error.
    if (!res.ok || !json.ok) throw new Error(json.message || 'Error');

    return json.data;
  }

  // POST para acciones que modifican (insertar / editar / eliminar).
  async function apiPost(action, payload) {
    const res = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const json = await res.json();

    if (!res.ok || !json.ok) throw new Error(json.message || 'Error');

    return json.data;
  }

  // -------- Especies (catálogo) ----------

  // Limpia y llena un <select> con el catálogo de especies.
  // value = id (entero), label = nombre (texto).
  function fillEspecieSelect(select, list, selectedId = '') {
    if (!select) return;

    // Se reconstruyen las opciones para mantener consistencia.
    select.innerHTML = '';

    const optDefault = document.createElement('option');
    optDefault.value = '';
    optDefault.textContent = 'Seleccione una especie';
    select.appendChild(optDefault);

    list.forEach(e => {
      const opt = document.createElement('option');
      opt.value = String(e.id);
      opt.textContent = e.nombre ?? '';
      select.appendChild(opt);
    });

    // Se selecciona un valor si se solicitó (por ejemplo, al editar).
    if (selectedId !== null && selectedId !== undefined && String(selectedId) !== '') {
      select.value = String(selectedId);
    } else {
      select.value = '';
    }
  }

  // Carga el catálogo de especies desde la API y rellena los selects.
  async function cargarEspecies() {
    // Si ya se cargaron especies, no se vuelve a pedir (optimización simple).
    if (Array.isArray(especies) && especies.length > 0) {
      fillEspecieSelect(addEspecieSelect, especies);
      fillEspecieSelect(editEspecieSelect, especies);
      return;
    }

    const data = await apiGet(`${API_URL}?action=listar_especies`);
    especies = Array.isArray(data) ? data : [];

    fillEspecieSelect(addEspecieSelect, especies);
    fillEspecieSelect(editEspecieSelect, especies);
  }

  // -------- Animales ----------

  // Carga el listado de animales desde la API y pinta la tabla.
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

  // -------- Modales (abrir/ver/editar/eliminar) ----------

  // Abre modal "Ver".
  async function openView(id) {
    try {
      // Primero intenta obtenerlo desde memoria.
      // Si no está, lo pide a la API con action=obtener.
      const a = findById(id) || await apiGet(`${API_URL}?action=obtener&id=${encodeURIComponent(id)}`);

      document.getElementById('v_id').textContent = a.id ?? '';
      document.getElementById('v_nombre').textContent = a.nombre ?? '';
      document.getElementById('v_especie').textContent = a.especie ?? '';
      document.getElementById('v_fn').textContent = a.fechanacimiento ?? '';
      document.getElementById('v_edad').textContent = calcEdad(a.fechanacimiento);

      viewModal.show();
    } catch (err) {
      showAlert(err.message || 'Error abriendo detalle', 'danger');
    }
  }

  // Abre modal "Editar".
  async function openEdit(id) {
    const a = findById(id);
    if (!a) {
      showAlert('No se encontró el animal en memoria; se recargará el listado.', 'warning');
      await listarAnimales();
      return;
    }

    // Garantiza que el catálogo de especies esté disponible antes de seleccionar una opción.
    try {
      await cargarEspecies();
    } catch (err) {
      showAlert(err.message || 'No fue posible cargar el catálogo de especies.', 'danger');
      return;
    }

    // Evita escoger fecha futura (en input date).
    document.getElementById('e_fechanacimiento').max = todayYmd();

    // Rellena formulario de edición.
    document.getElementById('e_id').value = a.id;
    document.getElementById('e_id_text').textContent = a.id;
    document.getElementById('e_nombre').value = a.nombre ?? '';
    document.getElementById('e_fechanacimiento').value = a.fechanacimiento ?? '';

    // Selecciona especie_id actual del animal.
    // Nota: el backend devuelve especie_id además del nombre de especie.
    fillEspecieSelect(editEspecieSelect, especies, a.especie_id ?? '');

    document.getElementById('e_edad_calc').textContent = calcEdad(a.fechanacimiento);

    // Quita estados "invalid" antiguos.
    editForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    editModal.show();
  }

  // Abre modal "Eliminar".
  function openDelete(id) {
    const a = findById(id);
    if (!a) {
      showAlert('No se encontró el animal en memoria; se recargará el listado.', 'warning');
      listarAnimales();
      return;
    }

    document.getElementById('d_id').value = a.id;
    document.getElementById('d_id_text').textContent = a.id;
    document.getElementById('d_nombre').textContent = a.nombre ?? '';
    document.getElementById('d_especie').textContent = a.especie ?? '';

    deleteModal.show();
  }

  // -------- Validación (cliente) ----------

  // Valida texto requerido y longitud máxima.
  function validateText(input, maxLen = 100) {
    input.classList.remove('is-invalid');
    const v = (input.value || '').trim();
    if (!v || v.length > maxLen) {
      input.classList.add('is-invalid');
      return null;
    }
    return v;
  }

  // Valida que un select tenga una opción válida seleccionada (distinta de vacío).
  function validateSelect(select) {
    select.classList.remove('is-invalid');
    const v = (select.value || '').trim();
    if (!v) {
      select.classList.add('is-invalid');
      return null;
    }
    return v;
  }

  // Valida que fecha exista y no sea futura.
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

  // -------- Eventos (listeners) ----------

  // Cada vez que se escribe en buscar, se filtra en cliente.
  searchInput.addEventListener('input', applyFilter);

  // Click en "Agregar Animal" abre modal y prepara catálogo de especies.
  btnOpenAdd.addEventListener('click', async () => {
    addForm.reset();

    // Quita invalids anteriores.
    addForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    // Limita fecha máxima a hoy.
    document.getElementById('a_fechanacimiento').max = todayYmd();

    // Carga el catálogo de especies y rellena el select.
    // Si falla, se informa y no se abre el modal (para evitar insertar sin especie_id válido).
    try {
      // Nota: cargarEspecies() también rellena ambos selects.
      await cargarEspecies();
      // Asegura que el select de agregar quede sin selección.
      if (addEspecieSelect) addEspecieSelect.value = '';
    } catch (err) {
      showAlert(err.message || 'No fue posible cargar el catálogo de especies.', 'danger');
      return;
    }

    addModal.show();
  });

  // Submit del formulario Agregar.
  addForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Validación de inputs.
    const nombre = validateText(document.getElementById('a_nombre'));
    const especieIdStr = validateSelect(addEspecieSelect);
    const fn = validateDate(document.getElementById('a_fechanacimiento'));

    if (!nombre || !especieIdStr || !fn) return;

    // Convierte especie_id a número para enviar a la API.
    const especie_id = Number(especieIdStr);

    const btn = document.getElementById('btnAddSave');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
      // Inserción con FK: se envía especie_id (no se envía especie como texto).
      const inserted = await apiPost('insertar', { nombre, especie_id, fechanacimiento: fn });

      // El backend devuelve el registro con JOIN (incluye especie y especie_id).
      animals.unshift(inserted);

      addModal.hide();
      showAlert('Animal agregado correctamente.', 'success');

      // Limpia búsqueda y repinta tabla.
      searchInput.value = '';
      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error insertando.', 'danger');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Guardar';
    }
  });

  // Al cambiar la fecha en el modal Editar, recalcula edad en vivo.
  document.getElementById('e_fechanacimiento').addEventListener('input', (e) => {
    document.getElementById('e_edad_calc').textContent = calcEdad(e.target.value);
  });

  // Submit del formulario Editar.
  editForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('e_id').value;

    const nombre = validateText(document.getElementById('e_nombre'));
    const especieIdStr = validateSelect(editEspecieSelect);
    const fn = validateDate(document.getElementById('e_fechanacimiento'));

    if (!id || !nombre || !especieIdStr || !fn) return;

    const especie_id = Number(especieIdStr);

    const btn = document.getElementById('btnEditSave');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
      // Edición con FK: se envía especie_id.
      const updated = await apiPost('editar', { id, nombre, especie_id, fechanacimiento: fn });

      // Actualiza el array animals reemplazando el registro editado.
      animals = animals.map(a => String(a.id) === String(updated.id) ? updated : a);

      editModal.hide();
      showAlert('Animal editado correctamente.', 'success');

      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error editando.', 'danger');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Guardar cambios';
    }
  });

  // Submit del formulario Eliminar.
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
      showAlert('Animal eliminado correctamente.', 'success');

      // Repinta y renumera la columna "No.".
      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error eliminando.', 'danger');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Sí, eliminar';
    }
  });

  // Carga inicial al finalizar el load del navegador.
  // Se carga primero el catálogo de especies (para selects) y luego la tabla de animales.
  window.addEventListener('load', async () => {
    try {
      await cargarEspecies();
    } catch (err) {
      // Si falla el catálogo, igualmente se intenta listar animales.
      showAlert(err.message || 'No fue posible cargar el catálogo de especies.', 'warning');
    } finally {
      listarAnimales();
    }
  });

})(); // Fin de la IIFE: el código se ejecuta automáticamente y no deja variables globales.
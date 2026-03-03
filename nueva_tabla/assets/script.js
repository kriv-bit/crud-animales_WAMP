(() => {
  // URL de tu API (backend PHP) que responde JSON
  const API_URL = 'api/algo.php';

  // Arreglo principal con TODOS los animales (incluye el id real de la BD)
  let animals = [];

  // Arreglo que se muestra en pantalla (puede ser filtrado por el buscador)
  let filtered = [];

  // Referencias a elementos del DOM (HTML)
  const tbody = document.getElementById('animalsBody');   // <tbody> donde se pintan filas
  const searchInput = document.getElementById('searchInput'); // input de búsqueda
  const alertBox = document.getElementById('alertBox');   // contenedor de alertas

  // Instancias de los modales de Bootstrap (para abrir/cerrar)
  const addModal = new bootstrap.Modal(document.getElementById('addModal'));
  const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
  const editModal = new bootstrap.Modal(document.getElementById('editModal'));
  const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

  // Botón "Agregar Animal" y formularios de los modales
  const btnOpenAdd = document.getElementById('btnOpenAdd');
  const addForm = document.getElementById('addForm');
  const editForm = document.getElementById('editForm');
  const deleteForm = document.getElementById('deleteForm');

  // -------- Helpers (funciones de apoyo) ----------

  // Escapa texto para evitar que alguien meta HTML/JS y rompa la página (XSS)
  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  // Muestra una alerta Bootstrap arriba (success, danger, warning, etc.)
  function showAlert(message, type = 'success') {
    alertBox.innerHTML = `
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    `;
  }

  // Devuelve la fecha de hoy en formato YYYY-MM-DD (para usar en input type="date")
  function todayYmd() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  // Calcula edad (años) a partir de fechanacimiento "YYYY-MM-DD"
  // Ojo: edad NO está guardada en BD, se calcula en el navegador.
  function calcEdad(fechaYmd) {
    // Valida formato básico
    if (!fechaYmd || !/^\d{4}-\d{2}-\d{2}$/.test(fechaYmd)) return '';

    // Convierte string a números
    const [y, m, d] = fechaYmd.split('-').map(Number);

    // Crea fecha de nacimiento
    const born = new Date(y, m - 1, d);
    if (isNaN(born.getTime())) return '';

    // Calcula diferencia de años y ajusta si aún no ha cumplido este año
    const today = new Date();
    let age = today.getFullYear() - born.getFullYear();
    const mm = today.getMonth() - born.getMonth();
    if (mm < 0 || (mm === 0 && today.getDate() < born.getDate())) age--;

    // Si por alguna razón queda negativa (fecha futura), devuelve vacío
    return (age < 0) ? '' : String(age);
  }

  // Pone mensaje "Cargando..." en la tabla
  function setLoading() {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Cargando...</td></tr>`;
  }

  // Pone mensaje "vacío" en la tabla con texto personalizado
  function setEmpty(msg = 'Sin resultados') {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">${escapeHtml(msg)}</td></tr>`;
  }

  // Busca un animal por id real dentro del array animals
  function findById(id) {
    return animals.find(a => String(a.id) === String(id)) || null;
  }

  // -------- Render (pintar tabla) ----------

  // Renderiza la tabla con la lista que le pases (filtered o animals)
  function renderTable(list) {
    tbody.innerHTML = '';

    // Si no hay datos, muestra mensaje
    if (!Array.isArray(list) || list.length === 0) {
      setEmpty('No hay animales para mostrar');
      return;
    }

    // Recorre la lista y crea una fila por animal
    // "No." es un número de fila (ID falso): 1..N, NO es el id real de la BD
    list.forEach((a, idx) => {
      const tr = document.createElement('tr');

      // Columna No. (se recalcula cada vez que renderizas/filtras)
      const tdNo = document.createElement('td');
      tdNo.textContent = String(idx + 1);

      // Nombre
      const tdNombre = document.createElement('td');
      tdNombre.textContent = a.nombre ?? '';

      // Especie
      const tdEspecie = document.createElement('td');
      tdEspecie.textContent = a.especie ?? '';

      // Fecha nacimiento
      const tdFN = document.createElement('td');
      tdFN.textContent = a.fechanacimiento ?? '';

      // Edad calculada (no viene de la BD)
      const tdEdad = document.createElement('td');
      tdEdad.textContent = calcEdad(a.fechanacimiento);

      // Acciones (botones)
      const tdAcc = document.createElement('td');

      // Botón Ver → abre modal ver
      const btnVer = document.createElement('button');
      btnVer.className = 'btn btn-sm btn-outline-primary me-2';
      btnVer.textContent = 'Ver';
      btnVer.addEventListener('click', () => openView(a.id));

      // Botón Editar → abre modal editar
      const btnEdit = document.createElement('button');
      btnEdit.className = 'btn btn-sm btn-outline-warning me-2';
      btnEdit.textContent = 'Editar';
      btnEdit.addEventListener('click', () => openEdit(a.id));

      // Botón Eliminar → abre modal eliminar
      const btnDel = document.createElement('button');
      btnDel.className = 'btn btn-sm btn-outline-danger';
      btnDel.textContent = 'Eliminar';
      btnDel.addEventListener('click', () => openDelete(a.id));

      // Se agregan botones a la celda de acciones
      tdAcc.appendChild(btnVer);
      tdAcc.appendChild(btnEdit);
      tdAcc.appendChild(btnDel);

      // Se agregan celdas a la fila
      tr.appendChild(tdNo);
      tr.appendChild(tdNombre);
      tr.appendChild(tdEspecie);
      tr.appendChild(tdFN);
      tr.appendChild(tdEdad);
      tr.appendChild(tdAcc);

      // Se agrega fila al tbody
      tbody.appendChild(tr);
    });
  }

  // Aplica filtro en cliente (sin pedir a la API)
  function applyFilter() {
    const q = (searchInput.value || '').trim().toLowerCase();

    // Si no hay texto, se muestra todo
    if (!q) {
      filtered = [...animals];
      renderTable(filtered);
      return;
    }

    // Filtra por nombre/especie/fecha/edad (edad calculada)
    filtered = animals.filter(a => {
      const nombre = String(a.nombre ?? '').toLowerCase();
      const especie = String(a.especie ?? '').toLowerCase();
      const fn = String(a.fechanacimiento ?? '').toLowerCase();
      const edad = calcEdad(a.fechanacimiento).toLowerCase();

      return nombre.includes(q) || especie.includes(q) || fn.includes(q) || edad.includes(q);
    });

    // Si no hay coincidencias, muestra mensaje
    if (filtered.length === 0) {
      setEmpty('No hay coincidencias');
      return;
    }

    // Renderiza con resultados filtrados
    renderTable(filtered);
  }

  // -------- API calls (comunicación con el backend) ----------

  // GET para acciones que solo consultan (listar/obtener)
  async function apiGet(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    const json = await res.json();

    // Si falla HTTP o la API responde ok:false → se lanza error
    if (!res.ok || !json.ok) throw new Error(json.message || 'Error');

    // Devuelve solo la parte data
    return json.data;
  }

  // POST para acciones que modifican (insertar/editar/eliminar)
  async function apiPost(action, payload) {
    const res = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json', // el body va como JSON
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload) // convierte objeto JS a string JSON
    });

    const json = await res.json();

    // Si falla HTTP o la API responde ok:false → error
    if (!res.ok || !json.ok) throw new Error(json.message || 'Error');

    // Devuelve solo data (por ejemplo, el animal insertado/actualizado)
    return json.data;
  }

  // Carga el listado desde la API y pinta la tabla
  async function listarAnimales() {
    try {
      setLoading(); // muestra "Cargando..."
      const data = await apiGet(`${API_URL}?action=listar`);

      // Asegura que sea array
      animals = Array.isArray(data) ? data : [];
      filtered = [...animals];

      renderTable(filtered);
    } catch (err) {
      setEmpty('Error cargando datos');
      showAlert(err.message || 'Error desconocido', 'danger');
    }
  }

  // -------- Modales (abrir/ver/editar/eliminar) ----------

  // Abre modal "Ver"
  async function openView(id) {
    try {
      // Primero intenta obtenerlo desde memoria (animals)
      // Si no está, entonces lo pide a la API con action=obtener
      const a = findById(id) || await apiGet(`${API_URL}?action=obtener&id=${encodeURIComponent(id)}`);

      // Rellena campos del modal Ver
      document.getElementById('v_id').textContent = a.id ?? '';
      document.getElementById('v_nombre').textContent = a.nombre ?? '';
      document.getElementById('v_especie').textContent = a.especie ?? '';
      document.getElementById('v_fn').textContent = a.fechanacimiento ?? '';
      document.getElementById('v_edad').textContent = calcEdad(a.fechanacimiento); // edad calculada

      // Muestra modal
      viewModal.show();
    } catch (err) {
      showAlert(err.message || 'Error abriendo detalle', 'danger');
    }
  }

  // Abre modal "Editar"
  function openEdit(id) {
    // Solo usa datos en memoria
    const a = findById(id);

    // Si no existe en memoria, fuerza recarga
    if (!a) {
      showAlert('No se encontró el animal en memoria, recargando...', 'warning');
      listarAnimales();
      return;
    }

    // Evita escoger fecha futura (en input date)
    document.getElementById('e_fechanacimiento').max = todayYmd();

    // Rellena formulario de edición
    document.getElementById('e_id').value = a.id;              // hidden
    document.getElementById('e_id_text').textContent = a.id;   // texto visible
    document.getElementById('e_nombre').value = a.nombre ?? '';
    document.getElementById('e_especie').value = a.especie ?? '';
    document.getElementById('e_fechanacimiento').value = a.fechanacimiento ?? '';
    document.getElementById('e_edad_calc').textContent = calcEdad(a.fechanacimiento);

    // Quita estados "invalid" antiguos
    editForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    // Muestra modal editar
    editModal.show();
  }

  // Abre modal "Eliminar"
  function openDelete(id) {
    const a = findById(id);

    // Si no existe en memoria, recarga
    if (!a) {
      showAlert('No se encontró el animal en memoria, recargando...', 'warning');
      listarAnimales();
      return;
    }

    // Rellena modal eliminar (hidden id + info)
    document.getElementById('d_id').value = a.id;
    document.getElementById('d_id_text').textContent = a.id;
    document.getElementById('d_nombre').textContent = a.nombre ?? '';
    document.getElementById('d_especie').textContent = a.especie ?? '';

    // Muestra modal eliminar
    deleteModal.show();
  }

  // -------- Validación (cliente) ----------

  // Valida texto requerido y largo máximo
  function validateText(input, maxLen = 100) {
    input.classList.remove('is-invalid');
    const v = (input.value || '').trim();

    // Si está vacío o se pasa de longitud → inválido
    if (!v || v.length > maxLen) {
      input.classList.add('is-invalid');
      return null;
    }
    return v;
  }

  // Valida que fecha exista y no sea futura
  function validateDate(input) {
    input.classList.remove('is-invalid');
    const v = input.value;

    // Debe existir
    if (!v) {
      input.classList.add('is-invalid');
      return null;
    }

    // Convierte a Date (00:00) y compara con hoy (00:00)
    const chosen = new Date(v + 'T00:00:00');
    const today = new Date();
    const today0 = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    // Si es inválida o mayor que hoy → inválida
    if (isNaN(chosen.getTime()) || chosen > today0) {
      input.classList.add('is-invalid');
      return null;
    }

    return v;
  }

  // -------- Eventos (listeners) ----------

  // Cada vez que escribes en buscar → filtra en cliente
  searchInput.addEventListener('input', applyFilter);

  // Click en "+ Agregar Animal" → abre modal agregar
  btnOpenAdd.addEventListener('click', () => {
    addForm.reset(); // limpia el formulario

    // Quita invalids anteriores
    addForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    // Limita fecha máxima a hoy
    document.getElementById('a_fechanacimiento').max = todayYmd();

    // Abre modal
    addModal.show();
  });

  // Submit del formulario Agregar
  addForm.addEventListener('submit', async (e) => {
    e.preventDefault(); // evita recargar página

    // Validación de inputs
    const nombre = validateText(document.getElementById('a_nombre'));
    const especie = validateText(document.getElementById('a_especie'));
    const fn = validateDate(document.getElementById('a_fechanacimiento'));

    // Si algo falla, no envía
    if (!nombre || !especie || !fn) return;

    // UI: deshabilitar botón mientras guarda
    const btn = document.getElementById('btnAddSave');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
      // Llama a la API para insertar
      const inserted = await apiPost('insertar', { nombre, especie, fechanacimiento: fn });

      // Como el listado viene DESC por id, lo ponemos al inicio del array
      animals.unshift(inserted);

      // Cierra modal
      addModal.hide();

      // Mensaje al usuario
      showAlert('Animal agregado ✅', 'success');

      // Limpia búsqueda y repinta tabla completa
      searchInput.value = '';
      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error insertando', 'danger');
    } finally {
      // UI: reactivar botón
      btn.disabled = false;
      btn.textContent = 'Guardar';
    }
  });

  // Cuando cambias la fecha en el modal Editar → recalcula edad en vivo
  document.getElementById('e_fechanacimiento').addEventListener('input', (e) => {
    document.getElementById('e_edad_calc').textContent = calcEdad(e.target.value);
  });

  // Submit del formulario Editar
  editForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Toma id real oculto + valida campos
    const id = document.getElementById('e_id').value;
    const nombre = validateText(document.getElementById('e_nombre'));
    const especie = validateText(document.getElementById('e_especie'));
    const fn = validateDate(document.getElementById('e_fechanacimiento'));

    // Si algo falla, no envía
    if (!id || !nombre || !especie || !fn) return;

    // UI: botón en modo guardando
    const btn = document.getElementById('btnEditSave');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
      // Llama a la API para editar
      const updated = await apiPost('editar', { id, nombre, especie, fechanacimiento: fn });

      // Actualiza el array animals reemplazando el registro editado
      animals = animals.map(a => String(a.id) === String(updated.id) ? updated : a);

      // Cierra modal y alerta
      editModal.hide();
      showAlert('Animal editado ✅', 'success');

      // Repinta respetando filtro actual
      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error editando', 'danger');
    } finally {
      // UI: restaurar botón
      btn.disabled = false;
      btn.textContent = 'Guardar cambios';
    }
  });

  // Submit del formulario Eliminar
  deleteForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Toma el id real oculto
    const id = document.getElementById('d_id').value;
    if (!id) return;

    // UI: botón en modo eliminando
    const btn = document.getElementById('btnDeleteConfirm');
    btn.disabled = true;
    btn.textContent = 'Eliminando...';

    try {
      // Llama a la API para eliminar
      await apiPost('eliminar', { id });

      // Quita del array animals el que se eliminó
      animals = animals.filter(a => String(a.id) !== String(id));

      // Cierra modal y alerta
      deleteModal.hide();
      showAlert('Animal eliminado ✅', 'success');

      // Repinta y renumera la columna "No."
      applyFilter();
    } catch (err) {
      showAlert(err.message || 'Error eliminando', 'danger');
    } finally {
      // UI: restaurar botón
      btn.disabled = false;
      btn.textContent = 'Sí, eliminar';
    }
  });

  // Cuando el navegador termina de cargar todo → pide listado a la API
  window.addEventListener('load', () => {
    listarAnimales();
  });

})(); // fin de la IIFE: el código se ejecuta automáticamente y no deja variables globales
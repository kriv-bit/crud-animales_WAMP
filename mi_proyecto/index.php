<?php
include __DIR__ . "/config/db.php";

$buscar = $_GET["buscar"] ?? "";

if ($buscar !== "") {
    $sql = "SELECT * FROM animales
            WHERE nombre LIKE :buscar OR especie LIKE :buscar
            ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["buscar" => "%$buscar%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM animales ORDER BY id ASC");
}

$animales = $stmt->fetchAll();

include __DIR__ . "/templates/header.php";
?>



<div class="container-xxl py-5">
    <!-- Topbar -->
    <div class="topbar mb-4">
        <div class="d-flex align-items-start gap-3">
            <div class="brand-badge">🐾</div>

            <div>
                <h1 class="page-title mb-1">Animales</h1>
                <div class="page-subtitle">
                    Gestiona el registro: agrega, edita, elimina y busca por nombre o especie.
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="count-pill">
                Total <span class="count-pill__num"><?= count($animales) ?></span>
            </div>

            <button
                class="btn btn-primary btn-lg btn-soft"
                data-bs-toggle="modal"
                data-bs-target="#modalAgregar"
                type="button"
            >
                <span class="me-2">＋</span> Nuevo
            </button>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card card-glass">
        <div class="card-body p-4 p-md-4">
            <!-- Toolbar -->
            <div class="toolbar mb-3">
                <form method="GET" class="flex-grow-1">
                    <div class="search">
                        <span class="search__icon">⌕</span>
                        <input
                            class="form-control form-control-lg search__input"
                            name="buscar"
                            placeholder="Buscar por nombre o especie…"
                            value="<?= htmlspecialchars($buscar) ?>"
                        />
                        <?php if ($buscar !== ""): ?>
                            <a class="search__clear" href="index.php" title="Limpiar">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="toolbar-actions">
                    <button
                        class="btn btn-outline-secondary btn-lg btn-soft"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalAgregar"
                    >
                        Agregar rápido
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-wrap">
                <table class="table table-clean align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 90px;">ID</th>
                            <th>Nombre</th>
                            <th>Especie</th>
                            <th style="width: 130px;">Edad</th>
                            <th style="width: 160px;" class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($animales) === 0): ?>
                            <tr>
                                <td colspan="5" class="empty">
                                    <div class="empty__title">No hay animales todavía</div>
                                    <div class="empty__subtitle">Crea el primero con “Nuevo”.</div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($animales as $a): ?>
                            <?php
                                $jsNombre  = json_encode($a["nombre"], JSON_UNESCAPED_UNICODE);
                                $jsEspecie = json_encode($a["especie"], JSON_UNESCAPED_UNICODE);
                            ?>
                            <tr class="row-clean">
                                <td>
                                    <span class="id-chip">#<?= (int)$a["id"] ?></span>
                                </td>

                                <td class="fw-semibold text-slate">
                                    <?= htmlspecialchars($a["nombre"]) ?>
                                </td>

                                <td>
                                    <span class="tag">
                                        <?= htmlspecialchars($a["especie"]) ?>
                                    </span>
                                </td>

                                <td class="text-slate">
                                    <?= (int)$a["edad"] ?> <span class="muted">años</span>
                                </td>

                                <td class="text-end">
                                    <div class="actions">
                                    <button
                                        class="btn btn-ghost"
                                        type="button"
                                        title="Editar"
                                        onclick='editarAnimal(
                                            <?= (int)$a["id"] ?>,
                                            <?= json_encode($a["nombre"]) ?>,
                                            <?= json_encode($a["especie"]) ?>,
                                            <?= (int)$a["edad"] ?>
                                        )'
                                    >
                                        ✏️
                                    </button>


                                        <a
                                            class="btn btn-ghost danger"
                                            title="Eliminar"
                                            href="actions/delete.php?id=<?= (int)$a['id'] ?>"
                                            onclick="return confirm('¿Eliminar este animal?');"
                                        >
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAgregar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-soft">
            <form method="POST" action="actions/create.php">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title mb-1">Nuevo animal</h5>
                        <div class="muted">Registra un animal en la base de datos.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input class="form-control form-control-lg" name="nombre" placeholder="Ej: Luna" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Especie</label>
                        <input class="form-control form-control-lg" name="especie" placeholder="Ej: Gato" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Edad</label>
                        <input class="form-control form-control-lg" name="edad" type="number" min="0" placeholder="Ej: 3" required>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-lg btn-soft" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button class="btn btn-primary btn-lg btn-soft">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-soft">
            <form method="POST" action="actions/update.php">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title mb-1">Editar animal</h5>
                        <div class="muted">Modifica nombre, especie o edad (ID no cambia).</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body pt-3">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input class="form-control form-control-lg" name="nombre" id="edit_nombre" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Especie</label>
                        <input class="form-control form-control-lg" name="especie" id="edit_especie" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Edad</label>
                        <input class="form-control form-control-lg" name="edad" id="edit_edad" type="number" min="0" required>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-lg btn-soft" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button class="btn btn-primary btn-lg btn-soft">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . "/templates/footer.php"; ?>

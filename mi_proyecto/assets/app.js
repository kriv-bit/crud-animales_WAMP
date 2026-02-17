function editarAnimal(id, nombre, especie, edad) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_nombre").value = nombre;
    document.getElementById("edit_especie").value = especie;
    document.getElementById("edit_edad").value = edad;

    const modal = new bootstrap.Modal(document.getElementById("modalEditar"));
    modal.show();
}
(function () {
  'use strict';

  const tabla = document.getElementById('categorias-filas');
  const alerta = document.getElementById('categorias-alerta');
  const modalElemento = document.getElementById('modalCategoria');
  const formulario = document.getElementById('formCategoria');
  const campoId = document.getElementById('categoria_id');
  const campoNombre = document.getElementById('categoria_nombre');
  const campoDescripcion = document.getElementById('categoria_descripcion');
  const titulo = document.getElementById('modalCategoriaTitulo');
  const botonGuardar = document.getElementById('btnGuardarCategoria');
  const alertaModal = document.getElementById('categoria-modal-alerta');
  const modal = window.bootstrap && modalElemento ? new window.bootstrap.Modal(modalElemento) : null;

  function abrirModal(categoria) {
    window.SoscurApi.clearAlert(alertaModal);
    formulario.reset();
    campoId.value = categoria ? String(categoria.id) : '';
    campoNombre.value = categoria ? categoria.nombre : '';
    campoDescripcion.value = categoria ? categoria.descripcion : '';
    titulo.textContent = categoria ? 'Editar categoría' : 'Nueva categoría';
    modal.show();
  }

  if (tabla) {
    tabla.addEventListener('click', function (evento) {
      const botonEditar = evento.target.closest('.js-editar-categoria');
      const botonEliminar = evento.target.closest('.js-eliminar-categoria');

      if (botonEditar) {
        const fila = botonEditar.closest('tr');
        abrirModal({
          id: fila.dataset.categoriaId,
          nombre: fila.dataset.nombre,
          descripcion: fila.dataset.descripcion
        });
        return;
      }

      if (botonEliminar) {
        const fila = botonEliminar.closest('tr');
        const nombre = fila.dataset.nombre;

        window.SoscurConfirm({
          titulo: 'Eliminar categoría',
          mensaje: '¿Eliminar la categoría "' + nombre + '"? Esta acción no se puede deshacer.',
          aceptar: 'Eliminar'
        }).then(function (confirmado) {
          if (!confirmado) {
            return;
          }

          window.SoscurApi.request('/categorias/' + fila.dataset.categoriaId, { method: 'DELETE' })
            .then(function () {
              window.location.reload();
            })
            .catch(function (error) {
              window.SoscurApi.showError(alerta, error);
            });
        });
      }
    });
  }

  if (formulario) {
    formulario.addEventListener('submit', function (evento) {
      evento.preventDefault();
      window.SoscurApi.clearAlert(alertaModal);
      window.SoscurApi.setLoading(botonGuardar, true, 'Guardando...');

      const id = campoId.value;
      const cuerpo = {
        nombre: campoNombre.value,
        descripcion: campoDescripcion.value
      };
      const promesa = id === ''
        ? window.SoscurApi.request('/categorias', { method: 'POST', body: cuerpo })
        : window.SoscurApi.request('/categorias/' + id, { method: 'PATCH', body: cuerpo });

      promesa
        .then(function () {
          window.location.reload();
        })
        .catch(function (error) {
          window.SoscurApi.showError(alertaModal, error);
        })
        .finally(function () {
          window.SoscurApi.setLoading(botonGuardar, false);
        });
    });
  }
})();

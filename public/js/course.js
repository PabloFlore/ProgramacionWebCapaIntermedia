(function () {
  'use strict';

  const api = window.SoscurApi;
  const root = document.getElementById('curso-detalle');
  const curso = window.SOSCUR_CURSO;

  if (!api || !root || !curso) {
    return;
  }

  const autenticado = root.dataset.autenticado === '1';
  const cursoId = Number(root.dataset.cursoId);
  const elementos = {
    alerta: document.getElementById('detalle-alerta'),
    compraAlerta: document.getElementById('compra-alerta'),
    formulario: document.getElementById('compra-formulario'),
    metodo: document.getElementById('metodo-pago'),
    nivel: document.getElementById('nivel-compra'),
    comprar: document.getElementById('btn-comprar'),
    pendiente: document.getElementById('pago-pendiente'),
    pagoId: document.getElementById('pago-id'),
    referencia: document.getElementById('referencia-pago'),
    confirmar: document.getElementById('btn-confirmar-pago'),
    diploma: document.getElementById('diploma-enlace'),
    niveles: Array.from(document.querySelectorAll('.btn-completar-nivel'))
  };

  const estado = {
    inscripcionId: null,
    pagoId: null,
    completado: false
  };

  function mostrarPendiente(referencia, pagoId) {
    if (!elementos.pendiente) {
      return;
    }

    estado.pagoId = pagoId;
    elementos.referencia.textContent = referencia || '';
    elementos.pagoId.textContent = pagoId === null || pagoId === undefined ? '' : String(pagoId);
    elementos.pendiente.classList.remove('d-none');
  }

  function ocultarPendiente() {
    if (!elementos.pendiente) {
      return;
    }

    elementos.pendiente.classList.add('d-none');
    estado.pagoId = null;
  }

  function habilitarNiveles(habilitado) {
    elementos.niveles.forEach(function (boton) {
      boton.disabled = !habilitado;
    });
  }

  function mostrarDiploma(inscripcionId) {
    if (!elementos.diploma) {
      return;
    }

    elementos.diploma.href = api.asset('diploma/' + inscripcionId);
    elementos.diploma.classList.remove('d-none');
  }

  async function cargarMetodosPago() {
    if (!elementos.metodo) {
      return;
    }

    try {
      const respuesta = await api.request('/metodos-pago');
      const metodos = respuesta && Array.isArray(respuesta.data) ? respuesta.data : [];
      elementos.metodo.innerHTML = '';

      if (metodos.length === 0) {
        const opcion = document.createElement('option');
        opcion.value = '';
        opcion.textContent = 'No hay métodos disponibles';
        elementos.metodo.appendChild(opcion);
        return;
      }

      metodos.forEach(function (metodo) {
        const opcion = document.createElement('option');
        opcion.value = String(metodo.metodo_pago_id);
        opcion.textContent = metodo.nombre;
        elementos.metodo.appendChild(opcion);
      });
    } catch (error) {
      api.showError(elementos.compraAlerta, error);
    }
  }

  async function cargarInscripcion() {
    if (!autenticado) {
      return;
    }

    try {
      const respuesta = await api.request('/mis-cursos');
      const lista = respuesta && Array.isArray(respuesta.data) ? respuesta.data : [];
      const inscripcion = lista.find(function (item) {
        return Number(item.curso_id) === cursoId;
      });

      if (!inscripcion) {
        return;
      }

      estado.inscripcionId = Number(inscripcion.inscripcion_id);

      if (inscripcion.estado_pago === 'pagado') {
        if (elementos.formulario) {
          elementos.formulario.classList.add('d-none');
        }

        habilitarNiveles(true);

        if (inscripcion.completado) {
          estado.completado = true;
          mostrarDiploma(estado.inscripcionId);
        }

        return;
      }

      if (inscripcion.estado_pago === 'pendiente') {
        mostrarPendiente(inscripcion.referencia_pago, inscripcion.pago_id);
      }
    } catch (error) {
      api.showError(elementos.alerta, error);
    }
  }

  async function comprar() {
    if (!elementos.metodo || elementos.metodo.value === '') {
      api.showAlert(elementos.compraAlerta, 'warning', 'Selecciona un método de pago.');
      return;
    }

    const cuerpo = {
      curso_id: cursoId,
      metodo_pago_id: Number(elementos.metodo.value)
    };

    if (elementos.nivel && elementos.nivel.value !== '') {
      cuerpo.nivel_id = Number(elementos.nivel.value);
    }

    api.clearAlert(elementos.compraAlerta);
    api.setLoading(elementos.comprar, true, 'Procesando...');

    try {
      const respuesta = await api.request('/inscripciones', { method: 'POST', body: cuerpo });
      estado.inscripcionId = Number(respuesta.inscripcion_id);
      api.showAlert(elementos.compraAlerta, 'success', respuesta.message);

      if (respuesta.pendiente) {
        await cargarInscripcion();
        return;
      }

      mostrarPendiente(respuesta.referencia, respuesta.pago_id);
    } catch (error) {
      api.showError(elementos.compraAlerta, error);
    } finally {
      api.setLoading(elementos.comprar, false);
    }
  }

  async function confirmarPago() {
    if (estado.pagoId === null) {
      api.showAlert(elementos.compraAlerta, 'danger', 'No se encontró el pago pendiente. Recarga la página.');
      return;
    }

    api.clearAlert(elementos.compraAlerta);
    api.setLoading(elementos.confirmar, true, 'Confirmando...');

    try {
      const respuesta = await api.request('/pagos/' + estado.pagoId + '/confirmar', { method: 'POST' });
      estado.inscripcionId = Number(respuesta.inscripcion_id);
      api.showAlert(elementos.compraAlerta, 'success', respuesta.message);
      ocultarPendiente();
      habilitarNiveles(true);
    } catch (error) {
      api.showError(elementos.compraAlerta, error);
    } finally {
      api.setLoading(elementos.confirmar, false);
    }
  }

  async function completarNivel(boton) {
    if (estado.inscripcionId === null) {
      api.showAlert(elementos.alerta, 'warning', 'Primero completa la compra del curso.');
      return;
    }

    const nivelId = Number(boton.dataset.nivelId);
    api.setLoading(boton, true, 'Guardando...');

    try {
      const respuesta = await api.request(
        '/inscripciones/' + estado.inscripcionId + '/niveles/' + nivelId + '/completar',
        { method: 'POST' }
      );

      api.showAlert(elementos.alerta, 'success', respuesta.message);
      boton.textContent = 'Completado';
      boton.classList.remove('btn-outline-success');
      boton.classList.add('btn-success');
      boton.disabled = true;

      if (respuesta.completado && respuesta.diploma) {
        estado.completado = true;
        mostrarDiploma(estado.inscripcionId);
      }
    } catch (error) {
      api.showError(elementos.alerta, error);
      api.setLoading(boton, false);
    }
  }

  if (elementos.comprar) {
    elementos.comprar.addEventListener('click', comprar);
  }

  if (elementos.confirmar) {
    elementos.confirmar.addEventListener('click', confirmarPago);
  }

  elementos.niveles.forEach(function (boton) {
    boton.addEventListener('click', function () {
      completarNivel(boton);
    });
  });

  if (autenticado) {
    cargarMetodosPago();
    cargarInscripcion();
  }
})();

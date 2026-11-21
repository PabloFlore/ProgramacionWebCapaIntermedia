(function () {
  'use strict';

  const api = window.SoscurApi;

  if (!api) {
    return;
  }

  const profileForm = document.getElementById('formPerfil');
  const passwordForm = document.getElementById('formContrasena');

  function manejarError(error, alertBox) {
    api.showError(alertBox, error);

    if (error && error.status === 401) {
      window.setTimeout(function () {
        api.redirect('login');
      }, 1200);
    }
  }

  function actualizarAvatar(path) {
    if (!path) {
      return;
    }

    const contenedor = document.getElementById('perfil-avatar');

    if (!contenedor) {
      return;
    }

    let imagen = document.getElementById('perfil-avatar-img');

    if (!imagen) {
      imagen = document.createElement('img');
      imagen.id = 'perfil-avatar-img';
      imagen.alt = 'Foto de perfil';
      imagen.className = 'rounded-circle';
      imagen.style.width = '110px';
      imagen.style.height = '110px';
      imagen.style.objectFit = 'cover';
      contenedor.innerHTML = '';
      contenedor.appendChild(imagen);
    }

    imagen.src = api.asset(path);
  }

  function actualizarDatosVisibles(usuario) {
    if (!usuario) {
      return;
    }

    const nombre = document.getElementById('perfil-nombre');
    const correo = document.getElementById('perfil-correo');
    const placeholder = document.getElementById('perfil-avatar-placeholder');

    if (nombre && usuario.nombre) {
      nombre.textContent = usuario.nombre;
    }

    if (correo && usuario.correo) {
      correo.textContent = usuario.correo;
    }

    if (placeholder && usuario.nombre) {
      placeholder.textContent = String(usuario.nombre).charAt(0).toUpperCase();
    }
  }

  if (profileForm) {
    const alertBox = document.getElementById('profile-alert');
    const submitButton = document.getElementById('btnGuardarPerfil');

    profileForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      api.clearAlert(alertBox);

      if (!profileForm.checkValidity()) {
        profileForm.reportValidity();
        return;
      }

      const nombre = profileForm.querySelector('#nombre');
      const genero = profileForm.querySelector('#genero');
      const fechaNacimiento = profileForm.querySelector('#fecha_nacimiento');
      const foto = profileForm.querySelector('#foto_perfil');

      api.setLoading(submitButton, true, 'Guardando...');

      try {
        const respuesta = await api.request('/perfil', {
          method: 'PATCH',
          body: {
            nombre: nombre ? nombre.value.trim() : '',
            genero: genero && genero.value !== '' ? genero.value : null,
            fecha_nacimiento: fechaNacimiento && fechaNacimiento.value !== ''
              ? fechaNacimiento.value
              : null
          }
        });

        const usuario = respuesta && respuesta.usuario ? respuesta.usuario : null;
        actualizarDatosVisibles(usuario);

        if (fechaNacimiento) {
          fechaNacimiento.value = usuario && usuario.fecha_nacimiento ? usuario.fecha_nacimiento : '';
        }

        let mensaje = respuesta && respuesta.mensaje
          ? respuesta.mensaje
          : 'Perfil actualizado correctamente.';

        if (foto && foto.files && foto.files.length > 0) {
          const datosFoto = new FormData();
          datosFoto.append('foto_perfil', foto.files[0]);

          try {
            const respuestaFoto = await api.request('/perfil/foto', {
              method: 'POST',
              body: datosFoto
            });

            const usuarioFoto = respuestaFoto && respuestaFoto.usuario ? respuestaFoto.usuario : null;

            if (usuarioFoto && usuarioFoto.foto_perfil) {
              actualizarAvatar(usuarioFoto.foto_perfil);
            }

            mensaje = 'Perfil y foto actualizados correctamente.';
            foto.value = '';
          } catch (errorFoto) {
            api.showAlert(alertBox, 'warning', [
              mensaje,
              'No se pudo actualizar la foto: ' + (errorFoto && errorFoto.message
                ? errorFoto.message
                : 'inténtalo de nuevo.')
            ]);
            return;
          }
        }

        api.showAlert(alertBox, 'success', mensaje);
      } catch (error) {
        manejarError(error, alertBox);
      } finally {
        api.setLoading(submitButton, false);
      }
    });
  }

  if (passwordForm) {
    const alertBox = document.getElementById('password-alert');
    const submitButton = document.getElementById('btnCambiarContrasena');

    passwordForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      api.clearAlert(alertBox);

      if (!passwordForm.checkValidity()) {
        passwordForm.reportValidity();
        return;
      }

      const actual = passwordForm.querySelector('#contrasena_actual');
      const nueva = passwordForm.querySelector('#contrasena_nueva');
      const confirmacion = passwordForm.querySelector('#contrasena_confirmacion');

      if (!nueva || !confirmacion || nueva.value !== confirmacion.value) {
        api.showAlert(alertBox, 'danger', 'Las contraseñas nuevas no coinciden.');

        if (confirmacion) {
          confirmacion.focus();
        }

        return;
      }

      api.setLoading(submitButton, true, 'Actualizando...');

      try {
        const respuesta = await api.request('/perfil/contrasena', {
          method: 'POST',
          body: {
            contrasena_actual: actual ? actual.value : '',
            contrasena_nueva: nueva.value,
            contrasena_confirmacion: confirmacion.value
          }
        });

        api.showAlert(alertBox, 'success', respuesta && respuesta.mensaje
          ? respuesta.mensaje
          : 'Contraseña actualizada correctamente.');

        passwordForm.reset();
      } catch (error) {
        api.showError(alertBox, error);
      } finally {
        api.setLoading(submitButton, false);
      }
    });
  }
})();

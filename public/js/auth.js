(function () {
  'use strict';

  const api = window.SoscurApi;

  if (!api) {
    return;
  }

  const loginForm = document.getElementById('formLogin');
  const registerForm = document.getElementById('formRegistro');
  const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/s;

  if (loginForm) {
    const alertBox = document.getElementById('login-alert');
    const submitButton = document.getElementById('btnLogin');

    loginForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      api.clearAlert(alertBox);

      if (!loginForm.checkValidity()) {
        loginForm.reportValidity();
        return;
      }

      const correo = loginForm.querySelector('#correo');
      const contrasena = loginForm.querySelector('#contrasena');

      api.setLoading(submitButton, true, 'Entrando...');

      try {
        const respuesta = await api.request('/login', {
          method: 'POST',
          body: {
            correo: correo ? correo.value.trim() : '',
            contrasena: contrasena ? contrasena.value : ''
          }
        });

        api.showAlert(alertBox, 'success', respuesta && respuesta.mensaje
          ? respuesta.mensaje
          : 'Sesión iniciada correctamente.');

        window.setTimeout(function () {
          api.redirect('dashboard');
        }, 350);
      } catch (error) {
        api.showError(alertBox, error);

        if (contrasena) {
          contrasena.focus();
          contrasena.select();
        }
      } finally {
        api.setLoading(submitButton, false);
      }
    });
  }

  if (registerForm) {
    const alertBox = document.getElementById('register-alert');
    const submitButton = document.getElementById('btnCrearCuenta');

    registerForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      api.clearAlert(alertBox);

      if (!registerForm.checkValidity()) {
        registerForm.reportValidity();
        return;
      }

      const contrasena = registerForm.querySelector('#contrasena');
      const confirmacion = registerForm.querySelector('#contrasena_confirmacion');
      const valorContrasena = contrasena ? contrasena.value : '';
      const valorConfirmacion = confirmacion ? confirmacion.value : '';

      if (!passwordPattern.test(valorContrasena)) {
        api.showAlert(alertBox, 'danger', [
          'La contraseña debe tener al menos 8 caracteres.',
          'Debe incluir una mayúscula, una minúscula, un número y un carácter especial.'
        ]);

        if (contrasena) {
          contrasena.focus();
        }

        return;
      }

      if (valorContrasena !== valorConfirmacion) {
        api.showAlert(alertBox, 'danger', 'Las contraseñas no coinciden.');

        if (confirmacion) {
          confirmacion.focus();
        }

        return;
      }

      const datos = new FormData(registerForm);
      api.setLoading(submitButton, true, 'Creando cuenta...');

      try {
        const respuesta = await api.request('/registro', {
          method: 'POST',
          body: datos
        });

        api.showAlert(alertBox, 'success', respuesta && respuesta.mensaje
          ? respuesta.mensaje
          : 'Cuenta creada correctamente.');

        registerForm.reset();

        window.setTimeout(function () {
          api.redirect('dashboard');
        }, 450);
      } catch (error) {
        api.showError(alertBox, error);
      } finally {
        api.setLoading(submitButton, false);
      }
    });
  }
})();

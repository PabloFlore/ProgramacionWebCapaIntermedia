(function () {
  var form = document.getElementById('form-reportes');
  if (!form) {
    return;
  }

  var desde = document.getElementById('desde');
  var hasta = document.getElementById('hasta');
  var limpiar = document.getElementById('limpiar-reportes');
  var hoy = new Date();
  var maximo = new Date(hoy.getTime() - hoy.getTimezoneOffset() * 60000).toISOString().slice(0, 10);

  if (hasta) {
    hasta.max = maximo;
  }

  if (desde) {
    desde.max = maximo;
  }

  if (limpiar) {
    limpiar.addEventListener('click', function () {
      window.location.href = form.action;
    });
  }

  form.addEventListener('submit', function (event) {
    if (desde && hasta && desde.value && hasta.value && desde.value > hasta.value) {
      event.preventDefault();
      window.alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta".');
    }
  });
})();

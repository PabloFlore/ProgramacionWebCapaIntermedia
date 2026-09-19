document.addEventListener("DOMContentLoaded", function () {
  // 1. Buscador de cursos en catálogo
  const input = document.getElementById("searchCurso");
  if (input) {
    input.addEventListener("keyup", function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll(".js-course-card").forEach(function (card) {
        card.style.display = card.dataset.search.includes(q) ? "" : "none";
      });
    });
  }

  // 2. Validación Regex de Registro al hacer Clic en el Botón
  const btnCrear = document.getElementById("btnCrearCuenta");
  if (btnCrear) {
    btnCrear.addEventListener("click", function () {
      const regForm = document.getElementById("formRegistro") || document.querySelector("form");
      if (!regForm) return;

      // Obtener todos los inputs
      const inputs = regForm.querySelectorAll("input");
      const passInputs = regForm.querySelectorAll("input[type='password']");

      // Validar que los campos obligatorios no estén vacíos
      for (let inp of inputs) {
        if (inp.hasAttribute("required") && !inp.value.trim()) {
          alert("Por favor completa todos los campos obligatorios.");
          inp.focus();
          return;
        }
      }

      if (passInputs.length < 2) return;

      const password = passInputs[0].value;
      const confirmPassword = passInputs[1].value;

      // Expresión Regular exigida por la rúbrica (PWCI)
      const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.#_-])[A-Za-z\d@$!%*?&.#_-]{8,}$/;

      if (!regex.test(password)) {
        alert("Error en la contraseña:\n- Debe tener al menos 8 caracteres.\n- Debe incluir al menos una MAYÚSCULA.\n- Debe incluir al menos un NÚMERO.\n- Debe incluir al menos un CARÁCTER ESPECIAL (ej. @$!%*?&.#_-).");
        passInputs[0].focus();
        return;
      }

      if (password !== confirmPassword) {
        alert("Las contraseñas no coinciden. Por favor verifícalas.");
        passInputs[1].focus();
        return;
      }

      // Redirección según el rol seleccionado sin recargar la página
      const isInstructor = document.getElementById("rol2")?.checked;
      if (isInstructor) {
        window.location.href = "../dashboard/instructor.html";
      } else {
        window.location.href = "../dashboard/estudiante.html";
      }
    });
  }
});
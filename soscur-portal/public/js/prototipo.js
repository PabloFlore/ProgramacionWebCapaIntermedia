document.addEventListener("DOMContentLoaded", function () {
  const input = document.getElementById("searchCurso");
  if (!input) return;
  input.addEventListener("keyup", function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll(".js-course-card").forEach(function (card) {
      card.style.display = card.dataset.search.includes(q) ? "" : "none";
    });
  });
});
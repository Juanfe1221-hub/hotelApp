document.addEventListener("DOMContentLoaded", function () {
  const userIcon = document.querySelector(".fa-user");
  const userPanel = document.getElementById("userPanel");

  userIcon.addEventListener("click", function (e) {
    e.preventDefault();
    userPanel.classList.add("show");
  });
});

function cerrarPanel() {
  document.getElementById("userPanel").classList.remove("show");
}
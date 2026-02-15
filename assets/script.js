document.addEventListener("DOMContentLoaded", function () {
  const loader = document.getElementById("loading-screen");

  function showLoading() {
    if (loader) {
      loader.style.display = "flex";
    }
  }

  // Dipakai oleh inline onclick di beberapa halaman.
  window.showLoading = showLoading;

  const logoutBtn = document.querySelector(".btn-logout");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", showLoading);
  }

  // Tampilkan loader saat submit form (contoh: login / upload).
  document.querySelectorAll("form").forEach(function (form) {
    form.addEventListener("submit", showLoading);
  });

  // Tampilkan loader saat pindah halaman lewat tombol/link lokal.
  document.querySelectorAll("a").forEach(function (link) {
    link.addEventListener("click", function (event) {
      const href = link.getAttribute("href");

      if (!href || href.startsWith("#") || href.startsWith("javascript:")) {
        return;
      }

      if (link.hasAttribute("download") || link.target === "_blank") {
        return;
      }

      // Abaikan link modifier key agar tidak ganggu open in new tab.
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      showLoading();
    });
  });
});
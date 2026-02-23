(() => {
  const loader = document.getElementById("page-loader");
  const backButton = document.getElementById("btn-back-prev");

  const showLoader = () => {
    if (!loader) return;
    loader.classList.add("visible");
  };

  const hideLoader = () => {
    if (!loader) return;
    loader.classList.remove("visible");
  };

  window.addEventListener("load", () => {
    setTimeout(hideLoader, 180);
  });

  document.addEventListener("submit", () => {
    showLoader();
  });

  document.addEventListener("click", (event) => {
    const anchor = event.target.closest("a");
    if (!anchor) return;
    if (anchor.target === "_blank") return;
    if (anchor.getAttribute("href") === "#" || anchor.getAttribute("href")?.startsWith("javascript:")) return;
    showLoader();
  });

  if (backButton) {
    backButton.addEventListener("click", () => {
      const fallback = "dashboard.php";
      const hasReferrer = document.referrer && document.referrer.startsWith(window.location.origin);
      const currentUrl = window.location.href;
      let refIsSamePage = false;

      if (hasReferrer) {
        try {
          const ref = new URL(document.referrer);
          refIsSamePage = ref.pathname === window.location.pathname && ref.search === window.location.search;
        } catch {
          refIsSamePage = false;
        }
      }

      if (window.history.length > 1 && hasReferrer && !refIsSamePage) {
        showLoader();
        window.history.back();

        // If browser cannot navigate back (or lands to same page), avoid infinite loader.
        setTimeout(() => {
          if (window.location.href === currentUrl) {
            hideLoader();
            window.location.href = fallback;
          }
        }, 700);
        return;
      }

      showLoader();
      window.location.href = fallback;
    });
  }

  const revealTargets = document.querySelectorAll(".hero-strip, .panel, .smart-card, .table-wrap");
  revealTargets.forEach((node) => node.classList.add("anim-reveal"));

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12 }
  );

  revealTargets.forEach((node) => observer.observe(node));
})();

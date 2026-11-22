(() => {
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const candidates = document.querySelectorAll(
    ".hero-sangatta .container, .hotel-card-stay, .facility-card-item, " +
    ".panel-surface, .booking-builder-card, .facility-form-panel, " +
    ".facility-preview, .facility-order-summary, .summary-pine-card, " +
    ".qris-payment-panel, .admin-body .main-content .card"
  );
  const elements = Array.from(candidates).filter((element) => !element.closest(".modal"));

  if (reduceMotion || !("IntersectionObserver" in window)) {
    elements.forEach((element) => element.classList.add("lux-visible"));
    return;
  }

  document.documentElement.classList.add("lux-motion-ready");

  const observer = new IntersectionObserver((entries, currentObserver) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("lux-visible");
        currentObserver.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.08,
    rootMargin: "0px 0px -24px 0px"
  });

  elements.forEach((element, index) => {
    element.classList.add("lux-reveal");
    element.style.setProperty("--lux-reveal-delay", `${Math.min(index % 5, 4) * 70}ms`);
    observer.observe(element);
  });
})();

function initLazyLoad(root = document) {
  const loadContent = (el) => {
    el.classList.add("loading");
    const url = el.getAttribute("data-url");
    if (!url) return;
    fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } })
      .then(resp => resp.text())
      .then(html => { el.innerHTML = html; })
      .catch(err => { el.innerHTML = "⚠️ Error"; console.error(err); })
      .finally(() => el.classList.remove("loading"));
  };

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        loadContent(entry.target);
        obs.unobserve(entry.target);
      }
    });
  }, { rootMargin: "100px" });

  root.querySelectorAll(".lazy-load[data-url]").forEach(el => {
    const trigger = el.getAttribute("data-trigger") || "load";

    if (trigger === "load") {
      loadContent(el);
    } else if (trigger === "load-refresh-click") {
      loadContent(el);
      el.addEventListener("click", () => loadContent(el));
    } else if (trigger === "click") {
      el.addEventListener("click", () => loadContent(el), { once: true });
    } else if (trigger === "hover") {
      el.addEventListener("mouseenter", () => loadContent(el), { once: true });
    } else if (trigger.startsWith("interval:")) {
      const ms = parseInt(trigger.split(":")[1], 10) || 5000;
      loadContent(el);
      setInterval(() => loadContent(el), ms);
    } else if (trigger === "visible") {
      observer.observe(el);
    }
  });
}

// první inicializace
document.addEventListener("DOMContentLoaded", () => initLazyLoad());

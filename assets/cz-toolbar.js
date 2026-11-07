/*
 * CZ Toolbar — comportamento della barra flottante (mu-plugins/cz-toolbar).
 *
 * - Compare (.is-visible) quando l'intestazione del sito (header.site-header)
 *   esce dalla finestra; senza intestazione, appena si scorre oltre 10px.
 * - Pulsanti di base: "home" (va a data-href), "top" (torna in cima),
 *   "print" (evento annullabile `cz-toolbar:print`, poi window.print()).
 * I pulsanti degli altri plugin sono gestiti dai rispettivi script.
 */
(function () {
  "use strict";

  function init() {
    var toolbar = document.querySelector(".cz-toolbar");
    if (!toolbar) return;

    // ---------- Visibilità ----------
    var header = document.querySelector("header.site-header");
    if (header && "IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) {
        var entry = entries[0];
        var headerVisible = !!(entry && entry.isIntersecting && entry.intersectionRatio > 0);
        toolbar.classList.toggle("is-visible", !headerVisible);
      }, { threshold: [0, 0.01, 1] }).observe(header);
    } else {
      var onScroll = function () {
        var headerVisible;
        if (header) {
          var r = header.getBoundingClientRect();
          headerVisible = r.bottom > 0 && r.top < window.innerHeight;
        } else {
          headerVisible = window.scrollY <= 10;
        }
        toolbar.classList.toggle("is-visible", !headerVisible);
      };
      window.addEventListener("scroll", onScroll, { passive: true });
      window.addEventListener("resize", onScroll, { passive: true });
      onScroll();
    }

    // ---------- Pulsanti di base ----------
    var home = toolbar.querySelector('[data-cz-toolbar="home"]');
    if (home) {
      home.addEventListener("click", function () {
        window.location.href = home.getAttribute("data-href") || "/";
      });
    }

    var top = toolbar.querySelector('[data-cz-toolbar="top"]');
    if (top) {
      top.addEventListener("click", function () {
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
    }

    // Il tema può gestire la stampa a modo suo (es. post paginati) annullando l'evento.
    var print = toolbar.querySelector('[data-cz-toolbar="print"]');
    if (print) {
      print.addEventListener("click", function () {
        var ev = new CustomEvent("cz-toolbar:print", { cancelable: true });
        if (document.dispatchEvent(ev)) window.print();
      });
    }
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();

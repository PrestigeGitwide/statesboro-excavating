document.documentElement.classList.add("js-enabled");

const revealElements = document.querySelectorAll(".reveal");

if ("IntersectionObserver" in window) {
  const revealObserver = new IntersectionObserver(
    (entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    {
      threshold: 0.15,
      rootMargin: "0px 0px -40px 0px"
    }
  );

  revealElements.forEach((element) => revealObserver.observe(element));
} else {
  revealElements.forEach((element) => element.classList.add("is-visible"));
}

const estimateForms = document.querySelectorAll(".js-estimate-form");

estimateForms.forEach((form) => {
  const statusMessage = form.querySelector(".form-status");
  const submitButton = form.querySelector('button[type="submit"]');
  const defaultButtonText = submitButton ? submitButton.textContent : "";

  if (!statusMessage) return;

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(form);
    formData.set("page_url", window.location.href);
    const turnstileToken = formData.get("cf-turnstile-response");

    if (!turnstileToken) {
      statusMessage.textContent =
        "Please complete the spam protection check before submitting the form.";
      return;
    }

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = "Sending...";
    }

    statusMessage.textContent = "Sending your request...";

    try {
      const response = await fetch("/submit-estimate.php", {
        method: "POST",
        body: formData
      });

      const payload = await response.json();

      statusMessage.textContent =
        payload.message || "Something went wrong. Please call (270) 317-1996.";

      if (response.ok && payload.ok) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
          event: "estimate_form_success",
          form_name: form.id || "contact_estimate_form",
          page_path: window.location.pathname
        });

        form.reset();
        if (window.turnstile) {
          window.turnstile.reset();
        }
      }
    } catch (error) {
      statusMessage.textContent =
        "Something went wrong while sending your request. Please call (270) 317-1996.";
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = defaultButtonText;
      }
    }
  });
});

const stickyCta = document.querySelector(".sticky-cta");
const stickyTrigger =
  document.querySelector("[data-sticky-cta-trigger]") ||
  document.querySelector("#services");
const navToggle = document.querySelector(".nav-toggle");
const siteNav = document.querySelector(".site-nav");
const navGroups = document.querySelectorAll(".nav-group");

if (navToggle && siteNav) {
  const closeNav = () => {
    navToggle.setAttribute("aria-expanded", "false");
    siteNav.classList.remove("is-open");
    navGroups.forEach((group) => {
      group.classList.remove("is-open");
      const toggle = group.querySelector(".nav-parent-row");
      if (toggle) toggle.setAttribute("aria-expanded", "false");
    });
  };

  navToggle.addEventListener("click", () => {
    const isOpen = navToggle.getAttribute("aria-expanded") === "true";
    navToggle.setAttribute("aria-expanded", String(!isOpen));
    siteNav.classList.toggle("is-open", !isOpen);
  });

  siteNav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", closeNav);
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth >= 920) {
      closeNav();
    }
  });
}

navGroups.forEach((group) => {
  const toggle = group.querySelector(".nav-parent-row");
  if (!toggle) return;

  toggle.addEventListener("click", () => {
    const isOpen = group.classList.contains("is-open");
    navGroups.forEach((otherGroup) => {
      if (otherGroup !== group) {
        otherGroup.classList.remove("is-open");
        const otherToggle = otherGroup.querySelector(".nav-group-toggle");
        if (otherToggle) otherToggle.setAttribute("aria-expanded", "false");
      }
    });
    group.classList.toggle("is-open", !isOpen);
    toggle.setAttribute("aria-expanded", String(!isOpen));
  });
});

if (stickyCta && stickyTrigger) {
  const updateStickyCta = () => {
    const triggerTop = stickyTrigger.getBoundingClientRect().top;
    stickyCta.classList.toggle("is-visible", triggerTop <= 80);
  };

  updateStickyCta();
  window.addEventListener("scroll", updateStickyCta, { passive: true });
  window.addEventListener("resize", updateStickyCta);
}

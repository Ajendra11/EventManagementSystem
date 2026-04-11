document.addEventListener("DOMContentLoaded", function () {
  var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var PASSWORD_RE = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,64}$/;

  //Inline error helpers
  function getOrCreateErrorEl(field) {
    var group = field.closest(".form-group");
    if (!group) return null;
    var el = group.querySelector(".field-error");
    if (!el) {
      el = document.createElement("span");
      el.className = "field-error";
      el.setAttribute("aria-live", "polite");
      el.style.cssText =
        "display:block;margin-top:.35rem;font-size:.84rem;font-weight:600;color:#b91c1c;";
      group.appendChild(el);
    }
    return el;
  }

  function setError(field, msg) {
    field.style.borderColor = "#dc2626";
    field.setAttribute("aria-invalid", "true");
    var el = getOrCreateErrorEl(field);
    if (el) el.textContent = msg;
  }

  function clearError(field) {
    field.style.borderColor = "";
    field.removeAttribute("aria-invalid");
    var el = getOrCreateErrorEl(field);
    if (el) el.textContent = "";
  }

  //Single-field validation
  function validateField(field) {
    var label = field.getAttribute("data-label") || field.name || "This field";
    var value = field.value.trim();
    var type = (field.getAttribute("type") || "").toLowerCase();
    var name = (field.name || "").toLowerCase();

    if (field.getAttribute("data-required") === "true" && !value)
      return label + " is required.";

    if (type === "email" && value && !EMAIL_RE.test(value))
      return "Please enter a valid email address.";

    // Complexity check for new passwords only
    if (
      (name === "password" || name === "new_password") &&
      value &&
      !PASSWORD_RE.test(value)
    )
      return (
        label +
        " must be 8-64 characters with uppercase, lowercase, a number, and a special character."
      );

    return "";
  }

  //Password match check
  function checkPasswordMatch(form) {
    var pass = form.querySelector(
      'input[name="password"], input[name="new_password"]',
    );
    var confirm = form.querySelector(
      'input[name="confirm_password"], input[name="confirm_new_password"]',
    );
    if (!pass || !confirm) return true;
    if (pass.value && confirm.value && pass.value !== confirm.value) {
      setError(confirm, "Passwords do not match.");
      return false;
    }
    clearError(confirm);
    return true;
  }

  //Wire up validated forms
  document
    .querySelectorAll('form[data-validate="true"]')
    .forEach(function (form) {
      var watchedFields = form.querySelectorAll(
        '[data-required="true"], [type="email"], input[name="password"], input[name="new_password"]',
      );

      // Submit: validate all, block if errors
      form.addEventListener("submit", function (e) {
        var valid = true;

        watchedFields.forEach(function (field) {
          var msg = validateField(field);
          if (msg) {
            setError(field, msg);
            valid = false;
          } else {
            clearError(field);
          }
        });

        if (!checkPasswordMatch(form)) valid = false;

        // Quantity check
        var qtyField = form.querySelector('input[name="quantity"]');
        if (qtyField) {
          var qty = parseInt(qtyField.value, 10);
          var max = parseInt(qtyField.getAttribute("max") || "10", 10);
          if (isNaN(qty) || qty < 1) {
            setError(qtyField, "Please select at least 1 seat.");
            valid = false;
          } else if (qty > max) {
            setError(
              qtyField,
              "Maximum " + max + " seat(s) allowed per booking.",
            );
            valid = false;
          }
        }

        // Star rating check
        var starPicker = form.querySelector(".star-picker");
        if (
          starPicker &&
          !form.querySelector(".star-picker input[type=radio]:checked")
        ) {
          valid = false;
          var starErr = starPicker.querySelector(".star-error");
          if (!starErr) {
            starErr = document.createElement("span");
            starErr.className = "star-error field-error";
            starErr.style.cssText =
              "display:block;margin-top:.35rem;font-size:.84rem;font-weight:600;color:#b91c1c;";
            starPicker.appendChild(starErr);
          }
          starErr.textContent = "Please select a star rating.";
        }

        if (!valid) {
          e.preventDefault();
          var firstBad = form.querySelector('[aria-invalid="true"]');
          if (firstBad) {
            firstBad.scrollIntoView({ behavior: "smooth", block: "center" });
            firstBad.focus();
          }
        }
      });

      // Blur: re-validate field when user leaves it
      watchedFields.forEach(function (field) {
        field.addEventListener("blur", function () {
          var msg = validateField(field);
          if (msg) setError(field, msg);
          else clearError(field);
          checkPasswordMatch(form);
        });

        // Input: clear error as user starts correcting
        field.addEventListener("input", function () {
          if (field.getAttribute("aria-invalid") === "true") clearError(field);
        });
      });

      // Live password-match feedback on confirm field
      var confirmField = form.querySelector(
        'input[name="confirm_password"], input[name="confirm_new_password"]',
      );
      if (confirmField) {
        confirmField.addEventListener("input", function () {
          if (confirmField.value) checkPasswordMatch(form);
          else clearError(confirmField);
        });
      }
    });

  //Character counter for description textarea
  document
    .querySelectorAll('textarea[data-char-counter="true"], textarea[maxlength]')
    .forEach(function (ta) {
      var max = parseInt(
        ta.getAttribute("data-maxlength") ||
          ta.getAttribute("maxlength") ||
          "0",
        10,
      );
      var counter = document.createElement("small");
      counter.className = "char-counter";
      counter.style.cssText =
        "display:block;text-align:right;margin-top:.3rem;font-size:.82rem;color:#6b7280;transition:color .15s;";

      var next = ta.nextSibling;
      next
        ? ta.parentNode.insertBefore(counter, next)
        : ta.parentNode.appendChild(counter);

      function update() {
        var len = ta.value.length;
        counter.textContent = max
          ? len + " / " + max + " characters"
          : len + " characters";
        if (max && len > max) {
          counter.style.color = "#b91c1c";
          counter.style.fontWeight = "700";
        } else if (max && len > max * 0.9) {
          counter.style.color = "#92400e";
          counter.style.fontWeight = "600";
        } else {
          counter.style.color = "#6b7280";
          counter.style.fontWeight = "400";
        }
      }

      ta.addEventListener("input", update);
      update();
    });

  //Auto-dismiss flash messages after 6 s
  document
    .querySelectorAll(".flash-success, .flash-error, .flash-warning")
    .forEach(function (el) {
      setTimeout(function () {
        el.style.transition = "opacity .4s";
        el.style.opacity = "0";
        setTimeout(function () {
          el.remove();
        }, 400);
      }, 6000);
    });

  //User profile dropdown
  var trigger = document.getElementById("userProfileTrigger");
  var menu = document.getElementById("userProfileMenu");
  if (trigger && menu) {
    trigger.addEventListener("click", function (e) {
      e.stopPropagation();
      var open = menu.classList.toggle("open");
      trigger.setAttribute("aria-expanded", open ? "true" : "false");
    });
    document.addEventListener("click", function (e) {
      if (!menu.contains(e.target) && !trigger.contains(e.target)) {
        menu.classList.remove("open");
        trigger.setAttribute("aria-expanded", "false");
      }
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        menu.classList.remove("open");
        trigger.setAttribute("aria-expanded", "false");
      }
    });
  }

  //Logout confirmation
  document.querySelectorAll(".js-logout-confirm").forEach(function (link) {
    link.addEventListener("click", function (e) {
      if (!window.confirm("Are you sure you want to logout?"))
        e.preventDefault();
    });
  });
});

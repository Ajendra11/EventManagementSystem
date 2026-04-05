document.addEventListener("DOMContentLoaded", function () {
  // Form validation for required fields and password match
  document
    .querySelectorAll('form[data-validate="true"]')
    .forEach(function (form) {
      form.addEventListener("submit", function (e) {
        var errors = [];

        form
          .querySelectorAll('[data-required="true"]')
          .forEach(function (field) {
            if (!field.value.trim()) {
              var label =
                field.getAttribute("data-label") || field.name || "Field";
              errors.push(label + " is required.");
              field.style.borderColor = "#dc2626";
            } else {
              field.style.borderColor = "";
            }
          });

        var pass = form.querySelector(
          'input[name="password"], input[name="new_password"]',
        );
        var confirm = form.querySelector(
          'input[name="confirm_password"], input[name="confirm_new_password"]',
        );
        if (
          pass &&
          confirm &&
          pass.value &&
          confirm.value &&
          pass.value !== confirm.value
        ) {
          errors.push("Passwords do not match.");
          confirm.style.borderColor = "#dc2626";
        }

        if (errors.length) {
          e.preventDefault();
          alert(errors.join("\n"));
        }
      });

      form.querySelectorAll('[data-required="true"]').forEach(function (field) {
        field.addEventListener("input", function () {
          if (this.value.trim()) this.style.borderColor = "";
        });
      });
    });

  // Auto-dismiss flashes after 6 seconds
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
});

/**
 * Contact form handler — POSTs to send-mail.php on www.konato.be
 * No external libraries. Uses the browser's built-in fetch().
 *
 * The PHP script must be deployed to: https://www.konato.be/send-mail.php
 */

var MAIL_ENDPOINT = "https://www.konato.be/send-mail.php";

(function () {
  "use strict";

  var form   = document.getElementById("contact-form");
  var status = document.getElementById("form-status");
  var btn    = form && form.querySelector(".submit-btn");

  if (!form) return;

  function showFieldError(id, show) {
    var el = document.getElementById(id);
    if (el) el.style.display = show ? "block" : "none";
  }

  function validate() {
    var name    = form.querySelector("#cf-name").value.trim();
    var email   = form.querySelector("#cf-email").value.trim();
    var message = form.querySelector("#cf-message").value.trim();
    var gdpr    = form.querySelector("#cf-gdpr").checked;
    var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    showFieldError("err-name",    !name);
    showFieldError("err-email",   !email || !emailOk);
    showFieldError("err-message", !message);
    showFieldError("err-gdpr",    !gdpr);

    return name && email && emailOk && message && gdpr;
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    status.style.display = "none";
    if (!validate()) return;

    btn.disabled    = true;
    btn.textContent = "Sending…";

    var payload = {
      name:    form.querySelector("#cf-name").value.trim(),
      email:   form.querySelector("#cf-email").value.trim(),
      message: form.querySelector("#cf-message").value.trim(),
      website: form.querySelector("#cf-website") ? form.querySelector("#cf-website").value : "",
    };

    fetch(MAIL_ENDPOINT, {
      method:  "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
      body:    JSON.stringify(payload),
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.status === "success") {
          status.className     = "success";
          status.textContent   = "Thank you! Your message has been sent. We'll get back to you shortly.";
          status.style.display = "block";
          form.reset();
        } else {
          throw new Error(data.message || "Unknown error");
        }
      })
      .catch(function (err) {
        status.className     = "error";
        status.textContent   = "Something went wrong. Please try again or email us at info@konato.be.";
        status.style.display = "block";
        console.error("Form error:", err);
      })
      .finally(function () {
        btn.disabled    = false;
        btn.textContent = "Send a message";
      });
  });
})();

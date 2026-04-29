/**
 * apply-form.js — job application form handler
 *
 * POSTs multipart/form-data (including CV file) to send-application.php
 * on www.konato.be. Also populates the job title from the URL ?jobtitle= param.
 *
 * The PHP script must be deployed to: https://www.konato.be/send-application.php
 */

var APPLY_ENDPOINT = "https://www.konato.be/send-application.php";

(function () {
  "use strict";

  var form   = document.getElementById("apply-form");
  var status = document.getElementById("apply-status");
  var btn    = form && form.querySelector(".submit-btn");

  if (!form) return;

  // ── Populate hidden fields + heading from URL params ────────────────────────
  (function () {
    var params   = new URLSearchParams(window.location.search);
    var jobTitle = params.get("jobtitle") || "";
    var jobId    = params.get("jobid")    || "";

    var titleEl = document.getElementById("apply-jobtitle");
    var idEl    = document.getElementById("apply-jobid");
    if (titleEl) titleEl.value = jobTitle;
    if (idEl)    idEl.value    = jobId;

    var h2 = document.getElementById("apply-job-title");
    if (h2 && jobTitle) h2.textContent = "Apply here for " + jobTitle;
    if (jobTitle) document.title = "Apply for " + jobTitle + " | Konato";
  })();

  // ── Field-level error display ───────────────────────────────────────────────
  function showFieldError(id, show) {
    var el = document.getElementById(id);
    if (el) el.style.display = show ? "block" : "none";
  }

  // ── Client-side validation ──────────────────────────────────────────────────
  function validate() {
    var firstName = form.querySelector("#apply-firstname").value.trim();
    var lastName  = form.querySelector("#apply-lastname").value.trim();
    var email     = form.querySelector("#apply-email").value.trim();
    var cvFiles   = form.querySelector("#apply-cv").files;
    var gdpr      = form.querySelector("#apply-gdpr").checked;
    var emailOk   = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    showFieldError("err-firstname", !firstName);
    showFieldError("err-lastname",  !lastName);
    showFieldError("err-email",     !email || !emailOk);
    showFieldError("err-cv",        cvFiles.length === 0);
    showFieldError("err-gdpr",      !gdpr);

    return firstName && lastName && email && emailOk && cvFiles.length > 0 && gdpr;
  }

  // ── Form submission ─────────────────────────────────────────────────────────
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    status.style.display = "none";
    if (!validate()) return;

    btn.disabled    = true;
    btn.textContent = "Sending…";

    // Use FormData so the CV file is included in the multipart body
    var payload = new FormData(form);

    fetch(APPLY_ENDPOINT, {
      method: "POST",
      body:   payload,
      // Do NOT set Content-Type manually — FormData sets it with the boundary
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.status === "success") {
          status.className   = "success";
          status.textContent = data.message;
          status.style.display = "block";
          form.reset();
        } else {
          throw new Error(data.message || "Unknown error");
        }
      })
      .catch(function (err) {
        status.className   = "error";
        status.textContent = "Something went wrong. Please try again or email us at info@konato.be.";
        status.style.display = "block";
        console.error("Apply form error:", err);
      })
      .finally(function () {
        btn.disabled    = false;
        btn.textContent = "Apply";
      });
  });
}());

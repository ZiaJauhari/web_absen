document.addEventListener("DOMContentLoaded", function () {
  const attendanceBtn = document.getElementById("attendance-btn");
  const checkinBtn = document.getElementById("checkin-btn");
  const checkoutBtn = document.getElementById("checkout-btn");
  const clockTime = document.getElementById("clock-time");
  const clockDate = document.getElementById("clock-date");

  const workStart = document.getElementById("work-start");
  const workDuration = document.getElementById("work-duration");
  const workProgressBar = document.getElementById("work-progress-bar");

  const breakBtn = document.getElementById("break-btn");
  const overtimeBtn = document.getElementById("overtime-btn");

  let state = window.__ATTENDANCE_STATE__ || null;

  if (attendanceBtn) {
    attendanceBtn.addEventListener("click", function () {
      if (!state || !state.nextAction) return;
      performAttendance(state.nextAction, attendanceBtn);
    });
  }

  if (checkinBtn) {
    checkinBtn.addEventListener("click", function () {
      performAttendance("checkin", checkinBtn);
    });
  }

  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", function () {
      performAttendance("checkout", checkoutBtn);
    });
  }

  if (breakBtn) {
    breakBtn.addEventListener("click", function () {
      showAlert("Fitur istirahat belum tersedia.", "info");
    });
  }

  if (overtimeBtn) {
    overtimeBtn.addEventListener("click", function () {
      showAlert("Fitur lembur belum tersedia.", "info");
    });
  }

  function pad2(num) {
    return String(num).padStart(2, "0");
  }

  function formatTime(isoString) {
    if (!isoString) return "--:--:--";
    const date = new Date(isoString.replace(" ", "T"));
    return `${pad2(date.getHours())}:${pad2(date.getMinutes())}:${pad2(
      date.getSeconds()
    )}`;
  }

  function updateClock() {
    if (!clockTime && !clockDate) return;
    const now = new Date();
    if (clockTime) {
      clockTime.textContent = `${pad2(now.getHours())}:${pad2(now.getMinutes())}:${pad2(
        now.getSeconds()
      )}`;
    }

    if (clockDate) {
      const formatted = new Intl.DateTimeFormat("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "2-digit",
      }).format(now);
      clockDate.textContent = formatted;
    }
  }

  function computeLiveDurationSeconds() {
    if (!state || !state.checkIn) return 0;
    if (state.checkOut) return state.durationSeconds || 0;

    const checkIn = new Date(state.checkIn.replace(" ", "T"));
    const now = new Date();
    return Math.max(0, Math.floor((now.getTime() - checkIn.getTime()) / 1000));
  }

  function durationTextFromSeconds(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${hours} hr ${minutes} min`;
  }

  function updateDashboardUI() {
    if (!state) return;

    const durationSeconds = computeLiveDurationSeconds();
    if (workStart) workStart.textContent = formatTime(state.checkIn);
    if (workDuration) workDuration.textContent = durationTextFromSeconds(durationSeconds);

    if (workProgressBar) {
      const targetSeconds = 8 * 3600;
      const pct = targetSeconds > 0 ? Math.min(100, (durationSeconds / targetSeconds) * 100) : 0;
      workProgressBar.style.width = `${pct}%`;
    }

    if (attendanceBtn) {
      if (!state.nextAction) {
        attendanceBtn.disabled = true;
        attendanceBtn.classList.add("is-disabled");
      } else {
        attendanceBtn.disabled = false;
        attendanceBtn.classList.remove("is-disabled");
      }
    }
  }

  updateClock();
  setInterval(updateClock, 1000);

  updateDashboardUI();
  setInterval(updateDashboardUI, 10000);

  function performAttendance(action, button) {
    // Check if geolocation is supported
    if (!navigator.geolocation) {
      showAlert("Geolocation tidak didukung oleh browser ini.", "danger");
      return;
    }

    // Show loading state
    setButtonLoading(button, true);

    // Get geolocation with timeout
    const options = {
      enableHighAccuracy: true,
      timeout: 10000, // 10 seconds
      maximumAge: 0
    };

    navigator.geolocation.getCurrentPosition(
      function (position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;

        // Send to server
        const formData = new FormData();
        formData.append("action", action);
        formData.append("lat", lat);
        formData.append("lng", lng);

        fetch("dashboard.php?api=1", {
          method: "POST",
          body: formData,
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
          },
        })
          .then((response) => {
            if (!response.ok) {
              return response.json().then(
                (payload) => {
                  throw new Error(payload && payload.message ? payload.message : "Respons server tidak valid");
                },
                () => {
                  throw new Error("Respons server tidak valid");
                }
              );
            }
            return response.json();
          })
          .then((payload) => {
            if (!payload || payload.ok !== true) {
              throw new Error(payload && payload.message ? payload.message : "Gagal melakukan absen");
            }

            state = payload.state || state;
            updateDashboardUI();

            showAlert(payload.message || "Berhasil.", "success");
            setButtonLoading(button, false);
          })
          .catch((error) => {
            console.error("Error:", error);
            showAlert("Gagal melakukan absen: " + error.message, "danger");
            setButtonLoading(button, false);
          });
      },
      function (error) {
        let errorMessage = "Gagal mendapatkan lokasi: ";
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage += "Akses lokasi ditolak. Mohon izinkan akses lokasi.";
            break;
          case error.POSITION_UNAVAILABLE:
            errorMessage += "Informasi lokasi tidak tersedia.";
            break;
          case error.TIMEOUT:
            errorMessage += "Permintaan lokasi timeout. Silakan coba lagi.";
            break;
          default:
            errorMessage += error.message;
        }
        showAlert(errorMessage, "danger");
        setButtonLoading(button, false);
      },
      options
    );
  }

  function setButtonLoading(button, isLoading) {
    if (!button) return;
    const btnText = button.querySelector(".btn-text") || button.querySelector(".finger-icon");
    const spinner = button.querySelector(".spinner-border");

    if (isLoading) {
      button.disabled = true;
      if (btnText) btnText.classList.add("d-none");
      if (spinner) spinner.classList.remove("d-none");
    } else {
      button.disabled = false;
      if (btnText) btnText.classList.remove("d-none");
      if (spinner) spinner.classList.add("d-none");
    }
  }

  function showAlert(message, type) {
    // Create alert element
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = "alert";
    alertDiv.innerHTML = `
      <strong>${type === "success" ? "Berhasil!" : "Error!"}</strong> ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Insert at the top of container
    const container =
      document.getElementById("dashboard-alerts") || document.querySelector(".container");
    if (container) container.prepend(alertDiv);

    // Auto dismiss after 5 seconds
    setTimeout(function () {
      alertDiv.classList.remove("show");
      setTimeout(function () {
        alertDiv.remove();
      }, 150);
    }, 5000);
  }

  // Form validation for admin and login pages
  const forms = document.querySelectorAll("form");
  forms.forEach(function (form) {
    form.addEventListener("submit", function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add("was-validated");
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
  // Real-time Clock Update
  updateClock();
  setInterval(updateClock, 1000);

  // Attendance button handler
  const attendanceBtn = document.getElementById("attendance-btn");
  if (attendanceBtn) {
    attendanceBtn.addEventListener("click", handleAttendance);
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

// Update Clock Function
function updateClock() {
  const timeElement = document.getElementById("current-time");
  const dateElement = document.getElementById("current-date");

  if (timeElement && dateElement) {
    const now = new Date();

    // Format time as HH:MM:SS
    const hours = String(now.getHours()).padStart(2, "0");
    const minutes = String(now.getMinutes()).padStart(2, "0");
    const seconds = String(now.getSeconds()).padStart(2, "0");
    timeElement.textContent = `${hours}:${minutes}:${seconds}`;

    // Format date
    const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

    const dayName = days[now.getDay()];
    const monthName = months[now.getMonth()];
    const date = now.getDate();
    const year = now.getFullYear();

    dateElement.textContent = `${dayName}, ${monthName} ${date}, ${year}`;
  }
}

// Handle Attendance (Check In/Out)
function handleAttendance() {
  const btn = document.getElementById("attendance-btn");
  const action = btn.getAttribute("data-action");

  // Check if geolocation is supported
  if (!navigator.geolocation) {
    showToast("Geolocation tidak didukung oleh browser ini.", "error");
    return;
  }

  // Show loading state
  btn.classList.add("loading");
  btn.disabled = true;

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

      // Send to server using fetch with XMLHttpRequest header
      const formData = new FormData();
      formData.append("action", action);
      formData.append("lat", lat);
      formData.append("lng", lng);

      fetch("dashboard.php", {
        method: "POST",
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Respons server tidak valid");
          }
          return response.json();
        })
        .then((data) => {
          // Show message based on response
          const messageType = data.type === 'success' ? 'success' : 'error';
          showToast(data.message, messageType);

          // Reload page after a short delay if successful
          if (data.type === 'success') {
            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            btn.classList.remove("loading");
            btn.disabled = false;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("Gagal melakukan absen: " + error.message, "error");
          btn.classList.remove("loading");
          btn.disabled = false;
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
      showToast(errorMessage, "error");
      btn.classList.remove("loading");
      btn.disabled = false;
    },
    options
  );
}

// Show Toast Notification
function showToast(message, type) {
  const toast = document.getElementById("toast");
  if (!toast) return;

  toast.textContent = message;
  toast.className = `toast-notification ${type} show`;

  // Auto hide after 4 seconds
  setTimeout(function () {
    toast.classList.remove("show");
  }, 4000);
}

// Update Working Hours in Real-time
function updateWorkingHours() {
  const hoursText = document.querySelector(".hours-text");
  const progressFill = document.querySelector(".progress-fill");
  const checkTime = document.querySelector(".check-time");

  if (hoursText && progressFill && checkTime) {
    // Get check-in time from the element
    const checkInText = checkTime.textContent.trim();

    if (checkInText !== "--:--:--") {
      const now = new Date();
      const checkInParts = checkInText.split(":");
      const checkInTime = new Date();
      checkInTime.setHours(parseInt(checkInParts[0]));
      checkInTime.setMinutes(parseInt(checkInParts[1]));
      checkInTime.setSeconds(parseInt(checkInParts[2]));

      // Calculate duration
      const duration = now - checkInTime;
      const hours = Math.floor(duration / (1000 * 60 * 60));
      const minutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));

      // Update text
      hoursText.textContent = `${hours} hr ${minutes} min`;

      // Update progress bar (assuming 8 hour workday = 480 minutes)
      const totalMinutes = hours * 60 + minutes;
      const percentage = Math.min((totalMinutes / 480) * 100, 100);
      progressFill.style.width = percentage + "%";
    }
  }
}

// Check if on attendance page and update working hours
if (document.querySelector(".attendance-page")) {
  // Update every minute
  setInterval(updateWorkingHours, 60000);
  // Initial update
  updateWorkingHours();
}

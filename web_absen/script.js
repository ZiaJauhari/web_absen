document.addEventListener("DOMContentLoaded", function () {
  const checkinBtn = document.getElementById("checkin-btn");
  const checkoutBtn = document.getElementById("checkout-btn");

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

  function performAttendance(action, button) {
    // Check if geolocation is supported
    if (!navigator.geolocation) {
      showAlert("Geolocation tidak didukung oleh browser ini.", "danger");
      return;
    }

    // Add loading state to button
    const originalText = button.innerHTML;
    button.disabled = true;
    button.classList.add("btn-loading");
    button.innerHTML = action === "checkin" ? "Memproses Check In..." : "Memproses Check Out...";

    // Show loading overlay
    showLoadingOverlay();

    // Get current position with timeout
    const options = {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    };

    navigator.geolocation.getCurrentPosition(
      function (position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        console.log(`Location obtained: Lat ${lat}, Lng ${lng}, Accuracy: ${accuracy}m`);

        // Send to server
        const formData = new FormData();
        formData.append("action", action);
        formData.append("lat", lat);
        formData.append("lng", lng);

        fetch("dashboard.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error("Network response was not ok");
            }
            return response.text();
          })
          .then((data) => {
            // Hide loading overlay
            hideLoadingOverlay();
            
            // Reload page to show updated status
            location.reload();
          })
          .catch((error) => {
            console.error("Error:", error);
            hideLoadingOverlay();
            button.disabled = false;
            button.classList.remove("btn-loading");
            button.innerHTML = originalText;
            showAlert("Terjadi kesalahan saat memproses absen. Silakan coba lagi.", "danger");
          });
      },
      function (error) {
        // Hide loading overlay
        hideLoadingOverlay();
        
        // Reset button state
        button.disabled = false;
        button.classList.remove("btn-loading");
        button.innerHTML = originalText;

        // Handle different error types
        let errorMessage = "";
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage = "Akses lokasi ditolak. Silakan izinkan akses lokasi di browser Anda.";
            break;
          case error.POSITION_UNAVAILABLE:
            errorMessage = "Informasi lokasi tidak tersedia. Pastikan GPS Anda aktif.";
            break;
          case error.TIMEOUT:
            errorMessage = "Permintaan lokasi timeout. Silakan coba lagi.";
            break;
          default:
            errorMessage = "Terjadi kesalahan saat mendapatkan lokasi: " + error.message;
            break;
        }
        
        showAlert(errorMessage, "danger");
      },
      options
    );
  }

  function showAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll(".dynamic-alert");
    existingAlerts.forEach(alert => alert.remove());

    // Create new alert
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} dynamic-alert`;
    alertDiv.innerHTML = `
      <strong>${type === "danger" ? "Error!" : "Info:"}</strong> ${message}
    `;

    // Insert after h2
    const h2 = document.querySelector("h2");
    if (h2) {
      h2.insertAdjacentElement("afterend", alertDiv);
    }

    // Auto dismiss after 5 seconds
    setTimeout(() => {
      alertDiv.style.opacity = "0";
      setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
  }

  function showLoadingOverlay() {
    const overlay = document.createElement("div");
    overlay.className = "loading-overlay";
    overlay.id = "loading-overlay";
    overlay.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(overlay);
  }

  function hideLoadingOverlay() {
    const overlay = document.getElementById("loading-overlay");
    if (overlay) {
      overlay.remove();
    }
  }

  // Form validation for admin panel
  const addEmployeeForm = document.querySelector('form[method="POST"]');
  if (addEmployeeForm && addEmployeeForm.querySelector('input[name="name"]')) {
    addEmployeeForm.addEventListener("submit", function (e) {
      const name = document.getElementById("name").value.trim();
      const email = document.getElementById("email").value.trim();
      const password = document.getElementById("password").value;

      if (name.length < 3) {
        e.preventDefault();
        showAlert("Nama harus minimal 3 karakter.", "danger");
        return false;
      }

      if (!isValidEmail(email)) {
        e.preventDefault();
        showAlert("Format email tidak valid.", "danger");
        return false;
      }

      if (password.length < 6) {
        e.preventDefault();
        showAlert("Password harus minimal 6 karakter.", "danger");
        return false;
      }

      // Show loading state
      const submitBtn = addEmployeeForm.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = "Memproses...";
      }
    });
  }

  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert:not(.dynamic-alert)");
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.opacity = "0";
      alert.style.transition = "opacity 0.3s ease";
      setTimeout(() => alert.remove(), 300);
    }, 5000);
  });

  // Add smooth scroll behavior
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start"
        });
      }
    });
  });

  // Table responsive wrapper
  const tables = document.querySelectorAll("table:not(.table-responsive table)");
  tables.forEach(table => {
    if (!table.parentElement.classList.contains("table-responsive")) {
      const wrapper = document.createElement("div");
      wrapper.className = "table-responsive";
      table.parentNode.insertBefore(wrapper, table);
      wrapper.appendChild(table);
    }
  });

  // Add empty state for empty tables
  const tableBodies = document.querySelectorAll("tbody");
  tableBodies.forEach(tbody => {
    if (tbody.children.length === 0) {
      const tr = document.createElement("tr");
      const td = document.createElement("td");
      td.colSpan = tbody.parentElement.querySelector("thead tr").children.length;
      td.className = "text-center py-5";
      td.innerHTML = `
        <div class="empty-state">
          <div class="empty-state-icon">📋</div>
          <div class="empty-state-text">Tidak ada data</div>
        </div>
      `;
      tr.appendChild(td);
      tbody.appendChild(tr);
    }
  });
});

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

        fetch("dashboard.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error("Respons server tidak valid");
            }
            return response.text();
          })
          .then((data) => {
            // Show success message
            showAlert(
              action === "checkin"
                ? "Berhasil check in! Halaman akan dimuat ulang..."
                : "Berhasil check out! Halaman akan dimuat ulang...",
              "success"
            );

            // Reload page after a short delay
            setTimeout(function () {
              location.reload();
            }, 1500);
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
    const btnText = button.querySelector(".btn-text");
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
    const container = document.querySelector(".container");
    const firstElement = container.querySelector(".row");
    if (firstElement) {
      container.insertBefore(alertDiv, firstElement.nextSibling);
    } else {
      container.prepend(alertDiv);
    }

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

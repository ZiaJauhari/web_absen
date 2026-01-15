document.addEventListener("DOMContentLoaded", function () {
  const checkinBtn = document.getElementById("checkin-btn");
  const checkoutBtn = document.getElementById("checkout-btn");

  if (checkinBtn) {
    checkinBtn.addEventListener("click", function () {
      performAttendance("checkin");
    });
  }

  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", function () {
      performAttendance("checkout");
    });
  }

  function performAttendance(action) {
    if (navigator.geolocation) {
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
            .then((response) => response.text())
            .then((data) => {
              // Reload page to show updated status
              location.reload();
            })
            .catch((error) => {
              alert("Error: " + error);
            });
        },
        function (error) {
          alert("Gagal mendapatkan lokasi: " + error.message);
        }
      );
    } else {
      alert("Geolocation tidak didukung oleh browser ini.");
    }
  }
});

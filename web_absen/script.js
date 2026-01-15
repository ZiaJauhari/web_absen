document.addEventListener("DOMContentLoaded", function () {
  const checkinBtn = document.getElementById("checkin-btn");
  const checkoutBtn = document.getElementById("checkout-btn");
  const geoStatus = document.getElementById("geo-status");

  const initialCheckinDisabled = checkinBtn ? checkinBtn.disabled : false;
  const initialCheckoutDisabled = checkoutBtn ? checkoutBtn.disabled : false;

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
    if (checkinBtn) checkinBtn.disabled = true;
    if (checkoutBtn) checkoutBtn.disabled = true;
    if (geoStatus) geoStatus.textContent = "Mengambil lokasi…";

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        function (position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          if (geoStatus) geoStatus.textContent = "Mengirim data…";

          // Use a normal form submit so PHP redirect + flash works.
          const form = document.createElement("form");
          form.method = "POST";
          form.action = "dashboard.php";

          const actionInput = document.createElement("input");
          actionInput.type = "hidden";
          actionInput.name = "action";
          actionInput.value = action;
          form.appendChild(actionInput);

          const latInput = document.createElement("input");
          latInput.type = "hidden";
          latInput.name = "lat";
          latInput.value = String(lat);
          form.appendChild(latInput);

          const lngInput = document.createElement("input");
          lngInput.type = "hidden";
          lngInput.name = "lng";
          lngInput.value = String(lng);
          form.appendChild(lngInput);

          document.body.appendChild(form);
          form.submit();
        },
        function (error) {
          if (geoStatus) geoStatus.textContent = "";
          if (checkinBtn) checkinBtn.disabled = initialCheckinDisabled;
          if (checkoutBtn) checkoutBtn.disabled = initialCheckoutDisabled;
          alert("Gagal mendapatkan lokasi: " + error.message);
        }
      );
    } else {
      if (geoStatus) geoStatus.textContent = "";
      if (checkinBtn) checkinBtn.disabled = initialCheckinDisabled;
      if (checkoutBtn) checkoutBtn.disabled = initialCheckoutDisabled;
      alert("Geolocation tidak didukung oleh browser ini.");
    }
  }
});

// Global variables
let map, directionsService, directionsRenderer;
let pickupAutocomplete, dropoffAutocomplete;

// Stripe globals
let stripe, elements, clientSecret;

document.addEventListener('DOMContentLoaded', function () {
  console.log('[RideScript] DOMContentLoaded fired');
  initMap();
  initAutocomplete();
  setupFormListeners();
  setDefaultPickupTime();

  // Init Stripe
  stripe = Stripe("pk_live_51PxreuGONt82dusSgjBpB6jJ1E2Lo0VV1chFThOdapHDQqMu0EDuciwRyMREgeviL6C4WhTfFM1Xidmao8ZE2GMg00w4yj0JVc"); // ✅ your public key
});

function setDefaultPickupTime() {
  console.log('[RideScript] Setting default pickup time');
  const now = new Date();
  now.setMinutes(now.getMinutes() + 30);
  const formattedDateTime = now.toISOString().slice(0, 16);
  const elem = document.getElementById('pickupTime');
  if (!elem) {
    console.error('[RideScript] #pickupTime not found');
    return;
  }
  elem.value = formattedDateTime;
}

function initMap() {
  console.log('[RideScript] initMap()');
  const mapContainer = document.querySelector('.map-container');
  const mapFrame = document.getElementById('mapFrame');
  if (!mapContainer || !mapFrame) {
    console.error('[RideScript] map-container or mapFrame not found in DOM');
    return;
  }

  const mapDiv = document.createElement('div');
  mapDiv.id = 'map';
  mapDiv.style.height = '100%';
  mapDiv.style.width = '100%';
  mapDiv.style.borderRadius = '8px';
  mapContainer.replaceChild(mapDiv, mapFrame);

  if (typeof google === 'undefined' || !google.maps) {
    console.error('[RideScript] google.maps is undefined—Maps API probably not loaded');
    return;
  }

  map = new google.maps.Map(mapDiv, {
    center: { lat: 53.349805, lng: -6.26031 },
    zoom: 13,
  });
  directionsService = new google.maps.DirectionsService();
  directionsRenderer = new google.maps.DirectionsRenderer({ map });
}

function initAutocomplete() {
  console.log('[RideScript] initAutocomplete()');
  const pickupInput = document.getElementById('pickup');
  const dropoffInput = document.getElementById('dropoff');
  if (!pickupInput || !dropoffInput) {
    console.error('[RideScript] #pickup or #dropoff input not found');
    return;
  }

  if (!google || !google.maps || !google.maps.places) {
    console.error('[RideScript] google.maps.places not available');
    return;
  }

  pickupAutocomplete = new google.maps.places.Autocomplete(pickupInput);
  dropoffAutocomplete = new google.maps.places.Autocomplete(dropoffInput);

  pickupAutocomplete.addListener('place_changed', calculateDistanceAndFare);
  dropoffAutocomplete.addListener('place_changed', calculateDistanceAndFare);
}

function calculateDistanceAndFare() {
  console.log('[RideScript] calculateDistanceAndFare()');
  const pickup = document.getElementById('pickup')?.value;
  const dropoff = document.getElementById('dropoff')?.value;
  const pickupTime = document.getElementById('pickupTime')?.value;

  if (!pickup || !dropoff || !pickupTime) {
    console.log('[RideScript] Missing one of pickup/dropoff/pickupTime');
    return;
  }

  const request = {
    origin: pickup,
    destination: dropoff,
    travelMode: google.maps.TravelMode.DRIVING,
  };

  directionsService.route(request, function (result, status) {
    if (status === google.maps.DirectionsStatus.OK) {
      console.log('[RideScript] Directions result OK');
      directionsRenderer.setDirections(result);
      const leg = result.routes[0].legs[0];
      const distanceInKm = leg.distance.value / 1000;
      const durationInMin = Math.round(leg.duration.value / 60);
      const fareAmount = calculateFare(distanceInKm, pickupTime);

      const distanceElem = document.getElementById('distance');
      const durationElem = document.getElementById('duration');
      const fareElem = document.getElementById('fare');
      const rideInfoAlert = document.getElementById('rideInfoAlert');

      if (!distanceElem || !durationElem || !fareElem || !rideInfoAlert) {
        console.error('[RideScript] One of #distance, #duration, #fare, or #rideInfoAlert not found');
        return;
      }

      distanceElem.textContent = distanceInKm.toFixed(2);
      durationElem.textContent = durationInMin;
      fareElem.textContent = fareAmount.toFixed(2);
      rideInfoAlert.classList.remove('d-none');
    } else {
      console.error('[RideScript] DirectionsService failed:', status);
      alert('Could not calculate directions. Please try again.');
    }
  });
}

function calculateFare(distanceInKm, pickupTimeStr) {
  console.log('[RideScript] calculateFare() with', distanceInKm, pickupTimeStr);
  const pickupDate = new Date(pickupTimeStr);
  const hour = pickupDate.getHours();

  let baseFare, ratePerKm;

  if (hour >= 8 && hour < 20) {
    baseFare = 4.4;
    ratePerKm = 1.32;
  } else {
    baseFare = 5.4;
    ratePerKm = 1.81;
  }

  return baseFare + ratePerKm * distanceInKm;
}

function setupFormListeners() {
  console.log('[RideScript] setupFormListeners()');
  const pickupTimeInput = document.getElementById('pickupTime');
  const employeeSelect = document.getElementById('employee');
  const bookRideBtn = document.getElementById('bookRideBtn');
  const paymentSelect = document.getElementById('paymentSource');

  if (pickupTimeInput) {
    pickupTimeInput.addEventListener('change', calculateDistanceAndFare);
  }

  if (employeeSelect) {
    employeeSelect.addEventListener('change', function () {
      const selectedOption = this.options[this.selectedIndex];
      document.getElementById('employeeName').value = selectedOption.textContent;
    });
  }

  if (bookRideBtn) {
    bookRideBtn.addEventListener('click', validateAndSubmitForm);
  }

  // Watch payment source
  if (paymentSelect) {
    paymentSelect.addEventListener('change', (e) => {
      if (e.target.value === "Credit Card") {
        showStripePaymentUI();
      } else {
        hideStripePaymentUI();
      }
    });
  }
}

// ==== STRIPE LOGIC ====

function showStripePaymentUI() {
  console.log('[Stripe] showStripePaymentUI()');
  const section = document.getElementById("paymentSection");
  if (!section) return;
  section.classList.remove("d-none");

  // Get client secret from server
  fetch("php/create_payment.php", { method: "POST" })
    .then(res => res.json())
    .then(data => {
      clientSecret = data.clientSecret;
      elements = stripe.elements({ clientSecret });
      const paymentElement = elements.create("payment");
      paymentElement.mount("#payment-element");
    })
    .catch(err => console.error("[Stripe] create_payment error:", err));
}

function hideStripePaymentUI() {
  const section = document.getElementById("paymentSection");
  if (section) section.classList.add("d-none");
}

async function processStripePayment() {
  console.log("[Stripe] processStripePayment()");
  const { error } = await stripe.confirmPayment({
    elements,
    confirmParams: {
      return_url: window.location.href,
    },
    redirect: "if_required"
  });

  if (error) {
    alert("Payment failed: " + error.message);
    throw error;
  } else {
    console.log("[Stripe] Payment success");
    return true;
  }
}

// ==== FORM SUBMISSION ====

async function validateAndSubmitForm() {
  console.log('[RideScript] validateAndSubmitForm()');

  const employee_id = document.getElementById('employee')?.value;
  const employee_name = document.getElementById('employeeName')?.value;
  const pickup = document.getElementById('pickup')?.value;
  const dropoff = document.getElementById('dropoff')?.value;
  const pickupTime = document.getElementById('pickupTime')?.value;
  const carType = document.getElementById('carType')?.value;
  const paymentSource = document.getElementById('paymentSource')?.value;
  const employee_phone = document.getElementById('employeePhone')?.value;

  const distanceText = document.getElementById('distance')?.textContent || '';
  const durationText = document.getElementById('duration')?.textContent || '';
  const fareText = document.getElementById('fare')?.textContent || '';

  if (!employee_id || !employee_name || !pickup || !dropoff || !pickupTime || !carType || !paymentSource) {
    alert('Please fill in all required fields.');
    return;
  }

  // If Credit Card -> process Stripe first
  if (paymentSource === "Credit Card") {
    try {
      const ok = await processStripePayment();
      if (!ok) return; // don’t save ride if payment failed
    } catch (err) {
      console.error("[RideScript] Stripe payment error:", err);
      return;
    }
  }

  const rideData = {
    employee_id,
    employee_name,
    employee_phone,
    pickup,
    dropoff,
    pickupTime,
    carType,
    paymentSource,
    distance: parseFloat(distanceText) || 0,
    eta: parseInt(durationText) || 0,
    fare: parseFloat(fareText) || 0,
  };

  console.log('[RideScript] Sending rideData:', rideData);

  fetch('php/save_ride.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(rideData)
  })
    .then(response => response.json())
    .then(data => {
      console.log('[RideScript] Server response:', data);
      if (data.success) {
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
      } else {
        alert('Error saving ride: ' + (data.message || 'Unknown error'));
      }
    })
    .catch(error => {
      console.error('[RideScript] fetch error:', error);
      alert('Failed to save ride.');
    });
}

// Global variables (pickup/dropoff flow mirrors pw_dispatcher/order.php)
let map, directionsService, directionsRenderer;
let pickupAutocomplete, dropoffAutocomplete;
let mapsReady = false;
let pickupLatLng = null;
let dropoffLatLng = null;

const CORPORATE_RIDES_BC = 'powercab-corporate-rides';
const CORPORATE_RIDES_LS = 'powercab_corporate_rides_refresh';

function notifyDashboardAndRideHistoryUpdated() {
  try {
    const bc = new BroadcastChannel(CORPORATE_RIDES_BC);
    bc.postMessage({ type: 'refresh' });
    bc.close();
  } catch (_) {
    /* ignore */
  }
  try {
    localStorage.setItem(CORPORATE_RIDES_LS, String(Date.now()));
  } catch (_) {
    /* ignore */
  }
}

document.addEventListener('DOMContentLoaded', function () {
  console.log('[RideScript] DOMContentLoaded fired');
  setupFormListeners();
  setDefaultPickupTime();
});

/**
 * Called by Google Maps script tag (callback=initBookRideGoogleMaps), same pattern as order.php initGoogleMaps.
 */
window.initBookRideGoogleMaps = function initBookRideGoogleMaps() {
  if (typeof google === 'undefined' || !google.maps) {
    setTimeout(initBookRideGoogleMaps, 200);
    return;
  }
  const mapFrame = document.getElementById('mapFrame');
  if (!mapFrame) {
    setTimeout(initBookRideGoogleMaps, 100);
    return;
  }

  initMap();
  initAutocomplete();
  mapsReady = true;
};

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

  const acOptions = { fields: ['formatted_address', 'geometry', 'name'] };

  pickupAutocomplete = new google.maps.places.Autocomplete(pickupInput, acOptions);
  dropoffAutocomplete = new google.maps.places.Autocomplete(dropoffInput, acOptions);

  pickupAutocomplete.addListener('place_changed', () => {
    const place = pickupAutocomplete.getPlace();
    if (place && place.formatted_address) {
      pickupInput.value = place.formatted_address;
    }
    if (place && place.geometry && place.geometry.location) {
      pickupLatLng = place.geometry.location;
    }
    calculateDistanceAndFare();
  });
  dropoffAutocomplete.addListener('place_changed', () => {
    const place = dropoffAutocomplete.getPlace();
    if (place && place.formatted_address) {
      dropoffInput.value = place.formatted_address;
    }
    if (place && place.geometry && place.geometry.location) {
      dropoffLatLng = place.geometry.location;
    }
    calculateDistanceAndFare();
  });
}

function calculateDistanceAndFare() {
  console.log('[RideScript] calculateDistanceAndFare()');
  if (!mapsReady || !directionsService || !directionsRenderer) {
    return;
  }
  const pickup = document.getElementById('pickup')?.value;
  const dropoff = document.getElementById('dropoff')?.value;
  const pickupTime = document.getElementById('pickupTime')?.value;

  if (!pickup || !dropoff || !pickupTime) {
    console.log('[RideScript] Missing one of pickup/dropoff/pickupTime');
    return;
  }

  const request = {
    origin: pickupLatLng || pickup,
    destination: dropoffLatLng || dropoff,
    travelMode: google.maps.TravelMode.DRIVING,
  };

  directionsService.route(request, function (result, status) {
    if (status === google.maps.DirectionsStatus.OK) {
      console.log('[RideScript] Directions result OK');
      directionsRenderer.setDirections(result);
      const leg = result.routes[0].legs[0];
      pickupLatLng = leg.start_location;
      dropoffLatLng = leg.end_location;
      const distanceInKm = leg.distance.value / 1000;
      const durationInMin = Math.round(leg.duration.value / 60);
      const fareAmount = calculateFare(distanceInKm, pickupTime);

      const summaryBar = document.getElementById('rideSummaryBar');
      const summaryFare = document.getElementById('summaryFare');
      const summaryDuration = document.getElementById('summaryDuration');
      const summaryDistance = document.getElementById('summaryDistance');

      if (!summaryBar || !summaryFare || !summaryDuration || !summaryDistance) {
        console.error('[RideScript] One of #rideSummaryBar, #summaryFare, #summaryDuration, or #summaryDistance not found');
        return;
      }

      summaryFare.textContent = fareAmount.toFixed(2);
      summaryDuration.textContent = durationInMin;
      summaryDistance.textContent = distanceInKm.toFixed(2);
      summaryBar.classList.remove('d-none');
    } else {
      console.error('[RideScript] DirectionsService failed:', status);
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
  const pickupInput = document.getElementById('pickup');
  const dropoffInput = document.getElementById('dropoff');
  const pickupTimeInput = document.getElementById('pickupTime');
  const employeeSelect = document.getElementById('employee');
  const bookRideBtn = document.getElementById('bookRideBtn');

  [pickupInput, dropoffInput].forEach((el) => {
    if (!el) return;
    el.addEventListener('change', calculateDistanceAndFare);
    el.addEventListener('blur', calculateDistanceAndFare);
  });

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
}

// ==== FORM SUBMISSION ====

function validateAndSubmitForm() {
  console.log('[RideScript] validateAndSubmitForm()');

  const employee_id = document.getElementById('employee')?.value;
  const employee_name = document.getElementById('employeeName')?.value;
  const pickup = document.getElementById('pickup')?.value;
  const dropoff = document.getElementById('dropoff')?.value;
  const pickupTime = document.getElementById('pickupTime')?.value;
  const carType = document.getElementById('carType')?.value;
  const paymentSource = document.getElementById('paymentSource')?.value;
  const employee_phone = document.getElementById('employeePhone')?.value;

  const distanceText = document.getElementById('summaryDistance')?.textContent || '';
  const durationText = document.getElementById('summaryDuration')?.textContent || '';
  const fareText = document.getElementById('summaryFare')?.textContent || '';

  if (!employee_id || !employee_name || !pickup || !dropoff || !pickupTime || !carType || !paymentSource) {
    showToast('Please fill in all required fields.', 'error');
    return;
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

  fetch(new URL('php/save_ride.php', window.location.href).href, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(rideData)
  })
    .then(response => response.json())
    .then(data => {
      console.log('[RideScript] Server response:', data);
      if (data.success) {
        notifyDashboardAndRideHistoryUpdated();
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
      } else {
        showToast(data.message || 'Could not save your ride. Please try again.', 'error');
      }
    })
    .catch(error => {
      console.error('[RideScript] fetch error:', error);
      showToast('Failed to save ride. Please try again.', 'error');
    });
}

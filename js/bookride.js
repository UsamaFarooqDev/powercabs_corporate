// Global variables (pickup/dropoff flow mirrors pw_dispatcher/order.php)
let map, directionsService, directionsRenderer;
let pickupAutocomplete, dropoffAutocomplete;
let mapsReady = false;
let pickupLatLng = null;
let dropoffLatLng = null;
/** Last successful route (mirrors pw_dispatcher/order.php — used to recalc fare when car type changes). */
let currentDistanceKm = null;
let currentDurationMin = null;

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

/** Local YYYY-MM-DDTHH:mm for datetime-local (same wall clock as dispatcher date+time fields). */
function formatDateTimeLocalValue(d) {
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function setDefaultPickupTime() {
  const now = new Date();
  now.setMinutes(now.getMinutes() + 30);
  const elem = document.getElementById('pickupTime');
  if (!elem) return;
  elem.value = formatDateTimeLocalValue(now);
}

/**
 * Same rules as pw_dispatcher/order.php buildPickupDateTime (fare tier uses getHours() on this string).
 */
function buildPickupDateTimeForFare() {
  const v = document.getElementById('pickupTime')?.value?.trim();
  if (v) return v;
  const now = new Date();
  return now.toISOString().slice(0, 16);
}

function initMap() {
  const mapFrame = document.getElementById('mapFrame');
  // bookRide.php uses .br-map; older layout used .map-container
  const mapContainer =
    document.querySelector('.map-container') ||
    (mapFrame && mapFrame.parentElement);
  if (!mapContainer || !mapFrame) return;

  const mapDiv = document.createElement('div');
  mapDiv.id = 'map';
  mapDiv.style.height = '100%';
  mapDiv.style.width = '100%';
  mapDiv.style.borderRadius = '8px';
  mapContainer.replaceChild(mapDiv, mapFrame);

  if (typeof google === 'undefined' || !google.maps) return;

  map = new google.maps.Map(mapDiv, {
    center: { lat: 53.349805, lng: -6.26031 },
    zoom: 12,
  });
  directionsService = new google.maps.DirectionsService();
  directionsRenderer = new google.maps.DirectionsRenderer({ map });
}

function initAutocomplete() {
  const pickupInput = document.getElementById('pickup');
  const dropoffInput = document.getElementById('dropoff');
  if (!pickupInput || !dropoffInput) return;
  if (!google || !google.maps || !google.maps.places) return;

  // Match pw_dispatcher/order.php (no Autocomplete options — same Places behavior)
  pickupAutocomplete = new google.maps.places.Autocomplete(pickupInput);
  dropoffAutocomplete = new google.maps.places.Autocomplete(dropoffInput);

  pickupAutocomplete.addListener('place_changed', () => {
    const place = pickupAutocomplete.getPlace();
    if (place && place.formatted_address) {
      pickupInput.value = place.formatted_address;
    }
    if (place && place.geometry) {
      pickupLatLng = place.geometry.location;
    }
    calculateDistanceAndFare();
  });
  dropoffAutocomplete.addListener('place_changed', () => {
    const place = dropoffAutocomplete.getPlace();
    if (place && place.formatted_address) {
      dropoffInput.value = place.formatted_address;
    }
    if (place && place.geometry) {
      dropoffLatLng = place.geometry.location;
    }
    calculateDistanceAndFare();
  });
}

function calculateDistanceAndFare() {
  if (!mapsReady || !directionsService || !directionsRenderer) return;

  const pickup = document.getElementById('pickup')?.value;
  const dropoff = document.getElementById('dropoff')?.value;
  const pickupTime = document.getElementById('pickupTime')?.value;

  if (!pickup || !dropoff || !pickupTime?.trim()) {
    currentDistanceKm = null;
    currentDurationMin = null;
    return;
  }

  const request = {
    origin: pickupLatLng || pickup,
    destination: dropoffLatLng || dropoff,
    travelMode: google.maps.TravelMode.DRIVING,
  };

  directionsService.route(request, function (result, status) {
    if (status === google.maps.DirectionsStatus.OK) {
      directionsRenderer.setDirections(result);
      const leg = result.routes[0].legs[0];
      const distanceInKm = leg.distance.value / 1000;
      const durationInMin = Math.round(leg.duration.value / 60);
      currentDistanceKm = distanceInKm;
      currentDurationMin = durationInMin;
      const pickupTimeStr = buildPickupDateTimeForFare();
      const carType = document.getElementById('carType')?.value || 'Economy';
      const fareAmount = calculateFare(distanceInKm, durationInMin, pickupTimeStr, carType);
      pickupLatLng = leg.start_location;
      dropoffLatLng = leg.end_location;

      const summaryBar = document.getElementById('rideSummaryBar');
      const summaryFare = document.getElementById('summaryFare');
      const summaryDuration = document.getElementById('summaryDuration');
      const summaryDistance = document.getElementById('summaryDistance');

      if (!summaryBar || !summaryFare || !summaryDuration || !summaryDistance) return;

      summaryFare.textContent = fareAmount.toFixed(2);
      summaryDuration.textContent = durationInMin;
      summaryDistance.textContent = distanceInKm.toFixed(2);
      summaryBar.classList.remove('d-none');
    } else {
      currentDistanceKm = null;
      currentDurationMin = null;
    }
  });
}

/**
 * Copied from pw_dispatcher/order.php — keep in sync manually if pricing changes.
 */
function calculateFare(distanceKm, durationMin, pickupTimeStr, rideType) {
  const pickupDate = new Date(pickupTimeStr);
  const hour = pickupDate.getHours();
  const initialFare = 3.0;
  let baseFare, ratePerKm, ratePerMinute;
  if (hour >= 8 && hour < 20) {
    baseFare = 4.4;
    ratePerKm = 1.32;
    ratePerMinute = 0.2;
  } else {
    baseFare = 5.4;
    ratePerKm = 1.81;
    ratePerMinute = 0.3;
  }
  const rawFare =
    initialFare +
    baseFare +
    distanceKm * ratePerKm +
    (durationMin || 0) * ratePerMinute;
  const multipliers = {
    'Economy': 1.0,
    'Economy XL': 1.2,
    'Business': 1.0,
    'Business Plus': 1.2,
    'Limousine': 2.0,
    'Wheelchair accessible': 1.1,
    'Wheelchair Taxi': 1.1,
    'Pets Taxi': 1.15,
    'Courier / Parcel': 0.9,
  };
  const multiplier = multipliers[rideType] ?? 1.0;
  return Math.round(rawFare * multiplier * 100) / 100;
}

function recalculateFareForCurrentRoute() {
  if (currentDistanceKm == null || currentDurationMin == null) return;

  const pickupTimeStr = document.getElementById('pickupTime')?.value?.trim();
  if (!pickupTimeStr) return;

  const carType = document.getElementById('carType')?.value || 'Economy';
  const fareAmount = calculateFare(
    currentDistanceKm,
    currentDurationMin,
    pickupTimeStr,
    carType
  );
  const summaryFare = document.getElementById('summaryFare');
  if (summaryFare) {
    summaryFare.textContent = fareAmount.toFixed(2);
  }
}

function setupFormListeners() {
  const pickupInput = document.getElementById('pickup');
  const dropoffInput = document.getElementById('dropoff');
  const pickupTimeInput = document.getElementById('pickupTime');
  const carTypeSelect = document.getElementById('carType');
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

  if (carTypeSelect) {
    carTypeSelect.addEventListener('change', () => {
      recalculateFareForCurrentRoute();
    });
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

function setBookBtnLoading(isLoading) {
  const btn = document.getElementById('bookRideBtn');
  if (!btn) return;
  if (isLoading) {
    btn.disabled = true;
    btn.dataset.originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Booking ride…';
  } else {
    btn.disabled = false;
    if (btn.dataset.originalHtml) {
      btn.innerHTML = btn.dataset.originalHtml;
    }
  }
}

function resetRideForm() {
  const form = document.getElementById('rideForm');
  if (!form) return;
  form.reset();
  // Hidden fields & computed values
  const employeeName = document.getElementById('employeeName');
  if (employeeName) employeeName.value = '';
  const summary = document.getElementById('rideSummaryBar');
  if (summary) summary.classList.add('d-none');
  ['summaryFare','summaryDuration','summaryDistance'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = '0';
  });
  // Clear autocomplete session route state if used
  window.__rideRouteCache = null;
}

function validateAndSubmitForm() {
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

  setBookBtnLoading(true);

  fetch(new URL('php/save_ride.php', window.location.href).href, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(rideData)
  })
    .then(response => response.json())
    .then(data => {
      setBookBtnLoading(false);
      if (data.success) {
        notifyDashboardAndRideHistoryUpdated();
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
      } else {
        showToast(data.message || 'Could not save your ride. Please try again.', 'error');
      }
    })
    .catch(() => {
      setBookBtnLoading(false);
      showToast('Failed to save ride. Please try again.', 'error');
    });
}

// Reset the form when the "Book Another" button (inside success modal) is clicked
document.addEventListener('DOMContentLoaded', () => {
  const successModalEl = document.getElementById('successModal');
  if (successModalEl) {
    successModalEl.addEventListener('hidden.bs.modal', resetRideForm);
  }
});

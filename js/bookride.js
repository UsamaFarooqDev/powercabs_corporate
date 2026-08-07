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

// 'now' (Book Now — dispatch immediately, current time used at submit) or
// 'schedule' (Schedule for Later — the #pickupTime field drives it).
let bookingMode = 'now';

function setDefaultPickupTime() {
  const now = new Date();
  now.setMinutes(now.getMinutes() + 30);
  const elem = document.getElementById('pickupTime');
  if (!elem) return;
  elem.value = formatDateTimeLocalValue(now);
}

/**
 * Pickup time used for the live fare estimate (day/night rate tier). In 'now'
 * mode this is always the current moment, fetched fresh — never a stale
 * pre-filled value. In 'schedule' mode it's whatever the corporate user has
 * picked in #pickupTime so far.
 */
function buildPickupDateTimeForFare() {
  if (bookingMode === 'schedule') {
    const v = document.getElementById('pickupTime')?.value?.trim();
    if (v) return v;
  }
  return formatDateTimeLocalValue(new Date());
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
  // In 'schedule' mode the fare tier depends on the picked date/time, so wait
  // for it. In 'now' mode buildPickupDateTimeForFare() always has a value
  // (the current moment), so there's nothing to wait for.
  const scheduleTimeMissing = bookingMode === 'schedule' && !document.getElementById('pickupTime')?.value?.trim();

  if (!pickup || !dropoff || scheduleTimeMissing) {
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
      const fareAmount = quotedFare(distanceInKm, durationInMin, pickupTimeStr, carType);
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
    'Parcel Delivery': 0.9,
    'Courier / Parcel': 0.9,
  };
  const multiplier = multipliers[rideType] ?? 1.0;
  return Math.round(rawFare * multiplier * 100) / 100;
}

/**
 * Flat per-ride fee for accounts on the revenue billing model (0 otherwise).
 * Supplied by bookRide.php from the company's `corporate` row.
 */
function revenueFixedFee() {
  const cfg = window.PC_BILLING || {};
  if (String(cfg.model || '').toLowerCase() !== 'revenue') return 0;
  const fee = parseFloat(cfg.fixedFee);
  return Number.isFinite(fee) && fee > 0 ? fee : 0;
}

/**
 * What the company is actually quoted: metered fare + the revenue-model fixed
 * fee. The fee is flat, so it is added after the ride-type multiplier.
 */
function quotedFare(distanceKm, durationMin, pickupTimeStr, rideType) {
  const metered = calculateFare(distanceKm, durationMin, pickupTimeStr, rideType);
  return Math.round((metered + revenueFixedFee()) * 100) / 100;
}

function recalculateFareForCurrentRoute() {
  if (currentDistanceKm == null || currentDurationMin == null) return;
  if (bookingMode === 'schedule' && !document.getElementById('pickupTime')?.value?.trim()) return;

  const pickupTimeStr = buildPickupDateTimeForFare();

  const carType    = document.getElementById('carType')?.value || 'Economy';
  const fareAmount = quotedFare(currentDistanceKm, currentDurationMin, pickupTimeStr, carType);
  const summaryFare = document.getElementById('summaryFare');
  if (summaryFare) summaryFare.textContent = fareAmount.toFixed(2);
  updateCreditHints(fareAmount);
}

// ── Passenger type helpers ──────────────────────────────
function getPassengerType() {
  return document.querySelector('input[name="passengerType"]:checked')?.value || 'employee';
}
function applyPassengerType(type) {
  const isEmp   = type === 'employee' || type === 'both';
  const isGuest = type === 'guest'    || type === 'both';
  const employeeRow = document.getElementById('employeeRow');
  const guestFields = document.getElementById('guestFields');
  if (employeeRow) employeeRow.style.display = isEmp   ? '' : 'none';
  if (guestFields) guestFields.style.display = isGuest ? '' : 'none';
}

function setupFormListeners() {
  const pickupInput = document.getElementById('pickup');
  const dropoffInput = document.getElementById('dropoff');
  const pickupTimeInput = document.getElementById('pickupTime');
  const carTypeSelect = document.getElementById('carType');
  const employeeSelect = document.getElementById('employee');
  const rideTypeGrid = document.getElementById('rideTypeGrid');
  const passengerTypeGrid = document.getElementById('passengerTypeGrid');

  // Passenger type card clicks
  passengerTypeGrid?.addEventListener('click', (e) => {
    const card = e.target.closest('.br-pax-card');
    if (!card) return;
    passengerTypeGrid.querySelectorAll('.br-pax-card').forEach(c => c.classList.remove('is-selected'));
    card.classList.add('is-selected');
    const radio = card.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    applyPassengerType(card.dataset.pax || 'employee');
  });

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

  if (rideTypeGrid && carTypeSelect) {
    rideTypeGrid.addEventListener('click', (e) => {
      const card = e.target.closest('.br-ride-card');
      if (!card) return;
      const value = card.dataset.value;
      if (!value) return;
      rideTypeGrid.querySelectorAll('.br-ride-card').forEach(c => c.classList.remove('is-selected'));
      card.classList.add('is-selected');
      const radio = card.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
      if (carTypeSelect.value !== value) {
        carTypeSelect.value = value;
        carTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  }

  if (employeeSelect) {
    employeeSelect.addEventListener('change', function () {
      const selectedOption = this.options[this.selectedIndex];
      document.getElementById('employeeName').value = selectedOption.textContent;
    });
  }

  // ── Book Now / Schedule for Later ───────────────────────
  const bookNowBtn      = document.getElementById('bookNowBtn');
  const scheduleLaterBtn = document.getElementById('scheduleLaterBtn');

  bookNowBtn?.addEventListener('click', () => {
    if (bookingMode === 'schedule') collapseScheduleFields();
    validateAndSubmitForm('pending');
  });

  scheduleLaterBtn?.addEventListener('click', () => {
    if (bookingMode !== 'schedule') {
      revealScheduleFields();
      return;
    }
    validateAndSubmitForm('scheduled');
  });

  // Credit payment — validate live when selection changes
  const paymentSelect = document.getElementById('paymentSource');
  paymentSelect?.addEventListener('change', () => {
    const fare = parseFloat(document.getElementById('summaryFare')?.textContent || '0');
    updateCreditHints(fare);
  });
}

/** Reveals the pickup date/time picker and turns scheduleLaterBtn into the confirm action. */
function revealScheduleFields() {
  bookingMode = 'schedule';
  const row    = document.getElementById('scheduleDateTimeRow');
  const hint   = document.getElementById('scheduleDateTimeHint');
  const divider = document.getElementById('scheduleDateTimeDivider');
  const label  = document.getElementById('scheduleLaterBtnLabel');
  const btn    = document.getElementById('scheduleLaterBtn');
  if (row) row.style.display = '';
  if (hint) hint.style.display = '';
  if (divider) divider.style.display = '';
  if (label) label.textContent = 'Confirm Schedule';
  btn?.classList.add('is-active');
  document.getElementById('pickupTime')?.focus();
  calculateDistanceAndFare();
}

/** Reverts to Book Now mode — hides the picker and restores the button label. */
function collapseScheduleFields() {
  bookingMode = 'now';
  const row    = document.getElementById('scheduleDateTimeRow');
  const hint   = document.getElementById('scheduleDateTimeHint');
  const divider = document.getElementById('scheduleDateTimeDivider');
  const label  = document.getElementById('scheduleLaterBtnLabel');
  const btn    = document.getElementById('scheduleLaterBtn');
  if (row) row.style.display = 'none';
  if (hint) hint.style.display = 'none';
  if (divider) divider.style.display = 'none';
  if (label) label.textContent = 'Schedule for Later';
  btn?.classList.remove('is-active');
  calculateDistanceAndFare();
}

// ==== CREDIT BALANCE ====

function creditBalance() {
  return parseFloat(window.PC_BILLING?.creditBalance || 0);
}

function updateCreditHints(fareAmount) {
  const paymentSelect = document.getElementById('paymentSource');
  const insufficientHint = document.getElementById('creditInsufficientHint');
  if (!paymentSelect) return;

  const creditOpt = paymentSelect.querySelector('option[value="corporate_credit"]');
  if (!creditOpt) return;

  const balance = creditBalance();
  const fare    = parseFloat(fareAmount) || 0;

  if (fare > 0 && balance < fare) {
    // Not enough credits — disable option and show warning when selected
    creditOpt.disabled = true;
    if (paymentSelect.value === 'corporate_credit') {
      paymentSelect.value = 'Cash';
    }
    if (insufficientHint) insufficientHint.style.display = 'block';
  } else {
    creditOpt.disabled = false;
    if (insufficientHint) insufficientHint.style.display = 'none';
  }

  // Update the option label with live fare context
  if (fare > 0) {
    creditOpt.textContent = balance >= fare
      ? `Use Credits (€${balance.toFixed(2)} available ✓ covers this ride)`
      : `Use Credits (€${balance.toFixed(2)} available — need €${fare.toFixed(2)})`;
  } else {
    creditOpt.textContent = `Use Credits (€${balance.toFixed(2)} available)`;
  }
}

// ==== FORM SUBMISSION ====

function setBookBtnLoading(isLoading, status) {
  const activeId = status === 'scheduled' ? 'scheduleLaterBtn' : 'bookNowBtn';
  const activeBtn = document.getElementById(activeId);
  const otherBtn  = document.getElementById(activeId === 'bookNowBtn' ? 'scheduleLaterBtn' : 'bookNowBtn');
  if (otherBtn) otherBtn.disabled = isLoading;
  if (!activeBtn) return;
  if (isLoading) {
    activeBtn.disabled = true;
    activeBtn.dataset.originalHtml = activeBtn.innerHTML;
    activeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Booking ride…';
  } else {
    activeBtn.disabled = false;
    if (activeBtn.dataset.originalHtml) {
      activeBtn.innerHTML = activeBtn.dataset.originalHtml;
    }
  }
}

function resetRideForm() {
  const form = document.getElementById('rideForm');
  if (!form) return;
  form.reset();
  // Reset passenger type to Employee
  const passengerTypeGrid = document.getElementById('passengerTypeGrid');
  passengerTypeGrid?.querySelectorAll('.br-pax-card').forEach((c, i) =>
    c.classList.toggle('is-selected', i === 0));
  applyPassengerType('employee');
  // Hidden fields & computed values
  const employeeName = document.getElementById('employeeName');
  if (employeeName) employeeName.value = '';
  const carType = document.getElementById('carType');
  const rideTypeGrid = document.getElementById('rideTypeGrid');
  const firstCard = rideTypeGrid?.querySelector('.br-ride-card');
  const defaultRide = firstCard?.dataset.value || 'Economy';
  if (carType) carType.value = defaultRide;
  if (rideTypeGrid) {
    rideTypeGrid.querySelectorAll('.br-ride-card').forEach(c => {
      c.classList.toggle('is-selected', c.dataset.value === defaultRide);
    });
  }
  const summary = document.getElementById('rideSummaryBar');
  if (summary) summary.classList.add('d-none');
  ['summaryFare','summaryDuration','summaryDistance'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = '0';
  });
  // Back to Book Now mode, fresh default time ready for next time Schedule is opened
  collapseScheduleFields();
  setDefaultPickupTime();
  // Clear autocomplete session route state if used
  window.__rideRouteCache = null;
}

/**
 * @param {'pending'|'scheduled'} status 'pending' = Book Now (dispatch immediately,
 *   current time), 'scheduled' = Schedule for Later (uses #pickupTime).
 */
function validateAndSubmitForm(status) {
  const passengerType = getPassengerType();
  const isEmployee = passengerType === 'employee' || passengerType === 'both';
  const isGuest    = passengerType === 'guest'    || passengerType === 'both';

  const employee_id   = document.getElementById('employee')?.value;
  const employee_name = document.getElementById('employeeName')?.value;
  const guestName     = document.getElementById('guestName')?.value?.trim()  || '';
  const guestPhone    = document.getElementById('guestPhone')?.value?.trim() || '';
  const pickup        = document.getElementById('pickup')?.value;
  const dropoff       = document.getElementById('dropoff')?.value;
  const carType       = document.getElementById('carType')?.value;
  const paymentSource = document.getElementById('paymentSource')?.value;

  const distanceText = document.getElementById('summaryDistance')?.textContent || '';
  const durationText = document.getElementById('summaryDuration')?.textContent || '';
  const fareText     = document.getElementById('summaryFare')?.textContent     || '';

  if (!pickup || !dropoff || !carType || !paymentSource) {
    showToast('Please fill in all required fields.', 'error');
    return;
  }

  // Book Now always uses the actual current moment, fetched fresh right here —
  // never a stale pre-filled value. Schedule Later requires a picked, future time.
  let pickupTime;
  if (status === 'scheduled') {
    pickupTime = document.getElementById('pickupTime')?.value;
    if (!pickupTime) {
      showToast('Please pick a date and time to schedule this ride.', 'error');
      return;
    }
    if (new Date(pickupTime) <= new Date()) {
      showToast('Scheduled time must be in the future.', 'error');
      return;
    }
  } else {
    pickupTime = formatDateTimeLocalValue(new Date());
  }

  if (paymentSource === 'corporate_credit') {
    const fare    = parseFloat(fareText) || 0;
    const balance = creditBalance();
    if (balance <= 0 || fare > balance) {
      showToast(`Insufficient credits. You have €${balance.toFixed(2)} but this ride costs €${fare.toFixed(2)}.`, 'error');
      return;
    }
  }
  if (isEmployee && (!employee_id || !employee_name)) {
    showToast('Please select an employee.', 'error');
    return;
  }
  if (isGuest && !guestName) {
    showToast('Please enter the guest name.', 'error');
    return;
  }

  // For guest-only rides use guest name as the passenger label
  const effectiveEmpName = isEmployee ? employee_name : guestName;
  const effectiveEmpId   = isEmployee ? employee_id   : '';

  const rideData = {
    passenger_type: passengerType,
    employee_id:    effectiveEmpId,
    employee_name:  effectiveEmpName,
    guest_name:     guestName,
    guest_phone:    guestPhone,
    pickup,
    dropoff,
    pickupTime,
    carType,
    paymentSource,
    distance: parseFloat(distanceText) || 0,
    eta:      parseInt(durationText)   || 0,
    fare:     parseFloat(fareText)     || 0,
    status,
    pickup_lat: pickupLatLng  ? pickupLatLng.lat()  : null,
    pickup_lng: pickupLatLng  ? pickupLatLng.lng()  : null,
    dest_lat:   dropoffLatLng ? dropoffLatLng.lat() : null,
    dest_lng:   dropoffLatLng ? dropoffLatLng.lng() : null,
  };

  setBookBtnLoading(true, status);

  fetch(new URL('php/save_ride.php', window.location.href).href, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(rideData)
  })
    .then(response => response.json())
    .then(data => {
      setBookBtnLoading(false, status);
      if (data.success) {
        notifyDashboardAndRideHistoryUpdated();
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
      } else {
        showToast(data.message || 'Could not save your ride. Please try again.', 'error');
      }
    })
    .catch(() => {
      setBookBtnLoading(false, status);
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

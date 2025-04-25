'use strict';

// --- Global Booking State ---
var selectedCabin    = null;       // currently selected cabin element
var selectedDates    = [];         // array of YYYY-MM-DD strings
var bookedActivities = [];         // array of selected activity IDs

// --- Debounce Utility ---
function debounce(fn, delay) {
  var timer;
  return function() {
    var args = arguments;
    clearTimeout(timer);
    timer = setTimeout(function() {
      fn.apply(null, args);
    }, delay);
  };
}

function handleBookingSubmit(event) {
  event.preventDefault();

  // Validate dates
  const start = document.getElementById('hbc-start-date').value;
  const end   = document.getElementById('hbc-end-date').value;
  if (!start || !end) { alert('Please select a start and end date.'); return; }

  // Validate contact info
  const name  = document.getElementById('hbc-name').value.trim();
  const email = document.getElementById('hbc-email').value.trim();
  const phone = document.getElementById('hbc-phone').value.trim();
  if (!name || !email || !phone) { alert('Please fill in your name, email, and phone.'); return; }


  // Store cabin id and reservation name in cookies for 5 minutes (redirecting with url did not work so this is a workaround)
  const cabinVal = document.getElementById('hbc-cabin').value;
  const nameVal  = encodeURIComponent(document.getElementById('hbc-name').value.trim());
  const maxAge   = 300;

  document.cookie = `hbc_cabin=${cabinVal};path=/;max-age=${maxAge}`;
  document.cookie = `hbc_name=${nameVal};path=/;max-age=${maxAge}`;

  // Build payload
  const formData = new FormData();
  formData.append('action', 'hbc_book_date');
  formData.append('name', name);
  formData.append('email', email);
  formData.append('phone', phone);
  formData.append('cabin_id', document.getElementById('hbc-cabin').value);
  formData.append('start_date', start);
  formData.append('end_date', end);
  formData.append('booked_activities', document.getElementById('hbc-booked-activities').value);
  const priceText = document.getElementById('hbc-reservation-price').textContent || '';
  const priceVal  = priceText.replace('€', '').trim();
  formData.append('reservation_price', priceVal);

  // UI feedback & AJAX
  const btn = document.getElementById('hbc-book-btn');
  const orig = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Booking...';

  fetch(hbc_ajax.ajaxurl, {
    method: 'POST', credentials: 'same-origin', body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      window.location.href = hbc_confirmation_url;
    } else {
      alert(data.data.message || 'Booking failed.');
    }
  })
  .catch(err => {
    console.error('Booking error:', err);
    alert('An unexpected error occurred.');
  })
  .finally(() => {
    btn.disabled = false;
    btn.textContent = orig;
  });
}

// --- Initialization ---
function initBooking() {
  // Always init calendar if present (cabin.php & form.php)
  var dateEl = document.getElementById('hbc-datepicker');
  if (dateEl) initDatepicker(dateEl);

  // Booking interactions only on form.php (booking container exists)
  var bookingContainer = document.getElementById('hbc-booking-container');
  if (!bookingContainer) return;

  // Cabin/activity click events (form page only)
  bookingContainer.addEventListener('click', handleBookingClick);

  // Initial pricing update
  updatePricingDetails();

  // Booking submission button
  var bookBtn = document.getElementById('hbc-book-btn');
  if (bookBtn) {
    bookBtn.addEventListener('click', handleBookingSubmit);
  }
}

document.addEventListener('DOMContentLoaded', initBooking);

// --- Flatpickr Setup ---
var fpInstance = null;
function parseYMD(str) {
  var p = str.split('-');
  return new Date(+p[0], p[1]-1, +p[2]);
}

function getDateRangeArray(from, to) {
  var arr = [];
  for (var d = new Date(from); d <= to; d.setDate(d.getDate()+1)) {
    arr.push(d.toISOString().slice(0,10));
  }
  return arr;
}

function initDatepicker(el) {
  var cabinId = el.dataset.cabinId;
  var url = hbc_ajax.ajaxurl + '?action=hbc_get_bookings&cabin_id=' + cabinId;

  fetch(url, { credentials: 'same-origin' })
    .then(res => res.json())
    .then(json => {
      // Build Date objects
      var ranges = (json.success?json.data:[]).map(b=>({
        from: parseYMD(b.start),
        to:   parseYMD(b.end)
      }));

      // Build a set of all booked ISO strings
      var bookedDatesSet = new Set();
      ranges.forEach(r=>{
        getDateRangeArray(r.from, r.to).forEach(d=>bookedDatesSet.add(d));
      });

      // Destroy old fp
      if (fpInstance) fpInstance.destroy();

      fpInstance = flatpickr(el, {
        inline: true,
        mode:   'range',
        dateFormat: 'Y-m-d',
        minDate:    'today',
        disable:    ranges,

        onDayCreate: function(dObj, dStr, fp, dayElem) {
          var d = dayElem.dateObj;
          var iso = d.toISOString().slice(0,10);

          // Past dates
          var today = new Date();
          today = new Date(today.getFullYear(),today.getMonth(),today.getDate());
          if (d < today) {
            dayElem.classList.add('fp-past');
            return;
          }

          // Booked dates
          if (bookedDatesSet.has(iso)) {
            dayElem.classList.add('fp-unavailable');
            dayElem.classList.remove('disabled');
            return;
          } else {
            dayElem.classList.add('fp-available');
          }
          // Future default
        },

        // Keep onChange for populating inputs
        onChange: debounce(function(dates) {
          selectedDates = dates.map(function(d) {
            var y = d.getFullYear();
            var m = ('0' + (d.getMonth() + 1)).slice(-2);
            var day = ('0' + d.getDate()).slice(-2);
            return y + '-' + m + '-' + day;
          });
          var startInput = document.getElementById('hbc-start-date');
          var endInput   = document.getElementById('hbc-end-date');
          if (startInput && selectedDates.length) {
            startInput.value = selectedDates[0];
          }
          if (endInput && selectedDates.length > 1) {
            endInput.value = selectedDates[selectedDates.length - 1];
          }

          // Show activities once range selected
          var activitiesContainer = document.getElementById('hbc-activities-container');
          if (activitiesContainer) {
            activitiesContainer.style.display = selectedDates.length > 1 ? 'block' : 'none';
          }

          updatePricingDetails();
        }, 300 )
      });
    })
    .catch(function(err) {
      console.error('Error fetching bookings:', err);
    });
}

// --- Click Handler for Cabin & Activities ---
function handleBookingClick(e) {
  var target = e.target;

  // Activity toggle
  if (target.classList.contains('select-activity-btn') ||
      target.classList.contains('remove-activity-btn')) {
    var activityDiv = target.closest('.hbc-activity');
    if (!activityDiv) return;
    var id = parseInt(activityDiv.dataset.activityId, 10);
    if (bookedActivities.includes(id)) {
      bookedActivities = bookedActivities.filter(function(v) { return v !== id; });
      activityDiv.classList.remove('selected-activity');
      activityDiv.querySelector('.select-activity-btn').style.display = 'block';
      activityDiv.querySelector('.remove-activity-btn').style.display = 'none';
    } else {
      bookedActivities.push(id);
      activityDiv.classList.add('selected-activity');
      activityDiv.querySelector('.select-activity-btn').style.display = 'none';
      activityDiv.querySelector('.remove-activity-btn').style.display = 'block';
    }
    // Update hidden field
    var bookedField = document.getElementById('hbc-booked-activities');
    if (bookedField) bookedField.value = JSON.stringify(bookedActivities);

    updatePricingDetails();
  }
}

// --- Live Pricing Update ---
function updatePricingDetails() {
  var container = document.getElementById('hbc-pricing-container');
  var det       = document.getElementById('hbc-reservation-details');
  var acts      = document.getElementById('hbc-selected-activities');
  var priceEl   = document.getElementById('hbc-reservation-price');

  // --- Cabin Name ---
  var staticName = container.dataset.cabinName;
  var cabinName = selectedCabin
                    ? selectedCabin.querySelector('h3').textContent
                    : staticName;
  if (det) det.innerHTML = '<p>' + cabinName + '</p>';

  // --- Cabin Cost ---
  var cabinPricePerNight = parseFloat(container.dataset.cabinPrice) || 0;
  var numNights = parseInt(container.dataset.numNights, 10) || 0;
  var cabinTotal = cabinPricePerNight * numNights;

  // --- Activities Total ---
  var activitiesTotal = 0;
  if (bookedActivities.length) {
    var html = bookedActivities.map(function(id) {
      var el = document.querySelector('.hbc-activity[data-activity-id="'+id+'"]');
      var title = el.querySelector('h3').textContent;
      var price = parseFloat(el.dataset.price) || 0;
      activitiesTotal += price;
      return title + ' - ' + price.toFixed(2) + '€';
    }).join('<br>');
    if (acts) acts.innerHTML = html;
  } else if (acts) {
    acts.innerHTML = 'No activities selected.';
  }

  // --- Total Price ---
  var total = cabinTotal + activitiesTotal;
  if (priceEl) priceEl.innerHTML = total.toFixed(2) + '€';
}
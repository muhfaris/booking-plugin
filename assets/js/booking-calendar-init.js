document.addEventListener("DOMContentLoaded", function () {
  var calendarEl = document.getElementById("booking-calendar"); // Elemen target
  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth", // Tampilan awal
    // events: "/?rest_route=/booking-plugin/v1/confirmed-bookings", // Endpoint REST API untuk event
    eventSources: [
      {
        url: "/?rest_route=/booking-plugin/v1/confirmed-bookings",
        method: "GET",
        extraParams: { _wpnonce: wpApiSettings.nonce },
      },
    ],
    weekNumbers: true,
    nowIndicator: true,
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay",
    },
    locale: "id", // Mengatur bahasa ke Indonesia
    dateClick: function (info) {
      alert("clicked " + info.dateStr);
    },
    select: function (info) {
      alert("selected " + info.startStr + " to " + info.endStr);
    },
    businessHours: {
      // days of week. an array of zero-based day of week integers (0=Sunday)
      daysOfWeek: [1, 2, 3, 4, 5], // Monday - Thursday

      startTime: "10:00", // a start time (10am in this example)
      endTime: "18:00", // an end time (6pm in this example)
    },
  });
  calendar.render(); // Render kalender
});

document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  var today = new Date(); // Get current date
  var calendar = new FullCalendar.Calendar(calendarEl, {
    // height: '100%',
    expandRows: true,
    slotMinTime: '08:00',
    slotMaxTime: '20:00',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },
    initialView: 'dayGridMonth',
    initialDate: today.getFullYear() + '-' + ('0' + (today.getMonth() + 1)).slice(-2) + '-' + ('0' + today.getDate()).slice(-2), // Format current date as YYYY-MM-DD
    navLinks: true, // can click day/week names to navigate views
    editable: true,
    selectable: true,
    nowIndicator: true,
    dayMaxEvents: true, // allow "more" link when too many events
    events:epEventsData.events
  });

  calendar.render();
});

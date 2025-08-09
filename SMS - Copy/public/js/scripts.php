<?php include_once APP_HEAD_FILE; ?>
<!-- ✅ jQuery (full version) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha384-..." crossorigin="anonymous"></script>

<!-- ✅ Bootstrap 5 Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>

<!-- ✅ Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" integrity="sha384-..." crossorigin="anonymous"></script>

<!-- ✅ FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<!-- ✅ DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- ✅ Tippy.js (for enhanced tooltips) -->
<script src="https://cdn.jsdelivr.net/npm/tippy.js@6.3.7/dist/tippy-bundle.umd.min.js"></script>

<!-- ✅ Slick Carousel (if needed) -->
<script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

<!-- ✅ Swiper JS (modern alternative to Slick) -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- ✅ Your own app-specific JS -->
<script src="<?php echo APP_PUBLIC_URL . '/bootstrap/bootstrap.js'; ?>"></script>


<!-- toggle nav -->
<script>
  console.log("Hello");

  const toggleNav = document.getElementById('toggleNav');
  const navItems1 = document.getElementById('navItems_1');
  const navItems2 = document.getElementById('navItems_2');
  const floatingNav = document.getElementById('floatingNav');

  toggleNav.addEventListener('click', (event) => {
    event.stopPropagation(); // Prevent click from bubbling up

    // Toggle expanded class on both nav items
    const isExpanded = navItems1.classList.toggle('expanded');
    navItems2.classList.toggle('expanded', isExpanded);

    // Update aria-expanded and icon
    toggleNav.setAttribute('aria-expanded', isExpanded);
    toggleNav.innerHTML = isExpanded
      ? '<i class="fas fa-times"></i>'
      : '<i class="fas fa-bars"></i>';
  });

  // Close nav if clicking outside floatingNav
  document.addEventListener('click', (event) => {
    if (!floatingNav.contains(event.target)) {
      navItems1.classList.remove('expanded');
      navItems2.classList.remove('expanded');
      toggleNav.setAttribute('aria-expanded', 'false');
      toggleNav.innerHTML = '<i class="fas fa-bars"></i>';
    }
  });

  // Hide floatingNav button on scroll past 20px, else show it
  window.addEventListener('scroll', () => {
    if (document.documentElement.scrollTop > 20 || document.body.scrollTop > 20) {
      floatingNav.style.display = 'none';
    } else {
      floatingNav.style.display = 'flex';
    }
  });
</script>

<!-- attendance -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const MyAttendanceLabels = <?= json_encode($mymonths); ?>;
    const presentDaysData = <?= json_encode($mypresent_days); ?>;
    const absentDaysData = <?= json_encode($myabsent_days); ?>;
    const lateDaysData = <?= json_encode($mylate_days); ?>;

    const ctx = document.getElementById('MyAttendanceChart').getContext('2d');

    const colors = {
      present: { bg: 'rgba(54, 162, 235, 0.6)', border: 'rgba(54, 162, 235, 1)' },
      absent: { bg: 'rgba(255, 99, 132, 0.6)', border: 'rgba(255, 99, 132, 1)' },
      late: { bg: 'rgba(255, 206, 86, 0.6)', border: 'rgba(255, 206, 86, 1)' }
    };

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: MyAttendanceLabels,
        datasets: [
          {
            label: 'Days Present',
            data: presentDaysData,
            backgroundColor: colors.present.bg,
            borderColor: colors.present.border,
            borderWidth: 1
          },
          {
            label: 'Days Absent',
            data: absentDaysData,
            backgroundColor: colors.absent.bg,
            borderColor: colors.absent.border,
            borderWidth: 1
          },
          {
            label: 'Days Late',
            data: lateDaysData,
            backgroundColor: colors.late.bg,
            borderColor: colors.late.border,
            borderWidth: 1
          }
        ]
      },
      options: {
        // responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          }
        },
        plugins: {
          legend: {
            position: 'top'
          },
          tooltip: {
            enabled: true,
            // mode: 'index',
            intersect: false
          }
        }
      }
    });
  });
</script>

<!-- timetable -->
<script>
    // Initialize Tables for teacher
    new DataTable('#my-timetable', {
        ordering: false,
        paging: false,
        searching: false,
        info: false,
        responsive: true
    });
</script>

<!-- notice board -->
<script>
    // Initialize Swiper
    const swiper = new Swiper('.swiper-container', {
        direction: 'vertical', // Enable vertical sliding
        loop: true, // Infinite looping
        autoplay: {
            delay: 3000, // Time between slides
            disableOnInteraction: true, // Keep autoplay even after user interaction
            pauseOnMouseEnter: true,
            Selection: true, //text seletion in slider aloow
            wheel: true, //wheel sliding allow
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true, // Allow clicking on dots
        },
        slidesPerView: 1, // Show one slide at a time
        speed: 560, // Transition speed
        mousewheel: {
            forceToAxis: true, // Enable mouse wheel control
            sensitivity: 1,
            releaseOnEdges: true, // Release mouse wheel control on edges
        },
        // keyboard: {
        //     enabled: true, // Enable keyboard navigation
        //     onlyInViewport: true, // Only allow keyboard navigation when the swiper is in the pointer under div of repectiv
        // },
        autoHeight: true, // Adjust height dynamically based on slide content
        spaceBetween: 10, // Adjust spacing between slides
        allowTouchMove: true, // Allow touch move
        touchMoveStopPropagation: false,
    });
</script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.copy-notice-btn').forEach(function(btn) {
          btn.addEventListener('click', function() {
              const text = btn.getAttribute('data-notice');
              navigator.clipboard.writeText(text).then(function() {
                  btn.textContent = 'Copied!';
                  setTimeout(() => btn.textContent = '📋', 1200);
              });
          });
      });
  });
</script>

<!-- FullCalendar Setup Script -->
<script>
    const calendarEvents = <?= json_encode($events); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('academic_events');

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: {
                start: window.innerWidth >= 768 ? 'prev,next today' : 'prev,next',
                center: 'title',
                end: window.innerWidth >= 768 ? 'dayGridMonth,timeGridWeek,timeGridDay,listMonth' : 'dayGridMonth'
            },
            events: calendarEvents.map(event => ({
                title: event.title,
                start: event.start_date,
                end: event.end_date || event.start_date,
                description: event.description || "No description",
            })),
            eventDidMount: function(info) {
                const tooltip = document.createElement("div");
                tooltip.className = "event-tooltip";
                tooltip.innerHTML = `
                    <div class="tw-text-sm">
                        <strong>${info.event.title}</strong><br>
                        ${info.event.extendedProps.description || "No description"}<br>
                        <button class="btn btn-sm btn-outline-primary mt-2"
                            onclick="addToCalendar('${info.event.title}', '${info.event.startStr}', '${info.event.endStr || info.event.startStr}', \`${info.event.extendedProps.description || ""}\`)">
                            📅 Add to Calendar
                        </button>
                    </div>
                `;
                Object.assign(tooltip.style, {
                    position: "absolute",
                    background: "#ffffff",
                    border: "1px solid #ccc",
                    padding: "10px",
                    borderRadius: "8px",
                    boxShadow: "0 5px 15px rgba(0,0,0,0.1)",
                    display: "none",
                    zIndex: 9999,
                });
                document.body.appendChild(tooltip);

                info.el.addEventListener("click", function(e) {
                    e.stopPropagation();
                    const rect = info.el.getBoundingClientRect();
                    tooltip.style.left = (rect.left + window.scrollX + 10) + "px";
                    tooltip.style.top = (rect.top + window.scrollY + 10) + "px";
                    tooltip.style.display = "block";
                });

                document.addEventListener("click", function hideTooltip(e) {
                    if (!tooltip.contains(e.target) && e.target !== info.el) {
                        tooltip.style.display = "none";
                    }
                });
            }
        });

        calendar.render();

        // Responsive toolbar on window resize
        window.addEventListener('resize', () => {
            calendar.setOption('headerToolbar', {
                start: window.innerWidth >= 768 ? 'prev,next today' : 'prev,next',
                center: 'title',
                end: window.innerWidth >= 768 ? 'dayGridMonth,timeGridWeek,timeGridDay,listMonth' : 'dayGridMonth'
            });
        });
    });

    function addToCalendar(title, startDate, endDate, description = "") {
        const escapeICS = (str) => String(str)
            .replace(/\\n/g, "\\n")
            .replace(/,/g, "\\,")
            .replace(/;/g, "\\;")
            .replace(/\r?\n|\r/g, "\\n");

        const formatDateICS = (dateStr, allDay = false) => {
            const date = new Date(dateStr);
            return allDay
                ? date.toISOString().slice(0, 10).replace(/-/g, '')
                : date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
        };

        const isAllDay = startDate.length <= 10 && endDate.length <= 10;
        const icsLines = [
            "BEGIN:VCALENDAR",
            "VERSION:2.0",
            "PRODID:-//Your School//Calendar//EN",
            "CALSCALE:GREGORIAN",
            "BEGIN:VEVENT",
            `UID:${Date.now()}@school.local`,
            `SUMMARY:${escapeICS(title)}`,
            `DESCRIPTION:${escapeICS(description)}`,
            isAllDay
                ? `DTSTART;VALUE=DATE:${formatDateICS(startDate, true)}`
                : `DTSTART:${formatDateICS(startDate)}`,
            isAllDay
                ? `DTEND;VALUE=DATE:${formatDateICS(endDate, true)}`
                : `DTEND:${formatDateICS(endDate)}`,
            `DTSTAMP:${formatDateICS(new Date().toISOString())}`,
            "END:VEVENT",
            "END:VCALENDAR"
        ];

        const blob = new Blob([icsLines.join("\r\n")], { type: "text/calendar" });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `${title.replace(/\s+/g, "_")}.ics`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
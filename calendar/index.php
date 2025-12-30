<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Interactive Calendar';
require '../includes/header.php';
require '../includes/sidebar.php';
?>

<!-- FullCalendar Scheduler CSS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.10/index.global.min.js'></script>
<!-- Tippy.js for Tooltips -->
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light.css"/>
<style>
    /* Fix Z-Index issue where tooltip hides behind calendar events */
    .tippy-box {
        z-index: 10000 !important;
    }
    /* Ensure popper itself is high too since tippy lives inside it */
    .tippy-popper {
        z-index: 10000 !important;
    }
</style>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-calendar-alt"></i> Interactive Calendar</h1>
        <div class="topbar-right">
             <div class="calendar-legend" style="display:flex; gap:15px; align-items:center;">
                <div style="display:flex; align-items:center; gap:5px;">
                    <span style="width:15px; height:15px; background:#22c55e; border-radius:3px;"></span> Booking
                </div>
                <div style="display:flex; align-items:center; gap:5px;">
                    <span style="width:15px; height:15px; background:#f59e0b; border-radius:3px;"></span> Contract
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="padding: 20px;">
        <div id='calendar'></div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            schedulerLicenseKey: 'CC-BY-NC-4.0', // Non-Commercial License
            initialView: 'resourceTimelineMonth', // Default View
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'resourceTimelineMonth,dayGridMonth,listWeek'
            },
            views: {
                resourceTimelineMonth: {
                    buttonText: 'Room View',
                    resourceAreaWidth: '15%',
                    slotLabelFormat: [
                        { day: 'numeric', weekday: 'short' } // Compact time header
                    ]
                }
            },
            editable: true, 
            droppable: true,
            resourceAreaHeaderContent: 'Rooms',
            resources: '/pms_hotel/api/resources.php', // Load Rooms
            events: '/pms_hotel/api/events.php', // Load Events matches to resourceId
            
            // Tooltip on Hover
            eventMouseEnter: function(info) {
                var props = info.event.extendedProps;
                var content = '';
                
                if (props.type === 'booking') {
                    content = `
                        <div style="text-align:left; min-width:200px;">
                            <strong style="font-size:1.1em; color:#22c55e;">${props.guest}</strong><br>
                            <hr style="margin:5px 0; border-color:#eee;">
                            <i class="fas fa-bed"></i> Room: <b>${props.room}</b><br>
                            <i class="fas fa-calendar"></i> In: ${info.event.start.toLocaleDateString()}<br>
                            <i class="fas fa-calendar"></i> Out: ${info.event.end ? info.event.end.toLocaleDateString() : '?'}<br>
                            <i class="fas fa-tag"></i> Price: ${props.price} ฿<br>
                            <i class="fas fa-info-circle"></i> Status: ${props.status}<br>
                            <i class="fas fa-phone"></i> ${props.phone || '-'}
                        </div>
                    `;
                } else {
                    content = `
                        <div style="text-align:left; min-width:200px;">
                            <strong style="font-size:1.1em; color:#f59e0b;">Contract: ${props.guest}</strong><br>
                            <hr style="margin:5px 0; border-color:#eee;">
                            <i class="fas fa-bed"></i> Room: <b>${props.room}</b><br>
                            <i class="fas fa-calendar"></i> Start: ${info.event.start.toLocaleDateString()}<br>
                            <i class="fas fa-calendar"></i> End: ${info.event.end ? info.event.end.toLocaleDateString() : '?'}<br>
                            <i class="fas fa-tag"></i> Total: ${props.price} ฿<br>
                            <p style="margin-top:5px; font-style:italic; font-size:0.9em;">${props.desc || ''}</p>
                        </div>
                    `;
                }

                tippy(info.el, {
                    content: content,
                    allowHTML: true,
                    theme: 'light',
                    placement: 'top',
                    interactive: true,
                    appendTo: document.body, // Force append to body to avoid clipping
                });
            },

            // Handle Drop (Move)
            eventDrop: function(info) {
                if (!confirm("Confirm move " + info.event.extendedProps.type + " to " + info.event.start.toLocaleDateString() + "?")) {
                    info.revert();
                    return;
                }
                updateEvent(info.event);
            },

            // Handle Resize
            eventResize: function(info) {
                if (!confirm("Confirm change end date?")) {
                    info.revert();
                    return;
                }
                updateEvent(info.event);
            }
        });
        calendar.render();

        function updateEvent(event) {
            var data = {
                id: event.id,
                start: event.startStr,
                end: event.endStr || event.startStr 
            };

            fetch('/pms_hotel/api/update_event.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Update failed');
                    event.revert();
                }
            })
            .catch((error) => {
                alert('Network error');
                event.revert();
            });
        }
    });
</script>

<?php require '../includes/footer.php'; ?>

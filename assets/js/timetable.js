(() => {
  const USER_ID = 1;
  const validColor = /^#[0-9a-f]{6}$/i;
  const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  let events = [];

  function formatTime(time) {
    return time.slice(0, 5);
  }

  function showFeedback(message, isError = false) {
    const feedback = document.querySelector('#timetable-feedback');
    feedback.textContent = message;
    feedback.hidden = message === '';
    feedback.classList.toggle('feedback-error', isError);
  }

  function createEventCard(event) {
    const card = document.createElement('article');
    card.className = 'timetable-event';
    card.dataset.eventId = event.id;
    card.style.setProperty('--event-color', validColor.test(event.color_code) ? event.color_code : '#b7a4ff');

    const title = document.createElement('span');
    title.className = 'timetable-event-title';
    title.textContent = event.activity_name || event.class_name;
    const time = document.createElement('span');
    time.className = 'timetable-event-time';
    time.textContent = `${formatTime(event.start_time)}–${formatTime(event.end_time)}`;
    card.append(title, time);

    return card;
  }

  function createActivityRow(activity) {
    const row = document.createElement('tr');
    const activityCell = document.createElement('td');
    activityCell.textContent = activity.activity_name || activity.class_name;
    const dayCell = document.createElement('td');
    dayCell.textContent = activity.day_of_week;
    const timeCell = document.createElement('td');
    timeCell.textContent = `${formatTime(activity.start_time)} - ${formatTime(activity.end_time)}`;
    const actionCell = document.createElement('td');
    const dropButton = document.createElement('button');
    dropButton.type = 'button';
    dropButton.className = 'button button-secondary activity-drop-button';
    dropButton.textContent = 'Drop';
    dropButton.setAttribute('aria-label', `Drop ${activity.activity_name || activity.class_name}`);
    dropButton.addEventListener('click', async () => {
      dropButton.disabled = true;
      events = events.filter((item) =>
        String(item.id) !== String(activity.id),
      );
      renderTimetable();
      showFeedback('');
      try {
        await window.API.deleteTimetableEvent(activity.id);
        showFeedback('Activity dropped.');
      } catch (error) {
        await loadTimetable();
        showFeedback(error.message, true);
      }
    });
    actionCell.append(dropButton);
    row.append(activityCell, dayCell, timeCell, actionCell);

    return row;
  }

  function renderActivityManager(events) {
    const list = document.querySelector('#activity-manager-rows');
    if (events.length === 0) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.className = 'activity-manager-empty';
      cell.colSpan = 4;
      cell.textContent = 'No activities registered yet.';
      row.append(cell);
      list.replaceChildren(row);
      return;
    }

    list.replaceChildren(...events.map(createActivityRow));
  }

  function renderTimetable() {
    const dayContainers = new Map(
      Array.from(document.querySelectorAll('.day-events[data-day]'), (container) => [
        container.dataset.day,
        container,
      ]),
    );
    document.querySelectorAll('.day-events[data-day]').forEach((container) => {
      container.replaceChildren();
    });

    events.forEach((event) => {
      const dayContainer = dayContainers.get(event.day_of_week);
      if (dayContainer) {
        dayContainer.append(createEventCard(event));
      }
    });
    renderActivityManager(events);
    checkActiveSlot();
  }

  function checkActiveSlot() {
    const now = new Date();
    const currentDay = daysOfWeek[now.getDay()];
    const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    const activeEvent = events.find((event) =>
      event.day_of_week === currentDay
      && formatTime(event.start_time) <= currentTime
      && currentTime < formatTime(event.end_time),
    );
    const banner = document.querySelector('#live-status-banner');

    document.querySelectorAll('.timetable-event.is-active').forEach((card) => {
      card.classList.remove('is-active');
    });

    if (activeEvent) {
      const activeCard = Array.from(document.querySelectorAll('.timetable-event'))
        .find((card) => card.dataset.eventId === String(activeEvent.id));
      if (activeCard) {
        activeCard.classList.add('is-active');
      }
    }

    const currentDayIndex = now.getDay();
    const nextEvent = events
      .map((event) => {
        const eventDayIndex = daysOfWeek.indexOf(event.day_of_week);
        const dayOffset = (eventDayIndex - currentDayIndex + 7) % 7;
        const startTime = formatTime(event.start_time);
        const minutesUntil = dayOffset * 1440
          + Number(startTime.slice(0, 2)) * 60
          + Number(startTime.slice(3, 5))
          - (now.getHours() * 60 + now.getMinutes());

        return { event, minutesUntil };
      })
      .filter(({ minutesUntil }) => minutesUntil > 0)
      .sort((first, second) => first.minutesUntil - second.minutesUntil)[0]?.event;
    const activeName = activeEvent
      ? activeEvent.activity_name || activeEvent.class_name
      : 'No active class';
    const nextName = nextEvent
      ? `${nextEvent.activity_name || nextEvent.class_name} at ${formatTime(nextEvent.start_time)}`
      : 'No upcoming classes';

    banner.textContent = `Currently: ${activeName} | Next: ${nextName}`;
  }

  async function loadTimetable() {
    try {
      const result = await window.API.getTimetable(USER_ID);
      if (!Array.isArray(result.events)) {
        throw new Error('The server returned an invalid timetable.');
      }

      events = result.events;
      renderTimetable();
      showFeedback(result.events.length === 0 ? 'No activities yet. Add one to plan your week.' : '');
    } catch (error) {
      events = [];
      renderTimetable();
      showFeedback(error.message, true);
    }
  }

  function initializeActivityDialog() {
    const dialog = document.querySelector('#activity-dialog');
    const form = document.querySelector('#add-activity-form');
    const addButton = document.querySelector('#add-activity-button');
    const cancelButtons = dialog.querySelectorAll('#cancel-activity-button, #cancel-activity-action');
    const startInput = form.elements.start_time;
    const endInput = form.elements.end_time;
    const dayCheckboxes = Array.from(document.querySelectorAll('input[name="days"]'));
    const dayPresets = {
      weekdays: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
      everyday: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
    };

    addButton.addEventListener('click', () => dialog.showModal());
    cancelButtons.forEach((button) => {
      button.addEventListener('click', () => dialog.close());
    });
    dialog.querySelectorAll('[data-days-preset]').forEach((button) => {
      button.addEventListener('click', () => {
        const selectedDays = dayPresets[button.dataset.daysPreset];
        dayCheckboxes.forEach((checkbox) => {
          checkbox.checked = selectedDays.includes(checkbox.value);
        });
      });
    });
    endInput.addEventListener('input', () => endInput.setCustomValidity(''));
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        dialog.close();
      }
    });

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(form);
      const startTime = formData.get('start_time');
      const endTime = formData.get('end_time');
      const selectedDays = Array.from(
        document.querySelectorAll('input[name="days"]:checked'),
      ).map((checkbox) => checkbox.value);
      if (selectedDays.length === 0) {
        window.alert('Please select at least one day.');
        return;
      }
      if (endTime <= startTime) {
        endInput.setCustomValidity('End time must be later than start time.');
        endInput.reportValidity();
        return;
      }
      endInput.setCustomValidity('');

      const submitButton = form.querySelector('[type="submit"]');
      submitButton.disabled = true;
      showFeedback('');
      const payload = {
        user_id: USER_ID,
        activity_name: formData.get('activity_name').trim(),
        days: selectedDays,
        start_time: startTime,
        end_time: endTime,
        color_code: formData.get('color_code') || '#8e73f5',
      };
      try {
        console.log('Submitting timetable payload:', payload);
        await window.API.createTimetableEvent(payload);
        dialog.close();
        form.reset();
        form.elements.color_code.value = '#8e73f5';
        await loadTimetable();
      } catch (error) {
        showFeedback(error.message, true);
      } finally {
        submitButton.disabled = false;
      }
    });
  }

  function initializeTimetable() {
    initializeActivityDialog();
    checkActiveSlot();
    window.setInterval(checkActiveSlot, 60_000);
    loadTimetable();
  }

  document.addEventListener('DOMContentLoaded', initializeTimetable, { once: true });
})();

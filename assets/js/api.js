(() => {
  const BASE_URL = './api';

  async function request(endpoint, options = {}) {
    const requestOptions = {
      ...options,
      headers: {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...options.headers,
      },
    };

    if (requestOptions.body && typeof requestOptions.body !== 'string') {
      requestOptions.body = JSON.stringify(requestOptions.body);
    }

    const response = await fetch(`${BASE_URL}/${endpoint}`, requestOptions);
    if (response.status === 204) {
      return null;
    }

    let payload;
    try {
      payload = await response.json();
    } catch {
      throw new Error(`The server returned an invalid response (${response.status}).`);
    }

    if (!response.ok) {
      throw new Error(payload.error || `Request failed (${response.status}).`);
    }

    return payload;
  }

  function localDate() {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
  }

  window.API = Object.freeze({
    getHabits(userId) {
      return request(
        `habits.php?user_id=${encodeURIComponent(userId)}&date_logged=${encodeURIComponent(localDate())}`,
      );
    },
    createHabit(habitData) {
      return request('habits.php', { method: 'POST', body: habitData });
    },
    deleteHabit(habitId) {
      return request('habits.php', { method: 'DELETE', body: { id: habitId } });
    },
    logHabit(logData) {
      return request('logs.php', { method: 'POST', body: logData });
    },
    getTimetable(userId) {
      return request(`timetable.php?user_id=${encodeURIComponent(userId)}`);
    },
    createTimetableEvent(eventData) {
      return request('timetable.php', { method: 'POST', body: eventData });
    },
    deleteTimetableEvent(eventId) {
      return request('timetable.php', { method: 'DELETE', body: { id: eventId } });
    },
    getAssignments(userId, status) {
      const query = new URLSearchParams({ user_id: userId });
      if (status) {
        query.set('status', status);
      }

      return request(`assignments.php?${query.toString()}`);
    },
    createAssignment(assignmentData) {
      return request('assignments.php', { method: 'POST', body: assignmentData });
    },
    toggleAssignmentStatus(assignmentId, isCompleted) {
      return request('assignments.php', {
        method: 'PUT',
        body: { id: assignmentId, is_completed: Number(isCompleted) },
      });
    },
    deleteAssignment(assignmentId) {
      return request('assignments.php', { method: 'DELETE', body: { id: assignmentId } });
    },
  });
})();

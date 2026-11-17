(() => {
  const USER_ID = 1;
  let assignments = [];

  function showFeedback(message, isError = false) {
    const feedback = document.querySelector('#assignment-feedback');
    feedback.textContent = message;
    feedback.hidden = message === '';
    feedback.classList.toggle('feedback-error', isError);
  }

  function showReminderFeedback(message, isError = false) {
    const feedback = document.querySelector('#reminder-feedback');
    feedback.textContent = message;
    feedback.hidden = message === '';
    feedback.classList.toggle('feedback-error', isError);
  }

  function updateReminderCount(count = assignments.filter((assignment) =>
    Number(assignment.is_completed) === 0,
  ).length) {
    const badge = document.querySelector('#reminder-count');
    badge.textContent = String(count);
    badge.setAttribute('aria-label', `${count} pending assignments`);
  }

  function localDateString(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  function getDueStatus(assignment) {
    if (Number(assignment.is_completed) === 1) {
      return { label: 'Completed', className: 'assignment-badge-completed' };
    }

    const dueDate = new Date(`${assignment.due_date}T00:00:00`);
    if (Number.isNaN(dueDate.getTime())) {
      throw new Error('The server returned an invalid assignment due date.');
    }
    const today = new Date(`${localDateString()}T00:00:00`);
    const daysUntilDue = Math.round((dueDate - today) / 86400000);

    if (daysUntilDue < 0) {
      return { label: 'Overdue', className: 'assignment-badge-urgent' };
    }
    if (daysUntilDue === 0) {
      return { label: 'Due Today', className: 'assignment-badge-urgent' };
    }

    return { label: `Due in ${daysUntilDue} days`, className: 'assignment-badge-upcoming' };
  }

  function createAssignmentRow(assignment) {
    const completed = Number(assignment.is_completed) === 1;
    const row = document.createElement('tr');
    row.className = completed ? 'assignment-row-completed' : '';

    [assignment.unit_code, assignment.unit_name, assignment.task, assignment.due_date].forEach((value) => {
      const cell = document.createElement('td');
      cell.textContent = value;
      row.append(cell);
    });

    const statusCell = document.createElement('td');
    const status = getDueStatus(assignment);
    const statusBadge = document.createElement('span');
    statusBadge.className = `assignment-badge ${status.className}`;
    statusBadge.textContent = status.label;
    statusCell.append(statusBadge);
    row.append(statusCell);

    const completedCell = document.createElement('td');
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.className = 'assignment-checkbox';
    checkbox.checked = completed;
    checkbox.setAttribute('aria-label', `Mark "${assignment.task}" as completed`);
    checkbox.addEventListener('change', async () => {
      checkbox.disabled = true;
      showFeedback('');
      try {
        await window.API.toggleAssignmentStatus(assignment.id, checkbox.checked);
        await loadAssignments();
      } catch (error) {
        showFeedback(error.message, true);
        await loadAssignments();
      }
    });
    completedCell.append(checkbox);
    row.append(completedCell);

    const deleteCell = document.createElement('td');
    deleteCell.className = 'assignment-actions';
    const deleteButton = document.createElement('button');
    deleteButton.type = 'button';
    deleteButton.className = 'assignment-delete';
    deleteButton.textContent = '🗑';
    deleteButton.setAttribute('aria-label', `Delete "${assignment.task}"`);
    deleteButton.disabled = !completed;
    deleteButton.title = completed
      ? 'Delete completed assignment'
      : 'Complete this assignment before deleting it';
    deleteButton.addEventListener('click', async () => {
      if (!completed) {
        return;
      }

      deleteButton.disabled = true;
      showFeedback('');
      try {
        await window.API.deleteAssignment(assignment.id);
        await loadAssignments();
      } catch (error) {
        showFeedback(error.message, true);
        await loadAssignments();
      }
    });
    deleteCell.append(deleteButton);
    row.append(deleteCell);

    return row;
  }

  function renderAssignments() {
    const list = document.querySelector('#assignment-list');
    if (assignments.length === 0) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.className = 'assignment-empty';
      cell.colSpan = 7;
      cell.textContent = 'No assignments yet. Add one above to keep track of a deadline.';
      row.append(cell);
      list.replaceChildren(row);
    } else {
      list.replaceChildren(...assignments.map(createAssignmentRow));
    }
    updateReminderCount();
  }

  async function loadAssignments() {
    const list = document.querySelector('#assignment-list');
    list.setAttribute('aria-busy', 'true');
    try {
      const result = await window.API.getAssignments(USER_ID);
      if (!Array.isArray(result.assignments)) {
        throw new Error('The server returned an invalid assignments list.');
      }
      assignments = result.assignments;
      renderAssignments();
      showFeedback(assignments.length === 0 ? 'Add your first assignment to get started.' : '');
    } catch (error) {
      assignments = [];
      renderAssignments();
      showFeedback(error.message, true);
    } finally {
      list.setAttribute('aria-busy', 'false');
    }
  }

  function createReminderItem(assignment) {
    const item = document.createElement('li');
    item.className = 'reminder-item';

    const title = document.createElement('span');
    title.className = 'reminder-item-title';
    title.textContent = `${assignment.task} · ${assignment.unit_code} ${assignment.unit_name}`;

    const dueDate = document.createElement('span');
    dueDate.className = 'reminder-item-meta';
    dueDate.textContent = `Due ${assignment.due_date}`;
    item.append(title, dueDate);

    return item;
  }

  async function loadReminders() {
    const list = document.querySelector('#reminder-list');
    list.setAttribute('aria-busy', 'true');
    list.replaceChildren();
    showReminderFeedback('');
    try {
      const result = await window.API.getAssignments(USER_ID, 'unattended');
      if (!Array.isArray(result.assignments)) {
        throw new Error('The server returned an invalid unattended assignments list.');
      }
      const unattendedAssignments = result.assignments.filter((assignment) =>
        Number(assignment.is_completed) === 0,
      );
      if (unattendedAssignments.length === 0) {
        const empty = document.createElement('li');
        empty.className = 'reminder-empty';
        empty.textContent = 'You are all caught up. No unattended tasks.';
        list.append(empty);
      } else {
        list.replaceChildren(...unattendedAssignments.map(createReminderItem));
      }
      updateReminderCount(unattendedAssignments.length);
    } catch (error) {
      showReminderFeedback(error.message, true);
    } finally {
      list.setAttribute('aria-busy', 'false');
    }
  }

  function initializeAssignments() {
    const form = document.querySelector('#add-assignment-form');
    const reminderDialog = document.querySelector('#reminder-dialog');
    const reminderButton = document.querySelector('#reminder-button');
    const closeButtons = reminderDialog.querySelectorAll('#close-reminder-button, #close-reminder-action');

    reminderButton.addEventListener('click', async () => {
      reminderDialog.showModal();
      await loadReminders();
    });
    closeButtons.forEach((button) => {
      button.addEventListener('click', () => reminderDialog.close());
    });
    reminderDialog.addEventListener('click', (event) => {
      if (event.target === reminderDialog) {
        reminderDialog.close();
      }
    });

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const payload = {
        user_id: 1,
        unit_code: document.getElementById('unit_code').value.trim(),
        unit_name: document.getElementById('unit_name').value.trim(),
        task: document.getElementById('task').value.trim(),
        due_date: document.getElementById('due_date').value || null,
      };
      if (!payload.unit_code || !payload.unit_name || !payload.task) {
        showFeedback('Unit code, unit, and task are required.', true);
        return;
      }

      const submitButton = form.querySelector('[type="submit"]');
      submitButton.disabled = true;
      showFeedback('');
      try {
        const result = await window.API.createAssignment(payload);
        console.log('Create assignment response:', result);
        form.reset();
        await loadAssignments();
      } catch (error) {
        showFeedback(error.message, true);
      } finally {
        submitButton.disabled = false;
      }
    });

    loadAssignments();
  }

  document.addEventListener('DOMContentLoaded', initializeAssignments, { once: true });
})();

(() => {
  const USER_ID = 1;
  const statuses = [
    { value: 'completed', label: 'Done' },
    { value: 'partial', label: 'Partial' },
    { value: 'grace_day', label: 'Grace Day' },
  ];
  const activeStatuses = new Map();

  function showFeedback(message, isError = false) {
    const feedback = document.querySelector('#habit-feedback');
    feedback.textContent = message;
    feedback.hidden = message === '';
    feedback.classList.toggle('feedback-error', isError);
  }

  function todayLocalDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  function updateActiveStatus(card, status) {
    card.querySelectorAll('.habit-status-button').forEach((button) => {
      button.setAttribute('aria-pressed', String(button.dataset.status === status));
    });
  }

  function createHabitCard(habit) {
    const card = document.createElement('article');
    card.className = 'habit-card';
    card.dataset.habitId = String(habit.id);

    const heading = document.createElement('div');
    heading.className = 'habit-card-heading';

    const details = document.createElement('div');
    const title = document.createElement('h3');
    title.className = 'habit-card-title';
    title.textContent = habit.title;
    const meta = document.createElement('p');
    meta.className = 'habit-card-meta';
    meta.textContent = `${habit.frequency} · ${habit.category}`;
    details.append(title, meta);
    heading.append(details);

    const buttons = document.createElement('div');
    buttons.className = 'habit-status-buttons';
    buttons.setAttribute('role', 'group');
    buttons.setAttribute('aria-label', `Log ${habit.title}`);

    statuses.forEach(({ value, label }) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'habit-status-button';
      button.dataset.status = value;
      button.textContent = label;
      button.setAttribute('aria-pressed', String(activeStatuses.get(String(habit.id)) === value));
      button.addEventListener('click', async () => {
        button.disabled = true;
        showFeedback('');
        try {
          await window.API.logHabit({
            habit_id: habit.id,
            date_logged: todayLocalDate(),
            status: value,
          });
          activeStatuses.set(String(habit.id), value);
          habit.today_status = value;
          updateActiveStatus(card, value);
          showFeedback(`${label} saved for "${habit.title}".`);
        } catch (error) {
          showFeedback(error.message, true);
        } finally {
          button.disabled = false;
        }
      });
      buttons.append(button);
    });

    const dropButton = document.createElement('button');
    dropButton.type = 'button';
    dropButton.className = 'habit-drop-button';
    dropButton.textContent = 'Drop Habit';
    dropButton.setAttribute('aria-label', `Drop habit "${habit.title}"`);
    dropButton.addEventListener('click', async () => {
      if (!window.confirm('Are you sure you want to drop this habit?')) {
        return;
      }

      dropButton.disabled = true;
      showFeedback('');
      try {
        await window.API.deleteHabit(habit.id);
        card.remove();
        activeStatuses.delete(String(habit.id));
        showFeedback(`Habit "${habit.title}" dropped.`);
      } catch (error) {
        showFeedback(error.message, true);
      } finally {
        dropButton.disabled = false;
      }
    });
    buttons.append(dropButton);

    card.append(heading, buttons);
    return card;
  }

  async function loadHabits() {
    const list = document.querySelector('#habit-list');
    list.setAttribute('aria-busy', 'true');
    try {
      const result = await window.API.getHabits(USER_ID);
      if (!Array.isArray(result.habits)) {
        throw new Error('The server returned an invalid habits list.');
      }

      result.habits.forEach((habit) => {
        const key = String(habit.id);
        if (habit.today_status) {
          activeStatuses.set(key, habit.today_status);
        } else {
          activeStatuses.delete(key);
        }
      });
      list.replaceChildren(...result.habits.map(createHabitCard));
      showFeedback(result.habits.length === 0 ? 'Add your first habit to get started.' : '');
    } catch (error) {
      showFeedback(error.message, true);
    } finally {
      list.setAttribute('aria-busy', 'false');
    }
  }

  function initializeHabits() {
    const form = document.querySelector('#add-habit-form');
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const submitButton = form.querySelector('[type="submit"]');
      const formData = new FormData(form);
      submitButton.disabled = true;
      showFeedback('');

      try {
        await window.API.createHabit({
          user_id: USER_ID,
          title: formData.get('title').trim(),
          frequency: formData.get('frequency'),
          category: formData.get('category'),
        });
        form.reset();
        await loadHabits();
      } catch (error) {
        showFeedback(error.message, true);
      } finally {
        submitButton.disabled = false;
      }
    });

    loadHabits();
  }

  document.addEventListener('DOMContentLoaded', initializeHabits, { once: true });
})();

(() => {
  const durations = {
    work: 25 * 60,
    'short-break': 5 * 60,
    'long-break': 15 * 60,
  };
  let remainingSeconds = durations.work;
  let intervalId = null;
  let endTime = null;

  function renderTime() {
    const minutes = Math.floor(remainingSeconds / 60);
    const seconds = remainingSeconds % 60;
    document.querySelector('#pomodoro-time').textContent =
      `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  }

  function setRunning(isRunning) {
    document.querySelector('#pomodoro-start').disabled = isRunning;
    document.querySelector('#pomodoro-pause').disabled = !isRunning;
  }

  function stopTimer() {
    if (intervalId !== null) {
      window.clearInterval(intervalId);
      intervalId = null;
    }
    endTime = null;
    setRunning(false);
  }

  function updateTimer() {
    remainingSeconds = Math.max(0, Math.ceil((endTime - Date.now()) / 1000));
    renderTime();

    if (remainingSeconds === 0) {
      stopTimer();
      document.querySelector('#pomodoro-status').textContent = 'Timer complete.';
    }
  }

  function startTimer() {
    if (intervalId !== null) {
      return;
    }
    if (remainingSeconds === 0) {
      remainingSeconds = durations[document.querySelector('#pomodoro-mode').value];
      renderTime();
    }

    endTime = Date.now() + remainingSeconds * 1000;
    setRunning(true);
    document.querySelector('#pomodoro-status').textContent = 'Timer started.';
    updateTimer();
    if (remainingSeconds > 0) {
      intervalId = window.setInterval(updateTimer, 1000);
    }
  }

  function pauseTimer() {
    if (intervalId === null) {
      return;
    }

    updateTimer();
    if (intervalId !== null) {
      stopTimer();
      document.querySelector('#pomodoro-status').textContent = 'Timer paused.';
    }
  }

  function initializePomodoro() {
    const mode = document.querySelector('#pomodoro-mode');
    document.querySelector('#pomodoro-start').addEventListener('click', startTimer);
    document.querySelector('#pomodoro-pause').addEventListener('click', pauseTimer);
    mode.addEventListener('change', () => {
      stopTimer();
      remainingSeconds = durations[mode.value];
      renderTime();
      document.querySelector('#pomodoro-status').textContent = `${mode.selectedOptions[0].text} selected.`;
    });
    renderTime();
    setRunning(false);
  }

  document.addEventListener('DOMContentLoaded', initializePomodoro, { once: true });
})();

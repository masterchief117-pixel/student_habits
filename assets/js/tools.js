(() => {
  function initializeToolsLauncher() {
    const dialog = document.querySelector('#terminal-help-dialog');
    const openButton = document.querySelector('#terminal-help-button');
    const closeButtons = dialog.querySelectorAll('#close-terminal-help, #close-terminal-help-action');

    openButton.addEventListener('click', () => dialog.showModal());
    closeButtons.forEach((button) => {
      button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        dialog.close();
      }
    });
  }

  document.addEventListener('DOMContentLoaded', initializeToolsLauncher, { once: true });
})();

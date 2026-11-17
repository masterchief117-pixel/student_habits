(() => {
  function initializeDevotionToggle() {
    const revealBtn = document.getElementById('reveal-verse-btn');
    const contentBox = document.getElementById('devotion-content');
    const themeDropdown = document.getElementById('verse-theme-dropdown');

    contentBox.style.display = 'none';
    revealBtn.setAttribute('aria-expanded', 'false');
    revealBtn.addEventListener('click', () => {
      if (contentBox.style.display === 'none' || !contentBox.style.display) {
        contentBox.style.display = 'block';
        revealBtn.textContent = 'Hide Bible Verse';
        revealBtn.setAttribute('aria-expanded', 'true');
      } else {
        contentBox.style.display = 'none';
        revealBtn.textContent = 'Bible Verse';
        revealBtn.setAttribute('aria-expanded', 'false');
      }
    });
    themeDropdown.addEventListener('change', () => {
      contentBox.style.display = 'none';
      revealBtn.textContent = 'Bible Verse';
      revealBtn.setAttribute('aria-expanded', 'false');
    });
  }

  async function loadDailyDevotion() {
    const reference = document.querySelector('#devotion-ref');
    const verseText = document.querySelector('#devotion-text');
    const explanation = document.querySelector('#devotion-explanation');

    try {
      const response = await fetch('./api/daily_verse.php', {
        headers: { Accept: 'application/json' },
      });
      let devotion;
      try {
        devotion = await response.json();
      } catch {
        throw new Error(`The server returned an invalid response (${response.status}).`);
      }
      if (!response.ok) {
        throw new Error(devotion.error || `Request failed (${response.status}).`);
      }
      if (
        typeof devotion.reference !== 'string'
        || typeof devotion.text !== 'string'
        || typeof devotion.explanation !== 'string'
      ) {
        throw new Error('The server returned an invalid daily devotion.');
      }

      reference.textContent = devotion.reference;
      verseText.textContent = devotion.text;
      explanation.textContent = devotion.explanation;
    } catch (error) {
      reference.textContent = 'Unable to load today’s verse';
      explanation.textContent = error.message;
      explanation.classList.add('feedback-error');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    initializeDevotionToggle();
    loadDailyDevotion();
  }, { once: true });
})();

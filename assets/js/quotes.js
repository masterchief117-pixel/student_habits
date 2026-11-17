(() => {
  const quotes = [
    { text: 'Start with one page, one problem, or one small step. Momentum will meet you there.', author: 'Student Hub' },
    { text: 'A focused half hour can move a big goal closer.', author: 'Student Hub' },
    { text: 'Progress is built from the study sessions you show up for.', author: 'Student Hub' },
    { text: 'Make today easier for your future self.', author: 'Student Hub' },
    { text: 'You do not need a perfect plan; you need a useful next step.', author: 'Student Hub' },
    { text: 'Every concept you practice becomes a tool you can use.', author: 'Student Hub' },
    { text: 'Small, steady effort turns deadlines into done.', author: 'Student Hub' },
    { text: 'Give your attention to the task in front of you.', author: 'Student Hub' },
    { text: 'Learning takes time. Let consistency do its quiet work.', author: 'Student Hub' },
    { text: 'Take a breath, choose a priority, and begin again.', author: 'Student Hub' },
  ];

  function displayRandomQuote() {
    const quote = quotes[Math.floor(Math.random() * quotes.length)];
    document.querySelector('#quote-text').textContent = `“${quote.text}”`;
    document.querySelector('#quote-author').textContent = quote.author;
  }

  document.addEventListener('DOMContentLoaded', () => {
    displayRandomQuote();
    setInterval(displayRandomQuote, 300000);
  }, { once: true });
})();

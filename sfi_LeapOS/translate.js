(() => {
  const rows = Array.from(document.querySelectorAll('[data-translate-id]'));
  const notice = document.querySelector('[data-translate-notice]');
  if (rows.length === 0 || !notice) {
    return;
  }

  const url = notice.dataset.translateUrl;
  const slowDelayMs = 5000;
  let done = 0;
  const timer = setTimeout(() => { notice.hidden = false; }, slowDelayMs);

  const setText = (cell, text) => {
    cell.replaceChildren();
    text.split('\n').forEach((line, index) => {
      if (index > 0) {
        cell.appendChild(document.createElement('br'));
      }
      cell.appendChild(document.createTextNode(line));
    });
  };

  const finish = () => {
    done += 1;
    if (done === rows.length) {
      clearTimeout(timer);
      notice.hidden = true;
    }
  };

  const translateRow = async (row) => {
    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id: row.dataset.translateId }),
        credentials: 'same-origin',
      });
      const data = response.ok ? await response.json() : null;
      if (data && data.ok) {
        setText(row.querySelector('[data-field="topic"]'), data.topic);
        setText(row.querySelector('[data-field="suggestion"]'), data.suggestion);
        row.dataset.filterTopic = data.topic;
        row.dataset.filterSuggestion = data.suggestion;
      }
    } catch (error) {
      // Original text stays visible.
    } finally {
      finish();
    }
  };

  // Sequential, so the translation server is not flooded.
  (async () => {
    for (const row of rows) {
      await translateRow(row);
    }
  })();
})();

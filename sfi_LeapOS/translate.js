(() => {
  const rows = Array.from(document.querySelectorAll('[data-translate-id]'));
  const notice = document.querySelector('[data-translate-notice]');
  if (rows.length === 0 || !notice) {
    return;
  }

  const url = notice.dataset.translateUrl;
  const label = notice.querySelector('[data-translate-text]');
  const baseText = label.textContent;
  let done = 0;
  const showProgress = () => { label.textContent = baseText + ' (' + done + '/' + rows.length + ')'; };
  showProgress();
  notice.hidden = false;

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
      notice.hidden = true;
    } else {
      showProgress();
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
      const data = await response.json().catch(() => null);
      if (data && data.ok) {
        setText(row.querySelector('[data-field="topic"]'), data.topic);
        setText(row.querySelector('[data-field="suggestion"]'), data.suggestion);
        row.dataset.filterTopic = data.topic;
        row.dataset.filterSuggestion = data.suggestion;
      } else {
        console.warn('Translation failed', response.status, data && data.error);
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

(function () {
  'use strict';

  const INSTANCE_KEY = '__kirbyMcpActivityIndicator';
  const POLL_INTERVAL = 15000;

  panel.plugin('bnomei/kirby-mcp', {
    created(app) {
      const panel = app.$panel;

      // The hook can run again during development or after plugin reloads.
      window[INSTANCE_KEY]?.destroy();

      const button = document.createElement('button');
      button.className = 'kirby-mcp-activity';
      button.type = 'button';
      button.hidden = true;
      button.setAttribute('aria-label', 'MCP activity hidden');
      button.innerHTML =
        '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 4V2m-1 0h2M7 7h10a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-7a3 3 0 0 1 3-3Zm1 5h.01M16 12h.01M8 16h8"/></svg><span class="kirby-mcp-activity__label"></span>';
      document.body.appendChild(button);

      const label = button.querySelector('.kirby-mcp-activity__label');
      let ageAtResponse = null;
      let responseTime = 0;
      let nextPoll = 0;
      let requestNumber = 0;
      let destroyed = false;

      // Kirby can retain the previous user object while showing the login view.
      const currentUserId = () => (panel.view.id === 'login' ? null : panel.user?.id || null);

      const hide = () => {
        ageAtResponse = null;
        responseTime = 0;
        button.hidden = true;
        button.dataset.state = 'hidden';
        button.setAttribute('aria-label', 'MCP activity hidden');
        label.textContent = '';
      };

      const render = () => {
        if (ageAtResponse === null || !currentUserId()) {
          hide();
          return;
        }

        const age = Math.max(0, Math.floor(ageAtResponse + (Date.now() - responseTime) / 1000));
        const state = age < 30 ? 'active' : age < 120 ? 'recent' : age < 300 ? 'idle' : 'hidden';

        if (state === 'hidden') {
          hide();
          return;
        }

        const elapsed =
          age < 5
            ? 'just now'
            : age < 60
              ? `${age} seconds ago`
              : `${Math.floor(age / 60)} minute${age < 120 ? '' : 's'} ago`;
        const text = `MCP activity ${elapsed}`;

        button.hidden = false;
        button.dataset.state = state;
        button.setAttribute('aria-label', text);
        label.textContent = text;
      };

      const poll = async () => {
        const userId = currentUserId();
        if (!userId || document.hidden) {
          if (!userId) hide();
          return;
        }

        const thisRequest = ++requestNumber;
        let timeout;
        try {
          const result = await Promise.race([
            panel.api.get('kirby-mcp/activity'),
            new Promise((_, reject) => {
              timeout = window.setTimeout(() => reject(new Error('Activity unavailable')), 10000);
            }),
          ]);
          if (destroyed || thisRequest !== requestNumber || currentUserId() !== userId) {
            return;
          }

          if (result?.state === 'hidden' || !Number.isInteger(result?.ageSeconds) || result.ageSeconds < 0) {
            hide();
            return;
          }

          ageAtResponse = result.ageSeconds;
          responseTime = Date.now();
          render();
        } catch (_) {
          // Network and authentication failures must never leave stale activity.
          if (thisRequest === requestNumber) hide();
        } finally {
          window.clearTimeout(timeout);
        }
      };

      const tick = () => {
        if (!currentUserId()) {
          requestNumber++;
          hide();
          return;
        }
        if (document.hidden) return;

        render();
        if (Date.now() >= nextPoll) {
          nextPoll = Date.now() + POLL_INTERVAL;
          poll();
        }
      };

      const onVisibilityChange = () => {
        if (document.hidden) return;
        nextPoll = 0;
        tick();
      };
      const onClick = () => button.classList.toggle('is-expanded');

      button.addEventListener('click', onClick);
      document.addEventListener('visibilitychange', onVisibilityChange);
      const timer = window.setInterval(tick, 1000);
      const unwatch = app.$watch(currentUserId, () => {
        requestNumber++;
        hide();
        nextPoll = 0;
        tick();
      });

      window[INSTANCE_KEY] = {
        destroy() {
          destroyed = true;
          requestNumber++;
          window.clearInterval(timer);
          unwatch();
          document.removeEventListener('visibilitychange', onVisibilityChange);
          button.removeEventListener('click', onClick);
          button.remove();
        },
      };

      tick();
    },
  });
})();

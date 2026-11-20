(function () {
  'use strict';

  const apiMeta = document.querySelector('meta[name="api-base"]');
  const appMeta = document.querySelector('meta[name="app-base"]');
  const apiBase = (apiMeta && apiMeta.content ? apiMeta.content : '/api/v1').replace(/\/+$/, '');
  const appBase = (appMeta && appMeta.content ? appMeta.content : '').replace(/\/+$/, '');

  class ApiError extends Error {
    constructor(status, message, details, payload) {
      super(message);
      this.name = 'ApiError';
      this.status = status;
      this.details = Array.isArray(details) ? details : [];
      this.payload = payload;
    }
  }

  async function request(path, options) {
    const opts = options || {};
    const method = (opts.method || 'GET').toUpperCase();
    const headers = Object.assign(
      {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      opts.headers || {}
    );

    const fetchOptions = {
      method: method,
      credentials: 'same-origin',
      headers: headers,
      signal: opts.signal || null
    };

    if (opts.body !== undefined && opts.body !== null) {
      if (opts.body instanceof FormData) {
        fetchOptions.body = opts.body;
      } else if (typeof opts.body === 'string') {
        fetchOptions.body = opts.body;
      } else {
        headers['Content-Type'] = 'application/json';
        fetchOptions.body = JSON.stringify(opts.body);
      }
    }

    let response;

    try {
      response = await fetch(apiBase + path, fetchOptions);
    } catch (error) {
      if (error && error.name === 'AbortError') {
        throw error;
      }

      throw new ApiError(
        0,
        'No se pudo conectar con el servidor. Revisa tu conexión e inténtalo de nuevo.',
        [],
        null
      );
    }

    const text = await response.text();
    let payload = null;

    if (text !== '') {
      try {
        payload = JSON.parse(text);
      } catch (error) {
        payload = null;
      }
    }

    if (!response.ok) {
      let message = payload && typeof payload.error === 'string' ? payload.error : '';

      if (message === '') {
        if (response.status === 401) {
          message = 'Tu sesión expiró. Inicia sesión de nuevo.';
        } else if (response.status === 403) {
          message = 'No tienes permisos para realizar esta acción.';
        } else if (response.status === 404) {
          message = 'No se encontró el recurso solicitado.';
        } else if (response.status >= 500) {
          message = 'Ocurrió un error interno. Inténtalo de nuevo más tarde.';
        } else {
          message = 'No se pudo completar la solicitud (' + response.status + ').';
        }
      }

      const details = payload && Array.isArray(payload.detalles) ? payload.detalles : [];
      throw new ApiError(response.status, message, details, payload);
    }

    return payload;
  }

  function resolveElement(container) {
    if (!container) {
      return null;
    }

    if (typeof container === 'string') {
      return document.querySelector(container);
    }

    return container;
  }

  function clearAlert(container) {
    const element = resolveElement(container);

    if (element) {
      element.innerHTML = '';
    }
  }

  function showAlert(container, type, message) {
    const element = resolveElement(container);

    if (!element) {
      return;
    }

    element.innerHTML = '';

    const messages = Array.isArray(message) ? message : [message];
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + (type || 'info') + ' alert-dismissible fade show';
    alert.setAttribute('role', 'alert');

    if (messages.length === 1) {
      const paragraph = document.createElement('p');
      paragraph.className = 'mb-0';
      paragraph.textContent = String(messages[0]);
      alert.appendChild(paragraph);
    } else {
      const list = document.createElement('ul');
      list.className = 'mb-0';

      messages.forEach(function (item) {
        const listItem = document.createElement('li');
        listItem.textContent = String(item);
        list.appendChild(listItem);
      });

      alert.appendChild(list);
    }

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.setAttribute('data-bs-dismiss', 'alert');
    close.setAttribute('aria-label', 'Cerrar');
    alert.appendChild(close);

    element.appendChild(alert);
  }

  function showError(container, error) {
    if (error && error.name === 'AbortError') {
      return;
    }

    const details = error && Array.isArray(error.details) ? error.details.filter(Boolean) : [];
    const message = error && error.message ? error.message : 'No se pudo completar la solicitud.';

    if (details.length > 0) {
      showAlert(container, 'danger', [message].concat(details));
      return;
    }

    showAlert(container, 'danger', message);
  }

  function setLoading(button, loading, loadingText) {
    const element = resolveElement(button);

    if (!element) {
      return;
    }

    if (loading) {
      if (!element.dataset.originalHtml) {
        element.dataset.originalHtml = element.innerHTML;
      }

      element.disabled = true;
      element.innerHTML = '';

      const spinner = document.createElement('span');
      spinner.className = 'spinner-border spinner-border-sm me-2';
      spinner.setAttribute('role', 'status');
      spinner.setAttribute('aria-hidden', 'true');
      element.appendChild(spinner);
      element.appendChild(document.createTextNode(loadingText || 'Procesando...'));
      return;
    }

    element.disabled = false;

    if (element.dataset.originalHtml) {
      element.innerHTML = element.dataset.originalHtml;
      delete element.dataset.originalHtml;
    }
  }

  function redirect(path) {
    const cleanPath = String(path || '').replace(/^\/+/, '');
    window.location.href = appBase + '/' + cleanPath;
  }

  function asset(path) {
    const cleanPath = String(path || '').replace(/^\/+/, '');

    return appBase + '/' + cleanPath;
  }

  window.SoscurApi = {
    request: request,
    showAlert: showAlert,
    showError: showError,
    clearAlert: clearAlert,
    setLoading: setLoading,
    redirect: redirect,
    asset: asset,
    ApiError: ApiError
  };
})();

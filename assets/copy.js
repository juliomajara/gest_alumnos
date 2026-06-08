(function () {
  var toast = document.createElement('div');
  toast.className = 'copy-toast';
  document.body.appendChild(toast);

  var toastTimer = null;

  window.showCopyToast = function (text) {
    toast.textContent = text.indexOf('@') !== -1
      ? 'Correo copiado al portapapeles'
      : 'Teléfono copiado al portapapeles';
    toast.classList.add('copy-toast--visible');
    if (toastTimer !== null) {
      clearTimeout(toastTimer);
    }
    toastTimer = setTimeout(function () {
      toast.classList.remove('copy-toast--visible');
      toastTimer = null;
    }, 2000);
  };

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('.copy-trigger');
    if (!trigger) return;
    var text = (trigger.dataset.copy || '').trim();
    if (!text) return;
    navigator.clipboard.writeText(text).then(function () {
      trigger.classList.add('copy-trigger--copied');
      setTimeout(function () {
        trigger.classList.remove('copy-trigger--copied');
      }, 1600);
      window.showCopyToast(text);
    }).catch(function () {});
  });

  document.addEventListener('keydown', function (event) {
    if ((event.key === 'Enter' || event.key === ' ') && event.target.closest('.copy-trigger')) {
      event.preventDefault();
      event.target.closest('.copy-trigger').click();
    }
  });
})();

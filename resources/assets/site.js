(function () {
  'use strict';

  // Horários relativos: a página é estática, então calculamos no navegador.
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var hm = function (d) { return pad(d.getHours()) + ':' + pad(d.getMinutes()); };
  var sameDay = function (a, b) { return a.toDateString() === b.toDateString(); };

  function relative(date, now) {
    var min = Math.round((now - date) / 60000);
    if (min < 1) return 'agora';
    if (min < 60) return 'há ' + min + ' min';
    if (sameDay(date, now) && min < 6 * 60) return 'há ' + Math.floor(min / 60) + ' h';
    if (sameDay(date, now)) return 'hoje, ' + hm(date);
    var yesterday = new Date(now); yesterday.setDate(now.getDate() - 1);
    if (sameDay(date, yesterday)) return 'ontem, ' + hm(date);
    return pad(date.getDate()) + '/' + pad(date.getMonth() + 1) + ', ' + hm(date);
  }

  function refreshTimes() {
    var now = new Date();
    document.querySelectorAll('time[data-rel]').forEach(function (el) {
      var date = new Date(el.getAttribute('datetime'));
      if (!isNaN(date)) el.textContent = relative(date, now);
    });
    // "Hoje"/"Ontem" também mudam se a página ficar aberta.
    document.querySelectorAll('[data-day]').forEach(function (el) {
      var parts = el.getAttribute('data-day').split('-');
      var day = new Date(+parts[0], +parts[1] - 1, +parts[2]);
      var yesterday = new Date(now); yesterday.setDate(now.getDate() - 1);
      if (sameDay(day, now)) el.textContent = 'Hoje';
      else if (sameDay(day, yesterday)) el.textContent = 'Ontem';
    });
  }

  refreshTimes();
  setInterval(refreshTimes, 60000);

  // Copiar o resumo do dia.
  document.querySelectorAll('[data-copy]').forEach(function (button) {
    button.addEventListener('click', function () {
      var field = document.getElementById(button.getAttribute('data-copy'));
      var status = document.querySelector('[data-copy-status]');
      var done = function () { if (status) status.textContent = 'Texto copiado.'; };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(field.value).then(done, function () { field.select(); });
      } else {
        field.select();
        try { document.execCommand('copy'); done(); } catch (e) { /* o texto fica selecionado */ }
      }
    });
  });

  // Filtro por fonte.
  var chips = document.querySelectorAll('.chip[data-source]');
  if (!chips.length) return;

  function apply(source) {
    chips.forEach(function (chip) {
      chip.setAttribute('aria-pressed', String(chip.getAttribute('data-source') === source));
    });
    document.querySelectorAll('.card').forEach(function (card) {
      var list = (card.getAttribute('data-sources') || '').split(' ');
      card.hidden = source !== 'all' && list.indexOf(source) === -1;
    });
    document.querySelectorAll('.day').forEach(function (day) {
      day.hidden = !day.querySelector('.card:not([hidden])');
    });
    try { localStorage.setItem('itape-filter', source); } catch (e) { /* modo privado */ }
  }

  chips.forEach(function (chip) {
    chip.addEventListener('click', function () { apply(chip.getAttribute('data-source')); });
  });

  var saved = 'all';
  try { saved = localStorage.getItem('itape-filter') || 'all'; } catch (e) { /* modo privado */ }
  if (saved !== 'all' && document.querySelector('.chip[data-source="' + saved + '"]')) apply(saved);
})();

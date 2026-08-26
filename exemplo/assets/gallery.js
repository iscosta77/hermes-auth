/* Hermes Gallery — slider/lightbox em JS puro, zero dependencias.
 * Auto-inicializa em qualquer [data-galeria]. Uso:
 *   <link rel="stylesheet" href="gallery.css">
 *   <script src="gallery.js" defer></script>
 *   <div data-galeria> ...figures geradas pelo PHP... </div>
 */
(function () {
  'use strict';

  function abrir(galeria, indice) {
    var itens = Array.prototype.slice.call(galeria.querySelectorAll('.hermes-galeria-item'));
    if (!itens.length) return;

    var overlay = document.createElement('div');
    overlay.className = 'hermes-lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-label', 'Galeria de imagens');

    var img = document.createElement('img');
    var legenda = document.createElement('div');
    var contador = document.createElement('div');
    var btnFechar = document.createElement('button');
    var btnAnterior = document.createElement('button');
    var btnProxima = document.createElement('button');

    btnFechar.className = 'hermes-lb-fechar';
    btnFechar.textContent = '×';
    btnFechar.setAttribute('aria-label', 'Fechar');
    btnAnterior.className = 'hermes-lb-nav hermes-lb-ant';
    btnAnterior.textContent = '‹';
    btnAnterior.setAttribute('aria-label', 'Anterior');
    btnProxima.className = 'hermes-lb-nav hermes-lb-prox';
    btnProxima.textContent = '›';
    btnProxima.setAttribute('aria-label', 'Próxima');
    legenda.className = 'hermes-lb-legenda';
    contador.className = 'hermes-lb-contador';

    overlay.appendChild(img);
    overlay.appendChild(legenda);
    overlay.appendChild(contador);
    overlay.appendChild(btnFechar);
    overlay.appendChild(btnAnterior);
    overlay.appendChild(btnProxima);
    document.body.appendChild(overlay);

    function mostrar(i) {
      indice = (i + itens.length) % itens.length;
      var item = itens[indice];
      img.src = item.getAttribute('data-src');
      img.alt = (item.querySelector('img') || {}).alt || '';
      legenda.textContent = img.alt;
      contador.textContent = (indice + 1) + ' / ' + itens.length;
    }

    function fechar() {
      document.removeEventListener('keydown', noTeclado);
      if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
    }

    function noTeclado(e) {
      if (e.key === 'Escape') fechar();
      if (e.key === 'ArrowLeft') mostrar(indice - 1);
      if (e.key === 'ArrowRight') mostrar(indice + 1);
    }

    btnFechar.addEventListener('click', fechar);
    btnAnterior.addEventListener('click', function () { mostrar(indice - 1); });
    btnProxima.addEventListener('click', function () { mostrar(indice + 1); });
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) fechar();
    });
    document.addEventListener('keydown', noTeclado);

    mostrar(indice);
  }

  document.addEventListener('click', function (e) {
    var item = e.target.closest('.hermes-galeria-item');
    if (!item) return;
    var galeria = item.closest('[data-galeria]');
    if (!galeria) return;
    var itens = Array.prototype.slice.call(galeria.querySelectorAll('.hermes-galeria-item'));
    abrir(galeria, itens.indexOf(item));
  });
})();

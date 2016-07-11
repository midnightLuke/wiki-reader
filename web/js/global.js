$(document).ready(function() {
  $('#toc').toc({
    'selectors': 'h1,h2,h3',
    'container': '.content',
    'scrollToOffset': 50
  });
  $('[data-submenu]').submenupicker();
});

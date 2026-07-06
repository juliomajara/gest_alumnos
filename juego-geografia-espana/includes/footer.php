<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('sw.js').catch(function () { /* sin SW la app sigue funcionando igual */ });
    });
  }
</script>
</body>
</html>

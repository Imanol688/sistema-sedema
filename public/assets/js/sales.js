(() => {
  const warehouse = document.querySelector('[data-sales-warehouse]');
  if (warehouse) {
    warehouse.addEventListener('change', () => {
      window.location.href = `order.php?warehouse=${encodeURIComponent(warehouse.value)}`;
    });
  }
})();

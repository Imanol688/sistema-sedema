(() => {
    const warehouse = document.querySelector('[data-order-warehouse]');
    if (warehouse) warehouse.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('warehouse', warehouse.value);
        window.location.href = url.href;
    });
})();

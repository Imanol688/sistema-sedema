(() => {
    const panel = document.getElementById('mp-qr-status');
    if (!panel) return;
    let count = 0;
    async function check() {
        if (document.hidden) { window.setTimeout(check, 10000); return; }
        try {
            const body = new URLSearchParams({idPago: panel.dataset.paymentId, csrf_token: panel.dataset.csrf});
            const response = await fetch('mp-qr-status.php', {method: 'POST', body, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
            if (response.redirected) throw new Error('La sesión venció. Recargá la página.');
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'No se pudo verificar.');
            panel.textContent = `Mercado Pago: ${data.remoteStatus} · SEDEMA: ${data.status}`;
            if (data.status !== 'PENDIENTE') { window.location.reload(); return; }
        } catch (error) {
            panel.textContent = error.message || 'Usá Verificar / recuperar solicitud.';
        }
        // A los 16 minutos se detiene el sondeo; puede continuarse manualmente.
        if (++count < 96) window.setTimeout(check, 10000);
        else panel.textContent += ' · Consulta automática finalizada. Usá Verificar / recuperar solicitud.';
    }
    window.setTimeout(check, 10000);
})();

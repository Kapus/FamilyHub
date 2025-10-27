// bitcoin-ticker.js
// Hämtar BTC/USD pris och dagens förändring
(() => {
    const tickerEl = document.getElementById('bitcoin-ticker');
    if (!tickerEl) {
        return;
    }

    const priceEl = document.getElementById('bitcoin-price');
    const changeEl = document.getElementById('bitcoin-change');
    const endpoint = tickerEl.dataset.endpoint || 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd&include_24hr_change=true';

    async function fetchTicker() {
        try {
            if (!endpoint) {
                throw new Error('Ingen endpoint angiven.');
            }

            const response = await fetch(endpoint, { cache: 'no-store' });
            if (!response.ok) {
                throw new Error('Kunde inte hämta prisdata.');
            }

            const data = await response.json();
            const btc = data?.bitcoin;
            const price = typeof btc?.usd === 'number' ? btc.usd : null;
            const changePct = typeof btc?.usd_24h_change === 'number' ? btc.usd_24h_change : null;

            if (price === null) {
                throw new Error('Ofullständig prisdata.');
            }

            priceEl.textContent = price.toLocaleString('sv-SE', { style: 'currency', currency: 'USD' });

            if (changePct !== null) {
                const isPositive = changePct >= 0;
                const displayPct = Math.abs(changePct).toFixed(2);
                changeEl.textContent = `${isPositive ? '▲' : '▼'} ${displayPct} % senaste 24h`;
                changeEl.classList.toggle('text-success', isPositive);
                changeEl.classList.toggle('text-danger', !isPositive);
            } else {
                changeEl.textContent = 'Ingen förändringsdata';
                changeEl.classList.remove('text-success', 'text-danger');
            }

            tickerEl.classList.remove('text-muted');
        } catch (error) {
            tickerEl.classList.add('text-muted');
            priceEl.textContent = 'Kunde inte hämta pris';
            changeEl.textContent = '';
            changeEl.classList.remove('text-success', 'text-danger');
            console.error('BTC ticker error:', error);
        }
    }

    fetchTicker();
    setInterval(fetchTicker, 60000);
})();

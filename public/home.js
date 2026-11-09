document.addEventListener('DOMContentLoaded', () => {
    const gamesGrid = document.getElementById('gamesGrid');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (nextBtn && gamesGrid) {
        nextBtn.addEventListener('click', () => {
            gamesGrid.scrollBy({ left: 320, behavior: 'smooth' });
        });
    }

    if (prevBtn && gamesGrid) {
        prevBtn.addEventListener('click', () => {
            gamesGrid.scrollBy({ left: -320, behavior: 'smooth' });
        });
    }
});
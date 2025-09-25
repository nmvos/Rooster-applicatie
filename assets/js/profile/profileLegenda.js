document.addEventListener('DOMContentLoaded', function () {				
    const legenda = document.querySelector('.legenda');
    const legendaDropdown = document.querySelector('.legenda-dropdown');
    const overlay = document.querySelector('.legenda-overlay');

    function closeLegenda() {
        legendaDropdown.style.display = 'none';
        overlay.style.display = 'none';
    }
        
    legenda.addEventListener('click', function () {
        legendaDropdown.style.display = 'block';
        overlay.style.display = 'block';
    });
        
    overlay.addEventListener('click', function (event) {
        event.stopPropagation();
        closeLegenda();
    });
})
document.addEventListener('DOMContentLoaded', function () {
    //open and closing of the changelog
    const changelog = document.querySelector('.changelog');
    const changelogDropdown = document.querySelector('.changelogDropdown');
    const overlay = document.querySelector('.changelogOverlay');

    function closeChangelog() {
        changelogDropdown.style.display = 'none';
        overlay.style.display='none';
    }
    
    changelog.addEventListener('click', function () {
        changelogDropdown.style.display = 'block';
        overlay.style.display='block';
    });
    
    overlay.addEventListener('click', function (event) {
        event.stopPropagation(); // Voorkom dat het klikgedrag opnieuw wordt getriggerd
        closeChangelog();
    }); 
})
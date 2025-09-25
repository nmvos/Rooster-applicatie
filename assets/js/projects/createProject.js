document.addEventListener('DOMContentLoaded', function () {
    const create_project = document.querySelector('.create_project');
    const createDropdown = document.querySelector('.create-dropdown');
    const overlay = document.querySelector('.create-overlay');

    function closeCreate() {
        createDropdown.style.display = 'none';
        overlay.style.display = 'none';
    }

    create_project.addEventListener('click', function () {
        createDropdown.style.display = 'block';
        overlay.style.display = 'block';
    });

    overlay.addEventListener('click', function () {
        closeCreate();
    });
})
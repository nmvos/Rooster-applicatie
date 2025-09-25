document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('toggle-btn');
    var sidebar = document.querySelector('nav');
    var chevron = toggleBtn.querySelector('.chevron');
    var header = document.querySelector('header');
    var main = document.querySelector('main');

    // Function to set a cookie
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    // Function to get a cookie
    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    // Function to update sidebar based on state
    function updateSidebar(state) {
        if (state === 'Open') {
            sidebar.classList.remove('collapsed');
            chevron.style.transform = 'rotate(180deg)';
            main.classList.remove('sideCollapsed');
            main.classList.add('sideOpen');
        } else {
            sidebar.classList.add('collapsed');
            main.classList.remove('sideOpen');
            main.classList.add('sideCollapsed');
            chevron.style.transform = 'rotate(0deg)';
        }
    }

    // Get the initial sidebar state from the cookie
    var sidebarState = getCookie('sidebarState') || 'Close';
    
       // Set sidebarState cookie to 'Open' if it doesn't exist (eerste bezoek)
    if (getCookie('sidebarState') === null) {
        setCookie('sidebarState', 'Open', 7);
    }

    // Get the initial sidebar state from the cookie
    var sidebarState = getCookie('sidebarState') || 'Close';

    // Apply the initial state
    updateSidebar(sidebarState);

    // Add click event listener to toggle button
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        if (sidebar.classList.contains('collapsed')) {
            setCookie('sidebarState', 'Close', 7); // Set cookie to Close
            updateSidebar('Close');
        } else {
            setCookie('sidebarState', 'Open', 7); // Set cookie to Open
            updateSidebar('Open');
        }
    });

    // Check for screen width less than 700px and close sidebar if necessary
    function checkWidth() {
        if (window.matchMedia("(max-width: 700px)").matches) {
            setCookie('sidebarState', 'Close', 7); // Automatically close the sidebar
            updateSidebar('Close');
        } else {
            // Restore the sidebar state from the cookie when the screen width is larger
            var savedState = getCookie('sidebarState') || 'Close';
            updateSidebar(savedState);
        }
    }

    // Run the check on page load and on resize
    checkWidth();
    window.addEventListener('resize', checkWidth);
});

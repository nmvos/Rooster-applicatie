function notificationAJAX(userId){
    const payload = {
        userId: userId
    };

    fetch(notificationPath, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
            body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Error:', error);
    });
}

document.addEventListener('DOMContentLoaded', function (){
    const overlay = document.querySelector('.notificationOverlay');
    const notification = document.querySelector('.notification');
    const notificationDropdown = document.querySelector('.notificationDropdown');
    const dot = document.querySelector('.dot');

    function closeNotifications(){
        notificationDropdown.style.display = 'none';
        overlay.style.display = 'none';
    }

    notification.addEventListener('click', function(){
        if (dot.style.display == 'block'){
            notificationAJAX(userId)
        }
        notificationDropdown.style.display = 'block';
        overlay.style.display = 'block';
        dot.style.display = 'none';
    })

    overlay.addEventListener('click', function (event){
        event.stopPropagation();
        closeNotifications();
    })
})
document.addEventListener('DOMContentLoaded', function (){
    notificationDeleteButtons = document.querySelectorAll('.deleteNotification')
    notificationDeleteButtons.forEach(function(button){
        button.addEventListener('click',function(){
            let notificationId = button.getAttribute('data-messageId');

            const payload = {
                notificationId: notificationId
            };
        
            fetch(notificationDeletePath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                    body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data =>{
                const notificationElement = document.querySelector(`.id-${notificationId}`);
                notificationElement.remove();
            })
            .catch(error => {
                console.error('Error:', error);
            });
        })
    })
})

document.addEventListener('DOMContentLoaded', function () {
    // ... bestaande code voor individuele knoppen ...

    const deleteAllBtn = document.getElementById('delete-all-notifications');
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', function() {
            fetch('/NotificationDeleteAll', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
            location.reload();
            // document.querySelectorAll('.notification-item').forEach(el => el.remove());
         }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
});
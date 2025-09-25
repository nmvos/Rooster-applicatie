// add a <div id='toaster'></div> to your page and this script than you can make a toast with showToaster(message)

function showToast(message) {
    const toaster = document.getElementById('toaster');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = message;
    toaster.appendChild(toast);
    toast.style.display = "block";

    setTimeout(() => {
        toast.remove();
    }, 4000);

}
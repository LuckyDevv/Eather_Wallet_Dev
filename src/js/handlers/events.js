window.addEventListener('popstate', function(event) {
    if (modal_opened) {
        closeModals();
        event.preventDefault();
    } else if (current_window !== '') {
        close_window();
        if (prev_window === '') {
            setBlock('nav-home');
        } else {
            if (prev_window.split('-')[0] === 'nav') {
                setBlock(prev_window);
            } else {
                // TODO
            }
        }
    }
    /* else {
        var last_nav = getCookie('nav') ?? 'home';
        console.log(window.location.href);
        if (last_nav !== 'home' && window.location.href.split('#')[1] !== 'transaction' && window.location.href.split('#')[1] !== 'statistic') {
            setBlock('nav-home');
            event.preventDefault();
        }
    }*/
});
window.addEventListener("click", function (e){
    var element = e.target;
    document.querySelectorAll('.modal').forEach((modal) => {
        if (modal_opened) {
            if (!modal.innerHTML.includes(element.innerHTML) && !modal.classList.contains('hide')) {
                closeModals();
            }
        }
    })
});
window.addEventListener("scroll", function (e){
    var hidden = document.getElementById('modal_news').style.display === "none";
    var hidden_2 = document.getElementById('modal_transaction').style.display === "none";
    var hidden_3 = document.getElementById("modal_statistic").style.display === "none";
    if (!hidden || !hidden_2 || !hidden_3)
    {
        document.body.style.overflowY = 'hidden';
        e.preventDefault();
        return false;
    }else{
        document.body.style.overflowY = 'auto';
    }
});
$(document).ready(function (){
    setCookie("_usi_g%$_id$$", new DeviceUUID().get(), 604800);
    history.replaceState(null, null, ' ');
    reportWindowSize();
    window.addEventListener('resize', reportWindowSize);
    all_contacts = document.getElementById("contacts_list").innerHTML;
});
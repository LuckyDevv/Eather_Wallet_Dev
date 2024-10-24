var intl = new Intl.NumberFormat("ru", {minimumFractionDigits: 2, maximumFractionDigits: 2});
var history_parsed = false, modal_opened = false; var disable_controls = false;
var chart_ = null; var last_nav = 'home';
var initialTouchX, initialTouchY, finalTouchX, finalTouchY;
var swipeThreshold = 100;
var current_window = '', prev_window = '', current_window_id = '';
Chart.defaults.color = "#dcdcdc";

function setCookie(cookie_name, cookie_value, cookie_age=7200) {
    document.cookie = `${cookie_name}=${cookie_value};max-age=${cookie_age};path=/`;
}
function empty(mixed_var) {
    return (mixed_var === "" || mixed_var === 0 || mixed_var === null || mixed_var === false || mixed_var === undefined || mixed_var.length === 0);
}
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}
function hexToRgb(hex) {
    // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
    var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
    hex = hex.replace(shorthandRegex, function(m, r, g, b) {
        return r + r + g + g + b + b;
    });
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}
(function($) {
    $.fn.hasScrollBar = function() {
        return this.get(0).scrollHeight > this.height();
    }
})(jQuery);
function getCurrentMoth() {
    const monthNames = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь",
        "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"
    ];
    return monthNames[new Date().getMonth()];
}

function reportWindowSize() {
    var device_width = window.innerWidth;
    var content_element = document.getElementById('content');
    var no_support_element = document.getElementById('no-support');
    var min_width = 375;
    if ($(document).hasScrollBar())
    {
        min_width = 370;
    }
    if (device_width < min_width || device_width > 500)
    {
        content_element.style.display = 'none';
        content_element.setAttribute('hidden', '');
        closeModals();
        no_support_element.style.display = 'flex';
        no_support_element.removeAttribute('hidden');
    }else{
        no_support_element.style.display = 'none';
        no_support_element.setAttribute('hidden', '');
        content_element.style.display = '';
        content_element.removeAttribute('hidden');
        updateNavigationPanel();
        updateBalanceFontSize();
        updateTransactionImages();
        updateTransactionPaidFontSize();
        cropTransactionDescription();
    }
}

function updateNavigationPanel()
{
    var main_block_hidden = document.getElementById('main-block')?.getAttribute('hidden')  === 'hidden';
    var swap_block_hidden = document.getElementById('swap-block')?.getAttribute('hidden') === 'hidden';
    var history_block_hidden = document.getElementById('history-block')?.getAttribute('hidden') === 'hidden';
    var notify_block_hidden = document.getElementById('notify-block')?.getAttribute('hidden') === 'hidden';

    if (!main_block_hidden && history_block_hidden && swap_block_hidden && notify_block_hidden)
    {
        document.getElementById('nav-home').classList.add('active');
        document.getElementById('nav-history').classList.remove('active');
        document.getElementById('nav-swap').classList.remove('active');
        document.getElementById('nav-bell').classList.remove('active');
    }else if(!swap_block_hidden && main_block_hidden && history_block_hidden && notify_block_hidden)
    {
        document.getElementById('nav-swap').classList.add('active');
        document.getElementById('nav-history').classList.remove('active');
        document.getElementById('nav-home').classList.remove('active');
        document.getElementById('nav-bell').classList.remove('active');
    }
    else if(!history_block_hidden && main_block_hidden && swap_block_hidden && notify_block_hidden)
    {
        document.getElementById('nav-history').classList.add('active');
        document.getElementById('nav-home').classList.remove('active');
        document.getElementById('nav-swap').classList.remove('active');
        document.getElementById('nav-bell').classList.remove('active');
    }else if (!notify_block_hidden && main_block_hidden && swap_block_hidden && history_block_hidden)
    {
        document.getElementById('nav-bell').classList.add('active');
        document.getElementById('nav-home').classList.remove('active');
        document.getElementById('nav-swap').classList.remove('active');
        document.getElementById('nav-history').classList.remove('active');
    }
}
function updateBalanceFontSize()
{
    if (document.getElementById('main-block').getAttribute('hidden') !== 'hidden')
    {
        var balance_element = document.getElementById('block_money');
        if (balance_element instanceof HTMLElement)
        {
            var balance_length = balance_element.innerText.trim().length;
            if (balance_length === 10)
            {
                balance_element.style.fontSize = '55px';
            }else if(balance_element.innerText.length > 10 && balance_length <= 13)
            {
                balance_element.style.fontSize = '45px';
            }else if(balance_length > 13 && balance_length <= 17)
            {
                balance_element.style.fontSize = '35px';
            }else if(balance_length > 17)
            {
                balance_element.style.fontSize = '35px';
               // balance_element.innerText = balance.toString().trim().substr(0, 14) + '.. ';
            }
        }
    }
}
function updateTransactionPaidFontSize()
{
    var transaction_paid_elements = document.querySelectorAll('.transaction-paid');
    transaction_paid_elements.forEach((element) => {
        var paidLength = element.innerText.length;
        if (paidLength > 17 && paidLength <= 19)
        {
            element.style.fontSize = '15px';
        }else if (paidLength > 19 && paidLength <= 22)
        {
            element.style.fontSize = '13px';
        }else if (paidLength > 22)
        {
            element.style.fontSize = '13px';
            element.innerText = element.innerText.substr(0, 18) + '.. ' + current_currency.currency;
        }
    });
}
function cropTransactionDescription()
{
    var transaction_description_elements = document.querySelectorAll('.transaction-description');
    transaction_description_elements.forEach((element) => {
        if (element.innerText.length > 10)
        {
            element.innerText = element.innerText.substr(0, 8) + '..' + current_currency.currency;
        }
    });
}
function updateTransactionImages()
{
    var imgs = $('.transaction-image');
    imgs.imgcolr(function (img, color) {
        color = hexToRgb(color);
        color = ''+ color.r +','+ color.g +','+ color.b +'';
        img.style = '' +
            'background: rgba(120, 120, 120, 0.6); ' +
            'background: radial-gradient(circle, rgba('+color+',1) 0%, rgba(120, 120, 120, 0.6) 100%);';
    });
}
function setBlock(id)
{
    if (disable_controls) return;
    switch (id)
    {
        case 'nav-home':
            document.getElementById('main-block').removeAttribute('hidden');
            document.getElementById('news-block').removeAttribute('hidden');
            if (last_nav !== 'home') {
                document.getElementById('main-block').classList.add('block-ltr-animate');
                document.getElementById('news-block').classList.add('block-ltr-animate');
            }
            document.getElementById('history-block').setAttribute('hidden', 'hidden');
            document.getElementById('swap-block').setAttribute('hidden', 'hidden');
            document.getElementById('notify-block').setAttribute('hidden', 'hidden');
            last_nav = 'home';
            break;
        case 'nav-swap':
            document.getElementById('swap-block').removeAttribute('hidden');
            if (last_nav !== 'swap') {
                if (last_nav === 'home') {
                    document.getElementById('swap-block').classList.remove('block-ltr-animate');
                    document.getElementById('swap-block').classList.add('block-rtl-animate');
                }else{
                    document.getElementById('swap-block').classList.add('block-ltr-animate');
                    document.getElementById('swap-block').classList.remove('block-rtl-animate');
                }
            }
            document.getElementById('main-block').setAttribute('hidden', 'hidden');
            document.getElementById('news-block').setAttribute('hidden', 'hidden');
            document.getElementById('history-block').setAttribute('hidden', 'hidden');
            document.getElementById('notify-block').setAttribute('hidden', 'hidden');
            last_nav = 'swap';
            break;
        case 'nav-history':
            if (!history_parsed) {
                $.post("server/api/history.php", {"type": "all", "session_id": getCookie("session_id"), "user_uuid": new DeviceUUID().get()}, function (data){
                    var data_parsed = $.parseJSON(data);
                    if (!empty(data_parsed.response)) {
                        if (data_parsed.response.code === 2) {
                            document.getElementById("history-block").innerHTML = data_parsed.response.message;
                            history_parsed = true;
                            setHistoryEvents();
                        }
                    }
                });
            }
            document.getElementById('history-block').removeAttribute('hidden');
            document.getElementById('history-block').classList.add('block-animate');
            if (last_nav !== 'history') {
                if (last_nav === 'home' || last_nav === 'swap') {
                    document.getElementById('history-block').classList.remove('block-ltr-animate');
                    document.getElementById('history-block').classList.add('block-rtl-animate');
                }else{
                    document.getElementById('history-block').classList.add('block-ltr-animate');
                    document.getElementById('history-block').classList.remove('block-rtl-animate');
                }
            }
            document.getElementById('main-block').setAttribute('hidden', 'hidden');
            document.getElementById('news-block').setAttribute('hidden', 'hidden');
            document.getElementById('swap-block').setAttribute('hidden', 'hidden');
            document.getElementById('notify-block').setAttribute('hidden', 'hidden');
            last_nav = 'history';
            break;
        case 'nav-bell':
            document.getElementById('notify-block').removeAttribute('hidden');
            document.getElementById('notify-block').classList.add('block-animate');
            if (last_nav !== 'notify') {
                document.getElementById('notify-block').classList.remove('block-ltr-animate');
                document.getElementById('notify-block').classList.add('block-rtl-animate');
            }
            document.getElementById('main-block').setAttribute('hidden', 'hidden');
            document.getElementById('news-block').setAttribute('hidden', 'hidden');
            document.getElementById('swap-block').setAttribute('hidden', 'hidden');
            document.getElementById('history-block').setAttribute('hidden', 'hidden');
            last_nav = 'notify';
            break;
        case 'hide-all':
            document.getElementById('main-block').setAttribute('hidden', 'hidden');
            document.getElementById('news-block').setAttribute('hidden', 'hidden');
            document.getElementById('swap-block').setAttribute('hidden', 'hidden');
            document.getElementById('history-block').setAttribute('hidden', 'hidden');
            document.getElementById('notify-block').setAttribute('hidden', 'hidden');
            break;
    }
    updateNavigationPanel();
}
$('.swiper').click(function (){
    closeModals();
});
function closeModals() {
    disable_controls = false;
    history.replaceState(null, null, ' ');
    document.querySelectorAll('.modal').forEach((modal) => {
        modal.classList.add('hide');
        setTimeout(function () {
            modal.style.display = 'none';
            modal.classList.remove("hide");
        }, 250);
    });
    chart_?.destroy();
    document.body.style.overflowY = 'auto';
    modal_opened = false;
}
$('.item-news').click(function () {
    if (disable_controls) return;

    var modal = document.getElementById('modal_news');
    var childNodes = $(this)[0].childNodes;
    var img_src = childNodes[1].src;
    var title = childNodes[3].childNodes[1].innerText;
    var description = childNodes[3].childNodes[3].innerHTML;
    document.getElementById('news-image').src = img_src;
    document.getElementById('news-title').innerText = title;
    document.getElementById('news-description').innerHTML = description;
    modal.classList.remove('hide');
    modal.style.display = 'block';
    document.body.style.overflowY = 'hidden';
    disable_controls = true;
    setTimeout(function (){
        disable_controls = false;
        modal_opened = true;
    }, 250);
});
function statisticModal(title, chart_data, all, chart=true)
{
    if (disable_controls) return;

    location.href = '#statistic';
    var modal = document.getElementById('modal_statistic');
    var ctx = document.getElementById('sdfsdfsdfsd');
    var duck = document.getElementById('ms_empty');
    var text = document.getElementById('ms_empty_text');
    if(chart) {
        document.getElementById("ms_amount").style.display = "block";
        duck.style.display = 'none';
        text.style.display = 'none';
        modal.classList.remove('hide');
        modal.style.display = 'block';
        document.body.style.overflowY = 'hidden';
        disable_controls = true;
        setTimeout(function (){
            disable_controls = false;
            modal_opened = true;
            ctx.style.display = 'block';
            chart_ = new Chart(ctx, {
                type: 'doughnut',
                data: chart_data,
                options: {
                    cutout: 100,
                    plugins: {
                        legend: {
                            labels: {
                                font: {
                                    family: 'SF UI Display Semibold',
                                    size: 12,
                                    style: 'normal',
                                    weight: 'bold',
                                },
                            },
                        },
                    }
                }
            });
        }, 250);
    }else{
        duck.style.display = 'block';
        text.style.display = 'block';
        ctx.style.display = 'none';
        modal.classList.remove('hide');
        modal.style.display = 'block';
        document.body.style.overflowY = 'hidden';
        document.getElementById("ms_amount").style.display = "none";
        disable_controls = true;
        setTimeout(function (){
            disable_controls = false;
            modal_opened = true;
        }, 250);
    }
    document.getElementById('ms_title').innerText = title;
    document.getElementById('ms_amount').innerText = all;
}
function sendNotifyField(type, text) {
    if (disable_controls) return;

    var id_elem = "";
    switch (type) {
        case "contact_name":
            id_elem = "notify_contact_name_field";
            break;
        case "contact_id":
            id_elem = "notify_contact_id_field";
            break;
        case "transfer_step1":
            id_elem = "notify_transfer_id_step1";
            break;
        case "transfer_step2_amount":
            id_elem = "notify_transfer_amount_step2";
            break;
        case "transfer_step2_comment":
            id_elem = "notify_transfer_comment_step2";
            break;
        case "transfer_step2":
            id_elem = "notify_transfer_name_step2";
            break;
        default:
            return false;
    }
    if (id_elem === "") { return false; }

    var elem = document.getElementById(id_elem);
    elem.innerText = text;
    if (text === "") {
        elem.style.display = "none";
    }else{
        elem.style.display = "block";
    }
    return true;
}
function create(htmlStr) {
    var frag = document.createDocumentFragment(),
        temp = document.createElement('div');
    temp.innerHTML = htmlStr;
    while (temp.firstChild) {
        frag.appendChild(temp.firstChild);
    }
    return frag;
}
function close_window () {
    modal_opened = false;
    if (current_window_id !== '') {
        var window_current = document.getElementById(current_window_id);
        if (window_current instanceof HTMLElement) {
            window_current.setAttribute('hidden', 'hidden');
            window_current.style.display = 'none'
        }
    }
    history.replaceState(null, null, ' ');
    var nav = document.getElementById('block-navigation');
    nav.removeAttribute('hidden');
    nav.style.display = 'flex';
    current_window_id = '';
    current_window = '';
    last_nav = 'swap';
    document.body.style.overflowY = "auto";
}
function handleTouch(startX, endX, modal) {
    if (disable_controls) return;

    var horizontalDistance = finalTouchX - initialTouchX;
    var verticalDistance = finalTouchY - initialTouchY;
    if (Math.abs(verticalDistance) > Math.abs(horizontalDistance) && Math.abs(verticalDistance) > swipeThreshold) {
        if (!(finalTouchY - initialTouchY < 0)) {
            closeModals();
            setTimeout(function(){
                modal.style.bottom = '0';
            }, 250);
            return;
        }
    }
    modal.style.bottom = '0';
}
function addBalance(count_b) {
    var balance_el = document.getElementById("money-balance");
    var real_balance = balance_el.getAttribute("real_balance");
    real_balance = parseInt(real_balance) + parseInt(count_b);
    balance_el.setAttribute("real_balance", real_balance);
    var intl_format = intl.format(real_balance).split(",");
    balance_el.innerHTML = intl_format[0]+"<span class='span-money-currency'>,"+intl_format[1]+"</span></span>";
}
function minusBalance(count_b) {
    var balance_el = document.getElementById("money-balance");
    var real_balance = balance_el.getAttribute("real_balance");
    real_balance = parseInt(real_balance) - parseInt(count_b);
    balance_el.setAttribute("real_balance", real_balance);
    var intl_format = intl.format(real_balance).split(",");
    balance_el.innerHTML = intl_format[0]+"<span class='span-money-currency'>,"+intl_format[1]+"</span></span>";
}
document.querySelectorAll('.modal').forEach((modal) => {
    modal.addEventListener('touchstart', (event) => {
        if ((
            event.target.classList.contains("t_data") ||
            event.target.classList.contains("t_mtd") ||
            event.target.classList.contains("t_title") ||
            event.target.classList.contains("t_desc")
        ) && disable_scroll) return;
        initialTouchX = event.touches[0].clientX;
        initialTouchY = event.touches[0].clientY;
    });
    modal.addEventListener('touchmove', (event) => {
        if ((
            event.target.classList.contains("t_data") ||
            event.target.classList.contains("t_mtd") ||
            event.target.classList.contains("t_title") ||
            event.target.classList.contains("t_desc")
        ) && disable_scroll) return;
        var moveY = initialTouchY - event.touches[0].clientY;
        if (moveY <= 0) modal.style.bottom = moveY + 'px';
    });
    modal.addEventListener('touchend', (event) => {
        if ((
            event.target.classList.contains("t_data") ||
            event.target.classList.contains("t_mtd") ||
            event.target.classList.contains("t_title") ||
            event.target.classList.contains("t_desc")
        ) && disable_scroll) return;
        finalTouchX = event.changedTouches[0].clientX;
        finalTouchY = event.changedTouches[0].clientY;
        handleTouch(initialTouchX, finalTouchX, modal);
    });
})



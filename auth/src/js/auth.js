var stop_tips = false;
var notify_id = document.getElementById('notify_id');
var notify_password = document.getElementById('notify_password');
var notify_name = document.getElementById('notify_name');
var notify_reg_pass = document.getElementById('notify_regpass');
var notify_reg_pass_2 = document.getElementById('notify_regpass_2');

var auth_btn_id = document.getElementById('auth_button_id_next');
var auth_input_id = document.getElementById('auth_input_id_text');
var auth_text_id = document.getElementById('auth_id_next_text');
var auth_btn_next_pwd = document.getElementById('auth_button_password_next');
var auth_btn_prev_pwd = document.getElementById('auth_button_password_prev');
var auth_input_pwd = document.getElementById('auth_input_password_text');
var auth_text_pwd = document.getElementById('auth_password_next_text');
var auth_show_pass = document.getElementById('auth_show_pass');
var auth_rem_pass = document.getElementById('auth_rem_pass');

var auth_id_form = document.getElementById('id_form');
var auth_pass_form = document.getElementById('password_form');

var reg_input_name = document.getElementById('reg_input_name');
var reg_input_pwd1 = document.getElementById('reg_input_password');
var reg_input_pwd2 = document.getElementById('reg_input_password_2');
var reg_btn_next = document.getElementById('reg_button_next');
var reg_text_next = document.getElementById('reg_text_next');
var reg_show_pass = document.getElementById('reg_show_pass');

var current_id = null; var os = null; var browser = null;

$('#auth_button_id_next').click(function (){
    var id = auth_input_id.value;
    if (id === '') { // TODO: Change =='' to `empty` function and check preg
        sendNotify('id', 'Вы не ввели ID кошелька!');
        return false;
    }
    sendNotify('id', '');
    auth_text_id.innerText = '';
    auth_text_id.classList.toggle('button--loading');
    auth_btn_id.style.cursor = 'default';
    auth_btn_id.setAttribute('disabled', '');
    auth_input_id.setAttribute('disabled', '');
    auth_input_id.style.cursor = 'default';
    stop_tips = true;
    findLabelForControl(auth_input_id).style.cursor = 'default';
    hideTip('auth_rem_id', true);
    hideTip('auth_reg_button', true);

    $.post('../server/api/account.php', {'type': 'check_id', 'id': id}, function (data){
        try {
            var data_parsed = JSON.parse(data);
            if (!empty(data_parsed.response)) {
                if (data_parsed.response.code === 3) {
                    auth_id_form.style.display = 'none';
                    auth_id_form.setAttribute('hidden', 'hidden');
                    auth_pass_form.style.display = 'flex';
                    auth_pass_form.removeAttribute('hidden');
                    auth_pass_form.classList.add('block-rtl-animate');
                    stop_tips = false;
                    unsetForm('auth_id');
                    current_id = id;
                }
            }else if (!empty(data_parsed.error) && !empty(data_parsed.error.message)) {
                sendNotify('id', data_parsed.error.message);
                unsetForm('auth_id');
            }
        }catch(e){
            sendNotify('id', 'Произошла серверная ошибка!');
            unsetForm('auth_id');
        }
    })
});

$('#auth_button_password_next').click(function (){
    var password = auth_input_pwd.value;
    if (password === '') { // TODO: Change =='' to `empty` function and check preg
        sendNotify('password', 'Вы не ввели пароль!');
        return false;
    }
    if (current_id !== null && current_id !== '') {
        sendNotify('password', '');
        auth_text_pwd.classList.toggle('button--loading--pass');
        auth_text_pwd.innerText = '';
        auth_btn_next_pwd.style.cursor = 'default';
        auth_btn_next_pwd.setAttribute('disabled', '');
        auth_btn_prev_pwd.setAttribute('disabled', '');
        auth_btn_prev_pwd.style.cursor = 'default';
        auth_input_pwd.setAttribute('disabled', '');
        auth_input_pwd.style.cursor = 'text';
        findLabelForControl(auth_input_pwd).style.cursor = 'default';
        hideTip('auth_rem_pass', true);
        hideTip('auth_show_pass', true);
        auth_show_pass.setAttribute('disabled', '');
        auth_show_pass.style.cursor = 'default';
        auth_rem_pass.setAttribute('disabled', '');
        auth_rem_pass.style.cursor = 'default';
        stop_tips = true;

        $.post('../server/api/account.php', {'type': 'auth', 'id': current_id, 'password': password, 'user_uuid': new DeviceUUID().get(), 'browser': browser, 'os': os}, function (data){
            try {
                var data_parsed = JSON.parse(data);

                if (!empty(data_parsed.response)) {
                    if (data_parsed.response.code === 2) {
                        setCookie("session_id", data_parsed.response.message);
                        setCookie("_usi_g%$_id$$", new DeviceUUID().get(), 604800);
                        location.href = "../";
                    }
                }else if (!empty(data_parsed.error) && !empty(data_parsed.error.message)) {
                    if (data_parsed.error.code === 12 || data_parsed.error.code === 13) {
                        auth_pass_form.style.display = 'none';
                        auth_pass_form.setAttribute('hidden', 'hidden');
                        auth_id_form.style.display = 'flex';
                        auth_id_form.removeAttribute('hidden');
                        auth_id_form.classList.add('block-ltr-animate');
                        stop_tips = false;
                        sendNotify('id', data_parsed.error.message);
                        unsetForm('auth_pwd');
                        unsetForm('auth_id');
                    }else{
                        sendNotify('password', data_parsed.error.message);
                        unsetForm('auth_pwd');
                    }
                }
            }catch(e){
                sendNotify('password', 'Произошла серверная ошибка!');
                unsetForm('auth_pwd');
            }
        })
    }

});

$('#auth_button_password_prev').click(function () {
    resetForm('auth_id');
    auth_pass_form.style.display = 'none';
    auth_pass_form.setAttribute('hidden', 'hidden');

    auth_id_form.style.display = 'flex';
    auth_id_form.removeAttribute('hidden');
    auth_id_form.classList.add('block-ltr-animate');
    resetForm('auth_pwd');

    stop_tips = false;
})

$('#auth_show_pass').click(function () {
    if (!stop_tips) {
        if (auth_input_pwd.type === 'password') {
            auth_show_pass.innerText = 'Скрыть пароль';
            auth_input_pwd.type = 'text';
        }else{
            auth_show_pass.innerText = 'Показать пароль';
            auth_input_pwd.type = 'password';
        }
    }
});

$('#auth_reg_button').click(function (){
    if (!stop_tips) {
        var content_auth = document.getElementById('content_auth');
        var content_reg = document.getElementById('content_reg');
        content_reg.style.display = 'block';
        content_auth.style.display = 'none';
        content_reg.classList.add('block-ltr-animate');
        setTimeout(function (){
            content_reg.classList.remove('block-ltr-animate');
        }, 250);
        resetForm('auth_id');
        resetForm('auth_pwd');
        stop_tips = false;
    }
});

// REGISTRATION EVENTS AND FUNCTIONS
$('#reg_auth_button').click(function (){
    if (!stop_tips) {
        var content_auth = document.getElementById('content_auth');
        var content_reg = document.getElementById('content_reg');
        content_auth.style.display = 'block';
        content_reg.style.display = 'none';
        content_auth.classList.add('block-rtl-animate');
        setTimeout(function (){
            content_auth.classList.remove('block-rtl-animate');
        }, 250);
        resetForm('reg_step1');
        stop_tips = false;
    }
});
$('#reg_show_pass').click(function (){
    if (!stop_tips) {
        if (reg_input_pwd1.type === 'password') {
            reg_input_pwd1.type = 'text';
            reg_input_pwd2.type = 'text';
            reg_show_pass.innerText = 'Скрыть пароль';
        }else{
            reg_input_pwd1.type = 'password';
            reg_input_pwd2.type = 'password';
            reg_show_pass.innerText = 'Показать пароль';
        }
    }
});
$('#reg_button_next').click(function (){
    var name = reg_input_name.value;
    var pass = reg_input_pwd1.value;
    var pass_confirm = reg_input_pwd2.value;
    if (name === '') {
        sendNotify('name', 'Вы не ввели имя!');
        return false;
    }
    sendNotify('name', '');

    if (pass === '') {
        sendNotify('reg_pass', 'Вы не придумали пароль!');
        return false;
    }
    sendNotify('reg_pass', '');

    if (pass_confirm === '') {
        sendNotify('reg_pass2', 'Вы не подтвердили пароль!');
        return false;
    }
    sendNotify('reg_pass2', '');

    if (pass !== pass_confirm) {
        sendNotify('reg_pass', 'Пароли не совпадают!');
        sendNotify('reg_pass2', 'Пароли не совпадают!');
        return false;
    }
    sendNotify('reg_pass2', '');

    // TODO: SEND NOTIFY BASED ON SERVER REQUESTS

    reg_input_name.setAttribute('disabled', '');
    reg_input_name.style.cursor = 'default';
    findLabelForControl(reg_input_name).style.cursor = 'default';
    reg_input_pwd1.setAttribute('disabled', '');
    reg_input_pwd1.style.cursor = 'default';
    findLabelForControl(reg_input_pwd1).style.cursor = 'default';
    reg_input_pwd2.setAttribute('disabled', '');
    reg_input_pwd2.style.cursor = 'default';
    findLabelForControl(reg_input_pwd2).style.cursor = 'default';
    reg_text_next.innerText = '';
    reg_text_next.classList.add('button--loading');
    reg_btn_next.style.cursor = 'default';
    reg_btn_next.setAttribute('disabled', '');
    stop_tips = true;
    hideTip('reg_auth_button', true);
    hideTip('reg_show_pass', true);

    $.post("../server/api/account.php", {'type': 'reg', 'name': name, 'password': pass, 'user_uuid': new DeviceUUID().get(), 'browser': browser, 'os': os}, function (data){
        try {
            var data_parsed = JSON.parse(data);
            unsetForm('reg');
            if (!empty(data_parsed.response)) {
                if (data_parsed.response.code === 0) {
                    setCookie("session_id", data_parsed.response.message);
                    setCookie("_usi_g%$_id$$", new DeviceUUID().get(), 604800);
                    location.href = "../";
                }
            }else if (!empty(data_parsed.error)) {
                switch (data_parsed.error.code) {
                    case 1:
                    case 4:
                    case 5:
                        sendNotify('reg_pass', data_parsed.error.message);
                        break;
                    case 2:
                    case 3:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                    case 10:
                        sendNotify('name', data_parsed.error.message);
                        break;
                    default:
                        sendNotify('name', 'Произошла неизвестная ошибка!');
                        break;
                }
            }
        }catch(e){
            sendNotify('name', 'Произошла серверная ошибка!');
        }
    })
});


window.onload = function (){
    var du = new DeviceUUID().parse();
    os = du.os;
    browser = du.source.toLowerCase().includes("yabrowser") ? "Яндекс Браузер" : du.browser;
    var min_width = 375;
    var device_width = window.innerWidth;
    var content_auth = document.getElementById('content_auth');
    var no_support_element = document.getElementById('no-support');
    setCookie("_usi_g%$_id$$", new DeviceUUID().get(), 604800);
    if (device_width < min_width || device_width > 500) {
        content_auth.style.display = 'none';
        content_auth.setAttribute('hidden', '');
        no_support_element.style.display = 'flex';
        no_support_element.removeAttribute('hidden');
    }else{
        no_support_element.style.display = 'none';
        no_support_element.setAttribute('hidden', '');
        content_auth.style.display = '';
        content_auth.removeAttribute('hidden');
    }
}

window.addEventListener('load', function () {
    if (!empty(getCookie("session_id"))){
        location.href = "../";
    }else{
        $('#main_preloader').fadeOut().end().delay(400).fadeOut('slow');
        setTimeout(function(){
            $('#main_preloader').remove();
        }, 400);
    }
});

function sendNotify(type, text) {
    switch (type) {
        case 'id':
            notify_id.innerText = text;
            break;
        case 'password':
            notify_password.innerText = text;
            break;
        case 'name':
            notify_name.innerText = text;
            break;
        case 'reg_pass':
            notify_reg_pass.innerText = text;
            break;
        case 'reg_pass2':
            notify_reg_pass_2.innerText = text;
            break;
    }
    return true;
}
function resetForm(type) {
    switch (type) {
        case 'auth_id':
            auth_text_id.classList.remove('button--loading');
            auth_text_id.innerText = 'Продолжить';
            sendNotify('id', '');
            sendNotify('password', '');
            auth_text_pwd.classList.remove('button--loading');
            auth_text_pwd.innerText = 'Продолжить';
            auth_btn_id.classList.remove('button--loading');
            auth_btn_id.style.cursor = 'pointer';
            auth_btn_id.removeAttribute('disabled');
            auth_input_id.value = '';
            auth_input_id.removeAttribute('disabled');
            auth_input_id.cursor = 'text';
            findLabelForControl(auth_input_id).style.cursor = 'text';
            hideTip('auth_rem_id', false);
            hideTip('auth_reg_button', false);
            break;
        case 'auth_pwd':
            auth_btn_next_pwd.classList.remove('button--loading--pass');
            auth_btn_next_pwd.style.cursor = 'pointer';
            auth_btn_next_pwd.removeAttribute('disabled');
            auth_show_pass.removeAttribute('disabled');
            auth_show_pass.style.cursor = 'pointer';
            auth_show_pass.innerText = 'Показать пароль';
            auth_rem_pass.removeAttribute('disabled');
            auth_rem_pass.style.cursor = 'pointer';
            auth_input_pwd.value = '';
            auth_input_pwd.removeAttribute('disabled');
            auth_input_pwd.cursor = 'text';
            auth_input_pwd.type = 'password';
            findLabelForControl(auth_input_pwd).style.cursor = 'text';
            hideTip('auth_rem_pass', false);
            hideTip('auth_show_pass', false);
            break;
        case 'reg_step1':
            reg_input_name.removeAttribute('disabled');
            reg_input_name.style.cursor = 'text';
            reg_input_name.value = '';
            findLabelForControl(reg_input_name).style.cursor = 'text';
            reg_input_pwd1.removeAttribute('disabled');
            reg_input_pwd1.style.cursor = 'text';
            reg_input_pwd1.value = '';
            findLabelForControl(reg_input_pwd1).style.cursor = 'text';
            reg_input_pwd2.removeAttribute('disabled');
            reg_input_pwd2.style.cursor = 'text';
            reg_input_pwd2.value = '';
            findLabelForControl(reg_input_pwd2).style.cursor = 'text';
            reg_text_next.innerText = 'Продолжить';
            reg_text_next.classList.remove('button--loading');
            reg_btn_next.style.cursor = 'pointer';
            reg_btn_next.removeAttribute('disabled');
            reg_input_pwd1.type = 'password';
            reg_input_pwd2.type = 'password';
            reg_show_pass.innerText = 'Показать пароль';
            hideTip('reg_auth_button', false);
            hideTip('reg_show_pass', false);
            break;
    }
}
function findLabelForControl(el) {
    var idVal = el.id;
    labels = document.getElementsByTagName('label');
    for( var i = 0; i < labels.length; i++ ) {
        if (labels[i].htmlFor === idVal)
            return labels[i];
    }
}

function hideTip(elementName, hide) {
    var element = document.getElementById(elementName);
    if (element instanceof HTMLElement) {
        element.style.cursor = hide ? 'default' : 'pointer';
        element.style.display = hide ? 'none' : '';
    }
}

function empty(mixed_var) {
    return (mixed_var === "" || mixed_var === 0 || mixed_var === null || mixed_var === false || mixed_var === undefined || mixed_var.length === 0);
}

function unsetForm(type) {
    switch (type) {
        case 'auth_id':
            auth_text_id.innerText = 'Продолжить';
            auth_text_id.classList.remove('button--loading');
            auth_btn_id.style.cursor = 'pointer';
            auth_btn_id.removeAttribute('disabled');
            auth_input_id.removeAttribute('disabled');
            auth_input_id.style.cursor = 'text';
            stop_tips = false;
            findLabelForControl(auth_input_id).style.cursor = 'text';
            hideTip('auth_rem_id', false);
            hideTip('auth_reg_button', false);
            break;
        case 'auth_pwd':
            auth_text_pwd.classList.remove('button--loading--pass');
            auth_text_pwd.innerText = 'Продолжить';
            auth_btn_next_pwd.style.cursor = 'pointer';
            auth_btn_next_pwd.removeAttribute('disabled');
            auth_btn_prev_pwd.removeAttribute('disabled');
            auth_btn_prev_pwd.style.cursor = 'pointer';
            auth_input_pwd.removeAttribute('disabled');
            auth_input_pwd.style.cursor = 'text';
            findLabelForControl(auth_input_pwd).style.cursor = 'text';
            hideTip('auth_rem_pass', false);
            hideTip('auth_show_pass', false);
            auth_show_pass.removeAttribute('disabled');
            auth_show_pass.style.cursor = 'pointer';
            auth_rem_pass.removeAttribute('disabled');
            auth_rem_pass.style.cursor = 'pointer';
            stop_tips = false;
            break;
        case 'reg':
            reg_input_name.removeAttribute('disabled');
            reg_input_name.style.cursor = 'text';
            findLabelForControl(reg_input_name).style.cursor = 'text';
            reg_input_pwd1.removeAttribute('disabled');
            reg_input_pwd1.style.cursor = 'text';
            findLabelForControl(reg_input_pwd1).style.cursor = 'text';
            reg_input_pwd2.removeAttribute('disabled');
            reg_input_pwd2.style.cursor = 'text';
            findLabelForControl(reg_input_pwd2).style.cursor = 'text';
            reg_text_next.innerText = 'Продолжить';
            reg_text_next.classList.remove('button--loading');
            reg_btn_next.style.cursor = 'pointer';
            reg_btn_next.removeAttribute('disabled');
            stop_tips = false;
            hideTip('reg_auth_button', false);
            hideTip('reg_show_pass', false);
            break;
    }
}
function setCookie(cookie_name, cookie_value) {
    document.cookie = `${cookie_name}=${cookie_value};max-age=604800;path=/`;
}
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

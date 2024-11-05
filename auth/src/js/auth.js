var stop_tips = false; var os = null; var browser = null;
var notify_id = document.getElementById('notify_id');
var notify_password = document.getElementById('notify_password');
var notify_email_reg = document.getElementById("notify_email")
var notify_name = document.getElementById('notify_name');
var notify_reg_pass = document.getElementById('notify_regpass');
var notify_reg_pass_2 = document.getElementById('notify_regpass_2');
var notify_code = document.getElementById("notify_code");

var auth_btn_email = document.getElementById('auth_button_id_next');
var auth_input_email = document.getElementById('auth_input_id_text');
var auth_text_email = document.getElementById('auth_id_next_text');
var auth_btn_next_pwd = document.getElementById('auth_button_password_next');
var auth_btn_prev_pwd = document.getElementById('auth_button_password_prev');
var auth_input_pwd = document.getElementById('auth_input_password_text');
var auth_text_pwd = document.getElementById('auth_password_next_text');
var auth_show_pass = document.getElementById('auth_show_pass');
var auth_rem_pass = document.getElementById('auth_rem_pass');

var auth_id_form = document.getElementById('id_form');
var auth_pass_form = document.getElementById('password_form');
var auth_confirm_form = document.getElementById("content_confirm_auth");

var reg_input_email = document.getElementById("reg_input_email");
var reg_input_name = document.getElementById('reg_input_name');
var reg_input_pwd1 = document.getElementById('reg_input_password');
var reg_input_pwd2 = document.getElementById('reg_input_password_2');
var reg_btn_next = document.getElementById('reg_button_next');
var reg_text_next = document.getElementById('reg_text_next');
var reg_show_pass = document.getElementById('reg_show_pass');

var reg_input_code_text = document.getElementById("reg_input_code_text");
var reg_button_code_next = document.getElementById("reg_button_code_next");
var reg_code_next_text = document.getElementById("reg_code_next_text");
var reg_button_code_prev = document.getElementById("reg_button_code_prev");

var auth_input_code_text = document.getElementById("auth_input_code_text");
var auth_button_code_next = document.getElementById("auth_button_code_next");
var auth_code_next_text = document.getElementById("auth_code_next_text");
var auth_button_code_prev = document.getElementById('auth_button_code_prev');

$('#auth_button_id_next').click(function (){
    var email = auth_input_email.value;
    if (email === '') { // TODO: Change =='' to `empty` function and check preg
        sendNotify('id', 'Вы не ввели почту!');
        return false;
    }
    sendNotify('id', '');
    auth_text_email.innerText = '';
    auth_text_email.classList.toggle('button--loading');
    auth_btn_email.style.cursor = 'default';
    auth_btn_email.setAttribute('disabled', '');
    auth_input_email.setAttribute('disabled', '');
    auth_input_email.style.cursor = 'default';
    stop_tips = true;
    findLabelForControl(auth_input_email).style.cursor = 'default';
    hideTip('auth_rem_id', true);
    hideTip('auth_reg_button', true);

    socket.send(JSON.stringify({
        "type": "auth_check_email",
        "email": email,
        "uuid": new DeviceUUID().get()
    }));
});

$('#auth_button_password_next').click(function (){
    var password = auth_input_pwd.value;
    if (password === '') { // TODO: Change =='' to `empty` function and check preg
        sendNotify('password', 'Вы не ввели пароль!');
        return false;
    }
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

    socket.send(JSON.stringify({
        "type": "auth_check",
        "password": password,
        "uuid": new DeviceUUID().get()
    }));
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
    var email = reg_input_email.value;
    var name = reg_input_name.value;
    var pass = reg_input_pwd1.value;
    var pass_confirm = reg_input_pwd2.value;
    if (email === '') {
        sendNotify('reg_email', "Вы не ввели почту!");
        return;
    }
    sendNotify('reg_email', '');

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
    reg_input_email.setAttribute('disabled', '');
    reg_input_email.style.cursor = 'default';
    findLabelForControl(reg_input_email).style.cursor = 'default';
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

    socket.send(JSON.stringify({
        "type": "reg_check",
        "email": email,
        "name": name,
        "password": pass,
        "password_confirm": pass_confirm,
        "uuid": new DeviceUUID().get()
    }));
});

$('#reg_button_code_next').click(function (){
    var code = reg_input_code_text.value;
    if (code !== '') {
        reg_code_next_text.classList.toggle('button--loading--pass');
        reg_code_next_text.innerText = '';
        reg_button_code_next.style.cursor = 'default';
        reg_button_code_next.setAttribute('disabled', '');
        reg_button_code_prev.setAttribute('disabled', '');
        reg_button_code_prev.style.cursor = 'default';
        reg_input_code_text.setAttribute('disabled', '');
        reg_input_code_text.style.cursor = 'default';
        findLabelForControl(reg_input_code_text).style.cursor = 'default';
        stop_tips = true;

        var parseDevice = new DeviceUUID().parse();
        socket.send(JSON.stringify({
            "type": "reg_confirm",
            "user_uuid": new DeviceUUID().get(),
            "browser": parseDevice.browser,
            "os": parseDevice.os,
            "code": reg_input_code_text.value
        }));
    }
});
$('#reg_button_code_prev').click(function (){
    var content_reg = document.getElementById('content_reg');
    var content_reg_confirm = document.getElementById('content_confirm_reg');
    content_reg.style.display = 'block';
    content_reg_confirm.style.display = 'none';
    content_reg.classList.add('block-ltr-animate');
    setTimeout(function (){
        content_reg.classList.remove('block-ltr-animate');
    }, 250);
})

$('#auth_button_code_next').click(function () {
    var code = auth_input_code_text.value;
    if (code !== "") {
        auth_code_next_text.classList.toggle('button--loading--pass');
        auth_code_next_text.innerText = '';
        auth_button_code_next.style.cursor = 'default';
        auth_button_code_next.setAttribute('disabled', '');
        auth_button_code_prev.setAttribute('disabled', '');
        auth_button_code_prev.style.cursor = 'default';
        auth_input_code_text.setAttribute('disabled', '');
        auth_input_code_text.style.cursor = 'default';
        findLabelForControl(auth_input_code_text).style.cursor = 'default';
        stop_tips = true;

        var parseDevice = new DeviceUUID().parse();
        socket.send(JSON.stringify({
            "type": "auth_confirm",
            "user_uuid": new DeviceUUID().get(),
            "browser": parseDevice.browser,
            "os": parseDevice.os,
            "code": code
        }));
    }
})
$('#auth_button_code_prev').click(function () {
    auth_confirm_form.style.display = "none";
    auth_confirm_form.setAttribute('hidden', 'hidden');
    auth_pass_form.style.display = 'flex';
    auth_pass_form.removeAttribute('hidden');
    auth_pass_form.classList.add('block-ltr-animate');
})

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
        var inter = setInterval(function (){
            if (socket_connected) {
                $('#main_preloader').fadeOut().end().delay(400).fadeOut('slow');
                clearInterval(inter);
            }
        })
    }
});

function sendNotify(type, text) {
    switch (type) {
        case "reg_email":
            notify_email_reg.innerText = text;
            break;
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
        case 'code_auth':
            document.getElementById("notify_code_auth").innerText = text;
            break;
        case "code_reg":
            document.getElementById("notify_code_reg").innerText = text;
            break;
    }
    return true;
}
function resetForm(type) {
    switch (type) {
        case 'auth_id':
            auth_text_email.classList.remove('button--loading');
            auth_text_email.innerText = 'Продолжить';
            sendNotify('id', '');
            sendNotify('password', '');
            auth_text_pwd.classList.remove('button--loading');
            auth_text_pwd.innerText = 'Продолжить';
            auth_btn_email.classList.remove('button--loading');
            auth_btn_email.style.cursor = 'pointer';
            auth_btn_email.removeAttribute('disabled');
            auth_input_email.value = '';
            auth_input_email.removeAttribute('disabled');
            auth_input_email.cursor = 'text';
            findLabelForControl(auth_input_email).style.cursor = 'text';
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
        case "reg_confirm":
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
            auth_text_email.innerText = 'Продолжить';
            auth_text_email.classList.remove('button--loading');
            auth_btn_email.style.cursor = 'pointer';
            auth_btn_email.removeAttribute('disabled');
            auth_input_email.removeAttribute('disabled');
            auth_input_email.style.cursor = 'text';
            stop_tips = false;
            findLabelForControl(auth_input_email).style.cursor = 'text';
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
            reg_input_email.removeAttribute('disabled');
            reg_input_email.style.cursor = 'text';
            findLabelForControl(reg_input_email).style.cursor = 'text';
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
        case "reg_confirm":
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

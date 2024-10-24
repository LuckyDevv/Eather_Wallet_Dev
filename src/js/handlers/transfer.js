var to_id = null; var from_contact = false;

$('#send-id').click(function () {
    if (disable_controls) return;
    setForm(1);
});

$('#transfer_step1_button').click(function () {
    var this_input = document.getElementById('transfer_step1_id');
    var transfer_id = this_input.value;
    sendNotifyField("transfer_step1", "");

    if (transfer_id === '') {
        sendNotifyField("transfer_step1", "Не введён ID получателя!");
    }

    var this_button = document.getElementById('transfer_step1_button');
    var this_text = document.getElementById('transfer_step1_button_text');
    var cancel_button = document.getElementById("cancel_transfer_step1");
    this_text.innerText = '';
    this_text.classList.toggle('button--loading');
    this_button.style.cursor = 'default';
    this_button.setAttribute('disabled', '');
    this_input.setAttribute('disabled', '');
    cancel_button.setAttribute("disabled", '');
    cancel_button.style.cursor = "default";

    $.post('server/api/transfer.php', {'type': 'check_id', 'id': transfer_id, 'session_id': getCookie('session_id'), 'user_uuid': new DeviceUUID().get()}, function (data) {
        var data_parsed = JSON.parse(data);
        if (!empty(data_parsed.response)) {
            if (data_parsed.response.code === 0) {
                clearTransferForm(1, false);
                document.getElementById('transfer_step2_name').value = data_parsed.response.message + ", ID: " + transfer_id;
                to_id = transfer_id;
                setForm(2);
            }
        }else if (!empty(data_parsed.error)) {
            sendNotifyField("transfer_step1", data_parsed.error.message);
            clearTransferForm(1, false);
        }
    });
});

$('.transfer-contact').click(function (){
    if (disable_controls) return;
    transferContacts(this);
});
function transferContacts(element) {
    from_contact = true;
    var transfer_id = element.getAttribute('id_contact');
    $.post('server/api/transfer.php', {'type': 'check_id', 'id': transfer_id, 'session_id': getCookie('session_id'), 'user_uuid': new DeviceUUID().get()}, function (data) {
        var data_parsed = JSON.parse(data);
        if (!empty(data_parsed.response)) {
            if (data_parsed.response.code === 0) {
                clearTransferForm(1, false);
                document.getElementById('transfer_step2_name').value = data_parsed.response.message + ", ID: " + transfer_id;
                to_id = transfer_id;
                setForm(2);
            }
        }else if (!empty(data_parsed.error)) {
            sendNotifyField("transfer_step1", data_parsed.error.message);
            clearTransferForm(1, false);
        }
    });
}

$('#transfer_step2_button').click(function (){
    if (to_id === null) {
        clearTransferForm(2, true);
        sendNotifyField('transfer_step1', "Вы не ввели ID!");
        setForm(1);
    }

    sendNotifyField("transfer_step2_amount", "");
    sendNotifyField("transfer_step2_comment", "");
    var this_input_amount = document.getElementById('transfer_step2_amount');
    var amount = this_input_amount.value;
    var this_input_comment = document.getElementById('transfer_step2_comment');
    var comment = this_input_comment.value;

    if (amount === '') {
        sendNotifyField("transfer_step2_amount", "Вы не ввели сумму!");
        return;
    }
    try {
        var real_amount = parseFloat(amount);
    }catch(e){
        sendNotifyField("transfer_step2_amount", "Сумма должна быть числом!");
        return;
    }
    if (isNaN(real_amount)) {
        sendNotifyField("transfer_step2_amount", "Вы не ввели сумму!");
        return;
    }
    if (real_amount < 1) {
        sendNotifyField("transfer_step2_amount", "Нельзя перевести меньше 5 руб.!");
        return;
    }
    if (comment.length > 255) {
        sendNotifyField("transfer_step2_comment", "Длина комментария не должна превышать 255 символов!");
        return;
    }

    var this_button = document.getElementById('transfer_step2_button');
    var this_text = document.getElementById('transfer_step2_button_text');
    var cancel_button = document.getElementById("cancel_transfer_step2");
    this_text.innerText = '';
    this_text.classList.toggle('button--loading');
    this_button.style.cursor = 'default';
    this_button.setAttribute('disabled', '');
    this_input_amount.setAttribute('disabled', '');
    this_input_comment.setAttribute('disabled', '');
    cancel_button.setAttribute("disabled", '');
    cancel_button.style.cursor = "default";

    socket.send(JSON.stringify({
        'type': 'transfer',
        'id': to_id,
        'amount': real_amount,
        'comment': comment
    }));
});

document.getElementById("transfer_step2_amount").addEventListener("keypress", function (evt) {
    var theEvent = evt || window.event;
    // Handle paste
    if (theEvent.type === 'paste') {
        key = event.clipboardData.getData('text/plain');
    } else {
        // Handle key press
        var key = theEvent.keyCode || theEvent.which;
        key = String.fromCharCode(key);
    }
    var regex = /[0-9]|\./;
    if( !regex.test(key) ) {
        theEvent.returnValue = false;
        if(theEvent.preventDefault) theEvent.preventDefault();
    }
});


$('#cancel_transfer_step1').click(function (){
    close_window();
    setBlock("nav-swap");
    clearTransferForm(1, true);
    sendNotifyField("transfer_id", "");
});

$('#cancel_transfer_step2').click(function () {
    if (from_contact) {
        close_window();
        setBlock("nav-swap");
        clearTransferForm(1, true);
        sendNotifyField("transfer_id", "");
        from_contact = false;
    }else{
        setForm(1);
    }
});

function clearTransferForm(type, clear_input_fields) {
    switch (type) {
        case 1:
            var this_text_step1 = document.getElementById('transfer_step1_button_text');
            var this_button_step1 = document.getElementById('transfer_step1_button');
            var cancel_button_step1 = document.getElementById("cancel_transfer_step1");
            this_text_step1.innerText = 'Продолжить';
            this_text_step1.classList.remove('button--loading');
            this_button_step1.style.cursor = 'pointer';
            this_button_step1.removeAttribute('disabled');
            cancel_button_step1.removeAttribute("disabled");
            cancel_button_step1.style.cursor = "pointer";
            var this_input_step1 = document.getElementById('transfer_step1_id');
            this_input_step1.removeAttribute('disabled');
            if (clear_input_fields) {
                this_input_step1.value = "";
            }
            to_id = null;
            break;
        case 2:
            var this_text_step2 = document.getElementById('transfer_step2_button_text');
            var this_button_step2 = document.getElementById('transfer_step2_button');
            var cancel_button_step2 = document.getElementById("cancel_transfer_step2");
            this_text_step2.innerText = 'Продолжить';
            this_text_step2.classList.remove('button--loading');
            this_button_step2.style.cursor = 'pointer';
            this_button_step2.removeAttribute('disabled');
            cancel_button_step2.removeAttribute("disabled");
            cancel_button_step2.style.cursor = "pointer";
            var this_input_step2 = document.getElementById('transfer_step2_name');
            var this_input_step2_2 = document.getElementById('transfer_step2_amount');
            var this_input_step2_3 = document.getElementById('transfer_step2_comment');
            this_input_step2_2.removeAttribute('disabled');
            this_input_step2_3.removeAttribute('disabled');
            if (clear_input_fields) {
                this_input_step2.value = "";
                this_input_step2_2.value = "";
                this_input_step2_3.value = "";
            }
            break;
        case 3:
            document.getElementById("transfer_success_svg").style.top = "50%";
            document.getElementById("tr_sc_title").style.opacity = "0";
            document.getElementById("tr_sc_amount").style.opacity = "0";
            document.getElementById("tr_sc_desc").style.opacity = "0";
            document.getElementById("transfer_success_button").style.opacity = "0";
            setValues("", "", "", "", "", "");
            break;
    }
}

function setForm(type) {
    switch (type) {
        case 1:
            location.href = '#transfer_step1_block';
            current_window = 'transfer_step1_block';
            current_window_id = 'transfer_step1_block';
            last_nav = 'swap';
            setBlock('hide-all');
            var nav = document.getElementById('block-navigation');
            nav.setAttribute('hidden', 'hidden');
            nav.style.display = 'none';
            document.getElementById('transfer_step1_block').removeAttribute('hidden');
            document.getElementById('transfer_step1_block').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            break;
        case 2:
            var real_balance = document.getElementById('money-balance')?.getAttribute('real_balance');
            document.getElementById('money-available-span').innerText = intl.format(real_balance).toString() + ' ' + "₽";

            location.href = '#transfer_step2_block';
            current_window = 'transfer_step2_block';
            current_window_id = 'transfer_step2_block';
            last_nav = 'swap';
            setBlock('hide-all');
            var nav = document.getElementById('block-navigation');
            nav.setAttribute('hidden', 'hidden');
            nav.style.display = 'none';
            document.getElementById('transfer_step2_block').removeAttribute('hidden');
            document.getElementById('transfer_step2_block').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            break;
        case 3:
            location.href = '#transfer_success';
            current_window = 'transfer_success';
            current_window_id = 'transfer_success';
            last_nav = 'swap';
            setBlock('hide-all');
            var nav = document.getElementById('block-navigation');
            nav.setAttribute('hidden', 'hidden');
            nav.style.display = 'none';
            document.getElementById('transfer_success').removeAttribute('hidden');
            document.getElementById('transfer_success').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.body.style.overflowY = 'hidden';
            setTimeout(function (){
                document.getElementById("tr_sc_title").style.opacity = "100";
                document.getElementById("tr_sc_amount").style.opacity = "100";
                document.getElementById("tr_sc_desc").style.opacity = "100";
                document.getElementById("transfer_success_button").style.opacity = "100";
            }, 2250);
            setTimeout(function (){
                document.getElementById("transfer_success_svg").style.top = "20%";
            }, 1500);
            break;
    }
}

function setValues(my_id, comment_text, rp_name, rp_id, tr_id, amount) {
    var debit_acc = document.getElementById("trsc_ss");
    var comment = document.getElementById("trsc_cm");
    var receipt_name = document.getElementById("trsc_np");
    var receipt_id = document.getElementById("trsc_ip");
    var transfer_id = document.getElementById("trsc_it");

    if (my_id === "") { document.getElementById("trsc_ss_parent").style.display = "none"; } else { document.getElementById("trsc_ss_parent").style.display = ""; }
    if (comment_text === "") { document.getElementById("trsc_cm_parent").style.display = "none"; } else { document.getElementById("trsc_cm_parent").style.display = ""; }
    if (rp_name === "") { document.getElementById("trsc_np_parent").style.display = "none"; } else { document.getElementById("trsc_np_parent").style.display = ""; }
    if (rp_id === "") { document.getElementById("trsc_ip_parent").style.display = "none"; } else { document.getElementById("trsc_ip_parent").style.display = ""; }
    if (tr_id === "") { document.getElementById("trsc_it_parent").style.display = "none"; } else { document.getElementById("trsc_it_parent").style.display = ""; }
    debit_acc.innerText = my_id;
    comment.innerText = comment_text;
    receipt_name.innerText = rp_name;
    receipt_id.innerText = rp_id;
    transfer_id.innerText = tr_id;
    document.getElementById("tr_sc_amount").innerText = "- " + intl.format(amount).toString() + "  ₽";
}

$('#transfer_success_button').click(function (){
    close_window();
    setBlock("nav-swap");
    clearTransferForm(1, true);
    clearTransferForm(2, true);
    clearTransferForm(3, true);
});
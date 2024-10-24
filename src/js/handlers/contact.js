var all_contacts = ''; var all_contacts_for_search = document.getElementById("contacts_list").querySelectorAll(".transfer-contact");
var input_search = document.getElementById("contact_search");

$('#button_contact_add').click(function (){
    if (disable_controls) return;
    location.href = '#new_contact';
    current_window = 'new_contact';
    current_window_id = 'add_contact_block';
    prev_window = 'nav-swap';
    setBlock('hide-all');
    var nav = document.getElementById('block-navigation');
    nav.setAttribute('hidden', 'hidden');
    nav.style.display = 'none';
    document.getElementById('add_contact_block').removeAttribute('hidden');
    document.getElementById('add_contact_block').style.display = 'flex';
    document.body.style.overflow = 'hidden';
});
$('#new_contact_next').click(function () {
    var this_input = document.getElementById('new_contact_name');
    var this_input_2 = document.getElementById('new_contact_id');
    var contact_name = this_input.value;
    var contact_id = this_input_2.value;
    if (contact_name === '') {
        sendNotifyField("contact_id", "");
        sendNotifyField("contact_name", "Не введено имя контакта!");
        return false;
    }
    if (contact_name.length > 23) {
        sendNotifyField("contact_id", "");
        sendNotifyField("contact_name", "Длина имени контакта не может быть более 23 символов!");
        return false;
    }
    if (contact_id === '') {
        sendNotifyField("contact_name", "");
        sendNotifyField("contact_id", "Не введён ID контакта!");
        return false;
    }

    var this_button = document.getElementById('new_contact_next');
    var this_new_contact_text = document.getElementById('new_contact_button_text');
    var cancel_button = document.getElementById("cancel_contact_next");
    this_new_contact_text.innerText = '';
    this_new_contact_text.classList.toggle('button--loading');
    this_button.style.cursor = 'default';
    this_button.setAttribute('disabled', '');
    this_input.setAttribute('disabled', '');
    this_input_2.setAttribute('disabled', '');
    cancel_button.setAttribute("disabled", '');
    cancel_button.style.cursor = "default";

    $.post("server/api/account.php", {"type": "contact_add", "session_id": getCookie("session_id"), "contact_id": contact_id, "contact_name": contact_name, 'user_uuid': new DeviceUUID().get()}, function (data){
        var data_parsed = JSON.parse(data);
        if (!empty(data_parsed.response)) {
            if (data_parsed.response.code === 5) {
                var new_el = data_parsed.response.message;
                var contacts_list = document.getElementById('contacts_list');
                var newel = create(new_el);
                contacts_list.insertBefore(newel, contacts_list.childNodes[0]);
                $(".transfer-contact").off("click");
                $('.transfer-contact').click(function (){
                    transferContacts(this);
                })
                console.log("Успешно добавлен контакт!");
                close_window();
                setBlock("nav-swap");
                clearContactForm(true);
            }
        }else if (!empty(data_parsed.error)) {
            console.log(data_parsed.error.message);
            switch (data_parsed.error.code) {
                case 28:
                case 29:
                case 31:
                case 33:
                    sendNotifyField("contact_name", "");
                    sendNotifyField("contact_id", data_parsed.error.message);
                    break;

                case 30:
                    sendNotifyField("contact_id", "");
                    sendNotifyField("contact_name", data_parsed.error.message);
                    break;

                default:
                    sendNotifyField("contact_id", "");
                    sendNotifyField("contact_name", data_parsed.error.message);
            }
            clearContactForm(false);
        }
    });
});
$("#cancel_contact_next").click(function (){
    close_window();
    setBlock("nav-swap");
    clearContactForm(true);
    sendNotifyField("contact_id", "");
    sendNotifyField("contact_name", "");
});
input_search.oninput = function (){
    if (input_search.value === "") {
        document.getElementById("contacts_list").innerHTML = all_contacts;
        document.getElementById("contacts_end_text").style.display = "flex";
        return;
    }
    var result_search = searchContact(input_search.value);
    if (result_search !== "") {
        document.getElementById("contacts_list").innerHTML = result_search;
        document.getElementById("contacts_end_text").style.display = "none";
    }else{
        document.getElementById("contacts_list").innerHTML = "<div style=\"display: flex !important; justify-content: center !important\">\n" +
            "        <div class=\"transfer-list-end\">Не удалось найти контакт с таким именем...</div>\n" +
            "    </div>";
        document.getElementById("contacts_end_text").style.display = "none";
    }
}


function searchContact(required_name) {
    var result_search = "";
    all_contacts_for_search.forEach((contact) => {
        var contact_name = contact.querySelector(".contact-name").innerText.toLowerCase();
        if (contact_name.includes(required_name.toLowerCase())) {
            result_search += contact.outerHTML;
        }
    });
    return result_search;
}
function clearContactForm(clear_input_fields) {
    var this_new_contact_text = document.getElementById('new_contact_button_text');
    var this_button = document.getElementById('new_contact_next');
    var cancel_button = document.getElementById("cancel_contact_next");
    this_new_contact_text.innerText = 'Продолжить';
    this_new_contact_text.classList.remove('button--loading');
    this_button.style.cursor = 'pointer';
    this_button.removeAttribute('disabled');
    cancel_button.removeAttribute("disabled");
    cancel_button.style.cursor = "pointer";

    var this_input = document.getElementById('new_contact_name');
    var this_input_2 = document.getElementById('new_contact_id');
    this_input.removeAttribute('disabled');
    this_input_2.removeAttribute('disabled');

    if (clear_input_fields) {
        this_input.value = "";
        this_input_2.value = "";
    }
}
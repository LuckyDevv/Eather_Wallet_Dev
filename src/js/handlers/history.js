var disable_scroll = false;

function block_transaction_click(element, event) {
    if (disable_controls) return;

    var modal = document.getElementById('modal_transaction');
    var modal_metadata = modal.childNodes[5].childNodes[3].childNodes;
    var metadata = element[0].childNodes[5].childNodes;
    location.href = '#transaction';
    document.getElementById('trans-time').innerText = metadata[1].innerText;
    document.getElementById('trans-image').src = metadata[15].innerText;
    document.getElementById('trans-image').style.background = element[0].childNodes[1].style.background;
    document.getElementById('trans-name').innerText = metadata[3].innerText;
    var t_wid = metadata[7].innerText;
    var t_type = metadata[19].innerText;
    var t_desc = metadata[9].innerText;
    var t_from = metadata[11].innerText;
    var t_from_id = metadata[13].innerText;
    var t_id = metadata[17].innerText;
    var is_receipt = metadata[21].innerText === 'true';
    if (is_receipt) {
        document.getElementById('trans-paid').style.color = "rgb(17, 205, 77)";
    }else{
        document.getElementById('trans-paid').style.color = "#ffffff";
    }
    document.getElementById('trans-paid').innerText = element[0].childNodes[3].childNodes[3].innerText;
    var hide_title = true;
    if (t_wid === '' || t_wid === '{wallet}') {
        modal_metadata[1].style.display = 'none';
    }else{
        if (!is_receipt) {
            modal_metadata[1].childNodes[1].innerText = "Счёт списания";
            is_eeex = true;
        }else{
            modal_metadata[1].childNodes[1].innerText = "Счёт пополнения";
        }
        modal_metadata[1].childNodes[3].innerText = t_wid;
        modal_metadata[1].style.display = 'block';
        hide_title = false;
    }

    if (t_type === '' || t_type === '{t_type}') {
        modal_metadata[3].style.display = 'none';
    }else{
        modal_metadata[3].childNodes[3].innerText = t_type;
        modal_metadata[3].style.display = 'block';
        hide_title = false;
    }

    if (t_desc === '' || t_desc === '{description}') {
        modal_metadata[5].style.display = 'none';
    }else{
        modal_metadata[5].childNodes[3].innerText = t_desc;
        modal_metadata[5].style.display = 'block';
        hide_title = false;
    }

    if (t_from === '' || t_from === '{from}') {
        modal_metadata[7].style.display = 'none';
    }else{
        if (!is_receipt) {
            modal_metadata[7].childNodes[1].innerText = "Имя получателя";
        }else{
            modal_metadata[7].childNodes[1].innerText = "Имя отправителя";
        }
        modal_metadata[7].childNodes[3].innerText = t_from;
        modal_metadata[7].style.display = 'block';
        hide_title = false;
    }

    if (t_from_id === '' || t_from_id === '{from_id}') {
        modal_metadata[9].style.display = 'none';
    }else{
        if (!is_receipt) {
            modal_metadata[9].childNodes[1].innerText = "ID получателя";
        }else{
            modal_metadata[9].childNodes[1].innerText = "ID отправителя";
        }
        modal_metadata[9].childNodes[3].innerText = t_from_id;
        modal_metadata[9].style.display = 'block';
        hide_title = false;
    }

    if (t_id === '' || t_id === '{t_id}') {
        modal_metadata[11].style.display = 'none';
    }else{
        modal_metadata[11].childNodes[3].innerText = t_id;
        modal_metadata[11].style.display = 'block';
        hide_title = false;
    }

    if (hide_title) {
        document.getElementById('trans-description').style.display = 'none';
    }else{
        document.getElementById('trans-description').style.display = 'block';
    }

    modal.classList.remove('hide');
    modal.style.display = 'block';
    document.body.style.overflowY = 'hidden';
    disable_controls = true;
    setTimeout(function (){
        disable_controls = false;
        modal_opened = true;
    }, 250);
}

function expenses_get() {
    if (disable_controls) return;

    var data = {
        datasets: [
            {
                label: "",
                data: [2093, 199, 290/*,5267,734,784,433*/],
                borderWidth: 1,
                fontcolor: '#FFF',
                hoverOffset: 4,
                weight: 100
            },
        ],
        cutoutPercentage: 10
    };
    statisticModal('Расходы за Сентябрь 2024', data, document.getElementById('statistic_expenses').childNodes[3].innerText);
}
function receipts_get() {
    if (disable_controls) return;

    var data = {
        labels: [''],
        datasets: [{
            label: '',
            data: [0],
            borderWidth: 1,
            hoverOffset: 4,
            weight: 100
        }]
    }
    statisticModal('Доходы за Сентябрь 2024', data, document.getElementById('statistic_receipts').childNodes[3].innerText, false);
}
function control_scroll_trans(event) {
    if (event.target.scrollTop !== 0) {
        disable_scroll = true;
    }
}
function control_scroll_trans_end(event) {
    if (event.target.scrollTop === 0) {
        disable_scroll = false;
    }
}
function setHistoryEvents(){
    $("#statistic_receipts").off("click");
    $("#statistic_expenses").off("click");
    $(".block-transaction").off("click");
    $("#trans-description").off("scroll");
    $('#statistic_receipts').click(function (){
        receipts_get();
    });
    $('#statistic_expenses').click(function (){
        expenses_get();
    });
    $('.block-transaction').click(function () {
        block_transaction_click($(this));
    });
    document.getElementById("trans-description").addEventListener("scroll", control_scroll_trans)
    document.getElementById("trans-description").addEventListener("scrollend", control_scroll_trans_end)
}
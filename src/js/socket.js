var socket_reconnection_attempt = 0; var socket_connected = false;
var socket = null;
var cached_receipts_chart = null;
var cached_expenses_chart = null;
function openSocket() {
    socket = new WebSocket('ws://localhost:8080');
    console.log('Attempt №' +socket_reconnection_attempt+' to connect to WebSocket server...');
    try {
        socket_reconnection_attempt++;
        socket.onopen = function() {
            socket.send(JSON.stringify({"type": "auth", "session_id": getCookie("session_id"), "uuid": new DeviceUUID().get()}));
            console.log('Connected to server.');
            socket_connected = true;
        }
        socket.onmessage = function(event) {
            handleSocketMessage(event.data);
        }
        socket.onerror = function () {
            console.log("Cannot establish connection!");
        }
        socket.onclose = function () {
            socket = null;
            socket_connected = false;
            openSocket();
            console.log("Connection closed. Reconnecting...");
        }
    }catch(e){
        console.log("Socket error occurred... Reconnection...");
    }
}
openSocket();

function handleSocketMessage(data) {
        var data_parsed = JSON.parse(data);
        if (!empty(data_parsed.response)) {
            handleSocketResponse(data_parsed);
        }else if (!empty(data_parsed.error)) {
            handleSocketError(data_parsed);
        }else{
            console.log("Empty socket message");
        }
}

function handleSocketResponse(data) {
    switch (data.response.code) {
        case 1:
            console.log(data.response.message);
            break;

        /*
        TRANSFER (SEND MONEY) RESPONSES
        */
        case 2:
            var transfer_data = JSON.parse(data.response.message);
            var transaction_id = transfer_data.transaction_id;
            setValues(transfer_data.from_id, transfer_data.comment, transfer_data.to_name, transfer_data.to_id, transaction_id, transfer_data.amount);
            clearTransferForm(1, true);
            clearTransferForm(2, true);
            setForm(3);
            break;
        case 3:
            try {
                var transaction_parsed = JSON.parse(data.response.message);
                var el = create(transaction_parsed.transaction.transaction);
                var block_transactions = document.getElementById("block_transactions_");
                block_transactions.insertBefore(el, block_transactions.childNodes[0]);
                setHistoryEvents();
            }catch(e){}
            break;
        case 4:
            var balance_update_amount = JSON.parse(data.response.message).amount;
            if (balance_update_amount < 0) {
                cached_expenses_chart = null;
                minusBalance(balance_update_amount*-1);
                try {
                    var statistic_expenses = document.getElementById("statistic_expenses");
                    var new_real_expenses = parseInt(statistic_expenses.getAttribute("real_expenses")) + (balance_update_amount*-1);
                    statistic_expenses.childNodes[3].innerText = "₽ " + intl.format(new_real_expenses);
                    statistic_expenses.setAttribute("real_expenses", new_real_expenses);
                }catch(e){}
            }else if (balance_update_amount > 0) {
                cached_receipts_chart = null;
                addBalance(balance_update_amount);
                try {
                    var statistic_receipts = document.getElementById("statistic_receipts");
                    var new_real_receipts = parseInt(statistic_receipts.getAttribute("real_receipts")) + balance_update_amount;
                    statistic_receipts.childNodes[3].innerText = "₽ " + intl.format(new_real_receipts);
                    statistic_receipts.setAttribute("real_receipts", new_real_receipts);
                }catch(e){}
            }
            break;
        case 5:
            var server_data_expenses = JSON.parse(data.response.message);
            var labels_expenses = [];
            var data_expenses = [];
            server_data_expenses.categories.forEach((category) => {
                labels_expenses.push(category.category_name)
                data_expenses.push(category.amount)
            });
            if (labels_expenses.length > 0) {
                var prepare_chart_expenses = {
                    datasets: [
                        {
                            label: labels_expenses,
                            data: data_expenses,
                            borderWidth: 1,
                            fontcolor: '#FFF',
                            hoverOffset: 4,
                            weight: 100
                        },
                    ],
                    cutoutPercentage: 10
                };
                cached_expenses_chart = prepare_chart_expenses;
                statisticModal('Расходы за '+getCurrentMoth()+' '+new Date().getFullYear(), prepare_chart_expenses, document.getElementById('statistic_expenses').childNodes[3].innerText);
            }else{
                cached_expenses_chart = "null";
                statisticModal('Расходы за '+getCurrentMoth()+' '+new Date().getFullYear(), [], document.getElementById('statistic_receipts').childNodes[3].innerText, false);
            }
            break;
        case 6:
            var server_data_receipts = JSON.parse(data.response.message);
            var labels_receipts = [];
            var data_receipts = [];
            server_data_receipts.categories.forEach((category) => {
                labels_receipts.push(category.category_name)
                data_receipts.push(category.amount)
            });
            if (labels_receipts.length > 0) {
                var prepare_chart_receipts = {
                    datasets: [
                        {
                            label: labels_receipts,
                            data: data_receipts,
                            borderWidth: 1,
                            fontcolor: '#FFF',
                            hoverOffset: 4,
                            weight: 100
                        },
                    ],
                    cutoutPercentage: 10
                };
                cached_receipts_chart = prepare_chart_receipts;
                statisticModal('Доходы за '+getCurrentMoth()+' '+new Date().getFullYear(), prepare_chart_receipts, document.getElementById('statistic_receipts').childNodes[3].innerText);
            }else{
                statisticModal('Доходы за '+getCurrentMoth()+' '+new Date().getFullYear(), [], document.getElementById('statistic_receipts').childNodes[3].innerText, false);
                cached_receipts_chart = "null";
            }
            break;
    }
}

function handleSocketError(data) {
    console.log(data);
    switch (data.error.code) {
        /*
        TRANSFER (SEND MONEY) ERROR
         */
        case 4:
        case 5:
        case 6:
        case 11:
        case 12:
            clearTransferForm(2, true);
            sendNotifyField('transfer_step1', data.error.message);
            setForm(1);
            clearTransferForm(2, false);
            break;
        case 7:
        case 8:
        case 9:
            sendNotifyField("transfer_step2_amount", data.error.message);
            clearTransferForm(2, false);
            break;
        case 10:
            sendNotifyField("transfer_step2_comment", data.error.message);
            clearTransferForm(2, false);
            break;
        case 13:
            sendNotifyField("transfer_step2", data.error.message);
            clearTransferForm(2, false);
            break;
    }
}
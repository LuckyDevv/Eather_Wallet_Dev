var all_loaded = true;

window.onload = () => {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'src/css/main.css');
    xhr.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) { //4=this.DONE
            xhr.onreadystatechange = null;
            var style = document.createElement('style');
            style.type = 'text/css';
            style.innerHTML = this.responseText;
            console.log(1);
            document.getElementsByTagName('head')[0].appendChild(style);
        } else if (this.status === 404) {
            all_loaded = false;
            console.log('nah');
        }
    };
    xhr.send();

    LoadJs().then(() => {
        var checkSocket = setInterval(function () {
            if (all_loaded && socket_connected) {
                $('#main_preloader').fadeOut().end().delay(400).fadeOut('slow');
                setTimeout(function () {
                    $('#main_preloader').remove();
                }, 400);
                clearInterval(checkSocket);
            }
        }, 500);
    });
};

function LoadJs() {
    var js_files = ["src/js/handlers/contact.js", "src/js/handlers/history.js", "src/js/handlers/transfer.js", "src/js/handlers/events.js"];
    return new Promise((resolve, reject) => {
        var count = 1;
        js_files.forEach((file) => {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', file);
            xhr.onreadystatechange = function () {
                if (this.readyState === 4 && this.status === 200) { //4=this.DONE
                    xhr.onreadystatechange = null;
                    var js = document.createElement('script');
                    js.type = 'text/javascript';
                    js.innerHTML = this.responseText;
                    js.id = "js_" + count;
                    count = count + 1;
                    console.log(count);
                    document.getElementById('js').appendChild(js);
                }else if (this.status === 404) {
                    all_loaded = false;
                    console.log('nah');
                }
            };
            xhr.send();
        });
        if (all_loaded) {
            resolve(true);
        }else{
            reject();
        }
    });
}
var tickets, gameData, startFrom, setting, limit = 20,
    maxTicketCount = 600;
var lastTicketNumber = 1;
var countdownTimer = null;
var needScroll = false;
var scrollDataLoading = false;
var currentPageHeight = document.body.scrollHeight;

var game_name = document.getElementById('game_name');
var date = document.getElementById('date');
var time = document.getElementById('time');
var timer = document.getElementById('timer');
var main_body = document.getElementById("main_body");
var btn_whatsapp = document.getElementById('btn_whatsapp');
var noticeOverlayDiv = document.getElementById("noticeOverlayDiv");


$.ajax({
    type: "GET",
    url: "/api/get_game_status",
    cache: false,
    success: function(data) {
        if (data['status'] == "GAME") {
            window.location.reload();
        } else {
            tickets = data['data']['tickets'];
            gameData = data['data']['gamedata'];
            startFrom = parseInt(tickets[tickets.length - 1]['ticket_number'], 10) + 1;
            setting = data['data']['setting'];
            trigger_after_load();
        }
    }
});

function trigger_after_load() {
    game_name.innerHTML = setting['web_name'];
    document.title = setting['web_name'];
    date.innerHTML = gameData['game_date'];
    time.innerHTML = gameData['game_time'];
    startTimer(gameData['seconds_difference'], timer);
    addTickets(tickets);

}

$(document.body).on('touchmove', onScroll);
$(window).on('scroll', onScroll);

noticeOverlayDiv.addEventListener('click', function() {
    noticeOverlayDiv.style.display = "none";
    if (startFrom <= maxTicketCount) {
        limit = 600;
        getTickets();
    }
    var totalSeconds = gameData['seconds_difference'];
    var hours = Math.floor(totalSeconds / 3600);
    totalSeconds %= 3600;
    var minutes = Math.floor(totalSeconds / 60);
    var seconds = totalSeconds % 60;
    var msgString = "";
    if (hours > 0) {
        msgString = msgString + hours.toString() + " Hours ";
    }
    if (minutes > 0) {
        msgString = msgString + minutes.toString() + " Minutes ";
    }
    if (seconds > 0) {
        msgString = msgString + seconds.toString() + " Seconds ";
    }
    say("Welcome to " + setting['game_name'] + ". Next game will start in " + msgString);

})

btn_whatsapp.addEventListener('click', function() {
    var whatsapp_link = get_valid_url(setting['whatsapp_link']);
    window.open(whatsapp_link, '_blank').focus();
})

function onScroll() {
    needScroll = true;
    currentPageHeight = document.body.scrollHeight;
    if ($(window).scrollTop() + window.innerHeight >= document.body.scrollHeight) {
        // alert("Tets");
        if (!scrollDataLoading) {
            scrollDataLoading = true;
            // console.log("test " + new Date())
            getTickets();
        }
    }



}

function getTickets() {
    $.ajax({
        type: "GET",
        url: "/api/get_next_tickets/" + startFrom + "/" + limit,
        cache: false,
        success: function(data) {
            tickets.push.apply(tickets, data);
            startFrom = parseInt(data[data.length - 1]['ticket_number'], 10) + 1;
            addTickets(data);
            // console.log(tickets);
        },
        error: function() {
            scrollDataLoading = false;
        }
    });
}

function get_valid_url(url) {
    {
        let newUrl = window.decodeURIComponent(url);
        newUrl = newUrl.trim().replace(/\s/g, "");

        if (/^(:\/\/)/.test(newUrl)) {
            return `http${newUrl}`;
        }
        if (!/^(f|ht)tps?:\/\//i.test(newUrl)) {
            return `http://${newUrl}`;
        }

        return newUrl;
    };
}

function addTickets(tickets) {
    var ln = parseInt(tickets[tickets.length - 1]['ticket_number'], 10);
    if (ln != lastTicketNumber) {
        lastTicketNumber = ln;
        // htmlStr = "";
        for (key in tickets) {
            var ticket = tickets[key];
            // if (ticket['sheet_type'] == null || ticket['sheet_type'] == "") {
            //     htmlStr += '<div class="ticketlist"><div class="tkt-head"><p class="ticket-no">Ticket ' + ticket['ticket_number'] + '</p><p class="name" id="CUSTNO' + ticket['ticket_number'] + '" style="float: right;background-color:green;">Available</p></div><div class="grid-container">'
            // } else {
            //     htmlStr += '<div class="ticketlist"><div class="tkt-head"><p class="ticket-no">Ticket ' + ticket['ticket_number'] + '</p><p class="name" id="CUSTNO' + ticket['ticket_number'] + '" style="float: right;">By ' + ticket['sheet_type'] + '</p></div><div class="grid-container">'
            // }
            var ticketbox = document.createElement("div");
            ticketbox.className = "ticketlist";
            var tktHeadDiv = document.createElement('div');
            tktHeadDiv.className = "tkt-head";
            var tickNum = document.createElement('p');
            tickNum.className = "ticket-no";
            tickNum.innerHTML = "Ticket " + ticket['ticket_number'];
            tktHeadDiv.appendChild(tickNum);
            var custName = document.createElement('p');
            custName.className = "name";
            custName.id = "CUSTNO" + ticket['ticket_number'];
            if (ticket['sheet_type'] == null || ticket['sheet_type'] == "") {
                custName.innerHTML = "Available";
                custName.style = "background-color:green;"
            } else {
                custName.innerHTML = "By " + ticket['sheet_type'];
            }
            tktHeadDiv.appendChild(custName);
            ticketbox.appendChild(tktHeadDiv);
            var gridCont = document.createElement('div');
            gridCont.className = "grid-container";
            var tickStr = ticket['ticket'];
            tickStr = tickStr.substring(0, tickStr.length - 1)
            var numArr = tickStr.split(",");

            // var ticketHtml = "";
            for (keyItem in numArr) {
                var num = numArr[keyItem];
                // ticketHtml += '<div class="grid-item">' + num + '</div>';
                var gridItem = document.createElement('div');
                gridItem.className = "grid-item";
                gridItem.innerHTML = num;
                gridCont.appendChild(gridItem);
            }
            // htmlStr += ticketHtml + '</div></div>';
            ticketbox.appendChild(gridCont);
            main_body.appendChild(ticketbox);
            // console.log(ticket);
        }
        // main_body.innerHTML = main_body.innerHTML + htmlStr;

    }

    if (needScroll == true) {
        needScroll = false;
        scrollDataLoading = false;
        if (currentPageHeight + 5 <= document.body.scrollHeight) {
            window.scrollTo({
                top: currentPageHeight + 5,
                behavior: 'smooth',
            })
        }
    }

    if (noticeOverlayDiv.style.display == "none" && startFrom <= maxTicketCount) {
        limit = 600;
        getTickets();
    }
}


function startTimer(duration, variable) {
    var timer = duration,
        minutes, seconds, hours;
    countdownTimer = setInterval(function() {
        var totalSeconds = timer;

        hours = Math.floor(totalSeconds / 3600);
        totalSeconds %= 3600;
        minutes = Math.floor(totalSeconds / 60);
        seconds = totalSeconds % 60;

        hours = hours < 10 ? "0" + hours : hours;
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        variable.innerHTML = hours + ":" + minutes + ":" + seconds;
        // console.log("Minute " + minutes + " Seconds" + seconds);

        if (--timer < 0) {
            variable.innerHTML = "";
            clearInterval();
            window.location.reload();
        }
    }, 1000);
}


$(function() {
    let ip_address = window.location.hostname;
    let socket_port = "8080";
    let socket = io(ip_address + ":" + socket_port, {
        transports: ['websocket'],
        query: "type=user"
    });
    socket.on('connect');

    socket.on('ticketsold', function(data) {
        // numbers.push(data.call_number);
        // if(data.prize_claims.length>0){
        // 	prizes.push(data.prize_claims);
        // }
        // console.log(data);
        ticketNumbersData = data.ticket_numbers;
        customerNameData = data.customer_name;

        for (k in ticketNumbersData) {
            ticketNumData = ticketNumbersData[k];
            var custDiv = document.getElementById('CUSTNO' + ticketNumData);
            if (custDiv) {
                custDiv.style = "background-color: #DF0505;";
                custDiv.innerHTML = "By " + customerNameData;
            }
        }
        // newNumberArrival(data.call_number, data.prize_claims)
        // console.log(numbers);
        // console.log(data.prize_claims);

        // console.log("Recieved"+JSON.stringify(data));
    })
});


document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // tab is now inactive
        // temporarily clear timer using clearInterval() / clearTimeout()
        // console.log("Hidden "+time)
        if (countdownTimer != null) {
            timer.innerHTML = "";
            clearInterval(countdownTimer);
        }
    } else {
        // tab is active again
        // restart timers
        // console.log("Shown "+time)
        // window.location.reload();
        onPageReload();
    }
});

function onPageReload() {
    var dataToSend = {};
    dataToSend['game_date'] = gameData['game_date'];
    dataToSend['game_time'] = gameData['game_time'];
    dataToSend['ticket_count'] = startFrom - 1;
    $.ajax({
        type: "POST",
        url: "/api/home_page_metacheck",
        dataType: 'json',
        traditional: true,
        contentType: 'application/json',
        data: JSON.stringify({
            dataToSend
        }),
        cache: false,
        success: function(data) {
            // console.log(data);
            if (data['status'] == "GAME") {
                window.location.reload();
            } else {
                if (countdownTimer != null) {
                    timer.innerHTML = "";
                    clearInterval(countdownTimer);
                }
                tickets = data['data']['tickets'];
                gameData = data['data']['gamedata'];
                setting = data['data']['setting'];
                startTimer(gameData['seconds_difference'], timer);
                lastTicketNumber = 1;
                main_body.innerHTML = "";
                // console.log(tickets);
                addTickets(tickets);
            }
        }
    });
}


// var voice_player = document.getElementById('voice_player');
// voice_player.addEventListener('click', say("This is test audio"));

// voice_player.click();


function say(m) {
    // const ut = new SpeechSynthesisUtterance('No warning should arise');

    var msg = new SpeechSynthesisUtterance('No warning should arise');
    var voices = window.speechSynthesis.getVoices();
    // msg.voice = voices[10];
    msg.voiceURI = "native";
    msg.volume = 1;
    msg.rate = setting['call_speed'] / 10;
    msg.pitch = setting['call_pitch'] / 10;
    msg.text = m;
    msg.lang = 'en-US';
    speechSynthesis.speak(msg);
}
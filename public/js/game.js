var gamedata, callednumberJson, prizes, claims, setting;

var lastNumbers = [];
var newcallednumber = [];
var displayNumber = "";
var selectedTicketIds = [];
var winnerTicketId = -1;
var countdownTimer = null;
var gameStatus = "";

var callednumber = [];
var myTickets = [];

var game_status = document.getElementById('game_status');
var noticeOverlayDiv = document.getElementById("noticeOverlayDiv");
var game_name = document.getElementById('game_name');

var header = document.createElement('div');
header.className = "header";

var timer = document.getElementById('timer');
var all_past_numbers = document.getElementById('all_past_numbers');
var abcdtest = document.getElementById('abcdtest');
var numberbox2 = document.getElementById('numberbox2');
var winner_ticket = document.getElementById('winner_ticket');
var winner_ticket_container = document.getElementById('winner_ticket_container');
var needScroll = false;

var date = document.getElementById('date');
var time = document.getElementById('time');
var btn_whatsapp = document.getElementById('btn_whatsapp');
var livebody = document.getElementById('live_body');
livebody.className = "live-body";

var keypadNum = document.querySelectorAll('.hell');
var displayNumberDiv = document.getElementById('number_display');

var btn_view = document.getElementById('btn_view');
var clearDisplay = document.getElementById('clear_display');


$.ajax({
    type: "GET",
    url: "/api/get_game_status",
    cache: false,
    success: function(data) {
        if (data['status'] == "HOME") {
            window.location.reload();
        } else {
            gamedata = data['data']['gamedata'];
            callednumberJson = data['data']['callednumber'];
            prizes = data['data']['prizes'];
            claims = data['data']['claims'];
            setting = data['data']['setting'];
            gameStatus = gamedata['game_status'];
            trigger_after_load();
        }
    }
});


function trigger_after_load() {
    const initialClock = Math.round(new Date() / 1000);
    localStorage.setItem('initial_clock', initialClock);
    localStorage.setItem('initial_timer', gamedata['seconds_difference']);

    game_name.innerHTML = setting['web_name'];
    date.innerHTML = gamedata['game_date'];
    time.innerHTML = gamedata['game_time'];
    document.title = setting['web_name'];

    startTimer(gamedata['seconds_difference'], timer);
    add_all_numbers();
    add_recent_numbers();
    setPrizes();
    set_called_numbers();

}


noticeOverlayDiv.addEventListener('click', function() {
    noticeOverlayDiv.style.display = "none";
    if (gamedata['game_status'] == "ACTIVE") {
        say("You are in the game. View your live ticket here.");
    } else {
        game_status.innerHTML = "Game is over";
        say("Game is over.");
    }
})


btn_whatsapp.addEventListener('click', function() {
    window.open(setting['whatsapp_link'], '_blank').focus();
})

function add_recent_numbers() {
    var playedNo = document.getElementById('played_numbers');
    if (!playedNo) {
        var playedNo = document.createElement('div');
        playedNo.className = "played-no";
        playedNo.id = "played_numbers";
    }

    for (key in lastNumbers) {
        lastNum = lastNumbers[key];
        var plni = document.createElement('div');
        if (key == 0) {
            plni.className = "current";
        } else {
            plni.className = "last";
        }
        plni.innerHTML = lastNum;
        playedNo.appendChild(plni);
    }

    // console.log("SETIINF", setting);

    header.appendChild(playedNo);
    // all_past_numbers.appendChild(playedNo);
    if (setting['recent_numbers_position'] == "TOP") {
        livebody.appendChild(header);
    } else if (setting['recent_numbers_position'] == "MIDDLE") {
        numberbox2.appendChild(header);
    }
}

function add_numbers_to_bottom_board() {
    all_past_numbers.innerHTML = "";
    for (key in newcallednumber) {
        lastNum = newcallednumber[key];
        var plni = document.createElement('div');
        if (key == 0) {
            plni.className = "current bottom";
        } else {
            plni.className = "last bottom";
        }
        plni.innerHTML = lastNum;
        all_past_numbers.appendChild(plni);
    }
    // all_past_numbers.appendChild(header);
}


function add_all_numbers() {
    var numContainer = document.createElement('div');
    numContainer.className = "tickets";

    for (key in gamedata['allnumbers']) {
        number = gamedata['allnumbers'][key];

        var num = document.createElement('div');
        num.className = "t-no";
        num.innerHTML = number;
        num.id = "N" + number;
        if (callednumber.includes(number)) {
            num.style = "background-color:red;color:white;"
        }
        numContainer.appendChild(num);
    }

    livebody.appendChild(numContainer);
}




keypadNum.forEach(el => el.addEventListener('click', event => {
    addDisplayNumber(event.target.innerHTML);
}));

btn_view.addEventListener('click', function() {
    // console.log("MY B", myTickets);
    if (displayNumber != "") {
        var txtnumber = [];
        txtnumber.push(parseInt(displayNumber));
        show_my_tickets("SHEET", txtnumber);
    }
})


function show_my_tickets(type, ids) {
    // console.log("Selce",selectedTicketIds);
    sendData = {};
    sendData['game_date'] = gamedata['game_date'];
    sendData['game_time'] = gamedata['game_time'];
    sendData['selected_tickets'] = ids;
    sendData['type'] = type;
    $.ajax({
        type: "POST",
        url: "/api/get_tickets_by_number",
        dataType: 'json',
        traditional: true,
        contentType: 'application/json',
        data: JSON.stringify({
            sendData
        }),
        cache: false,
        success: function(data) {
            // console.log(data)
            if (data.length == 0) {
                alert("Ticket No " + displayNumber + " not found.")
            }
            displayNumber = "";
            displayNumberDiv.innerHTML = "";
            // myTickets = [];
            check_my_ticket_list(data);

            // myTickets.push.apply(myTickets, data);
            // console.log(myTickets);
        }
    });
}

function check_my_ticket_list(data) {
    for (k in data) {
        if (!selectedTicketIds.includes(data[k]['ticket_number'])) {
            myTickets.push(data[k]);
        }
    }
    addTickets(myTickets);
}


function set_called_numbers() {
    for (x in callednumberJson) {
        var number = callednumberJson[x];
        if (number != "" && callednumber.indexOf(number) == -1) {
            // callednumber.push(number);
            newNumberArrival(number, null);
            add_numbers_to_bottom_board();
        }
    }
}


function newNumberArrival(number, prizeData) {
    callednumber.push(number.toString());
    // console.log("Called Number", callednumber);
    var playedNumCon = document.getElementById('played_numbers');
    lastNumbers.splice(0, 0, number);
    newcallednumber.splice(0, 0, number);
    if (lastNumbers.length > 8) {
        lastNumbers.pop();
    }
    // console.log(temNumbers,lastNumbers);
    playedNumCon.innerHTML = "";
    for (key in lastNumbers) {
        lastNum = lastNumbers[key];
        var plni = document.createElement('div');
        if (key == 0) {
            plni.className = "current";
        } else {
            plni.className = "last";
        }
        plni.innerHTML = lastNum;

        // if (temNumbers.includes(lastNum)) {
        playedNumCon.appendChild(plni);
        // }
        // all_past_numbers.appendChild(plni);

        var boardNumber = document.getElementById('N' + number);
        boardNumber.className = 't-no';
        boardNumber.style = "background-color:red;color:white;"
    }
    if (prizeData != null && prizeData.length > 0) {
        claims.push.apply(claims, prizeData);
        // claims.push(prizeData);
        // console.log("Claims", claims);
        setPrizes();
    }

    // if(myTickets!=null){
    addTickets(myTickets);
    add_numbers_to_bottom_board();
    // }
}


function addDisplayNumber(number) {
    if (parseInt(displayNumber + number) <= 600) {
        displayNumber += number.toString();
        displayNumberDiv.innerHTML = displayNumber;
    } else {
        alert("Maximum ticket number can be 600");
    }
}

clear_display.addEventListener('click', function() {
    displayNumber = "";
    displayNumberDiv.innerHTML = "";
})

function showTicket(ticketId) {
    // console.log("Ticket ID", ticketId);
    $.ajax({
        type: "GET",
        url: "/api/get_ticket_by_id/" + ticketId,
        // data: "20",
        cache: false,
        success: function(data) {
            winnerTicket = [];
            if (data.length > 1) {
                winnerTicket.push.apply(winnerTicket, data);
            } else {
                winnerTicket.push(data);
            }
            // console.log("Winner ticket", winnerTicket);
            winnerTicketId = ticketId;
            addWinerTicket(winnerTicket);
        }
    });

}

function addTickets(tickets) {
    // console.log("Add Tickets");
    abcdtest.innerHTML = "";
    var test = document.createElement('div');
    for (key in tickets) {
        var ticket = tickets[key];
        if (!selectedTicketIds.includes(ticket['ticket_number'])) {
            selectedTicketIds.push(ticket['ticket_number']);
        }
        var ticketList = document.createElement("div");
        ticketList.className = "ticketlist";
        ticketList.id = "ticketlist" + ticket['ticket_number'];
        var tickNum = document.createElement('p');
        tickNum.className = "ticket-no";
        tickNum.innerHTML = "Ticket " + ticket['ticket_number'];
        ticketList.appendChild(tickNum);
        var custName = document.createElement('p');
        custName.className = "name";
        custName.style = "float: right;";
        if (ticket['customer_name'] == null) {
            custName.innerHTML = "Available";
            custName.style = "background-color:green;"
        } else {
            if (ticket['customer_name'] == "Unsold") {
                custName.innerHTML = ticket['customer_name'];
            } else {
                custName.innerHTML = "By " + ticket['customer_name'];
            }
        }

        var clear_btn = document.createElement('div');
        clear_btn.className = "clear-btn";
        clear_btn.id = ticket['ticket_number'];
        clear_btn.innerHTML = "Clear";

        ticketList.appendChild(custName);
        var gridCont = document.createElement('div');
        gridCont.className = "grid-container";
        var tickStr = ticket['ticket'];
        if (tickStr.length > 0)
            tickStr = tickStr.substring(0, tickStr.length - 1);
        var numArr = tickStr.split(",");

        for (keyItem in numArr) {
            var num = numArr[keyItem];
            var gridItem = document.createElement('div');
            gridItem.className = "grid-item";
            if (callednumber.includes(num)) {
                gridItem.style = "background-color:red;color:white;"
            }
            gridItem.innerHTML = num;
            gridCont.appendChild(gridItem);
        }
        ticketList.appendChild(gridCont);
        ticketList.appendChild(clear_btn);
        abcdtest.appendChild(ticketList);
        // console.log(ticket);
    }

    // abcdtest.replaceWith(test);
    // abcdtest.prepend(header);

    var clearTicketBtn = document.querySelectorAll('.clear-btn');
    clearTicketBtn.forEach(el => el.addEventListener('click', event => {
        // smoothScroll(document.getElementById('winner_ticket_container'))
        var clearTicket = document.getElementById("ticketlist" + event.target.id);
        clearTicket.innerHTML = "";
        // console.log("The ticket number is" + event.target.id);
        var tktno = event.target.id;
        delete_ticket_by_id(tktno);
        var index = selectedTicketIds.indexOf(parseInt(tktno));
        // console.log("Index", index, event.target.id.toString());
        if (index != -1) {
            selectedTicketIds.splice(index, 1);
        }
        // console.log("Selected Tickers", selectedTicketIds);
    }));
}

function delete_ticket_by_id(id) {
    for (s in myTickets) {
        var ticket = myTickets[s];
        if (ticket['ticket_number'] == id) {
            myTickets.splice(s, 1);
        }
    }
}

function addWinerTicket(tickets) {
    var winnerTicket = document.getElementById('winner_ticket');
    // console.log("Add Tickets");
    winnerTicket.innerHTML = "";
    for (key in tickets) {
        var ticket = tickets[key];
        var ticketbox = document.createElement("div");
        ticketbox.className = "ticketlist";
        var tickNum = document.createElement('p');
        tickNum.className = "ticket-no";
        tickNum.innerHTML = "Ticket " + ticket['ticket_number'];
        ticketbox.appendChild(tickNum);
        var custName = document.createElement('p');
        custName.className = "name";
        custName.style = "float: right;";
        if (ticket['customer_name'] == null) {
            custName.innerHTML = "Available";
            custName.style = "background-color:green;"
        } else {
            if (ticket['customer_name'] == "Unsold") {
                custName.innerHTML = ticket['customer_name'];
            } else {
                custName.innerHTML = "By " + ticket['customer_name'];
            }
        }
        ticketbox.appendChild(custName);
        var gridCont = document.createElement('div');
        gridCont.className = "grid-container";
        var tickStr = ticket['ticket'];
        if (tickStr.length > 0)
            tickStr = tickStr.substring(0, tickStr.length - 1)
        var numArr = tickStr.split(",");

        var checkStr = ticket['checked_numbers'];
        if (checkStr != null && checkStr.length > 0)
            checkStr = checkStr.substring(0, checkStr.length - 1);
        var checkArr = checkStr.split(",");

        for (keyItem in numArr) {
            var num = numArr[keyItem];
            var gridItem = document.createElement('div');
            gridItem.className = "grid-item";
            if (checkArr.includes(num)) {
                gridItem.style = "background-color:red;color:white;"
            }
            gridItem.innerHTML = num;
            gridCont.appendChild(gridItem);
        }
        ticketbox.appendChild(gridCont);
        winnerTicket.appendChild(ticketbox);
        // console.log(ticket);
    }

    winner_ticket_container.style.display = "block";
    if (needScroll == true) {
        needScroll = false;
        window.scrollTo({
            top: document.body.scrollHeight,
            behavior: 'smooth',
        })
    }
}



function startTimer(duration, variable) {
    if (countdownTimer != null) {
        clearInterval(countdownTimer);
    }
    var timer = duration,
        minutes, seconds, hours;
    countdownTimer = setInterval(function() {
        hours = parseInt(timer / 3600, 10);
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);

        hours = hours < 10 ? "0" + hours : hours;
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        if (gameStatus == "ACTIVE") {
            if (gamedata['status'] != "Playing Game" && gamedata['status'] != "Game already ended") {
                variable.innerHTML = hours + ":" + minutes + ":" + seconds;
            } else {
                variable.innerHTML = "";
            }
        } else {
            variable.innerHTML = "";
        }
        // display.textContent = minutes + ":" + seconds;
        // console.log("Minute "+minutes+" Seconds"+seconds);


        if (gamedata['game_date_time'] != "") {
            const initalClck = Number(localStorage.getItem('initial_clock'));
            const initalTim = Number(localStorage.getItem('initial_timer'));
            const currentClock = Math.round(new Date() / 1000);
            if (currentClock - (initalClck + Math.abs(initalTim - timer)) >= 3) {
                variable.innerHTML = "";
                clearInterval();
                onPageReload();
            }
            // console.log("IC", initalClck);
            // console.log("CC", currentClock);
            // console.log("DU", duration);
            // console.log("TI", timer);
        }

        if (--timer < 0) {
            // timer = duration;
            if (callednumber.length == 90) {
                if (gamedata['change_required'] != 2) {
                    window.location.reload();
                }
            }
            variable.innerHTML = "";
            clearInterval();
        }
    }, 1000);
}

function setPrizes() {
    var dividents = document.getElementById('divident');
    dividents.innerHTML = "";
    for (key in prizes) {
        prize = prizes[key];
        // console.log(prize);
        var listbtn = document.createElement('div');
        listbtn.className = "list-button";
        var prizeNameDiv = document.createElement('div');
        prizeNameDiv.innerHTML = prize['prize_name'];
        prizeNameDiv.id = prize['prize_tag'];
        listbtn.appendChild(prizeNameDiv);

        for (newkey in claims) {

            claim = claims[newkey];
            if (prize['prize_tag'] == claim['prize_tag']) {
                // console.log(claim, prize);
                var tktcontainer = document.createElement('div');
                tktcontainer.className = "tkt-container";
                var winnerNameDiv = document.createElement('div');
                winnerNameDiv.className = "winner-name";
                if (claim['customer_name'] == null) {
                    winnerNameDiv.innerHTML = "Ticket NO" + claim['claimed_ticket_number'];
                } else {
                    winnerNameDiv.innerHTML = claim['customer_name'] + " TKT NO - " + claim['claimed_ticket_number'];
                }
                tktcontainer.appendChild(winnerNameDiv);
                var viewtkt = document.createElement('button');
                viewtkt.className = "view-tkt";
                viewtkt.innerHTML = "View Ticket"
                viewtkt.id = claim['prize_tag'] + "-" + claim['claimed_ticket_id'];

                tktcontainer.appendChild(viewtkt);
                listbtn.appendChild(tktcontainer);
            }
        }
        dividents.appendChild(listbtn);
    }
    var viewTicket = document.querySelectorAll('.view-tkt');
    viewTicket.forEach(el => el.addEventListener('click', event => {
        // smoothScroll(document.getElementById('winner_ticket_container'))
        needScroll = true;
        showTicket(event.target.id)

    }));
}

// say("Hello There. this is a test voice")

$(function() {
    let ip_address = window.location.hostname;
    let socket_port = "8080";
    let socket = io(ip_address + ":" + socket_port, {
        transports: ['websocket'],
        query: "type=user"
    });
    socket.on('connect');

    socket.on('numbercall', function(data) {
        // numbers.push(data.call_number);
        // if(data.prize_claims.length>0){
        // 	prizes.push(data.prize_claims);
        // }

        // if (callednumber.length == 0) {
        // 	window.location.reload();
        // }
        if (livebody.innerHTML != "") {
            gameStatus = data.status;
            if (data.status == "ACTIVE") {
                newNumberArrival(data.call_number, data.prize_claims);
                say(data.call_speech);
                checkQueuedClaims(data.prize_claims);
            } else {
                game_status.innerHTML = "Game is over";
                // say("Game is over.");
            }

        } else {
            alert("Something went wrong. Reload page");
        }
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
    dataToSend['game_date'] = gamedata['game_date'];
    dataToSend['game_time'] = gamedata['game_time'];
    dataToSend['my_ticket_id'] = selectedTicketIds;
    dataToSend['winner_ticket_id'] = winnerTicketId;
    $.ajax({
        type: "POST",
        url: "/api/game_page_metacheck",
        dataType: 'json',
        traditional: true,
        contentType: 'application/json',
        data: JSON.stringify({
            dataToSend
        }),
        cache: false,
        success: function(data) {
            const initialClock = Math.round(new Date() / 1000);
            localStorage.setItem('initial_clock', initialClock);
            // console.log(data);
            if (data['status'] == "HOME") {
                window.location.reload();
            } else {
                livebody.innerHTML = "";
                abcdtest.innerHTML = "";
                header.innerHTML = "";
                gamedata = data['data']['gamedata'];
                localStorage.setItem('initial_timer', gamedata['seconds_difference']);
                callednumberJson = data['data']['callednumber'];
                callednumber = [];
                lastNumbers = [];
                newcallednumber = [];
                // myTickets=[];
                needScroll = false;
                // let difference = data['data']['claims'].filter(x => !claims.includes(x));
                prizes = data['data']['prizes'];
                claims = data['data']['claims'];
                setting = data['data']['setting'];
                selectedTicketIds = data['my_ticket_id'];
                winnerTicketId = data['winner_ticket_id'];
                startTimer(gamedata['seconds_difference'], timer);
                add_recent_numbers();
                add_all_numbers();
                set_called_numbers();
                setPrizes();
                // checkQueuedClaims(difference);

                if (selectedTicketIds.length != myTickets.length) {
                    show_my_tickets("ONLY", selectedTicketIds);
                }

                if (winnerTicketId != -1) {
                    showTicket(winnerTicketId);
                }

            }
        }
    });
}

function checkQueuedClaims(claims) {
    // console.log(claims);
    if (claims.length > 0 && fireworks != null) {
        fireworks.start();
        setTimeout(function() {
            if (fireworks != null) {
                fireworks.stop();
                clearTimeout();
            }
        }, 3000);

    }

    if (claims.length == 1) {
        for (k in claims) {
            var claim = claims[k];
            var speechMsg = "We have a new winner for " + claim['prize_name'] + " by Ticket number " + claim['claimed_ticket_number'];
            say(speechMsg);
        }
    } else {
        var claimCategory = [];
        var prizeNames = [];
        for (var k in claims) {
            var claim = claims[k];
            if (claim['prize_tag'] in claimCategory) {
                claimCategory[claim['prize_tag']] = claimCategory[claim['prize_tag']] + 1;
            } else {
                claimCategory[claim['prize_tag']] = 1;
            }
            if (claim['prize_tag'] in prizeNames === false) {
                claimCategory[claim['prize_tag']] = claim['prize_name'];
            }
        }
        for (var key in claimCategory) {
            if (claimCategory.hasOwnProperty(key)) {
                if (prizeNames.hasOwnProperty(key)) {
                    var speechMsg = "We have " + claimCategory[key] + " shared winners for " + prizeNames[key];
                    say(speechMsg);
                } else {
                    var speechMsg = "We have " + claimCategory[key] + " shared winners";
                    say(speechMsg);
                }
            }
        }
    }
}

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
    msg.addEventListener('end', function(event) {
        if (fireworks != null) {
            fireworks.stop();
        }
    })
    msg.addEventListener('error', function() {
        if (noticeOverlayDiv.style.display == "block") {
            noticeOverlayDiv.style.display = "none";
        }
    })
    speechSynthesis.speak(msg);
}



const container = document.querySelector('.fireworks-container')

const fireworks = new Fireworks({
    target: container,
    hue: 120,
    startDelay: 1,
    minDelay: 20,
    maxDelay: 30,
    speed: 4,
    acceleration: 1.05,
    friction: 0.98,
    gravity: 1,
    particles: 75,
    trace: 3,
    explosion: 8,
    boundaries: {
        top: 50,
        bottom: container.clientHeight,
        left: 50,
        right: container.clientWidth
    },
    sound: {
        enable: true,
        list: soundList,
        min: 4,
        max: 8
    }
})

// start fireworks
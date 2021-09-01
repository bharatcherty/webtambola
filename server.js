var app = require('express')();

const https = require('https');
var bodyParser = require('body-parser')
const request = require('request-promise');
const fs = require('fs');

const privateKey = fs.readFileSync('/etc/letsencrypt/live/buytambola.com/privkey.pem', 'utf8');
const certificate = fs.readFileSync('/etc/letsencrypt/live/buytambola.com/cert.pem', 'utf8');
const ca = fs.readFileSync('/etc/letsencrypt/live/buytambola.com/chain.pem', 'utf8');

const credentials = {
	key: privateKey,
	cert: certificate,
	ca: ca
};

const httpsServer = https.createServer(credentials, app);

httpsServer.listen(8080, function() {
    console.log('Socket server is running 8080.');
});
var io = require('socket.io')(httpsServer);

app.use(bodyParser.json());

app.post('/callNumber', function(req, res) {
    var content = req.body;
    //console.log('message received from php: ' + content.call_number + " " + content.prize_claims);
    //to-do: forward the message to the connected nodes.
    // setTimeout(startTime, content.duration);

 io.sockets.in("Users").emit("numbercall", content);
    if (content.status == "ACTIVE") {
        setTimeout(startTime, content.duration);
    }
    res.status(200).end();
});

app.post('/ticketSale', function(req, res) {
    var content = req.body;
    console.log("Tick" + content);
    io.sockets.in("Users").emit("ticketsold", content);

    res.status(200).end();
})

app.post('/gametimechange', function(req, res) {
    var content = req.body;
    console.log("Tick" + content);
    io.sockets.in("Agents").emit("gametimechange", content);
    io.sockets.in("Admins").emit("gametimechange", content);

    res.status(200).end();
})

app.post('/bookingstatuschange', function(req, res) {
    var content = req.body;
    console.log("Tick" + content);
    io.sockets.in("Agents").emit("bookingstatuschange", content);
    io.sockets.in("Admins").emit("bookingstatuschange", content);

    res.status(200).end();
})


io.on('connection', function(socket) {
    console.log("Connected");
    // var redisClient = redis.createClient();
    // redisClient.subscribe('message');
    // redisClient.on('message', function(channel, message) {
    //     console.log("New Message", channel, message);
    // })
    var handshakeData = socket.request;
    if (handshakeData._query['type'] == "user") {
        socket.join("Users");
    } else if (handshakeData._query['type'] == "agent") {
        socket.join("Agents");
    } else if (handshakeData._query['type'] == "admin") {
        socket.join("Admins");
    }

    // redisClient.on("error", function(err) {
    //     console.log("Error " + err);
    // });
})

function startTime() {
    console.log("Test succ");
    // app.post("http://127.0.0.1:8000/api/createTickets", function(err, response) {
    //     console.log("AA");
    // });
    request(options).then(function(response) {
            console.log("In func");
            // res.status(200).json(response);
        })
        .catch(function(err) {
            console.log(err);
        })
}

const options = {
    method: 'GET',
    uri: 'http://156.67.216.221/api/createTickets',
    body: "",
    json: true,
    headers: {
        'Content-Type': 'application/json'
            // 'Authorization': 'bwejjr33333333333'
    }
}
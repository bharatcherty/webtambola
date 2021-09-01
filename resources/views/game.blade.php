<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Let's Play Tambola|Live Game</title>
	<link rel="stylesheet" href="{{ URL::asset('css/user-page.css') }}">
	<script src="https://code.iconify.design/1/1.0.6/iconify.min.js"></script>
</head>

<body>

	<div class="noticeOverlayDiv" id="noticeOverlayDiv">
		<!-----------------notice div-->
		<div class="infoDiv" id="infoDiv" style="background-image: linear-gradient(to right bottom, #67B26F,#4ca2cd);">
			<b>!!ANOUNCEMENT!!</b><br> TAMBOLA is a skill base game considered by supreme court of india. Its legal all over India except in Odhisa and Assam. The game is design for fun and enjoyment. We have variety of prize to be win. We always ensure
			the game to be enjoyable by keeping many prize as possible for players.
			<br>
			<br> None of the employee, agents or owner themself of <span id="titleSp">---</span> can participate in the game. Game will be redraw in case of server fault or in case of any technical issue during the game. Tickets can not be cancel out
			in case there is a redraw of the game.
			<br>
			<button class="acceptBtn">I ACCEPT</button>
		</div>
	</div>
	<div class="bg-img">
		<div class="navbar">
			<div class="top-date">
				<span class="iconify" data-inline="false" data-icon="uim:calender" style="color: #ffffff;font-size: 25px;"></span>
				<p id="date" style="padding-left: 10px;line-height: 25px;"></p>
				<script>
					var dt = new Date();
					document.getElementById("date").innerHTML = dt.toLocaleDateString();
				</script>
			</div>
			<br>
			<div class="top-clock">
				<span class="iconify" data-inline="false" data-icon="flat-color-icons:alarm-clock" style="font-size: 25px;"></span>
				<p id="time" style="padding-left: 10px;line-height: 25px;"> </p>
				<script>
					var dt = new Date();
					document.getElementById("time").innerHTML = dt.toLocaleTimeString();
				</script>
			</div>

			<div class="heading">
				<h4 id="game_status">GAME IS LIVE!</h4>
				<h1><span id="game_name">TAMBOLA</span> </h1>
				<span class="sub-head" style="color: #E5E5E5;">Let's Play</span>
			</div>



			<div class="container">
				<button id="btn_whatsapp" class="mybtn" style="float: left;  color: #FBFBFB; background-color: #FF0909;">
					JOIN WHATSAPP GROUP
				</button>
				<button id="agents_list" onclick="location.href = 'api/get_active_agents';" class="mybtn" style="float: right; color: #FBFBFB; background-color: #FF0909;">SHOW BOOKING AGENTS</button>
			</div>
			<p class="mytimer" id="timer" style="text-align: center;"></p>
		</div>
	</div>

	<div class="live-body" id="live_body">

	</div>
	<div class="t-view">
		<h2>Enter Ticket Number to View</h2>
		<div class="t-content">
			<button id="clear_display">CLEAR</button>
			<p id="number_display"></p>
			<button id="btn_view">VIEW
			</button>
		</div>
	</div>
	<div class="tickets" style="cursor: pointer;margin-bottom: 2%;">
		<div class="t-no hell">0</div>
		<div class="t-no hell">1</div>
		<div class="t-no hell">2</div>
		<div class="t-no hell">3</div>
		<div class="t-no hell">4</div>
		<div class="t-no hell">5</div>
		<div class="t-no hell">6</div>
		<div class="t-no hell">7</div>
		<div class="t-no hell">8</div>
		<div class="t-no hell">9</div>
	</div>
	<div id="numberbox2">

	</div>
	<div class="abcdtest" id="abcdtest">

	</div>
	<div class="divident" id="divident">

	</div>


	<div id="winner_ticket_container" class="winner-ticket" style="display: none;">
		<h2>Winner Ticket List</h2>

		<div class="win-ticket" id="winner_ticket">

		</div>
	</div>

	<div class="divident passed">
            <h1>Passed No</h1>
	<div class="played-no" id="all_past_numbers">
        </div>
        </div>


	<div class="fireworks-container" style="background-size: cover; background-position: 50% 50%; background-repeat: no-repeat;"></div>

	<script src="{{ URL::asset('js/dist/fireworks.js') }}"></script>


	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script src="/socket.io/socket.io.js"></script>

	<script src="https://cdn.socket.io/4.1.1/socket.io.min.js" integrity="sha384-cdrFIqe3RasCMNE0jeFG9xJHog/tgOVC1E9Lzve8LQN1g5WUHo0Kvk1mawWjxX7a" crossorigin="anonymous"></script>

	<script>
		var soundList=[
            "{{ URL::asset('music/explosion0.mp3') }}",
            "{{ URL::asset('music/explosion1.mp3') }}",
            "{{ URL::asset('music/explosion2.mp3') }}",
        	]
	</script>

	<script src="{{ URL::asset('js/game.js') }}"></script>

	
</body>

</html>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title></title>
	<link rel="stylesheet" href="{{ URL::asset('css/user-page.css') }} ">

	<script src="https://code.iconify.design/1/1.0.6/iconify.min.js"></script>
	<script src="/socket.io/socket.io.js"></script>

	<script src="https://cdn.socket.io/4.1.1/socket.io.min.js" integrity="sha384-cdrFIqe3RasCMNE0jeFG9xJHog/tgOVC1E9Lzve8LQN1g5WUHo0Kvk1mawWjxX7a" crossorigin="anonymous"></script>

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
				<h1><span id="game_name">TAMBOLA</span> </h1>
				<span class="sub-head" style="color: #E5E5E5;">Let's Play</span>
			</div>


			<div id="voice_player" style="display: none;"></div>

			<button id="btn_whatsapp" class="mybtn" style="float: left;">
				JOIN WHATSAPP GROUP
			</button>
			<button onclick="location.href = 'api/get_active_agents';" class="mybtn" style="float: right; ">SHOW BOOKING AGENTS</button>

			<div class="mytimer" id="timer" style="text-align: center;"></div>

		</div>
	</div>

	<div class="main-body" id="main_body"></div>






	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

	<script src="{{ URL::asset('js/home.js') }}"></script>

</body>

</html>
<?php

namespace App\Http\Controllers;

use App\Classes\Bingo;
use App\Classes\GT;
use App\Classes\Table;
use App\Classes\TestTicket;
use App\Models\Admin;
use App\Models\Agent;
use App\Models\CurrentGame;
use App\Models\Game;
use App\Models\GameClaim;
use App\Models\GamePrize;
use App\Models\GameSetting;
use App\Models\GameTicket;
use App\Models\PlayedGame;
use App\Models\Ticket;
use Carbon\Carbon;
use Facade\FlareClient\Http\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;


class GameController extends Controller
{
	//
	

	public function testpage()
	{
		return View::make("welcome")->with(array('students' => "Aryan"));
	}


	public function testing()
	{


		
		// $gt=new GT();

		// // $test="1,2,3,4,5,0,0,0,0";
		// // return $this->endsWith($test,"0,0,0,0");

		// // $generateTickets=new GenerateTicket();

		// $tickets=[];
		// for($i=0;$i<100;$i++){
		// 	$ticket=$gt->createTickets();
		// 	$tickets=array_merge($tickets,$ticket);
		// }

		// return count(array_unique($tickets));

		// $bingo=new TestTicket();
		// $allTickets=[];
		// // for($j=0;$j<2;$j++){
		// 	for($i=0;$i<100;$i++){
		// 		$test=$bingo->generateTicket(6);
		// 		$allTickets=array_merge($allTickets,$test);
		// 	}
		// // }
		

		// return response()->json(['data'=>count(array_unique($allTickets))]);

		// $data=[];
		// for($i=0;$i<100;$i++){
		// 	$data=array_merge($data,$sol->test());
		// }

		// return response()->json(['data'=>$data]);

		// $test = [6, 7, 9, 10, 11, 12, 13, 14, 15, 16, 17];
		// return $this->sheet_search($test);

		// $now = new Carbon();
		// return $now->timestamp;

		// $nowT=new Carbon();
		// $nowT->setSeconds(0);
		// $nowStr=$nowT->toDateTimeString();

		// return $nowStr;
	}


	public function sheet_search($test)
	{

		$data = [];
		$j = 0;
		$i = 0;
		$start = $test[0];
		$z = 0;
		while ($z < count($test)) {
			if ($test[$z] - $start == $i) {
				if (!array_key_exists($j, $data)) {
					$data[$j] = [];
				}
				array_push($data[$j], $test[$z]);
				$z++;
				$i++;
			} else {
				$j++;
				$i = 0;
				$start = $test[$z];
			}
		}

		$newData = [];
		$newData['random'] = [];
		$newData['half_sheet'] = [];
		$newData['full_sheet'] = [];
		$fullSheetIndex = 0;

		foreach ($data as $d) {
			if (count($d) < 3) {
				$newData['random'] = array_merge($newData['random'], $d);
			} else if (count($d) < 6) {
				$firstNum = $d[0];
				foreach ($d as $ad) {
					if ($ad % 3 == 1) {
						$firstNum = $ad;
						break;
					}
				}
				$box3 = [];
				array_push($box3, $firstNum);
				array_push($box3, $firstNum + 1);
				array_push($box3, $firstNum + 2);
				$diff = array_diff($d, $box3);
				$newData['random'] = array_merge($newData['random'], $diff);
				$inter = array_intersect($d, $box3);
				// return $d[2];
				if (count($inter) == 3) {
					array_push($newData['half_sheet'], $inter);
				} else {
					$newData['random'] = array_merge($newData['random'], $inter);
				}
			} else {
				$firstHalf = [];
				$lastHalf = [];

				$dcopy = $d;


				foreach ($dcopy as $z) {
					if ($z % 6 == 1) {
						break;
					}
					array_push($firstHalf, $z);
				}

				$dcopy = array_reverse($dcopy);

				foreach ($dcopy as $z) {
					if ($z % 6 == 0) {
						break;
					}
					array_push($lastHalf, $z);
				}

				// return $lastHalf;

				// array_push($data, $firstHalf);
				// array_push($data, $lastHalf);
				$dcopy = array_reverse($dcopy);
				$lastHalf = array_reverse($lastHalf);

				$onlySix = array_diff($dcopy, $firstHalf, $lastHalf);

				$chunks = array_chunk($onlySix, 6, false);

				for ($t = 0; $t < count($chunks); $t++) {
					if (!array_key_exists($fullSheetIndex, $newData['full_sheet'])) {
						$newData['full_sheet'][$fullSheetIndex] = [];
					}
					$newData['full_sheet'][$fullSheetIndex] = array_merge($newData['full_sheet'][$fullSheetIndex], $chunks[$t]);
					$fullSheetIndex++;
				}




				$xData = [];
				array_push($xData, $firstHalf);
				array_push($xData, $lastHalf);

				foreach ($xData as $x) {
					if (count($x) < 3) {
						$newData['random'] = array_merge($newData['random'], $x);
					} else if (count($x) < 6) {
						$firstNum = $x[0];
						foreach ($x as $xd) {
							if ($xd % 3 == 1) {
								$firstNum = $xd;
								break;
							}
						}
						$box3 = [];
						array_push($box3, $firstNum);
						array_push($box3, $firstNum + 1);
						array_push($box3, $firstNum + 2);
						$diff = array_diff($x, $box3);
						$newData['random'] = array_merge($newData['random'], $diff);
						$inter = array_intersect($x, $box3);
						// return $d[2];
						if (count($inter) == 3) {
							array_push($newData['half_sheet'], $inter);
						} else {
							$newData['random'] = array_merge($newData['random'], $inter);
						}
					}
				}
				// return $newData['full_sheet'];
			}
		}
		return $newData;
	}


	public function home_page_metacheck(Request $request)
	{
		$requestData = $request['dataToSend'];
		$date = $requestData['game_date'];
		$time = $requestData['game_time'];
		$ticketCount = $requestData['ticket_count'];

		$currentGame = CurrentGame::where('id', 1)->first();

		// return $request['dataToSend'];

		if ($currentGame->change_required == 2) {

			$gameSetting = GameSetting::where('id', 1)->first();

			$gameSettingData = [];
			$gameSettingData['call_pitch'] = $gameSetting->call_pitch;
			$gameSettingData['call_speed'] = $gameSetting->call_speed;
			$gameSettingData['game_name'] = $gameSetting->game_name;
			$gameSettingData['web_name'] = $gameSetting->web_name;
			$gameSettingData['game_terms_conditions'] = $gameSetting->game_terms_conditions;
			$gameSettingData['whatsapp_link'] = $gameSetting->whatsapp_link;
			$gameSettingData['recent_numbers_position'] = $gameSetting->recent_numbers_position;
			$gameSettingData['rhyming_speech'] = $gameSetting->rhyming_speech;

			$playedGame = PlayedGame::orderBy('created_at', 'DESC')
				->first();

			$now = new Carbon();

			$data['status'] = "Playing Game";
			$data['seconds_difference'] = 0;

			$data = [];
			$data['game_date_time'] = "";
			$data['booking_close'] = "";
			$data['game_over'] = "";
			$data['time'] = $now->toDateTimeString();
			$data['game_date'] = $playedGame->game_date;
			$data['game_time'] = $playedGame->game_time;
			$data['change_required'] = $currentGame->change_required;

			$allNumbers = [];
			for ($i = 1; $i <= 90; $i++) {
				array_push($allNumbers, $i);
			}

			$data['allnumbers'] = $allNumbers;



			$calledNumbers = explode(",", $playedGame->called_numbers);
			$avaliablePrizes = GamePrize::where('enabled', 1)
				->get();
			$prizesClaimed = GameClaim::leftjoin('game_tickets', 'game_tickets.id', 'game_claims.claimed_ticket_id')
				->where('game_claims.game_date', $playedGame->game_date)
				->where('game_claims.game_time', $playedGame->game_time)
				->select('game_claims.prize_tag', 'game_claims.claimed_ticket_id', 'game_claims.claimed_ticket_number', 'game_tickets.customer_name')
				->get();

			return view('game', ['gamedata' => $data, 'callednumber' => $calledNumbers, 'prizes' => $avaliablePrizes, 'claims' => $prizesClaimed, 'setting' => $gameSettingData]);
		}

		if ($currentGame['game_date'] != $date || $currentGame['game_time'] != $time) {
			$result = [];
			$result['status'] = "GAME";
			return response()->json($result);
		}

		$result = $this->get_game_data_to_send("HOME", $ticketCount);
		return response()->json($result);
	}


	public function game_page_metacheck(Request $request)
	{
		$requestData = $request['dataToSend'];
		$date = $requestData['game_date'];
		$time = $requestData['game_time'];
		$myTicketId = $requestData['my_ticket_id'];
		$winnerTicketId = $requestData['winner_ticket_id'];

		$currentGame = CurrentGame::where('id', 1)->first();

		if ($currentGame->change_required == 2) {
			$gameSetting = GameSetting::where('id', 1)->first();

			$gameSettingData = [];
			$gameSettingData['call_pitch'] = $gameSetting->call_pitch;
			$gameSettingData['call_speed'] = $gameSetting->call_speed;
			$gameSettingData['game_name'] = $gameSetting->game_name;
			$gameSettingData['web_name'] = $gameSetting->web_name;
			$gameSettingData['game_terms_conditions'] = $gameSetting->game_terms_conditions;
			$gameSettingData['whatsapp_link'] = $gameSetting->whatsapp_link;
			$gameSettingData['recent_numbers_position'] = $gameSetting->recent_numbers_position;
			$gameSettingData['rhyming_speech'] = $gameSetting->rhyming_speech;

			$playedGame = PlayedGame::orderBy('created_at', 'DESC')
				->first();

			$now = new Carbon();

			$data['status'] = "Playing Game";
			$data['seconds_difference'] = 0;

			$data = [];
			$data['game_date_time'] = "";
			$data['booking_close'] = "";
			$data['game_over'] = "";
			$data['time'] = $now->toDateTimeString();
			$data['game_date'] = $playedGame->game_date;
			$data['game_time'] = $playedGame->game_time;
			$data['change_required'] = $currentGame->change_required;

			$allNumbers = [];
			for ($i = 1; $i <= 90; $i++) {
				array_push($allNumbers, $i);
			}

			$data['allnumbers'] = $allNumbers;



			$calledNumbers = explode(",", $playedGame->called_numbers);
			$avaliablePrizes = GamePrize::where('enabled', 1)
				->get();
			$prizesClaimed = GameClaim::leftjoin('game_tickets', 'game_tickets.id', 'game_claims.claimed_ticket_id')
				->where('game_claims.game_date', $playedGame->game_date)
				->where('game_claims.game_time', $playedGame->game_time)
				->select('game_claims.prize_tag', 'game_claims.claimed_ticket_id', 'game_claims.claimed_ticket_number', 'game_tickets.customer_name')
				->get();

			return view('game', ['gamedata' => $data, 'callednumber' => $calledNumbers, 'prizes' => $avaliablePrizes, 'claims' => $prizesClaimed, 'setting' => $gameSettingData]);
		}

		if ($currentGame['game_date'] != $date || $currentGame['game_time'] != $time) {
			$result = [];
			$result['status'] = "HOME";
			return response()->json($result);
		}


		$result = $this->get_game_data_to_send("GAME", "");
		$result['my_ticket_id'] = $myTicketId;
		$result['winner_ticket_id'] = $winnerTicketId;
		return response()->json($result);
	}


	public function get_ticket_by_id($ticketId)
	{
		$ticketData = explode("-", $ticketId);
		$prizeTag = $ticketData[0];
		$id = $ticketData[1];
		$ticket = GameTicket::leftjoin('game_claims',function($join)use($prizeTag){
			$join->on('game_claims.claimed_ticket_id','game_tickets.id');
			$join->where('game_claims.prize_tag','=',$prizeTag);
		})
			->where('game_tickets.id', $id)
			->select('game_tickets.ticket_number','game_tickets.ticket','game_tickets.customer_name',
					'game_tickets.game_date','game_tickets.game_time','game_tickets.sheet_number',
					'game_claims.prize_name','game_claims.prize_tag','game_claims.claimed_ticket_id',
					'game_claims.claimed_ticket_number','game_claims.checked_numbers')
			->first();

		if ($prizeTag == "HALFSHEETBONUS" || $prizeTag == "FULLSHEETBONUS") {
			if ($ticket) {
				if ($ticket->sheet_number != -1) {
					$tickets = GameTicket::leftjoin('game_claims',function($join)use($prizeTag){
						$join->on('game_claims.claimed_ticket_id','game_tickets.id');
						$join->where('game_claims.prize_tag','=',$prizeTag);
					})->where('game_tickets.game_date', $ticket->game_date)
						->where('game_tickets.game_time', $ticket->game_time)
						->where('game_tickets.sheet_number', $ticket->sheet_number)
						->where('game_tickets.sheet_type', '!=', "RANDOM")
						->select('game_tickets.ticket_number','game_tickets.ticket','game_tickets.customer_name',
							'game_tickets.game_date','game_tickets.game_time','game_tickets.sheet_number',
							'game_claims.prize_name','game_claims.prize_tag','game_claims.claimed_ticket_id',
							'game_claims.claimed_ticket_number','game_claims.checked_numbers')
						->get();

						$tkts=[];
						$checkedNumbers="";
						foreach($tickets as $tk){
							if($tk->checked_numbers!=null&&$tk->checked_numbers!=""){
								$checkedNumbers=$tk->checked_numbers;
								array_push($tkts,$tk);
							}else{
								if($checkedNumbers!=""){
									$tk['checked_numbers']=$checkedNumbers;
									array_push($tkts,$tk);
								}
							}
						}

					return response()->json($tkts);
				}
			}
		}

		return response()->json($ticket);
	}


	public function get_ticket_by_id_app($ticketId)
	{
		$ticketData = explode("-", $ticketId);
		$prizeTag = $ticketData[0];
		$id = $ticketData[1];
		$ticket = GameTicket::where('id', $id)
			->first();

		if ($prizeTag == "HALFSHEETBONUS" || $prizeTag == "FULLSHEETBONUS") {
			if ($ticket) {
				if ($ticket->sheet_number != -1) {
					$tickets = GameTicket::where('game_date', $ticket->game_date)
						->where('game_time', $ticket->game_time)
						->where('sheet_number', $ticket->sheet_number)
						->where('sheet_type', '!=', "RANDOM")
						// ->exclude(['customer_phone', 'agent_id'])
						->get();

					return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$tickets]);
				}
			}
		}

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$ticket]);
	}

	public function get_active_agents()
	{

		$agents = Agent::where('agent_deleted', 0)
			->where('active', 1)
			->select('agent_name', 'agent_phone', 'agent_whatsapp')
			->get();
		return view('agent', ['agents' => $agents]);
	}


	public function get_gameplay()
	{
	}

	public function get_tickets_by_number(Request $request)
	{
		$requestData = $request['sendData'];
		$type = $requestData['type'];
		$currentGame = CurrentGame::where('id', 1)
			->first();
		// if ($currentGame->change_required != 2) {
		// 	$gameDate = $currentGame->game_date;
		// 	$gameTime = $currentGame->game_time;
		// } else {
			$gameDate = $requestData['game_date'];
			$gameTime = $requestData['game_time'];
		// }

		if ($type == "SHEET") {
			$allSheets = GameTicket::whereIn('ticket_number', $requestData['selected_tickets'])
				->where('game_date', $gameDate)
				->where('game_time', $gameTime)
				->where('sheet_type', '!=', "RANDOM")
				->pluck('sheet_number');

			if(sizeof($allSheets)>0){
				$AllTickets = GameTicket::whereIn('sheet_number', $allSheets)
				->where('game_date', $gameDate)
				->where('game_time', $gameTime)
				// ->makeHidden(['customer_phone', 'agent_id'])
				->get();
			}else{
				$AllTickets = GameTicket::whereIn('ticket_number', $requestData['selected_tickets'])
				->where('game_date', $gameDate)
				->where('game_time', $gameTime)
				// ->makeHidden(['customer_phone', 'agent_id'])
				->get();
			}
			return response()->json($AllTickets);
		}

		$AllTickets = GameTicket::whereIn('ticket_number', $requestData['selected_tickets'])
			->where('game_date', $gameDate)
			->where('game_time', $gameTime)
			// ->makeHidden(['customer_phone', 'agent_id'])
			->get();
		return response()->json($AllTickets);
	}

	public function get_next_tickets($startFrom,$limit)
	{
		$tickets = Ticket::where('ticket_number', '>=', $startFrom)
			->orderBy('ticket_number', "ASC")
			->limit($limit)
			->get();

		return response()->json($tickets);
	}

	public function get_game_status()
	{

		$currentGame = CurrentGame::where('id', 1)
			->first();

		$gameSetting = GameSetting::where('id', 1)->first();

		$gameSettingData = [];
		$gameSettingData['call_pitch'] = $gameSetting->call_pitch;
		$gameSettingData['call_speed'] = $gameSetting->call_speed;
		$gameSettingData['game_name'] = $gameSetting->game_name;
		$gameSettingData['web_name'] = $gameSetting->web_name;
		$gameSettingData['game_terms_conditions'] = $gameSetting->game_terms_conditions;
		$gameSettingData['whatsapp_link'] = $gameSetting->whatsapp_link;
		$gameSettingData['recent_numbers_position'] = $gameSetting->recent_numbers_position;
		$gameSettingData['rhyming_speech'] = $gameSetting->rhyming_speech;


		$gameDateTime = $currentGame->game_date_time;
		$bookingCloseTime = $currentGame->booking_close;
		$gameOverTime = $currentGame->game_over_time;
		$gameDate = $currentGame->game_date;
		$gameTime = $currentGame->game_time;
		// $gameDate="01-01-2002";
		// $gameTime="11:00:00";

		$now = new Carbon();
		$gameTimeC = new Carbon($gameDateTime);
		$bookingCloseTimeC = new Carbon($bookingCloseTime);
		$gameOverTimeC = new Carbon($gameOverTime);


		$data = [];
		$data['game_date_time'] = $gameTimeC->toDateTimeString();
		$data['booking_close'] = $bookingCloseTimeC->toDateTimeString();
		$data['game_over'] = $gameOverTimeC->toDateTimeString();
		$data['time'] = $now->toDateTimeString();
		$data['game_date'] = $gameDate;
		$data['game_time'] = $gameTime;
		$data['change_required'] = $currentGame->change_required;
		$data['game_status']=$currentGame->game_status;

		//When no next game
		if ($currentGame->change_required == 2) {


			$playedGame = PlayedGame::orderBy('created_at', 'DESC')
				->first();

			$now = new Carbon();



			$data = [];
			$data['game_date_time'] = "";
			$data['booking_close'] = "";
			$data['game_over'] = "";
			$data['time'] = $now->toDateTimeString();
			$data['game_date'] = $playedGame->game_date;
			$data['game_time'] = $playedGame->game_time;
			$data['change_required'] = $currentGame->change_required;
			$data['status'] = "Game Over";
			$data['game_status']=$currentGame->game_status;
			$data['seconds_difference'] = 0;

			$allNumbers = [];
			for ($i = 1; $i <= 90; $i++) {
				array_push($allNumbers, $i);
			}

			$data['allnumbers'] = $allNumbers;



			$calledNumbers = explode(",", $playedGame->called_numbers);
			$avaliablePrizes = GamePrize::where('enabled', 1)
				->get();
			$prizesClaimed = GameClaim::leftjoin('game_tickets', 'game_tickets.id', 'game_claims.claimed_ticket_id')
				->where('game_claims.game_date', $playedGame->game_date)
				->where('game_claims.game_time', $playedGame->game_time)
				->select('game_claims.prize_tag', 'game_claims.claimed_ticket_id', 'game_claims.claimed_ticket_number', 'game_tickets.customer_name')
				->get();

			$result = [];
			$result['status'] = "GAME";
			$result['data'] = ['gamedata' => $data, 'callednumber' => $calledNumbers, 'prizes' => $avaliablePrizes, 'claims' => $prizesClaimed, 'setting' => $gameSettingData];
			return $result;
		}

		// When next game is there
		if ($now > $bookingCloseTimeC && $now < $gameTimeC) {
			//Booking is closed
			$data['status'] = "Booking closed";
			$data['seconds_difference'] = $gameTimeC->timestamp - $now->timestamp;

			$allNumbers = [];
			for ($i = 1; $i <= 90; $i++) {
				array_push($allNumbers, $i);
			}

			$data['allnumbers'] = $allNumbers;

			$avaliablePrizes = GamePrize::where('enabled', 1)
				->get();

			$result = [];
			$result['status'] = "GAME";
			$result['data'] = ['gamedata' => $data, 'callednumber' => [], 'prizes' => $avaliablePrizes, 'claims' => [], 'setting' => $gameSettingData];
			return $result;
		} else if ($now > $gameTimeC && $now < $gameOverTimeC) {
			//Playing game
			$data['status'] = "Playing Game";
			$data['seconds_difference'] = $gameOverTimeC->timestamp - $now->timestamp;

			$allNumbers = [];
			for ($i = 1; $i <= 90; $i++) {
				array_push($allNumbers, $i);
			}

			$data['allnumbers'] = $allNumbers;

			$playedGame = PlayedGame::where('game_date', $gameDate)
				->where('game_time', $gameTime)
				->first();
			$calledNumbers = explode(",", $playedGame->called_numbers);
			$avaliablePrizes = GamePrize::where('enabled', 1)
				->get();
			$prizesClaimed = GameClaim::leftjoin('game_tickets', 'game_tickets.id', 'game_claims.claimed_ticket_id')
				->where('game_claims.game_date', $gameDate)
				->where('game_claims.game_time', $gameTime)
				->select('game_claims.prize_tag', 'game_claims.claimed_ticket_id', 'game_claims.claimed_ticket_number', 'game_tickets.customer_name')
				->get();

			$result = [];
			$result['status'] = "GAME";
			$result['data'] = ['gamedata' => $data, 'callednumber' => $calledNumbers, 'prizes' => $avaliablePrizes, 'claims' => $prizesClaimed, 'setting' => $gameSettingData];
			return $result;
		} else if ($now < $gameTimeC) {
			//Booking open
			$data['status'] = "Booking open";
			$data['seconds_difference'] = $gameTimeC->timestamp - $now->timestamp;

			$tickets = Ticket::leftjoin('game_tickets', function ($join) use ($gameDate, $gameTime) {
				$join->on('game_tickets.ticket_number', '=', 'tickets.ticket_number');
				$join->where('game_tickets.game_date', '=', $gameDate);
				$join->where('game_tickets.game_time', '=', $gameTime);
			})->select('tickets.*', 'game_tickets.customer_name')
				->orderBy('tickets.ticket_number', "ASC")
				->limit(20)
				->get();

			$result = [];
			$result['status'] = "HOME";
			$result['data'] = ['tickets' => $tickets, 'gamedata' => $data, 'setting' => $gameSettingData];

			return $result;
		} else {
			//Game already ended
			$data['status'] = "Game already ended";
			$data['seconds_difference'] = $gameTimeC->timestamp - $now->timestamp;

			$this->find_current_game_time();
			$tickets = Ticket::leftjoin('game_tickets', function ($join) use ($gameDate, $gameTime) {
				$join->on('game_tickets.ticket_number', '=', 'tickets.ticket_number');
				$join->where('game_tickets.game_date', '=', $gameDate);
				$join->where('game_tickets.game_time', '=', $gameTime);
			})->select('tickets.*', 'game_tickets.customer_name')
				->orderBy('tickets.ticket_number', "ASC")
				->limit(20)
				->get();

				$result=[];
				$result['statu']="HOME";
				$result['data']=['tickets' => $tickets, 'gamedata' => $data, 'setting' => $gameSettingData];

			return $result;
		}

		// return response()->json($data);
	}

	//Get Active Prizes
	public function getActivePrizes()
	{
		$activePrizes = GamePrize::where('prize_count', 1)
			->get();
		return $activePrizes;
	}

	//Get All Prizes
	public function getAllPrizes()
	{
		$allPrizes = GamePrize::all();

		return $allPrizes;
	}

	//Create Prizes
	public function createPrizes()
	{
		$prizeData = [];

		//Early Five Prize
		$earlyFive = [];
		$earlyFive['prize_name'] = "Early Five";
		$earlyFive['prize_tag'] = "EARLY5";
		$earlyFive['prize_count'] = 1;

		array_push($prizeData, $earlyFive);

		//Early Seven Prize
		$earlySeven = [];
		$earlySeven['prize_name'] = "Early Seven";
		$earlySeven['prize_tag'] = "EARLY7";
		$earlySeven['prize_count'] = 1;

		array_push($prizeData, $earlySeven);

		//Laddu Prize
		$laddu = [];
		$laddu['prize_name'] = "Laddu";
		$laddu['prize_tag'] = "LADDU";
		$laddu['prize_count'] = 1;

		array_push($prizeData, $laddu);

		//Top Line Prize
		$topLine = [];
		$topLine['prize_name'] = "Top Line";
		$topLine['prize_tag'] = "TOPLINE";
		$topLine['prize_count'] = 1;

		array_push($prizeData, $topLine);

		//Middle Line Prize
		$middleLine = [];
		$middleLine['prize_name'] = "Middle Line";
		$middleLine['prize_tag'] = "MIDDLELINE";
		$middleLine['prize_count'] = 1;

		array_push($prizeData, $middleLine);

		//Bottom Line Prize
		$bottomLine = [];
		$bottomLine['prize_name'] = "Bottom Line";
		$bottomLine['prize_tag'] = "BOTTOMLINE";
		$bottomLine['prize_count'] = 1;

		array_push($prizeData, $bottomLine);

		//Corners Prize
		$corners = [];
		$corners['prize_name'] = "Corners";
		$corners['prize_tag'] = "CORNERS";
		$corners['prize_count'] = 1;

		array_push($prizeData, $corners);

		//Corners with Star Prize
		$cornersWithStar = [];
		$cornersWithStar['prize_name'] = "Corners with Star";
		$cornersWithStar['prize_tag'] = "CORNERSWITHSTAR";
		$cornersWithStar['prize_count'] = 1;

		array_push($prizeData, $cornersWithStar);

		//Day Prize
		$day = [];
		$day['prize_name'] = "Day";
		$day['prize_tag'] = "DAY";
		$day['prize_count'] = 1;

		array_push($prizeData, $day);

		//Night Prize
		$night = [];
		$night['prize_name'] = "Night";
		$night['prize_tag'] = "NIGHT";
		$night['prize_count'] = 1;

		array_push($prizeData, $night);

		//Breakfast Prize
		$breakfast = [];
		$breakfast['prize_name'] = "Breakfast";
		$breakfast['prize_tag'] = "BREAKFAST";
		$breakfast['prize_count'] = 1;

		array_push($prizeData, $breakfast);

		//Lunch Prize
		$lunch = [];
		$lunch['prize_name'] = "Lunch";
		$lunch['prize_tag'] = "LUNCH";
		$lunch['prize_count'] = 1;

		array_push($prizeData, $lunch);

		//Dinner Prize
		$dinner = [];
		$dinner['prize_name'] = "Dinner";
		$dinner['prize_tag'] = "DINNER";
		$dinner['prize_count'] = 1;

		array_push($prizeData, $dinner);

		//L Prize
		$l = [];
		$l['prize_name'] = "L";
		$l['prize_tag'] = "L";
		$l['prize_count'] = 1;

		array_push($prizeData, $l);

		//H Prize
		$h = [];
		$h['prize_name'] = "H";
		$h['prize_tag'] = "H";
		$h['prize_count'] = 1;

		array_push($prizeData, $h);

		//T Prize
		$t = [];
		$t['prize_name'] = "T";
		$t['prize_tag'] = "T";
		$t['prize_count'] = 1;

		array_push($prizeData, $t);

		//Full House Prize
		$fullHouse = [];
		$fullHouse['prize_name'] = "Full House";
		$fullHouse['prize_tag'] = "FULLHOUSE";
		$fullHouse['prize_count'] = 1;

		array_push($prizeData, $fullHouse);


		//Full House 2 Prize
		$fullHouse2 = [];
		$fullHouse2['prize_name'] = "Second House";
		$fullHouse2['prize_tag'] = "FULLHOUSE2";
		$fullHouse2['prize_count'] = 1;

		array_push($prizeData, $fullHouse2);


		//Full House 3 Prize
		$fullHouse3 = [];
		$fullHouse3['prize_name'] = "Third House";
		$fullHouse3['prize_tag'] = "FULLHOUSE3";
		$fullHouse3['prize_count'] = 1;

		array_push($prizeData, $fullHouse3);


		//Half Sheet Bonus Prize
		$halfSheetBonus = [];
		$halfSheetBonus['prize_name'] = "Half Sheet Bonus";
		$halfSheetBonus['prize_tag'] = "HALFSHEETBONUS";
		$halfSheetBonus['prize_count'] = 1;

		array_push($prizeData, $halfSheetBonus);

		//Full Sheet Bonus Prize
		$fullSheetBonus = [];
		$fullSheetBonus['prize_name'] = "Full Sheet Bonus";
		$fullSheetBonus['prize_tag'] = "FULLSHEETBONUS";
		$fullSheetBonus['prize_count'] = 1;

		array_push($prizeData, $fullSheetBonus);

		GamePrize::insert($prizeData);

		return "Saved";
	}


	// Find Current Game Time
	public function find_current_game_time()
	{
		// Log::info('Find current game time ' . now());

		$currentGame = CurrentGame::where('id', 1)
			->first();

		$now = new Carbon();
		$nowT = new Carbon();
		$nowT->setSeconds(0);
		$regularDate = $now->format("d-m-Y");
		$regularTime = $now->toTimeString();

		$changeRequired = $currentGame->change_required;
		$gameOverTimeCarbon = new Carbon($currentGame->game_over_time);

		$setting = GameSetting::where('id', 1)
			->first();


		if (($changeRequired == 1 || $gameOverTimeCarbon <= $now) && $changeRequired != 2) {
			$callInterval = $setting->call_interval;
			$bookingCloseMinute = $setting->booking_close_minute;

			$date = $now->format("d-m-Y");
			$today = $now->format("d-m-Y");
			$timeNow = $now->toTimeString();
			$timeC = $now->addSeconds(- ((90 * $callInterval)));
			$time = $timeC->toTimeString();

			$i = 0;

			$newNow = new Carbon();
			$newNow->setHour(0);
			$newNow->setMinute(0);
			$newNow->setSecond(0);
			$timeCheck = $newNow->addSeconds((90 * $callInterval) )->toTimeString();


			if ($timeNow > "00:00:00" && $timeNow < $timeCheck) {
				$date = $now->format("d-m-Y");
			}

			do {
				$i++;
				$removedGames = Game::where('game_date', $date)
					->where('type', "REMOVE")
					->where('game_time', '>', $time)
					->pluck('game_time');
				$availableGames = Game::whereIn('game_date', ["01-01-2000", $date])
					->whereNotIn('game_time', $removedGames)
					->where('game_time', '>', $time)
					->orderBy('game_time', 'asc')
					->pluck('game_time');


				$date = $now->addDay(1)->format("d-m-Y");
				$time = "00:00:00";
				if ($i > 30) {
					$currentGame->change_required = 2;
					$currentGame->save();
					$gameErrorData = [];
					$gameErrorData['message'] = "No Games for next 30 days";
					return response()->json(['status' => 'SUCCESS', 'code' => 'SC_10', 'data' => $gameErrorData]);
				}
			} while (count($availableGames) == 0);
			$date = $now->addDay(-1)->format("d-m-Y");

			$totalTickets = GameTicket::where('game_date', $date)
				->where('game_time', $availableGames[0])
				->count();

			$gameData = [];
			$gameData['game_date'] = $date;
			$gameData['game_time'] = $availableGames[0];
			$gameData['time'] = $timeNow;
			$gameData['date'] = $today;
			$gameData['ticket_count'] = $totalTickets;

			$currentGame = CurrentGame::where('id', 1)
				->first();
			if ($currentGame) {
				$lastGameDate = $currentGame->game_date;
				$lastGameTime = $currentGame->game_time;
				$lastGameDateTime = $currentGame->game_date_time;

				$currentGame->game_date = $date;
				$currentGame->game_time = $availableGames[0];
				$gameDateTime = new Carbon($date . " " . $availableGames[0]);
				$currentGame->game_date_time = $gameDateTime->toDateTimeString();
				$gameOverTime = $gameDateTime->addSeconds((90 * $callInterval));
				$currentGame->game_over_time = $gameOverTime->toDateTimeString();
				$gameDateTime->addSeconds(- ((90 * $callInterval)));
				$bookingCloseTime = $gameDateTime->addMinutes(-$bookingCloseMinute);
				$currentGame->booking_close = $bookingCloseTime->toDateTimeString();
				$currentGame->last_game_date = $lastGameDate;
				$currentGame->last_game_time = $lastGameTime;
				$currentGame->last_game_datetime = $lastGameDateTime;
				$currentGame->game_status="ACTIVE";
				$currentGame->change_required = 0;

				$currentGame->save();

				$this->check_tickets_and_reset($date,$availableGames[0]);
			}

			$bookingStatus = "OPEN";
			if ($setting->booking_open == 0 || $currentGame->booking_close < $timeNow) {
				$bookingStatus = "CLOSE";
			}

			$lastGameDate = $currentGame->last_game_date;
			$lastGameTime = $currentGame->last_game_time;
			$gameData['last_game_date'] = $lastGameDate;
			$gameData['last_game_time'] = $lastGameTime;
			$gameData['booking_status'] = $bookingStatus;
			$gameData['game_over_time'] = $currentGame->game_over_time;
			$gameData['change_required']=$currentGame->change_required;

			$data_string = json_encode($gameData);

			$ch = curl_init($_SERVER['SERVER_NAME'] . ':8080/gametimechange');
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen($data_string)
				)
			);

			echo curl_exec($ch) . "\n";
			curl_close($ch);

			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $gameData]);
		} else {
			$gameDate = $currentGame->game_date;
			$gameTime = $currentGame->game_time;
			$lastGameDate = $currentGame->last_game_date;
			$lastGameTime = $currentGame->last_game_time;
			$gameOverTime = $currentGame->game_over_time;
			$totalTickets = GameTicket::where('game_date', $gameDate)
				->where('game_time', $gameTime)
				->count();

			$bookingStatus = "OPEN";
			if ($setting->booking_open == 0 || $currentGame->booking_close < $regularTime) {
				$bookingStatus = "CLOSE";
			}

			$gameData = [];
			$gameData['game_date'] = $gameDate;
			$gameData['game_time'] = $gameTime;
			$gameData['time'] = $regularTime;
			$gameData['date'] = $regularDate;
			$gameData['last_game_date'] = $lastGameDate;
			$gameData['last_game_time'] = $lastGameTime;
			$gameData['ticket_count'] = $totalTickets;
			$gameData['booking_status'] = $bookingStatus;
			$gameData['game_over_time'] = $gameOverTime;
			$gameData['change_required']=$currentGame->change_required;

			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $gameData]);
		}
	}


	public function check_tickets_and_reset($date,$time){
		$gameTickets=GameTicket::where('game_date',$date)
								->where('game_time',$time)
								->get();
		$soldTickets=[];
		foreach($gameTickets as $gameTicket){
			array_push($soldTickets,$gameTicket->ticket_number);
			Ticket::where('ticket_number',$gameTicket->ticket_number)
					->update(['sheet_type'=>$gameTicket->customer_name]);
		}

		Ticket::whereNotIn('ticket_number',$soldTickets)
				->update(['sheet_type'=>""]);
	}


	public function set_allticket_sold($gameDate,$gameTime){
		$soldTickets=GameTicket::where('game_date',$gameDate)
								->where('game_time',$gameTime)
								->pluck('ticket_number');
		$allTicketArray=Ticket::whereNotIn('ticket_number',$soldTickets)
							->pluck('ticket_number')->toArray();

		sort($allTicketArray);

		if(count($allTicketArray)!=0){

			$data_string = json_encode(array('agent_id' => -1, 'customer_name' => 'Unsold','customer_phone'=>'9999999999','ticket_numbers'=>$allTicketArray));

				$ch = curl_init($_SERVER['SERVER_NAME'] . '/api/sale_ticket');
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt(
					$ch,
					CURLOPT_HTTPHEADER,
					array(
						'Content-Type: application/json',
						'Content-Length: ' . strlen($data_string)
					)
				);

				$response= curl_exec($ch) . "\n";
				curl_close($ch);
				
				Log::debug("Sale ticket ".$response);
		}
	}


	public function notification($tokenList, $title, $message, $auth)
	{
		$fcmUrl = 'https://fcm.googleapis.com/fcm/send';
		// $token=$token;

		$notification = [
			'title' => $title,
			'body' => $message,
			'sound' => true,
		];

		$extraNotificationData = ["message" => $notification];

		$fcmNotification = [
			'registration_ids' => $tokenList, //multple token array
			// 'to'        => $token, //single token
			'notification' => $notification,
			'data' => $extraNotificationData
		];

		$headers = [
			'Authorization: key=' . $auth,
			'Content-Type: application/json'
		];


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $fcmUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
		$result = curl_exec($ch);
		curl_close($ch);

		return true;
	}


	public function generateCallNumbers()
	{
		$callNum = [];

		for ($i = 1; $i <= 90; $i++) {
			array_push($callNum, $i);
		}

		shuffle($callNum);
		shuffle($callNum);

		return $callNum;
	}





	public function get_game_data_to_send($type, $anovar)
	{

		$currentGame = CurrentGame::where('id', 1)
			->first();

		$gameSetting = GameSetting::where('id', 1)->first();

		$gameSettingData = [];
		$gameSettingData['call_pitch'] = $gameSetting->call_pitch;
		$gameSettingData['call_speed'] = $gameSetting->call_speed;
		$gameSettingData['game_name'] = $gameSetting->game_name;
		$gameSettingData['web_name'] = $gameSetting->web_name;
		$gameSettingData['game_terms_conditions'] = $gameSetting->game_terms_conditions;
		$gameSettingData['whatsapp_link'] = $gameSetting->whatsapp_link;
		$gameSettingData['recent_numbers_position'] = $gameSetting->recent_numbers_position;
		$gameSettingData['rhyming_speech'] = $gameSetting->rhyming_speech;

		$gameDateTime = $currentGame->game_date_time;
		$bookingCloseTime = $currentGame->booking_close;
		$gameOverTime = $currentGame->game_over_time;
		$gameDate = $currentGame->game_date;
		$gameTime = $currentGame->game_time;

		// $gameDate="01-01-2002";
		// $gameTime="11:00:00";

		$now = new Carbon();
		$gameTimeC = new Carbon($gameDateTime);
		$bookingCloseTimeC = new Carbon($bookingCloseTime);
		$gameOverTimeC = new Carbon($gameOverTime);


		$data = [];
		$data['game_date_time'] = $gameTimeC->toDateTimeString();
		$data['booking_close'] = $bookingCloseTimeC->toDateTimeString();
		$data['game_over'] = $gameOverTimeC->toDateTimeString();
		$data['time'] = $now->toDateTimeString();
		$data['game_date'] = $gameDate;
		$data['game_time'] = $gameTime;

		if ($now > $bookingCloseTimeC && $now < $gameTimeC) {
			if ($type == "HOME") {
				$result = [];
				$result['status'] = "GAME";
				return $result;
			}
			//Booking is closed
			$data['status'] = "Booking closed";
			$data['seconds_difference'] = $gameTimeC->timestamp - $now->timestamp;

			$allNumbers = [];
			for ($i = 1; $i <= 90; $i++) {
				array_push($allNumbers, $i);
			}

			$data['allnumbers'] = $allNumbers;

			$avaliablePrizes = GamePrize::where('enabled', 1)
				->get();

			$result = [];
			$result['status'] = "GAME";
			$result['data'] = ['gamedata' => $data, 'callednumber' => [], 'prizes' => $avaliablePrizes, 'claims' => [], 'setting' => $gameSettingData];
			return $result;
		} else if ($now > $gameTimeC && $now < $gameOverTimeC) {
			if ($type == "HOME") {
				$result = [];
				$result['status'] = "GAME";
				return $result;
			}
			//Playing game
			$data['status'] = "Playing Game";
			$data['seconds_difference'] = $gameOverTimeC->timestamp - $now->timestamp;

			$allNumbers = [];
			for ($i = 1; $i <= 90; $i++) {
				array_push($allNumbers, $i);
			}

			$data['allnumbers'] = $allNumbers;

			$playedGame = PlayedGame::where('game_date', $gameDate)
				->where('game_time', $gameTime)
				->first();
			$calledNumbers = explode(",", $playedGame->called_numbers);
			$avaliablePrizes = GamePrize::where('enabled', 1)
				->get();
			$prizesClaimed = GameClaim::leftjoin('game_tickets', 'game_tickets.id', 'game_claims.claimed_ticket_id')
				->where('game_claims.game_date', $gameDate)
				->where('game_claims.game_time', $gameTime)
				->select('game_claims.prize_tag', 'game_claims.prize_name', 'game_claims.claimed_ticket_id', 'game_claims.claimed_ticket_number', 'game_tickets.customer_name')
				->get();

			$result = [];
			$result['status'] = "GAME";
			$result['data'] = ['gamedata' => $data, 'callednumber' => $calledNumbers, 'prizes' => $avaliablePrizes, 'claims' => $prizesClaimed, 'setting' => $gameSettingData];
			return $result;
		} else if ($now < $gameTimeC) {
			if ($type == "GAME") {
				$result = [];
				$result['status'] = "HOME";
				return $result;
			}
			//Booking open
			$data['status'] = "Booking open";
			$data['seconds_difference'] = $gameTimeC->timestamp - $now->timestamp;

			$tickets = Ticket::leftjoin('game_tickets', function ($join) use ($gameDate, $gameTime) {
				$join->on('game_tickets.ticket_number', '=', 'tickets.ticket_number');
				$join->where('game_tickets.game_date', '=', $gameDate);
				$join->where('game_tickets.game_time', '=', $gameTime);
			})->select('tickets.*', 'game_tickets.customer_name')
				->orderBy('tickets.ticket_number', "ASC")
				->limit($anovar)
				->get();

			$result = [];
			$result['status'] = "HOME";
			$result['data'] = ['tickets' => $tickets, 'gamedata' => $data, 'setting' => $gameSettingData];
			return $result;
		} else {
			if ($type == "GAME") {
				$result = [];
				$result['status'] = "HOME";
				return $result;
			}
			//Game already ended
			$data['status'] = "Game already ended";
			$data['seconds_difference'] = $gameTimeC->timestamp - $now->timestamp;

			$this->find_current_game_time();
			$tickets = Ticket::leftjoin('game_tickets', function ($join) use ($gameDate, $gameTime) {
				$join->on('game_tickets.ticket_number', '=', 'tickets.ticket_number');
				$join->where('game_tickets.game_date', '=', $gameDate);
				$join->where('game_tickets.game_time', '=', $gameTime);
			})->select('tickets.*', 'game_tickets.customer_name')
				->orderBy('tickets.ticket_number', "ASC")
				->limit($anovar)
				->get();
			$result = [];
			$result['status'] = "HOME";
			$result['data'] = ['tickets' => $tickets, 'gamedata' => $data, 'setting' => $gameSettingData];
			return $result;
		}

		// return response()->json($data);
	}



	public function get_game_page()
	{
		$setting=GameSetting::where('id',1)->first();

		if($setting->website_status==0){
			return abort(404,'Page not found');
		}

		$currentGame = CurrentGame::where('id', 1)
			->first();

		$gameDateTime = $currentGame->game_date_time;
		$bookingCloseTime = $currentGame->booking_close;
		$gameOverTime = $currentGame->game_over_time;

		$now = new Carbon();
		$gameTimeC = new Carbon($gameDateTime);
		$bookingCloseTimeC = new Carbon($bookingCloseTime);
		$gameOverTimeC = new Carbon($gameOverTime);

		//When no next game
		if ($currentGame->change_required == 2) {
			return view('game');
		}

		// When next game is there
		if ($now > $bookingCloseTimeC && $now < $gameTimeC) {
			//Booking is closed
			return view('game');
		} else if ($now > $gameTimeC && $now < $gameOverTimeC) {

			return view('game');
		} else if ($now < $gameTimeC) {
			//Booking open
			return view('home');
		} else {
			//Game already ended
			return view('home');
		}
	}







	public function find_current_game_time_server_call()
	{
		Log::info('Find current game time ' . now());

		$currentGame = CurrentGame::where('id', 1)
			->first();

		$now = new Carbon();
		$nowT = new Carbon();
		$nowT->setSeconds(0);
		$nowStr = $nowT->toDateTimeString();
		$regularDate = $now->format("d-m-Y");
		$regularTime = $now->toTimeString();

		$changeRequired = $currentGame->change_required;
		$gameOverTimeCarbon = new Carbon($currentGame->game_over_time);

		$gameDateTimeCarbon = new Carbon($currentGame->game_date_time);
		$gameTimeMinus2 = $gameDateTimeCarbon->addMinute(-2)->toDateTimeString();
		$gameDateTimeStr = $gameDateTimeCarbon->addMinute(2)->toDateTimeString();


		$setting = GameSetting::where('id', 1)
			->first();
		$playedGame = PlayedGame::where('game_date', $currentGame->game_date)
			->where('game_time', $currentGame->game_time)
			->first();
		if ($nowStr >= $gameTimeMinus2 && $nowStr < $gameDateTimeStr) {

			if (!$playedGame) {
				$callNum = $this->generateCallNumbers();
				$callNumStr = "";
				for ($i = 0; $i < 90; $i++) {
					$callNumStr = $callNumStr . $callNum[$i] . ",";
				}

				$playedGameData = [];
				$playedGameData['game_date'] = $currentGame->game_date;
				$playedGameData['game_time'] = $currentGame->game_time;
				$playedGameData['call_numbers'] = $callNumStr;
				$playedGameData['called_numbers'] = "";

				PlayedGame::create($playedGameData);
			}
		}

		if($nowStr==$gameTimeMinus2){
			if($setting->ticket_play_status=="ALLTICKET"){
				$this->set_allticket_sold($currentGame->game_date,$currentGame->game_time);
			}
		}

		if ($nowStr == $gameDateTimeStr) {
			if ($playedGame) {
				if ($playedGame->called_numbers == "") {
					$ch = curl_init($_SERVER['HTTP_HOST'] . '/api/createTickets');
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					echo curl_exec($ch) . "\n";
					curl_close($ch);


					if ($setting->game_start_notification == 1) {
						$admins = Admin::pluck('token');

						$tokens = [];
						foreach ($admins as $admin) {
							array_push($tokens, $admin);
						}

						$auth = $setting->notification_auth;
						$title = "Game started";
						$message = "The game has started and is playing";

						$this->notification($tokens, $title, $message, $auth);
					}
				}
			} else {
				$ch = curl_init($_SERVER['HTTP_HOST'] . '/api/createTickets');
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				echo curl_exec($ch) . "\n";
				curl_close($ch);


				if ($setting->game_start_notification == 1) {
					$admins = Admin::pluck('token');

					$tokens = [];
					foreach ($admins as $admin) {
						array_push($tokens, $admin);
					}

					$auth = $setting->notification_auth;
					$title = "Game started";
					$message = "The game has started and is playing";

					$this->notification($tokens, $title, $message, $auth);
				}
			}
		}

		if (($changeRequired == 1 || $gameOverTimeCarbon <= $now) && $changeRequired != 2) {
			$callInterval = $setting->call_interval;
			$bookingCloseMinute = $setting->booking_close_minute;

			$date = $now->format("d-m-Y");
			$today = $now->format("d-m-Y");
			$timeNow = $now->toTimeString();
			$timeC = $now->addSeconds(- ((90 * $callInterval)));
			$time = $timeC->toTimeString();

			$i = 0;

			$newNow = new Carbon();
			$newNow->setHour(0);
			$newNow->setMinute(0);
			$newNow->setSecond(0);
			$timeCheck = $newNow->addSeconds((90 * $callInterval) )->toTimeString();


			if ($timeNow > "00:00:00" && $timeNow < $timeCheck) {
				$date = $now->format("d-m-Y");
			}

			do {
				$i++;
				$removedGames = Game::where('game_date', $date)
					->where('type', "REMOVE")
					->where('game_time', '>', $time)
					->pluck('game_time');
				$availableGames = Game::whereIn('game_date', ["01-01-2000", $date])
					->whereNotIn('game_time', $removedGames)
					->where('game_time', '>', $time)
					->orderBy('game_time', 'asc')
					->pluck('game_time');


				$date = $now->addDay(1)->format("d-m-Y");
				$time = "00:00:00";
				if ($i > 30) {
					$currentGame->change_required = 2;
					$currentGame->save();
					$gameErrorData = [];
					$gameErrorData['message'] = "No Games for next 30 days";
					return response()->json(['status' => 'SUCCESS', 'code' => 'SC_10', 'data' => $gameErrorData]);
				}
			} while (count($availableGames) == 0);
			$date = $now->addDay(-1)->format("d-m-Y");

			$totalTickets = GameTicket::where('game_date', $date)
				->where('game_time', $availableGames[0])
				->count();

			$gameData = [];
			$gameData['game_date'] = $date;
			$gameData['game_time'] = $availableGames[0];
			$gameData['time'] = $timeNow;
			$gameData['date'] = $today;
			$gameData['ticket_count'] = $totalTickets;

			$currentGame = CurrentGame::where('id', 1)
				->first();
			if ($currentGame) {
				$lastGameDate = $currentGame->game_date;
				$lastGameTime = $currentGame->game_time;
				$lastGameDateTime = $currentGame->game_date_time;

				$currentGame->game_date = $date;
				$currentGame->game_time = $availableGames[0];
				$gameDateTime = new Carbon($date . " " . $availableGames[0]);
				$currentGame->game_date_time = $gameDateTime->toDateTimeString();
				$gameOverTime = $gameDateTime->addSeconds((90 * $callInterval));
				$currentGame->game_over_time = $gameOverTime->toDateTimeString();
				$gameDateTime->addSeconds(- ((90 * $callInterval)));
				$bookingCloseTime = $gameDateTime->addMinutes(-$bookingCloseMinute);
				$currentGame->booking_close = $bookingCloseTime->toDateTimeString();
				$currentGame->last_game_date = $lastGameDate;
				$currentGame->last_game_time = $lastGameTime;
				$currentGame->last_game_datetime = $lastGameDateTime;
				$currentGame->game_status="ACTIVE";
				$currentGame->change_required = 0;

				$currentGame->save();

				$this->check_tickets_and_reset($date,$availableGames[0]);
			}

			$bookingStatus = "OPEN";
			if ($setting->booking_open == 0 || $currentGame->booking_close < $timeNow) {
				$bookingStatus = "CLOSE";
			}

			$lastGameDate = $currentGame->last_game_date;
			$lastGameTime = $currentGame->last_game_time;
			$gameData['last_game_date'] = $lastGameDate;
			$gameData['last_game_time'] = $lastGameTime;
			$gameData['booking_status'] = $bookingStatus;
			$gameData['game_over_time'] = $currentGame->game_over_time;
			$gameData['change_required']=$currentGame->change_required;

			$data_string = json_encode($gameData);

			$ch = curl_init($_SERVER['SERVER_NAME'] . ':8080/gametimechange');
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen($data_string)
				)
			);

			echo curl_exec($ch) . "\n";
			curl_close($ch);

			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $gameData]);
		} else {
			$gameDate = $currentGame->game_date;
			$gameTime = $currentGame->game_time;
			$lastGameDate = $currentGame->last_game_date;
			$lastGameTime = $currentGame->last_game_time;
			$gameOverTime = $currentGame->game_over_time;
			$totalTickets = GameTicket::where('game_date', $gameDate)
				->where('game_time', $gameTime)
				->count();

			$bookingStatus = "OPEN";
			if ($setting->booking_open == 0 || $currentGame->booking_close < $regularTime) {
				$bookingStatus = "CLOSE";
			}

			$gameData = [];
			$gameData['game_date'] = $gameDate;
			$gameData['game_time'] = $gameTime;
			$gameData['time'] = $regularTime;
			$gameData['date'] = $regularDate;
			$gameData['last_game_date'] = $lastGameDate;
			$gameData['last_game_time'] = $lastGameTime;
			$gameData['ticket_count'] = $totalTickets;
			$gameData['booking_status'] = $bookingStatus;
			$gameData['game_over_time'] = $gameOverTime;
			$gameData['change_required']=$currentGame->change_required;

			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $gameData]);
		}
	}

}

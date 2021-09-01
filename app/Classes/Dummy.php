<?php

namespace App\Http\Controllers;

use App\Models\GameClaim;
use App\Models\GamePrize;
use App\Models\GameTicket;
use App\Models\PlayedGame;
use App\Models\CurrentGame;
use App\Models\GameSetting;
use App\Models\Admin;
use App\Models\Rhyme;
use App\Models\Ticket;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;


class TicketController extends Controller
{
	//

	public function buyTickets()
	{
		$lastSheetNum = 1;
		$date = "01-06-2021";
		$time = "10:00:00";


		// $lastSheetNumber=GameTicket::where("game_date",$date)
		// 							->where("game_time",$time)
		// 							->orderBy("sheet_number","DESC")
		// 							->limit(1)
		// 							->pluck('sheet_number');

		// if(count($lastSheetNumber)>0){
		// 	$lastSheetNum=$lastSheetNumber[0]+1;
		// }

		// $allTicketData=[];

		// foreach($ticketData as $singleTicket){
		// 	$gameTicketData=[];
		// 	$gameTicketData['game_date']=$date;
		// 	$gameTicketData['game_time']=$time;
		// 	$gameTicketData['agent_id']=1;
		// 	$gameTicketData['ticket_number']=$singleTicket->ticket_number;
		// 	$gameTicketData['ticket']=$singleTicket->ticket;
		// 	$gameTicketData['sheet_number']=$lastSheetNum;
		// 	$gameTicketData['customer_name']="Aryan";
		// 	$gameTicketData['customer_phone']="9358174783";

		// 	array_push($allTicketData,$gameTicketData);
		// }




		// return GameTicket::insert($allTicketData);

		// try{
		// 	DB::transaction();
		// 	$lastSheetNumber=GameTicket::
		// 	DB::commit();
		// }catch(Exception $e){
		// 	DB::rollBack();
		// }

	}

	public function createTickets()
	{	

		// $tickets=Ticket::where("id",">",24)->orderBy('id')->limit(6)->get();
		$gameSetting=GameSetting::where('id',1)->first();

		if($gameSetting->game_freeze==0){
			$data = $this->processTickets();
		}else{

				$resultData=[];
				$resultData['status']="GAMEOVER";
				$resultData['call_number']="";
				$resultData['prize_claims']=[];
				$resultData['duation']=0;
				$resultData['call_speech']="";

				$currentGame=CurrentGame::where('id',1)->first();
				$currentGame->game_status="GAMEOVER";
				$currentGame->save();

			$data=$resultData;
		}
		// $redis=Redis::connection();
		// $redis->publish('message',$data);

		if ($data != null&&$data!=false) {
			$duration=6000;
			if($gameSetting){
				if($gameSetting->call_interval>1){
					$duration=$gameSetting->call_interval*1000;
				}
			}
			$dataSend = array("status"=>$data['status'],"call_number" => $data['call_number'], "prize_claims" => $data['prize_claims'],"duration"=>$duration,"call_speech"=>$data['call_speech']);
			$data_string = json_encode($dataSend);

			$ch = curl_init($_SERVER['SERVER_NAME'] . ':8080/callNumber');
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
		}

		if($data['status']=="GAMEOVER"){

			if($gameSetting->game_end_notification==1){
				$admins=Admin::pluck('token');

				$tokens=[];
				foreach($admins as $admin){
					array_push($tokens,$admin);
				}

				$auth=$gameSetting->notification_auth;
				$title="Game completed";
				$message="The game has ended successfully";

				$this->notification($tokens,$title,$message,$auth);

			}
		}

		return null;
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

	public function processTickets()
	{
		$numArray = [];

		$currentGame = CurrentGame::where('id', 1)
			->first();
		$now = new Carbon();
		$dtt = new Carbon($currentGame->game_date_time);

		if ($dtt > $now) {
			return false;
		}

		$date = $currentGame->game_date;
		$time = $currentGame->game_time;
		// $date="08-06-2021";
		// $time="23:00:00";
		$calledNumSaved = "";
		$tick = [];

		$playedGame = PlayedGame::where('game_date', $date)
			->where('game_time', $time)
			->first();
		if (!$playedGame) {
			$callNum = $this->generateCallNumbers();
			$callNumStr = "";
			for ($i = 0; $i < 90; $i++) {
				$callNumStr = $callNumStr . $callNum[$i] . ",";
			}

			$playedGameData = [];
			$playedGameData['game_date'] = $date;
			$playedGameData['game_time'] = $time;
			$playedGameData['call_numbers'] = $callNumStr;
			$playedGameData['called_numbers'] = "";

			PlayedGame::create($playedGameData);
		}

		$playedGame = PlayedGame::where('game_date', $date)
			->where('game_time', $time)
			->first();

		$callNumArray = explode(",", $playedGame->call_numbers);
		$calledNumArray = explode(",", $playedGame->called_numbers);
		$leftOverArray = array_diff($callNumArray, $calledNumArray);

		if (count($leftOverArray) > 0) {
			$cn = $leftOverArray[array_rand($leftOverArray, 1)];
			array_push($calledNumArray, $cn);
			$calledNumSaved = $playedGame->called_numbers . $cn . ",";
			$numArray = $calledNumArray;
			$avaliablePrizes = [];
			$activePrizes = $this->getActivePrizes();
			

			$calimedPrizes = GameClaim::where('game_date', $date)
				->where('game_time', $time)
				->get();
			$claimedPrizeData = [];
			foreach ($calimedPrizes as $claim) {
				if (array_key_exists($claim->prize_tag, $claimedPrizeData)) {
					$ticketNumArray = $claimedPrizeData[$claim->prize_tag];
				} else {
					$ticketNumArray = [];
				}
				array_push($ticketNumArray, $claim->claimed_ticket_number);
				$claimedPrizeData[$claim->prize_tag] = $ticketNumArray;
			}

			$gameOverStatus=false;
			foreach ($activePrizes as $prize) {
				$avaliablePrizes[$prize->prize_tag] = $prize->prize_count;
				if(!array_key_exists($prize->prize_tag,$claimedPrizeData)){
					$gameOverStatus=false;
				}else{
					if(sizeof($claimedPrizeData[$prize->prize_tag])==0){
						$gameOverStatus=false;
					}else{
						$gameOverStatus=true;
					}
				}
			}

			if($gameOverStatus){
				$resultData=[];
				$resultData['status']="GAMEOVER";
				$resultData['call_number']="";
				$resultData['prize_claims']=[];
				$resultData['duation']=0;
				$resultData['call_speech']="";

				$currentGame->game_status="GAMEOVER";
				$currentGame->save();

				return $resultData;
			}

			$sheetWiseTickets = GameTicket::where('game_date', $date)
				->where('game_time', $time)
				->get()
				->groupBy('sheet_number');

			foreach ($sheetWiseTickets as $sheets) {
				$sheetTicketId = $sheets[0]->id;
				$sheetTicketNumber = $sheets[0]->ticket_number;
				$tickets = $sheets;
				$sheetCombo = [];
				$sheetType="RANDOM";
				foreach ($tickets as $ticket) {
					$ticketCombo = explode(",", $ticket->ticket);
					$topLine = array_slice($ticketCombo, 0, 9);
					$middleLine = array_slice($ticketCombo, 9, 9);
					$bottomLine = array_slice($ticketCombo, 18, 9);
					$topLineNum = [];
					$middleLineNum = [];
					$bottomLineNum = [];
					$corners = [];
					$star = [];
					$pyramid = [];
					$day = [];
					$night = [];
					$breakfast = [];
					$lunch = [];
					$dinner = [];
					$lprize = [];
					$hprize = [];
					$tprize = [];
					$fullHouse = [];
					$middleNumber = "";

					$i = 0;
					$j = 0;
					foreach ($topLine as $item) {
						if ($item != " ") {
							if ($i == 0 || $i == 4) {
								array_push($corners, $item);
								array_push($star, $item);
							}
							if ($i == 2) {
								array_push($pyramid, $item);
							}
							if ($item <= 45) {
								array_push($day, $item);
							} else {
								array_push($night, $item);
							}
							if ($item <= 30) {
								array_push($breakfast, $item);
							} else if ($item >= 31 && $item <= 60) {
								array_push($lunch, $item);
							} else {
								array_push($dinner, $item);
							}
							array_push($fullHouse, $item);
							$i++;

							if ($j == 0) {
								array_push($lprize, $item);
							}
							if ($j == 0 || $j == 8) {
								array_push($hprize, $item);
							}
							array_push($tprize, $item);
							array_push($topLineNum, $item);
						}
						$j++;
					}

					$i = 0;
					$j = 0;
					foreach ($middleLine as $item) {
						if ($item != " ") {
							if ($i == 2) {
								array_push($star, $item);
								$middleNumber = $item;
							}
							if ($i == 1 || $i == 3) {
								array_push($pyramid, $item);
							}
							if ($item <= 45) {
								array_push($day, $item);
							} else {
								array_push($night, $item);
							}
							if ($item <= 30) {
								array_push($breakfast, $item);
							} else if ($item >= 31 && $item <= 60) {
								array_push($lunch, $item);
							} else {
								array_push($dinner, $item);
							}
							array_push($fullHouse, $item);
							$i++;
							if ($j == 0) {
								array_push($lprize, $item);
							}
							if ($j == 4) {
								array_push($tprize, $item);
							}
							array_push($hprize, $item);
							array_push($middleLineNum, $item);
						}
						$j++;
					}

					$i = 0;
					$j = 0;
					foreach ($bottomLine as $item) {
						if ($item != " ") {
							if ($i == 0 || $i == 4) {
								array_push($corners, $item);
								array_push($star, $item);
							}
							if ($i == 0 || $i == 2 || $i == 4) {
								array_push($pyramid, $item);
							}
							if ($item <= 45) {
								array_push($day, $item);
							} else {
								array_push($night, $item);
							}
							if ($item <= 30) {
								array_push($breakfast, $item);
							} else if ($item >= 31 && $item <= 60) {
								array_push($lunch, $item);
							} else {
								array_push($dinner, $item);
							}
							array_push($fullHouse, $item);
							$i++;
							if ($j == 0 || $j == 8) {
								array_push($hprize, $item);
							}
							if ($j == 4) {
								array_push($tprize, $item);
							}
							array_push($lprize, $item);
							array_push($bottomLineNum, $item);
						}
						$j++;
					}


					$matchedArr = array_intersect($numArray, $fullHouse);
					array_push($sheetCombo, sizeof($matchedArr));
					$sheetType=$ticket->sheet_type;
					$matchArrayString = "";
					foreach ($matchedArr as $ma) {
						if ($ma != "") {
							$matchArrayString = $matchArrayString . $ma . ",";
						}
					}

					//Early Five Prize
					if (array_key_exists("EARLY5", $avaliablePrizes)) {
						if (array_key_exists("EARLY5", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["EARLY5"]) < $avaliablePrizes["EARLY5"] && !in_array($ticket->ticket_number, $claimedPrizeData["EARLY5"])) {
								if (sizeof($matchedArr) >= 5) {
									//Make a early 5 claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"EARLY5");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "EARLY5";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["EARLY5"], $ticket->ticket_number);
								}
							}
						} else {
							if (sizeof($matchedArr) >= 5) {
								//Make a early 5 claim data
								$claimedPrizeData["EARLY5"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"EARLY5");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "EARLY5";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["EARLY5"], $ticket->ticket_number);
							}
						}
					}


					//Early Seven Prize
					if (array_key_exists("EARLY7", $avaliablePrizes)) {
						if (array_key_exists("EARLY7", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["EARLY7"]) < $avaliablePrizes["EARLY7"] && !in_array($ticket->ticket_number, $claimedPrizeData["EARLY7"])) {
								if (sizeof($matchedArr) >= 7) {
									//Make a early 7 claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"EARLY7");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "EARLY7";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["EARLY7"], $ticket->ticket_number);
								}
							}
						} else {
							if (sizeof($matchedArr) >= 7) {
								//Make a early 7 claim data
								$claimedPrizeData["EARLY7"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"EARLY7");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "EARLY7";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["EARLY7"], $ticket->ticket_number);
							}
						}
					}


					//Laddu Prize
					if (array_key_exists("LADDU", $avaliablePrizes)) {
						if (array_key_exists("LADDU", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["LADDU"]) < $avaliablePrizes["LADDU"] && !in_array($ticket->ticket_number, $claimedPrizeData["LADDU"])) {
								if (in_array($middleNumber, $numArray)) {
									//Make a Laddu claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"LADDU");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "LADDU";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["LADDU"], $ticket->ticket_number);
								}
							}
						} else {
							if (in_array($middleNumber, $numArray)) {
								//Make a Laddu claim data
								$claimedPrizeData["LADDU"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"LADDU");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "LADDU";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["LADDU"], $ticket->ticket_number);
							}
						}
					}


					//Top Line Prize
					if (array_key_exists("TOPLINE", $avaliablePrizes)) {
						$topLineMatch = array_intersect($numArray, $topLineNum);
						if (array_key_exists("TOPLINE", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["TOPLINE"]) < $avaliablePrizes["TOPLINE"] && !in_array($ticket->ticket_number, $claimedPrizeData["TOPLINE"])) {
								if (count($topLineMatch) == 5) {
									//Make a Top Line claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"TOPLINE");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "TOPLINE";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["TOPLINE"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($topLineMatch) == 5) {
								//Make a Top Line claim data
								$claimedPrizeData["TOPLINE"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"TOPLINE");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "TOPLINE";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["TOPLINE"], $ticket->ticket_number);
							}
						}
					}

					//Middle Line Prize
					if (array_key_exists("MIDDLELINE", $avaliablePrizes)) {
						$middleLineMatch = array_intersect($numArray, $middleLineNum);
						if (array_key_exists("MIDDLELINE", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["MIDDLELINE"]) < $avaliablePrizes["MIDDLELINE"] && !in_array($ticket->ticket_number, $claimedPrizeData["MIDDLELINE"])) {
								if (count($middleLineMatch) == 5) {
									//Make a Middle Line claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"MIDDLELINE");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "MIDDLELINE";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["MIDDLELINE"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($middleLineMatch) == 5) {
								//Make a Middle Line claim data
								$claimedPrizeData["MIDDLELINE"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"MIDDLELINE");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "MIDDLELINE";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["MIDDLELINE"], $ticket->ticket_number);
							}
						}
					}

					//Bottom Line Prize
					if (array_key_exists("BOTTOMLINE", $avaliablePrizes)) {
						$bottomLineMatch = array_intersect($numArray, $bottomLineNum);
						if (array_key_exists("BOTTOMLINE", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["BOTTOMLINE"]) < $avaliablePrizes["BOTTOMLINE"] && !in_array($ticket->ticket_number, $claimedPrizeData["BOTTOMLINE"])) {
								if (count($bottomLineMatch) == 5) {
									//Make a Bottom Line claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"BOTTOMLINE");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "BOTTOMLINE";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["BOTTOMLINE"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($bottomLineMatch) == 5) {
								//Make a Bottom Line claim data
								$claimedPrizeData["BOTTOMLINE"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"BOTTOMLINE");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "BOTTOMLINE";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["BOTTOMLINE"], $ticket->ticket_number);
							}
						}
					}


					//Corners Prize
					if (array_key_exists("CORNERS", $avaliablePrizes)) {
						$cornersMatch = array_intersect($numArray, $corners);
						if (array_key_exists("CORNERS", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["CORNERS"]) < $avaliablePrizes["CORNERS"] && !in_array($ticket->ticket_number, $claimedPrizeData["CORNERS"])) {
								if (count($cornersMatch) == count($corners)) {
									//Make a Corners claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"CORNERS");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "CORNERS";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["CORNERS"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($cornersMatch) == count($corners)) {
								//Make a Corners claim data
								$claimedPrizeData["CORNERS"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"CORNERS");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "CORNERS";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["CORNERS"], $ticket->ticket_number);
							}
						}
					}

					//Corners with Star Prize
					if (array_key_exists("CORNERSWITHSTAR", $avaliablePrizes)) {
						$cornersWithStarMatch = array_intersect($numArray, $star);
						if (array_key_exists("CORNERSWITHSTAR", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["CORNERSWITHSTAR"]) < $avaliablePrizes["CORNERSWITHSTAR"] && !in_array($ticket->ticket_number, $claimedPrizeData["CORNERSWITHSTAR"])) {
								if (count($cornersWithStarMatch) == count($star)) {
									//Make a Corners with Star claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"CORNERSWITHSTAR");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "CORNERSWITHSTAR";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["CORNERSWITHSTAR"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($cornersWithStarMatch) == count($star)) {
								//Make a Corners with Star claim data
								$claimedPrizeData["CORNERSWITHSTAR"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"CORNERSWITHSTAR");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "CORNERSWITHSTAR";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["CORNERSWITHSTAR"], $ticket->ticket_number);
							}
						}
					}


					//Day Prize
					if (array_key_exists("DAY", $avaliablePrizes)) {
						$dayMatch = array_intersect($numArray, $day);
						if (array_key_exists("DAY", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["DAY"]) < $avaliablePrizes["DAY"] && !in_array($ticket->ticket_number, $claimedPrizeData["DAY"])) {
								if (count($dayMatch) == count($day)) {
									//Make a Day claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"DAY");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "DAY";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["DAY"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($dayMatch) == count($day)) {
								//Make a Day claim data
								$claimedPrizeData["DAY"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"DAY");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "DAY";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["DAY"], $ticket->ticket_number);
							}
						}
					}


					//Night Prize
					if (array_key_exists("NIGHT", $avaliablePrizes)) {
						$nightMatch = array_intersect($numArray, $night);
						if (array_key_exists("NIGHT", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["NIGHT"]) < $avaliablePrizes["NIGHT"] && !in_array($ticket->ticket_number, $claimedPrizeData["NIGHT"])) {
								if (count($nightMatch) == count($night)) {
									//Make a Night claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"NIGHT");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "NIGHT";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["NIGHT"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($nightMatch) == count($night)) {
								//Make a Night claim data
								$claimedPrizeData["NIGHT"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"NIGHT");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "NIGHT";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["NIGHT"], $ticket->ticket_number);
							}
						}
					}


					//Breakfast Prize
					if (array_key_exists("BREAKFAST", $avaliablePrizes)) {
						$breakfastMatch = array_intersect($numArray, $breakfast);
						if (array_key_exists("BREAKFAST", $claimedPrizeData)) {
							if (
								sizeof($claimedPrizeData["BREAKFAST"]) < $avaliablePrizes["BREAKFAST"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["BREAKFAST"])
							) {
								if (count($breakfastMatch) == count($breakfast)) {
									//Make a Breakfast claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"BREAKFAST");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "BREAKFAST";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["BREAKFAST"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($breakfastMatch) == count($breakfast)) {
								//Make a Breakfast claim data
								$claimedPrizeData["BREAKFAST"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"BREAKFAST");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "BREAKFAST";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["BREAKFAST"], $ticket->ticket_number);
							}
						}
					}


					//Lunch Prize
					if (array_key_exists("LUNCH", $avaliablePrizes)) {
						$lunchMatch = array_intersect($numArray, $lunch);
						if (array_key_exists("LUNCH", $claimedPrizeData)) {
							if (
								sizeof($claimedPrizeData["LUNCH"]) < $avaliablePrizes["LUNCH"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["LUNCH"])
							) {
								if (count($lunchMatch) == count($lunch)) {
									//Make a Lunch claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"LUNCH");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];										$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "LUNCH";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["LUNCH"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($lunchMatch) == count($lunch)) {
								//Make a Lunch claim data
								$claimedPrizeData["LUNCH"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"LUNCH");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "LUNCH";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["LUNCH"], $ticket->ticket_number);
							}
						}
					}


					//Dinner Prize
					if (array_key_exists("DINNER", $avaliablePrizes)) {
						$dinnerMatch = array_intersect($numArray, $dinner);
						if (array_key_exists("DINNER", $claimedPrizeData)) {
							if (
								sizeof($claimedPrizeData["DINNER"]) < $avaliablePrizes["DINNER"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["DINNER"])
							) {
								if (count($dinnerMatch) == count($dinner)) {
									//Make a Dinner claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"DINNER");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];										$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "DINNER";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["DINNER"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($dinnerMatch) == count($dinner)) {
								//Make a Dinner claim data
								$claimedPrizeData["DINNER"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"DINNER");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "DINNER";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["DINNER"], $ticket->ticket_number);
							}
						}
					}


					//L Prize
					if (array_key_exists("L", $avaliablePrizes)) {
						$lprizeMatch = array_intersect($numArray, $lprize);
						if (array_key_exists("L", $claimedPrizeData)) {
							if (
								sizeof($claimedPrizeData["L"]) < $avaliablePrizes["L"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["L"])
							) {
								if (count($lprizeMatch) == count($lprize)) {
									//Make a L claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"L");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];										$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "L";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["L"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($lprizeMatch) == count($lprize)) {
								//Make a L claim data
								$claimedPrizeData["L"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"L");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "L";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["L"], $ticket->ticket_number);
							}
						}
					}


					//H Prize
					if (array_key_exists("H", $avaliablePrizes)) {
						$hprizeMatch = array_intersect($numArray, $hprize);
						if (array_key_exists("H", $claimedPrizeData)) {
							if (
								sizeof($claimedPrizeData["H"]) < $avaliablePrizes["H"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["H"])
							) {
								if (count($hprizeMatch) == count($hprize)) {
									//Make a H claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"H");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];										$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "H";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["H"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($hprizeMatch) == count($hprize)) {
								//Make a H claim data
								$claimedPrizeData["H"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"H");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "H";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["H"], $ticket->ticket_number);
							}
						}
					}


					//T Prize
					if (array_key_exists("T", $avaliablePrizes)) {
						$tprizeMatch = array_intersect($numArray, $tprize);
						if (array_key_exists("T", $claimedPrizeData)) {
							if (
								sizeof($claimedPrizeData["T"]) < $avaliablePrizes["T"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["T"])
							) {
								if (count($tprizeMatch) == count($tprize)) {
									//Make a T claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"T");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "T";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["T"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($tprizeMatch) == count($tprize)) {
								//Make a H claim data
								$claimedPrizeData["T"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"T");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "T";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["T"], $ticket->ticket_number);
							}
						}
					}


					//Full House Prize
					if (array_key_exists("FULLHOUSE", $avaliablePrizes)) {
						$fullHouseMatch = array_intersect($numArray, $fullHouse);
						if (array_key_exists("FULLHOUSE", $claimedPrizeData)) {
							if (
								sizeof($claimedPrizeData["FULLHOUSE"]) < $avaliablePrizes["FULLHOUSE"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["FULLHOUSE"])
							) {
								if (count($fullHouseMatch) == count($fullHouse)) {
									//Make a Full House claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"FULLHOUSE");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];										$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "FULLHOUSE";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["FULLHOUSE"], $ticket->ticket_number);
								}
							}
						} else {
							if (count($fullHouseMatch) == count($fullHouse)) {
								//Make a Full House claim data
								$claimedPrizeData["FULLHOUSE"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"FULLHOUSE");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "FULLHOUSE";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["FULLHOUSE"], $ticket->ticket_number);
							}
						}
					}


					//Full House 2 Prize
					if (array_key_exists("FULLHOUSE2", $avaliablePrizes)
						&&array_key_exists("FULLHOUSE",$avaliablePrizes)
						&&array_key_exists("FULLHOUSE",$claimedPrizeData)
						&&sizeof($claimedPrizeData['FULLHOUSE'])>=$avaliablePrizes['FULLHOUSE']) {
						$fullHouseMatch = array_intersect($numArray, $fullHouse);
						if (array_key_exists("FULLHOUSE2", $claimedPrizeData)) {
							if (sizeof($claimedPrizeData["FULLHOUSE2"]) < $avaliablePrizes["FULLHOUSE2"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["FULLHOUSE"])
								&& !in_array($ticket->ticket_number, $claimedPrizeData["FULLHOUSE2"])
							) {
								if (count($fullHouseMatch) == count($fullHouse)) {
									//Make a Full House 2 claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"FULLHOUSE2");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];										$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "FULLHOUSE2";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["FULLHOUSE"], $ticket->ticket_number);
								}
							}
						} else {
							if(!in_array($ticket->ticket_number, $claimedPrizeData["FULLHOUSE"])){

								if (count($fullHouseMatch) == count($fullHouse)) {
									//Make a Full House claim data
									$claimedPrizeData["FULLHOUSE2"] = [];
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"FULLHOUSE2");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "FULLHOUSE2";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["FULLHOUSE"], $ticket->ticket_number);
								}

							}
						}
					}

					//Full House 3 Prize
					if (array_key_exists("FULLHOUSE3", $avaliablePrizes)
						&&array_key_exists("FULLHOUSE2",$avaliablePrizes)
						&&array_key_exists("FULLHOUSE",$avaliablePrizes)
						&&array_key_exists("FULLHOUSE2",$claimedPrizeData)
						&&array_key_exists("FULLHOUSE",$claimedPrizeData)
						&&sizeof($claimedPrizeData['FULLHOUSE2'])>=$avaliablePrizes['FULLHOUSE2']
						&&sizeof($claimedPrizeData['FULLHOUSE'])>=$avaliablePrizes['FULLHOUSE']) {
						$fullHouseMatch = array_intersect($numArray, $fullHouse);
						if (array_key_exists("FULLHOUSE3", $claimedPrizeData)) {
							if (
								sizeof($claimedPrizeData["FULLHOUSE3"]) < $avaliablePrizes["FULLHOUSE3"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["FULLHOUSE"])
								&& !in_array($ticket->ticket_number, $claimedPrizeData["FULLHOUSE2"])
								&& !in_array($ticket->ticket_number, $claimedPrizeData["FULLHOUSE3"])
							) {
								if (count($fullHouseMatch) == count($fullHouse)) {
									//Make a Full House 2 claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"FULLHOUSE3");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];										$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "FULLHOUSE3";
									$data['claimed_ticket_id'] = $ticket->id;
									$data['claimed_ticket_number'] = $ticket->ticket_number;
									$data['checked_numbers'] = $matchArrayString;
									array_push($tick, $data);
									// array_push($claimedPrizeData["FULLHOUSE"], $ticket->ticket_number);
								}
							}
						} else {
							if(!in_array($ticket->ticket_number, $claimedPrizeData["FULLHOUSE"])
							&& !in_array($ticket->ticket_number, $claimedPrizeData["FULLHOUSE2"]))
							if (count($fullHouseMatch) == count($fullHouse)) {
								//Make a Full House claim data
								$claimedPrizeData["FULLHOUSE3"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"FULLHOUSE3");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];									$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "FULLHOUSE3";
								$data['claimed_ticket_id'] = $ticket->id;
								$data['claimed_ticket_number'] = $ticket->ticket_number;
								$data['checked_numbers'] = $matchArrayString;
								array_push($tick, $data);
								// array_push($claimedPrizeData["FULLHOUSE"], $ticket->ticket_number);
							}
						}
					}

				}

				$minSheetCombo = min($sheetCombo);

				if (count($sheetCombo) == 3&&$sheetType=="HALFSHEET") {
					if (array_key_exists("HALFSHEETBONUS", $avaliablePrizes)) {
						if (array_key_exists("HALFSHEETBONUS", $claimedPrizeData)) {
							if (
								sizeof($claimedPrizeData["HALFSHEETBONUS"]) < $avaliablePrizes["HALFSHEETBONUS"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["HALFSHEETBONUS"])
							) {
								if ($minSheetCombo >= 2) {
									//Make a Full House claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"HALFSHEETBONUS");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];										$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "HALFSHEETBONUS";
									$data['claimed_ticket_id'] = $sheetTicketId;
									$data['claimed_ticket_number'] = $sheetTicketNumber;
									$data['checked_numbers'] = "";
									array_push($tick, $data);
									// array_push($claimedPrizeData["HALFSHEETBONUS"], $ticket->ticket_number);
								}
							}
						} else {
							if ($minSheetCombo >= 2) {
								//Make a Full House claim data
								$claimedPrizeData["HALFSHEETBONUS"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"HALFSHEETBONUS");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "HALFSHEETBONUS";
								$data['claimed_ticket_id'] = $sheetTicketId;
								$data['claimed_ticket_number'] = $sheetTicketNumber;
								$data['checked_numbers'] = "";
								array_push($tick, $data);
								// array_push($claimedPrizeData["HALFSHEETBONUS"], $ticket->ticket_number);
							}
						}
					}
				}

				if (count($sheetCombo) == 6&&$sheetType=="FULLSHEET") {
					if (array_key_exists("FULLSHEETBONUS", $avaliablePrizes)) {
						if (array_key_exists("FULLSHEETBONUS", $claimedPrizeData)) {
							if (
								sizeof($claimedPrizeData["FULLSHEETBONUS"]) < $avaliablePrizes["FULLSHEETBONUS"]
								&& !in_array($ticket->ticket_number, $claimedPrizeData["FULLSHEETBONUS"])
							) {
								if ($minSheetCombo >= 2) {
									//Make a Full House claim data
									$data = [];
									$data['game_date'] = $date;
									$data['game_time'] = $time;
									$prizeDataC=$this->get_prize_name($activePrizes,"FULLSHEETBONUS");
									$data['prize_name'] = $prizeDataC['prize_name'];
									$data['prize_amount']=$prizeDataC['prize_amount'];
									$data['customer_name'] = $ticket->customer_name;
									$data['prize_tag'] = "FULLSHEETBONUS";
									$data['claimed_ticket_id'] = $sheetTicketId;
									$data['claimed_ticket_number'] = $sheetTicketNumber;
									$data['checked_numbers'] = "";
									array_push($tick, $data);
									// array_push($claimedPrizeData["FULLSHEETBONUS"], $ticket->ticket_number);
								}
							}
						} else {
							if ($minSheetCombo >= 2) {
								//Make a Full House claim data
								$claimedPrizeData["FULLSHEETBONUS"] = [];
								$data = [];
								$data['game_date'] = $date;
								$data['game_time'] = $time;
								$prizeDataC=$this->get_prize_name($activePrizes,"FULLSHEETBONUS");
								$data['prize_name'] = $prizeDataC['prize_name'];
								$data['prize_amount']=$prizeDataC['prize_amount'];
								$data['customer_name'] = $ticket->customer_name;
								$data['prize_tag'] = "FULLSHEETBONUS";
								$data['claimed_ticket_id'] = $sheetTicketId;
								$data['claimed_ticket_number'] = $sheetTicketNumber;
								$data['checked_numbers'] = "";
								array_push($tick, $data);
								// array_push($claimedPrizeData["FULLSHEETBONUS"], $ticket->ticket_number);
							}
						}
					}
				}
			}

			$playedGame = PlayedGame::where('game_date', $date)
				->where('game_time', $time)
				->update(array('called_numbers' => $calledNumSaved));

			foreach ($tick as $prizes) {
				$claimData = [];
				$claimData['game_date'] = $prizes['game_date'];
				$claimData['game_time'] = $prizes['game_time'];
				$claimData['prize_name'] = $prizes['prize_name'];
				$claimData['prize_tag'] = $prizes['prize_tag'];
				$claimData['prize_amount']=$prizes['prize_amount'];
				$claimData['claimed_ticket_id'] = $prizes['claimed_ticket_id'];
				$claimData['claimed_ticket_number'] = $prizes['claimed_ticket_number'];
				$claimData['checked_numbers'] = $prizes['checked_numbers'];
				$savePrizes = GameClaim::insert($claimData);

				$this->check_status_and_send($prizes);
			}

			$callSpeech=(string)$cn;

			$setting=GameSetting::where('id',1)->first();

			if($setting->rhyming_speech==1){
				$rhyme=Rhyme::where('title',(string)$cn)
				->first();
				if($rhyme){
					$callSpeech=$rhyme->message;
				}
			}

			if($currentGame->game_status=="GAMEOVER"){
				$currentGame->game_status="ACTIVE";
				$currentGame->save();
			}

			$resultData = [];
			$resultData['status']="ACTIVE";
			$resultData['call_number'] = $cn;
			$resultData['call_speech']=$callSpeech;
			$resultData['prize_claims'] = $tick;

			return $resultData;
		} else {
			return null;
		}

		// $tickets=Ticket::all();
	}

	public function get_prize_name($activePrizes,$prizeTag){
		foreach($activePrizes as $ap){
			if($ap['prize_tag']==$prizeTag){
				$data=[];
				$data['prize_name']=$ap['prize_name'];
				$data['prize_amount']=$ap['prize_amount'];
				return $data;
			}
		}
	}

	public function check_status_and_send($prizes){
		$gameSetting=GameSetting::where('id',1)->first();
		
		if($gameSetting->prize_claim_notification==1){
			$prizeName=GamePrize::where('prize_tag',$prizes['prize_tag'])->first();
			$ticket=GameTicket::where('id',$prizes['claimed_ticket_id'])->first();
			$admins=Admin::pluck('token');

			$tokens=[];
			foreach($admins as $admin){
				array_push($tokens,$admin);
			}

			$auth=$gameSetting->notification_auth;
			$title="Prize claimed";
			$message=$prizeName['prize_name']." claimed by ".$prizes['customer_name'];

			$this->notification($tokens,$title,$message,$auth);

		}

		if($gameSetting->claim_sms==1){
			$prizeName=GamePrize::where('prize_tag',$prizes['prize_tag'])->first();
			$ticket=GameTicket::where('id',$prizes['claimed_ticket_id'])->first();
			$api=$gameSetting->sms_api;
			$senderId=$gameSetting->sms_sender_id;
			$numbers=[];
			array_push($numbers,'91'.$ticket['customer_phone']);

			$message=$gameSetting->purchase_sms_message;
			$messageData=str_replace('{ticket-number}',$prizes['claimed_ticket_number'],$message);
			$messageData=str_replace('{prize-name}',$prizeName['prize_name'],$messageData);

			$this->sendSms($api,$numbers,$senderId,$messageData);
		}
	}


	//Get Active Prizes
	public function getActivePrizes()
	{
		$activePrizes = GamePrize::where('enabled', 1)
			->get();
		return $activePrizes;
	}


	public function notification($tokenList, $title,$message,$auth)
    {
        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
        // $token=$token;

        $notification = [
            'title' => $title,
            'body'=>$message,
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
            'Authorization: key='.$auth,
            'Content-Type: application/json'
        ];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);
        curl_close($ch);

        return true;
    }

	function sendSms($api,$numbersData,$senderId,$messageData)
	{
		$apiKey = urlencode($api);
		// Message details
		$numbers = array($numbersData);
		$sender = urlencode($senderId);
		$message = rawurlencode($messageData);

		$numbers = implode(',', $numbers);

		// Prepare data for POST request
		$data = array('apikey' => $apiKey, 'numbers' => $numbers, 'sender' => $sender, 'message' => $message);
		// Send the POST request with cURL
		$ch = curl_init('https://api.textlocal.in/send/');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		// Process your response here
		// echo $response;
	}
}

<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\CurrentGame;
use App\Models\GameClaim;
use App\Models\GameSetting;
use App\Models\GameTicket;
use App\Models\Ticket;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{

	//1. Get Ticket Numbers with availablility
	public function get_ticket_numbers($date, $time)
	{

		$allTicketsNum = Ticket::pluck('ticket_number');

		$tickets = GameTicket::where('game_date', '=', $date)
			->where('game_time', '=', $time)
			->pluck('ticket_number');

		$ticketData = [];
		$ticketData['all_tickets'] = $allTicketsNum;
		$ticketData['sold_tickets'] = $tickets;

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $ticketData]);
	}

	//Change agent status
	public function change_agent_status($agentId)
	{
		$agent = Agent::where('id', $agentId)
			->first();
		if ($agent) {
			if ($agent->active == 1) {
				$agent->active = 0;
			} else {
				$agent->active = 1;
			}
			$agent->save();
		}

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $agent]);
	}

	//2. Get Agent Ticket
	public function get_agents_ticket($date, $time, $agentId, $search)
	{
		if ($search != "--1") {
			$agentTicket = GameTicket::where('agent_id', $agentId)
				->where('game_date', $date)
				->where('game_time', $time)
				->where('ticket_number', 'like', '%' . $search . '%')
				->orwhere('customer_name', 'like', '%' . $search . '%')
				->orwhere('customer_phone', 'like', '%' . $search . '%')
				->get();
		} else {
			$agentTicket = GameTicket::where('agent_id', $agentId)
				->where('game_date', $date)
				->where('game_time', $time)
				->get();
		}


		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $agentTicket]);
	}

	//3. Get All Prizes Claims for date and time
	public function get_prize_claimed_for_agent($gameDate, $gameTime, $agentId)
	{
		$claimedPrize = GameClaim::leftjoin('game_tickets', function ($join) use ($agentId, $gameDate, $gameTime) {
			$join->on('game_tickets.ticket_number', '=', 'game_claims.claimed_ticket_number');
			$join->where('game_tickets.game_date', '=', $gameDate);
			$join->where('game_tickets.game_time', '=', $gameTime);
		})
			->leftjoin('game_prizes', 'game_prizes.prize_tag', 'game_claims.prize_tag')
			->leftjoin('agents', 'agents.id', 'game_tickets.agent_id')
			->select('game_claims.*', 'game_tickets.ticket_number', 'game_tickets.ticket', 'game_tickets.agent_id', 'game_tickets.customer_name', 'game_tickets.customer_phone', 'game_prizes.prize_name', 'agents.agent_name')
			->where('game_claims.game_date', $gameDate)
			->where('game_claims.game_time', $gameTime)
			->get();
		// ->groupBy('prize_tag');

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $claimedPrize]);
	}

	//4. Sale Ticket
	public function sale_ticket(Request $request)
	{

		$currentGame = CurrentGame::where('id', 1)->first();
		$now = new Carbon();

		$gameSetting = GameSetting::where('id', 1)->first();

		
		$gameDate = $currentGame->game_date;
		$gameTime = $currentGame->game_time;
		$agentId = $request->agent_id;
		$ticketNumbers = $request->ticket_numbers;
		$customerName = $request->customer_name;
		$customerPhone = $request->customer_phone;

		if (($currentGame->booking_close < $now || $gameSetting->booking_open == 0)&&$agentId!=-1) {
			return response()->json(['status' => 'SUCCESS', 'code' => 'FC_03', 'data' => null]);
		}


		sort($ticketNumbers);

		DB::beginTransaction();

		try {
			$gameTickets = GameTicket::where('game_date', $gameDate)
				->where('game_time', $gameTime)
				->whereIn('ticket_number', $ticketNumbers)
				->get();

			$categoryDetails = $this->sheet_search($ticketNumbers);

			if ($gameTickets->isEmpty()) {
				$ticketNumbersMessage = "";
				$ticketDataSheet = [];

				if (count($categoryDetails['random']) > 0) {
					$tickets = Ticket::whereIn('ticket_number', $categoryDetails['random'])
						->get();
					foreach ($tickets as $ticket) {
						$ticketData = [];
						$ticketData['game_date'] = $gameDate;
						$ticketData['game_time'] = $gameTime;
						$ticketData['agent_id'] = $agentId;
						$ticketData['ticket_number'] = $ticket->ticket_number;
						$ticketData['ticket'] = $ticket->ticket;
						$ticketData['sheet_number'] = -1;
						$ticketData['sheet_type'] = "RANDOM";
						$ticketData['customer_name'] = $customerName;
						$ticketData['customer_phone'] = $customerPhone;
						$ticketData['created_at'] = now();
						$ticketData['updated_at'] = now();

						if ($ticketNumbersMessage == "") {
							$ticketNumbersMessage = $ticketNumbersMessage . $ticket->ticket_number;
						} else {
							$ticketNumbersMessage = $ticketNumbersMessage . ',' . $ticket->ticket_number;
						}

						array_push($ticketDataSheet, $ticketData);
					}
				}

				$nextSheetNumber = -1;
				$gameForSheet = GameTicket::where('game_date', $gameDate)
					->where('game_time', $gameTime)
					->orderBy('sheet_number', 'DESC')
					->first();
				if ($gameForSheet) {
					$nextSheetNumber = $gameForSheet->sheet_number + 1;
				} else {
					$nextSheetNumber = 1;
				}

				if (count($categoryDetails['half_sheet']) > 0) {

					foreach ($categoryDetails['half_sheet'] as $hs) {
						$tickets = Ticket::whereIn('ticket_number', $hs)
							->get();
						foreach ($tickets as $ticket) {
							$ticketData = [];
							$ticketData['game_date'] = $gameDate;
							$ticketData['game_time'] = $gameTime;
							$ticketData['agent_id'] = $agentId;
							$ticketData['ticket_number'] = $ticket->ticket_number;
							$ticketData['ticket'] = $ticket->ticket;
							$ticketData['sheet_number'] = $nextSheetNumber;
							$ticketData['sheet_type'] = "HALFSHEET";
							$ticketData['customer_name'] = $customerName;
							$ticketData['customer_phone'] = $customerPhone;
							$ticketData['created_at'] = now();
							$ticketData['updated_at'] = now();

							if ($ticketNumbersMessage == "") {
								$ticketNumbersMessage = $ticketNumbersMessage . $ticket->ticket_number;
							} else {
								$ticketNumbersMessage = $ticketNumbersMessage . ',' . $ticket->ticket_number;
							}

							array_push($ticketDataSheet, $ticketData);
						}
						$nextSheetNumber++;
					}
				}

				if (count($categoryDetails['full_sheet']) > 0) {
					foreach ($categoryDetails['full_sheet'] as $fs) {
						$tickets = Ticket::whereIn('ticket_number', $fs)
							->get();
						foreach ($tickets as $ticket) {
							$ticketData = [];
							$ticketData['game_date'] = $gameDate;
							$ticketData['game_time'] = $gameTime;
							$ticketData['agent_id'] = $agentId;
							$ticketData['ticket_number'] = $ticket->ticket_number;
							$ticketData['ticket'] = $ticket->ticket;
							$ticketData['sheet_number'] = $nextSheetNumber;
							$ticketData['sheet_type'] = "FULLSHEET";
							$ticketData['customer_name'] = $customerName;
							$ticketData['customer_phone'] = $customerPhone;
							$ticketData['created_at'] = now();
							$ticketData['updated_at'] = now();

							if ($ticketNumbersMessage == "") {
								$ticketNumbersMessage = $ticketNumbersMessage . $ticket->ticket_number;
							} else {
								$ticketNumbersMessage = $ticketNumbersMessage . ',' . $ticket->ticket_number;
							}

							array_push($ticketDataSheet, $ticketData);
						}
						$nextSheetNumber++;
					}
				}

				$gameTickets = GameTicket::insert($ticketDataSheet);

				if($gameDate==$currentGame->game_date&&$gameTime==$currentGame->game_time){
					Ticket::whereIn('ticket_number',$ticketNumbers)
							->update(['sheet_type'=>$customerName]);
				}

				DB::commit();

				if ($gameSetting->ticket_purchase_notification == 1) {
					$admins = Admin::pluck('token');
					$agent = Agent::where('id', $agentId)->first();

					$tokens = [];
					foreach ($admins as $admin) {
						array_push($tokens, $admin);
					}

					$auth = $gameSetting->notification_auth;
					$title = "Ticket sold by " . $agent->agent_name;
					$message = count($ticketNumbers) . " Tickets sold for next game";

					$this->notification($tokens, $title, $message, $auth);
				}

				if ($gameSetting->purchase_sms == 1) {
					$api = $gameSetting->sms_api;
					$senderId = $gameSetting->sms_sender_id;
					$numbers = [];
					array_push($numbers, '91' . $customerPhone);

					$message = $gameSetting->purchase_sms_message;
					$messageData = str_replace('{ticket-numbers}', $ticketNumbersMessage, $message);

					$this->sendSms($api, $numbers, $senderId, $messageData);
				}

				// $dataSend = array("call_number" => $data['call_number'], "prize_claims" => $data['prize_claims']);                                                                    
				if($agentId!=-1){
					$data_string = json_encode(array('ticket_numbers' => $ticketNumbers, 'customer_name' => $customerName));

					$ch = curl_init($_SERVER['SERVER_NAME'] . ':8080/ticketSale');
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
				

				return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $ticketDataSheet]);
			} else {
				//Some tickets are already sold
				return response()->json(['status' => 'SUCCESS', 'code' => 'FC_02', 'data' => $gameTickets]);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['status' => 'SUCCESS', 'code' => 'FC_01', 'data' => $e]);
		}
	}

	//5. Login Agent
	public function login_agent(Request $request)
	{
		$username = $request->username;
		$password = $request->password;

		$agent = Agent::where('agent_username', $username)
			->where('agent_password', $password)
			->first();
		if ($agent) {
			if ($agent->agent_deleted == 0) {
				return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $agent]);
			} else {
				//Agent Deleted
				return response()->json(['status' => 'SUCCESS', 'code' => 'FC_01', 'data' => null]);
			}
		} else {
			return response()->json(['status' => 'SUCCESS', 'code' => 'FC_01', 'data' => null]);
		}
	}

	//Get agent status
	public function get_agent_status(Request $request)
	{
		$username = $request->username;
		$password = $request->password;
		$agent = Agent::where('agent_username', $username)
			->where('agent_password', $password)
			->first();
		if ($agent) {
			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $agent]);
		} else {
			return response()->json(['status' => 'SUCCESS', 'code' => 'FC_01', 'data' => null]);
		}
	}

	//Get sale status
	public function get_sale_status($agentId){
		$currentGame=CurrentGame::where('id',1)->first();

		$gameDate=$currentGame->game_date;
		$gameTime=$currentGame->game_time;

		$agent=Agent::where('id',$agentId)->first();
		$allTicketsCount=Ticket::count();
		$gameTicketCount=GameTicket::where('game_date',$gameDate)
									->where('game_time',$gameTime)
									->count();
		$myTicketCount=GameTicket::where('game_date',$gameDate)
								->where('game_time',$gameTime)
								->where('agent_id',$agentId)
								->count();
		$ticketPrice=GameSetting::where('id',1)->first();
		$ticketPriceAmount=$ticketPrice->next_game_ticket_price;
		$commissionAmount=$agent->commission_amount;

		$data=[];
		$data['total_tickets']=$allTicketsCount;
		$data['sold_tickets']=$gameTicketCount;
		$data['my_tickets']=$myTicketCount;
		$data['ticket_price']=$ticketPriceAmount;
		$data['commission_amount']=$commissionAmount;

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$data]);
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

	function sendSms($api, $numbersData, $senderId, $messageData)
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

		// foreach($data as $d){
		// 	if(count($d)<3){
		// 		$newData['random']=array_merge($newData['random'],$d);
		// 	}else if(count($d)==3){
		// 		array_push($newData['half_sheet'],$d);
		// 	}else if(count($d)>3&&count($d)<6){
		// 		array_push($newData['half_sheet'],array_slice($d,0,3));
		// 		if(count($d)>3){
		// 			$newData['random']=array_merge($newData['random'],array_slice($d,3,count($d)-3));
		// 		}
		// 	}else if(count($d)==6){
		// 		array_push($newData['full_sheet'],$d);
		// 	}else{
		// 		$size=count($d);
		// 		$fullSheetCount=intdiv($size,6);
		// 		$remaining=$size-($fullSheetCount*6);
		// 		$halfCount=intdiv($remaining,3);
		// 		$remaining2=$size-(($fullSheetCount*6)+($halfCount*3));

		// 		if($fullSheetCount!=0){
		// 			$onlySix=array_slice($d,0,(6*$fullSheetCount));
		// 			$chunks = array_chunk($onlySix, 6, false);

		// 			for ($t = 0; $t < count($chunks); $t++) {
		// 				if (!array_key_exists($fullSheetIndex, $newData['full_sheet'])) {
		// 					$newData['full_sheet'][$fullSheetIndex] = [];
		// 				}
		// 				$newData['full_sheet'][$fullSheetIndex] = array_merge($newData['full_sheet'][$fullSheetIndex], $chunks[$t]);
		// 				$fullSheetIndex++;
		// 			}
		// 		}

		// 		if($halfCount!=0){
		// 			array_push($newData['half_sheet'],array_slice($d,(6*$fullSheetCount),$size-(6*$fullSheetCount)-1));
		// 		}

		// 		if($remaining2!=0){
		// 			$newData['random']=array_merge($newData['random'],array_slice($d,(6*$fullSheetCount)+(3*$halfCount),$remaining2));
		// 		}
		// 	}
		// }

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
}

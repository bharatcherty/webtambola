<?php

namespace App\Http\Controllers;

use App\Classes\GenerateTicket;
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
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
	//1. Login Admin
	//2. Change Admin Password
	//3. Add or Remove Game
	//4. Get Game list for Admin
	//5. Get All Prizes
	//6. Enable/Disable Prize
	//7. Edit Prize Details
	//8. Add New Agent
	//9. Edit a agent
	//10. Delete Agnet
	//11. Get All Prize Claim for date and time
	//12. Save Notification Settings
	//13. Save Call Speech Setting
	//14. Save Whatsapp Link
	//15. Get Settings
	//16. Save Terms and Conditions
	//17. Get Agents Tickets by date
	//18. Unsale Tickets by agent and date
	//19. Get Agents List
	//20. Get Avaliable times by date
	//21. Get Tickets by Date and Time
	//22. Reset All Tickets Sale
	//23. Change Ticket Set
	//24. Change Booking Status
	//25. Find Current Game Time
	//26. Unsale a single ticket by id
	//27. Update Single ticket
	//28. Change Game close time
	//29. Set Ticket Price
	//30. Change Game status
	//31. Change website status

	//1. Login Admin
    public function login_admin(Request $request){
		$username=$request->username;
		$password=$request->password;
		$token=$request->token;

		$admin=Admin::where('admin_username',$username)
					->where('admin_password',$password)
					->first();
		if($admin){
			$admin->token=$token;
			$admin->save();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$admin]);
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
	}

	public function get_admin_status(Request $request){
		$username=$request->username;
		$password=$request->password;

		$admin=Admin::where('admin_username',$username)
					->where('admin_password',$password)
					->first();
		if($admin){
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$admin]);
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
	}

	public function update_admin_token(Request $request){
		$admin=Admin::where('id',$request->id)->first();

		$admin->token=$request->token;
		$admin->save();
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$admin]);
	}

	//2. Change Admin Password
	public function change_admin_password(Request $request){
		$username=$request->username;
		$password=$request->password;

		$admin=Admin::where('admin_username',$username)
					->first();
		if($admin){
			$admin->admin_password=$password;
			$admin->save();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$admin]);
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
	}

	//3. Add Game
	public function add_game(Request $request){
		$gameDate=$request->game_date;
		$gameTime=$request->game_time;
		$type=$request->type;

		$game=Game::where('game_date',$gameDate)
					->where('game_time',$gameTime)
					->where('type',"NEW")
					->first();
		$masterGame=Game::where('game_date',"01-01-2000")
						->where('game_time',$gameTime)
						->first();
		$gameRemove=Game::where('game_date',$gameDate)
						->where('game_time',$gameTime)
						->where('type',"REMOVE")
						->first();

		$currentGame=CurrentGame::where('id',1)->first();
		$currentGame->change_required=1;
		$currentGame->save();

		if($type=="NEW"){
			if(($game||$masterGame)&&$gameRemove){
				Game::find($gameRemove->id)->delete();
				$this->find_current_game_time();
				return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
			}else{
				if($gameRemove){
					$gameRemove->type="NEW";
					$gameRemove->save();
					$this->find_current_game_time();
					return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
				}else {
					if(!$game&&!$masterGame){

						$gameData=[];
						$gameData['game_date']=$gameDate;
						$gameData['game_time']=$gameTime;
						$gameData['type']="NEW";

						Game::create($gameData);
						$this->find_current_game_time();
						return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
					}else{
						return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
					}
				}
			}
		}else{
			if($gameRemove){
				return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
			}else{
				if($game){
					Game::find($game->id)->delete();
					$this->find_current_game_time();
					return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
				}else if($masterGame){
					$gameData=[];
						$gameData['game_date']=$gameDate;
						$gameData['game_time']=$gameTime;
						$gameData['type']="REMOVE";

						Game::create($gameData);
						$this->find_current_game_time();
						return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
				}else{
					if($gameDate=="01-01-2000"){
						Game::where('game_time',$gameTime)->delete();
						$this->find_current_game_time();
						return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
					}else{
						return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
					}
				}
			}
		}
	}

	public function call_time_reset(){
		$ch = curl_init($_SERVER['HTTP_HOST'].'/api/find_current_game_time');                                                                      
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                                                                                      
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
                                                                                            
			echo curl_exec($ch)."\n";
			curl_close($ch);
	}

	//4. Get Game List
	public function get_game_list($gameDate){
		$gamesRemoved=Game::where('game_date',$gameDate)
						->where('type',"REMOVE")
						->pluck('game_time');
		$games=Game::whereIn('game_date',['01-01-2000',$gameDate])
					->whereNotIn('game_time',$gamesRemoved)
					->orderBy('game_time','ASC')
					->get();
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$games]);			
	}


	//5. Get All Prizes
	public function get_all_prizes(){
		$prizes=GamePrize::orderBy('enabled','DESC')->get();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$prizes]);			
	}

	//6. Enable/Disbale Prizes
	public function change_prize_status($id){
		$gamePrize=GamePrize::where('id',$id)
							->first();
		if($gamePrize){
			if($gamePrize->enabled==1){
				$gamePrize->enabled=0;
			}else{
				$gamePrize->enabled=1;
			}
			$gamePrize->save();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$gamePrize]);			
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);			
		}
	}

	//7. Edit Prize Details
	public function edit_prize_details(Request $request){
		$id=$request->id;
		$prizeName=$request->prize_name;
		$prizeAmount=$request->prize_amount;

		$gamePrize=GamePrize::where('id',$id)
							->first();
		if($gamePrize){
			$gamePrize->prize_name=$prizeName;
			$gamePrize->prize_amount=$prizeAmount;

			$gamePrize->save();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$gamePrize]);			
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);			
		}
	}

	//8. Add a Agent
	public function add_agent(Request $request){

		$username=$request->agent_username;
		$password=$request->agent_password;

		$agent=Agent::where('agent_username',$username)
					->where('agent_password',$password)
					->first();

		if($agent){
			if($agent->agent_deleted==1){
				$agent->agent_deleted=0;
				$agent->save();
				return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$agent]);			
			}else{
				return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>$agent]);			
			}
		}

		$agent=Agent::create($request->all());

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$agent]);			
	}

	//9. Edit a Agent
	public function edit_agent(Request $request){
		$id=$request->id;
		$agentName=$request->agent_name;
		$agentUserName=$request->agent_username;
		$agentPassword=$request->agent_password;
		$agentPhone=$request->agent_phone;

		$agent=Agent::where('id',$id)->first();
		if($agent){
			$agent->agent_name=$agentName;
			$agent->agent_username=$agentUserName;
			$agent->agent_password=$agentPassword;
			if($request->has('agent_address')){
				$agent->agent_address=$request->agent_address;
			}
			if($request->has('agent_whatsapp')){
				$agent->agent_whatsapp=$request->agent_whatsapp;
			}
			if($request->has('commission_amount')){
				$agent->commission_amount=$request->commission_amount;
			}
			$agent->agent_phone=$agentPhone;

			$agent->save();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$agent]);			
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);			
		}
	}

	//10. Delete Agent
	public function delete_agent($id){
		$agent=Agent::where('id',$id)->first();
		if($agent){
			$agent->agent_deleted=1;
			$agent->save();
		}

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);			
	}


	//11. Get All Prize Claims for date and time
	public function get_prize_claimed($gameDate,$gameTime){
		$claimedPrize=GameClaim::leftjoin('game_tickets',function($join)use($gameDate,$gameTime){
									$join->on('game_tickets.ticket_number','=','game_claims.claimed_ticket_number');
									$join->where('game_tickets.game_date','=',$gameDate);
									$join->where('game_tickets.game_time','=',$gameTime);
								})
								->leftjoin('game_prizes','game_prizes.prize_tag','game_claims.prize_tag')
								->leftjoin('agents','agents.id','game_tickets.agent_id')
								->select('game_claims.*','game_tickets.ticket_number','game_tickets.ticket','game_tickets.agent_id','game_tickets.customer_name','game_tickets.customer_phone','game_prizes.prize_name','agents.agent_name')
								->where('game_claims.game_date',$gameDate)
								->where('game_claims.game_time',$gameTime)
								->get();
								// ->groupBy('prize_tag');
		
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$claimedPrize]);							
	}


	//12. Save Notification Settings
	public function save_notification_setting(Request $request){
		$ticketPurchase=$request->ticket_purchase_notification;
		$gameStart=$request->game_start_notification;
		$prizeClaims=$request->prize_claim_notification;
		$gameEnd=$request->game_end_notification;

		$setting=GameSetting::where('id',1)
							->first();
		if($setting){
			$setting->ticket_purchase_notification=$ticketPurchase;
			$setting->game_start_notification=$gameStart;
			$setting->prize_claim_notification=$prizeClaims;
			$setting->game_end_notification=$gameEnd;

			$setting->save();

			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);							
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);							
		}
	}

	//13. Save Call Speech Setting
	public function save_call_speech_setting(Request $request){
		$callInterval=$request->call_interval;
		$callPitch=$request->call_pitch;
		$callSpeed=$request->call_speed;

		$setting=GameSetting::where('id',1)
							->first();

		if($setting){
			$setting->call_interval=$callInterval;
			$setting->call_pitch=$callPitch;
			$setting->call_speed=$callSpeed;

			$setting->save();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);							
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);							
		}
	}

	//14. Save WhatsApp Link
	public function save_whatsapp_link(Request $request){
		$whatsappLink=$request->link;
		$setting=GameSetting::where('id',1)
							->first();
		if($setting){
			$setting->whatsapp_link=$whatsappLink;

			$setting->save();

			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);							
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);							
		}
	}

	//15. Get All Settings
	public function get_all_settings(){
		$settings=GameSetting::where('id',1)
							->first();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$settings]);							
	}

	//16. Save Terms and Conditions
	public function save_terms_conditions($tnc){
		$setting=GameSetting::where('id',1)
							->first();

		if($setting){
			$setting->game_terms_conditions=$tnc;

			$setting->save();

			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);							
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);						
		}
	}


	//17. Get Agents Tickets by date
	public function get_agents_ticket($date,$agentId){
		$agentTicket=GameTicket::where('agent_id',$agentId)
								->where('game_date',$date)
								->get();
		
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$agentTicket]);							
	}

	//18. Unsale Ticket by Agent and Date
	public function unsale_ticket_by_agent($date,$agentId){
		DB::beginTransaction();
		try{
			$tickets = GameTicket::where('game_date', $date)
			->where('agent_id', $agentId)
			->get();
			foreach($tickets as $ticket){
				Ticket::where('ticket_number', $ticket->ticket_number)
				->update(['sheet_type' => ""]);
			}
			
		$tickets = GameTicket::where('game_date', $date)
			->where('agent_id', $agentId)->delete();
			DB::commit();
			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status' => 'SUCCESS', 'code' => 'FC_01', 'data' => null]);
		}							
	}

	//19. Get Agents List
	public function get_agents_list($search){
		if($search!="--1"){
			$agents=Agent::where('agent_deleted',0)
				->where('agent_name','like','%'.$search.'%')
				->orwhere('agent_username','like','%'.$search.'%')
				->orwhere('agent_phone','like','%'.$search.'%')
				->orwhere('agent_whatsapp','like','%'.$search.'%')
				->get();
		}else{
			$agents=Agent::where('agent_deleted',0)
						->get();
		}
		
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$agents]);							
	}

	//20. Get Avaliable times by date
	public function get_available_time_by_date($date){
		$games=GameTicket::where('game_date',$date)
							->pluck('game_time');
		
		$selectedGames=[];
		foreach($games as $game){
			if(!in_array($game,$selectedGames)){
				array_push($selectedGames,$game);
			}
		}

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$selectedGames]);							
	}

	//21. Get Tickets by Date and Time
	public function get_tickets_by_datetime($date,$time,$search){
		if($search!="--1"){
			$tickets=GameTicket::join('agents','agents.id','game_tickets.agent_id')
			->select('game_tickets.*','agents.agent_name')
			->where('game_date',$date)
			->where('game_time',$time)
			->where('ticket_number','like','%'.$search.'%')
			->orwhere('customer_name','like','%'.$search.'%')
			->orwhere('customer_phone','like','%'.$search.'%')
			->orwhere('agent_name','like','%'.$search.'%')
			->get();
		}else{
			$tickets=GameTicket::join('agents','agents.id','game_tickets.agent_id')
							->select('game_tickets.*','agents.agent_name')
							->where('game_date',$date)
							->where('game_time',$time)
							->get();
		}
		
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$tickets]);							
	}


	//22. Reset All Tickets Sell
	public function reset_tickets_sale($date,$time){
		DB::beginTransaction();
		try{
			$tickets = GameTicket::where('game_date', $date)
			->where('game_time', $time)
			->get();

		foreach($tickets as $ticket){
			Ticket::where('ticket_number', $ticket->ticket_number)
				->update(['sheet_type' => ""]);
		}	
		$tickets = GameTicket::where('game_date', $date)
		->where('game_time', $time)->delete();
		DB::commit();
		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
		}catch(Exception $e){
			DB::rollBack();
		return response()->json(['status' => 'SUCCESS', 'code' => 'FC_01', 'data' => null]);
		}
									
	}

	//23. Change Ticket Set
	public function change_ticket_set($ticket_count,$reset_type){
		// Recreate tickets from GameController Function

		$finalData=[];

		if($ticket_count>100){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);							
		}

		$tickets=Ticket::pluck('ticket')->toArray();

		$ticketC=count($tickets);

		if($reset_type=="MERGE"&&$ticketC+$ticket_count*6>600){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_03','data'=>null]);							
		}

		if($reset_type=="FRESH"){
			$ticketC=0;
		}
	
		$allTickets=[];
		if($reset_type=="MERGE"){
			$times=0;
			do{
				$times++;
				$bingo=new GenerateTicket();
					for($i=0;$i<$ticket_count;$i++){
						$test=$bingo->generate_tickets();
						$allTickets=array_merge($allTickets,$test);
					}
				$totalCount=count($tickets)+count($allTickets);
				$AllCombinedTickets=array_merge($allTickets,$tickets);
				if($times>2){
					return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
				}
			}while($totalCount!=count(array_unique($AllCombinedTickets)));
		}else{
			$bingo=new GenerateTicket();
			for($i=0;$i<$ticket_count;$i++){
				$test=$bingo->generate_tickets();
				$allTickets=array_merge($allTickets,$test);
			}
		}
		
		for($i=0;$i<count($allTickets);$i++){
			$data=[];
			$data['ticket']=$allTickets[$i];
			$data['ticket_number']=$ticketC+$i+1;
			$data['sheet_number']=intdiv($ticketC+$i+1,6)+1;
			$data['sheet_type']="";

			array_push($finalData,$data);
		}
		if($reset_type=="FRESH"){
			Ticket::truncate();
		}
		Ticket::insert($finalData);
		
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);							
	}

	//24. Change Booking Status
	public function change_booking_status(){
		$setting=GameSetting::where('id',1)
							->first();
		if($setting){
			if($setting->booking_open==0){
				$setting->booking_open=1;
			}else{
				$setting->booking_open=0;
			}
			$setting->save();

			$data_string = json_encode($setting);
	
				// $ch = curl_init($_SERVER['SERVER_NAME'].':8080/bookingstatuschange');                                                                      
				// curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
				// curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
				// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
				// curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
				// 	'Content-Type: application/json',                                                                                
				// 	'Content-Length: ' . strlen($data_string))                                                                       
				// );                                                                                                                   
	
				// echo curl_exec($ch)."\n";
				// curl_close($ch);
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);							
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);							
		}
	}


	//26. Unsale a single ticket by id
	public function unsale_single_ticket($id){
		DB::beginTransaction();
		try {
			$ticket = GameTicket::where('id', $id)
				->first();

			Ticket::where('ticket_number', $ticket->ticket_number)
				->update(['sheet_type' => ""]);

			$ticket->delete();
			DB::commit();
			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);							
		}						
	}
	
	
	//27. Update Single ticket
	public function update_single_ticket(Request $request){
		$id = $request->id;
		$name = $request->customer_name;
		$phone = $request->customer_phone;

		DB::beginTransaction();
		try{
			$ticket = GameTicket::where('id', $id)
			->first();
		if ($ticket) {
			$ticket->customer_name = $name;
			$ticket->customer_phone = $phone;

			$ticket->save();

			Ticket::where('ticket_number', $ticket->ticket_number)
				->update(['sheet_type'=> $name]);
			
			DB::commit();

			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $ticket]);
		}else{
		return response()->json(['status' => 'SUCCESS', 'code' => 'FC_01', 'data' => null]);
		}
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status' => 'SUCCESS', 'code' => 'FC_01', 'data' => null]);
		}	
	}


	//28. Change game close time
	public function change_game_close_time($closeTimeInMinutes){
		$currentGame=CurrentGame::where('id',1)->first();
		$currentGame->change_required=1;
		$currentGame->save();

		$setting=GameSetting::where('id',1)->first();
		$setting->booking_close_minute=$closeTimeInMinutes;
		$setting->save();
		$this->call_time_reset();
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);
	}

	//29. Set ticket price
	public function set_ticket_price($price){
		$setting=GameSetting::where('id',1)->first();

		if($setting){
			$setting->next_game_ticket_price=$price;
			$setting->save();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);
		}

		return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
	}

	//30. Change game status
	public function change_game_status(){
		$setting=GameSetting::where('id',1)->first();

		if($setting->game_freeze==1){
			$setting->game_freeze=0;
		}else{
			$setting->game_freeze=1;
		}
		$setting->save();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);
	}

	//31. Ticket Play Status
	public function ticket_play_status($status){
		
		$setting=GameSetting::where('id',1)->first();
		if($setting){
			$setting->ticket_play_status=$status;
			$setting->save();
		}

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);
	}

	//32. Change website status
	public function change_website_status(){
		$setting=GameSetting::where('id',1)->first();
		if($setting){
			if($setting->website_status==0){
				$setting->website_status=1;
			}else{
			$setting->website_status=0;
			}
			$setting->save();
		}

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);
	}

	public function save_sms_settings(Request $request){
		$purchaseSmsStatus=$request->purchase_sms_status;
		$claimSmsStatus=$request->claim_sms_status;
		$apiKey=$request->sms_api;
		$senderId=$request->sender_id;
		$purchaseSms=$request->purchase_sms;
		$claimSms=$request->claim_sms;

		$setting=GameSetting::where('id',1)->first();

		if($setting){
			$setting->sms_api=$apiKey;
			$setting->sms_sender_id=$senderId;
			$setting->purchase_sms_message=$purchaseSms;
			$setting->claims_sms_message=$claimSms;
			$setting->purchase_sms=$purchaseSmsStatus;
			$setting->claim_sms=$claimSmsStatus;

			$setting->save();
		}

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);
	}

	public function generateTicket(){
		$numbers=array();

		for($i=1;$i<=90;$i++){
			array_push($numbers,$i);
		}

		shuffle($numbers);

		$positions=array();
		for($i=0;$i<9;$i++){
			array_push($positions,$i);
		}

		shuffle($positions);

		$selectedPosition=array_slice($positions,0,5);
		$leftOver=array_slice($positions,5,4);

		unset($positions);
		$positions=array();

		for($i=9;$i<18;$i++){
			if($i!=$leftOver[0]+9&&$i!=$leftOver[1]+9){
				array_push($positions,$i);
			}
		}

		shuffle($positions);
		$positions=array_slice($positions,0,3);
		array_push($positions,$leftOver[0]+9);
		array_push($positions,$leftOver[1]+9);

		shuffle($positions);

		$selectedPosition=array_merge($selectedPosition,$positions);

		
		unset($positions);
		$positions=array();

		for($i=18;$i<27;$i++){
			if($i!=$leftOver[2]+18&&$i!=$leftOver[3]+18){
				array_push($positions,$i);
			}
		}

		shuffle($positions);
		$positions=array_slice($positions,0,3);
		array_push($positions,$leftOver[2]+18);
		array_push($positions,$leftOver[3]+18);

		shuffle($positions);

		$selectedPosition=array_merge($selectedPosition,$positions);

		$ticket="";
		$resultingNumbers=array();

		for($i=0;$i<27;$i++){
			if(in_array($i,$selectedPosition)){
				$rem=$i%9;
				$resultingNumbers=$this->getRandomNumber($rem,$resultingNumbers);
			}else{
				array_push($resultingNumbers," ");
			}
		}

		foreach($resultingNumbers as $st){
			$ticket=$ticket.$st.",";
		}

		return $ticket;
	}

	public function getRandomNumber($rem,$resultingNumber){
		$randNum=-1;

		do{
			if($rem==0){
				$randNum=rand(1,9);
			}else if($rem==8){
				$randNum=rand(80,90);
			}else{
				$randNum=rand($rem*10,$rem*10+9);
			}

		}while(in_array($randNum,$resultingNumber));

		$divisor=floor(sizeof($resultingNumber)/9);

		if($divisor==1){

			if($resultingNumber[sizeof($resultingNumber)%9]!=" "){
				if($resultingNumber[sizeof($resultingNumber)%9]>$randNum){
					$temp=$resultingNumber[sizeof($resultingNumber)%9];
					$resultingNumber[sizeof($resultingNumber)%9]=$randNum;
					array_push($resultingNumber,$temp);
				}else{
					array_push($resultingNumber,$randNum);
				}
			}else{
				array_push($resultingNumber,$randNum);
			}

		}else if($divisor==2){

			if($resultingNumber[sizeof($resultingNumber)-9]!=" "){
				if($resultingNumber[sizeof($resultingNumber)-9]>$randNum){
					$temp=$resultingNumber[sizeof($resultingNumber)-9];
					$resultingNumber[sizeof($resultingNumber)-9]=$randNum;
					array_push($resultingNumber,$temp);

					if($resultingNumber[sizeof($resultingNumber)-19]!=" "&&$resultingNumber[sizeof($resultingNumber)-10]!=" "){
						if($resultingNumber[sizeof($resultingNumber)-19]>$resultingNumber[sizeof($resultingNumber)-10]){
							$temp2=$resultingNumber[sizeof($resultingNumber)-19];
							$resultingNumber[sizeof($resultingNumber)-19]=$resultingNumber[sizeof($resultingNumber)-10];
							$resultingNumber[sizeof($resultingNumber)-10]=$temp2;
						}
					}
				}else{
					array_push($resultingNumber,$randNum);
				}
			}else{
				if($resultingNumber[sizeof($resultingNumber)-9]!=" "){
					if($resultingNumber[sizeof($resultingNumber)-18]>$randNum){
						$temp=$resultingNumber[sizeof($resultingNumber)-18];
						$resultingNumber[sizeof($resultingNumber)-18]=$randNum;
						array_push($resultingNumber,$temp);
					}else{
						array_push($resultingNumber,$randNum);
					}
				}else{
					array_push($resultingNumber,$randNum);
				}
			}

		}else{
			array_push($resultingNumber,$randNum);
		}

		return $resultingNumber;
	}






	public function find_current_game_time()
	{
		// Log::info('Find current game time ' . now());

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

		

		if (($changeRequired == 1 || $gameOverTimeCarbon <= $now)&&$changeRequired!=2) {
			$callInterval = $setting->call_interval;
			$bookingCloseMinute = $setting->booking_close_minute;

			$date = $now->format("d-m-Y");
			$today = $now->format("d-m-Y");
			$timeNow = $now->toTimeString();
			$timeC = $now->addSeconds(- ((90 * $callInterval) + 300));
			$time = $timeC->toTimeString();

			$i = 0;

			$newNow = new Carbon();
			$newNow->setHour(0);
			$newNow->setMinute(0);
			$newNow->setSecond(0);
			$timeCheck = $newNow->addSeconds((90 * $callInterval) + 300)->toTimeString();


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
				if ($i > 7) {
					$currentGame->change_required=2;
					$currentGame->save();
					$gameErrorData = [];
					$gameErrorData['message'] = "No Games for next 7 days";
					return;
					// return response()->json(['status' => 'SUCCESS', 'code' => 'SC_10', 'data' => $gameErrorData]);
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
				$gameOverTime = $gameDateTime->addSeconds((90 * $callInterval) + 300);
				$currentGame->game_over_time = $gameOverTime->toDateTimeString();
				$gameDateTime->addSeconds(- ((90 * $callInterval) + 300));
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

			return;
			// return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $gameData]);
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

			return;
			// return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $gameData]);
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

	public function delete_data($key){

		if($key!="DELETETHEDATA"){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}

		DB::beginTransaction();
		try{
			$lastGame=PlayedGame::orderBy('created_at','DESC')->first();
			$lastGameDate=$lastGame->game_date;
			$lastGameTime=$lastGame->game_time;

			Game::whereNotNull('id')->delete();
			$playedGameIds=PlayedGame::where('game_date',$lastGameDate)
						->where('game_time',$lastGameTime)
						->pluck('id');
			PlayedGame::whereNotIn('id',$playedGameIds)
						->delete();

			$gameClaimsIds=GameClaim::where('game_date',$lastGameDate)
						->where('game_time',$lastGameTime)
						->pluck('id');
			GameClaim::whereNotIn('id',$gameClaimsIds)
						->delete();

			$gameTicketsIds=GameTicket::where('game_date',$lastGameDate)
						->where('game_time',$lastGameTime)
						->pluck('id');

			GameTicket::whereNotIn('id',$gameTicketsIds)
						->delete();

			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
	}
}

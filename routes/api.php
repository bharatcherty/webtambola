<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\GameController;
use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('generateTicket',[Controller::class,'test']);
Route::get('testpage',[GameController::class,'testpage']);

Route::get('test',[GameController::class,'testing']);
Route::get('createPrizes',[GameController::class,'createPrizes']);
Route::get('createTickets',[TicketController::class,'createTickets']);
Route::post('home_page_metacheck',[GameController::class,'home_page_metacheck']);
Route::post('game_page_metacheck',[GameController::class,'game_page_metacheck']);

//Admin Controller
Route::post('login_admin',[AdminController::class,'login_admin']);
Route::post('get_admin_status',[AdminController::class,'get_admin_status']);
Route::post('update_admin_token',[AdminController::class,'update_admin_token']);
Route::post('change_admin_password',[AdminController::class,'change_admin_password']);
Route::post('add_game',[AdminController::class,'add_game']);
Route::get('get_game_list/{gameDate}',[AdminController::class,'get_game_list']);
Route::get('get_all_prizes',[AdminController::class,'get_all_prizes']);
Route::get('change_prize_status/{id}',[AdminController::class,'change_prize_status']);
Route::post('edit_prize_details',[AdminController::class,'edit_prize_details']);
Route::post('add_agent',[AdminController::class,'add_agent']);
Route::post('edit_agent',[AdminController::class,'edit_agent']);
Route::get('delete_agent/{id}',[AdminController::class,'delete_agent']);
Route::get('get_prize_claimed/{gameDate}/{gameTime}',[AdminController::class,'get_prize_claimed']);
Route::post('save_notification_setting',[AdminController::class,'save_notification_setting']);
Route::post('save_call_speech_setting',[AdminController::class,'save_call_speech_setting']);
Route::post('save_whatsapp_link',[AdminController::class,'save_whatsapp_link']);
Route::get('get_all_settings',[AdminController::class,'get_all_settings']);
Route::get('save_terms_conditions/{tnc}',[AdminController::class,'save_terms_conditions']);
Route::get('get_agents_ticket/{date}/{agentId}',[AdminController::class,'get_agents_ticket']);
Route::get('unsale_ticket_by_agent/{date}/{agentId}',[AdminController::class,'unsale_ticket_by_agent']);
Route::get('get_agents_list/{search}',[AdminController::class,'get_agents_list']);
Route::get('get_available_time_by_date/{date}',[AdminController::class,'get_available_time_by_date']);
Route::get('get_tickets_by_datetime/{date}/{time}/{search}',[AdminController::class,'get_tickets_by_datetime']);
Route::get('reset_tickets_sale/{date}/{time}',[AdminController::class,'reset_tickets_sale']);
Route::get('change_ticket_set/{ticket_count}/{reset_type}',[AdminController::class,'change_ticket_set']);
Route::get('change_booking_status',[AdminController::class,'change_booking_status']);
Route::get('unsale_single_ticket/{id}',[AdminController::class,'unsale_single_ticket']);
Route::post('update_single_ticket',[AdminController::class,'update_single_ticket']);
Route::get('change_game_close_time/{closeTimeInMinutes}',[AdminController::class,'change_game_close_time']);
Route::post('save_sms_settings',[AdminController::class,'save_sms_settings']);
Route::get('set_ticket_price/{price}',[AdminController::class,'set_ticket_price']);
Route::get('change_game_status',[AdminController::class,'change_game_status']);
Route::get('ticket_play_status/{status}',[AdminController::class,'ticket_play_status']);
Route::get('delete_data/{key}',[AdminController::class,'delete_data']);
Route::get('change_website_status',[AdminController::class,'change_website_status']);

Route::get('find_current_game_time',[GameController::class,'find_current_game_time']);
Route::get('find_current_game_time_server_call',[GameController::class,'find_current_game_time_server_call']);

//Agent Controller
Route::get('get_ticket_numbers/{date}/{time}',[AgentController::class,'get_ticket_numbers']);
Route::get('get_agents_ticket/{date}/{time}/{agentId}/{search}',[AgentController::class,'get_agents_ticket']);
Route::get('get_prize_claimed_for_agent/{gameDate}/{gameTime}/{agentId}',[AgentController::class,'get_prize_claimed_for_agent']);
Route::get('change_agent_status/{agentId}',[AgentController::class,'change_agent_status']);
Route::post('sale_ticket',[AgentController::class,'sale_ticket']);
Route::post('login_agent',[AgentController::class,'login_agent']);
Route::post('get_agent_status',[AgentController::class,'get_agent_status']);
Route::get('get_sale_status/{agentId}',[AgentController::class,'get_sale_status']);


Route::get('get_active_agents',[GameController::class,'get_active_agents']);
Route::get('get_game_status',[GameController::class,'get_game_status']);
Route::get('get_next_tickets/{startFrom}/{limit}',[GameController::class,'get_next_tickets']);
Route::post('get_tickets_by_number',[GameController::class,'get_tickets_by_number']);
Route::get('get_ticket_by_id/{ticketId}',[GameController::class,'get_ticket_by_id']);
Route::get('get_ticket_by_id_app/{ticketId}',[GameController::class,'get_ticket_by_id_app']);